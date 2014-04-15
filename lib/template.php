<?php

/**
 * Rudimentary template class.
 *
 * Constructs supported:
 *
 * --> Substitution:
 * {$variable}
 * {$variable|modifiers}
 * {"text"}
 * {"text"|modifiers}
 * {$variable.key}
 * {$variable.key.key}
 *
 * Will be substituted for assigned variables. Supported modifiers:
 * - escape, trim, i18n, strip, lower
 *
 * Internationalisation will probably fail in combination with other
 * modifiers.
 *
 * // FIXME: Internationalisation probably will fail anyway, as template
 * texts won't be recognised by gettext.
 *
 * --> Conditions:
 * {if $var} ... {/if}
 * {if !$var} ... {/if}
 * {if $var} ... {else} ... {/if}
 *
 * Nesting supported, but no conditions other than variable values: no
 * comparisons, ands, or, buts or maybes.
 *
 * --> Iteration:
 * {foreach $arrayname as $variable} ... {/foreach}
 *
 * Iterations are parsed prior to substitution, resulting into variables
 * such as {$comments.0.author} which will later be replaced.
 *
 * --> Debug notes:
 * {* I won't be shown}
 *
 * --> Includes:
 * {include 'other/template/file.tpl'}
 *
 * @todo Add a {debug} statement showing the entire available variable scope to templaters.
 *
 * @class Template
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2011-2012 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

namespace Kiki;

class Template
{
  private static $instance;

  private $template = null;
  private $data = array();
  private $content = null;

  private $ifDepth = 0;
  private $maxIfDepth = 0;
  private $loopDepth = 0;
  private $maxLoopDepth = 0;

  private $cleanup = true;

  private $db;

  public function __construct( $template = null )
  {
    $this->db = Core::getDb();

    $this->template = $template;

		$data = Core::getTemplateData();

		// @deprecated Assign into global namespace. For backwards compatibility
		// with <= 0.0.32, when the namespace kiki was introduced and populated
		// but not yet ported and used.  Compatibility will be removed in future
		// 0.1.0 (the this is getting somewhere update, which seems to be
		// approaching).

		foreach( $data as $key => $value )
		{
			switch($key)
			{
				case 'config':
					break;

				default:
					$this->data[$key] = $value;
			}
		}

		// Assign to {$kiki} namespace.

		$this->data['kiki'] = $data;
  }

  public static function getInstance()
  {
    if ( !isset(self::$instance) )
      self::$instance = new Template(); // __CLASS__;

    return self::$instance;
  }
  
	public function data()
	{
		return $this->data;
	}

  /**
  * Resolves a template filename. Looks for a site-specific file first and
  * falls back on the Kiki default.
	*
  * @param $template [string] name of the template
  * @return string filename of the template
  */
  public static function file( $template )
  {
    // Try site-specific version of template
    $file = Core::getRootPath(). '/templates/'. $template. '.tpl';
    if ( file_exists($file) )
      return $file;

    // Fallback to Kiki base version of template
    return Core::getInstallPath(). '/templates/'. $template. '.tpl';
  }

  public function load( $template )
  {
    $this->template = $template;
  }

  public function assign( $key, $value )
  {
    $this->data[$key] = $value;
  }

  public function append( $key, $value )
  {
    if ( !isset($this->data[$key]) )
      $this->data[$key] = array();

    if ( is_array($this->data[$key]) )
      $this->data[$key][] = $value;
    else
      Log::error( "cannot append value $value to $key, which isn't an array (currently value: $value)" );
  }

  public function normalise( &$data )
  {
    foreach( $data as &$value )
    {
      if ( gettype($value) == "object" )
        $value = (array) $value;
      if ( gettype($value) == "array" )
        $this->normalise( $value );
    }
  }

  public function preparse()
  {
		// Log::beginTimer( 'Template::preparse '. $this->template );

    $reIncludes = '~\{include \'([^\']+)\'\}~';
    $reIfs = '~\{((\/)?if)([^}]+)?\}~';
    $reLoops = '~\{((\/)?foreach)([^}]+)?\}~';

    // echo "<h2>pre preparse includes:</h2><pre>". htmlspecialchars($this->content). "</pre>";

    while( preg_match($reIncludes, $this->content) )
    {
      $this->content = preg_replace_callback( $reIncludes, array($this, 'includes'), $this->content );
    }
    // echo "<h2>post preparse includes:</h2><pre>". htmlspecialchars($this->content). "</pre>";

    $this->content = preg_replace_callback( $reIfs, array($this, 'preIfs'), $this->content );
    // echo "<h2>post preparse ifs:</h2><pre>". htmlspecialchars($this->content). "</pre>";

    $this->content = preg_replace_callback( $reLoops, array($this, 'preLoops'), $this->content );
    // echo "<h2>post preparse loops:</h2><pre>". htmlspecialchars($this->content). "</pre>";

    for( $i=$this->maxIfDepth; $i>=0; $i-- )
    {
      $reConditions = '~\n?\{if('. $i. ')([^\}]+)\}\n??(.*)\n?\{\/if'. $i. '\}\n?~sU';
      $this->content = preg_replace_callback( $reConditions, array($this, 'preElse'), $this->content );
    }
    // echo "<h2>post preparse elses:</h2><pre>". htmlspecialchars($this->content). "</pre>";

    // Log::endTimer( 'Template::preparse '. $this->template );
  }

  public function parse()
  {
		// Log::beginTimer( 'Template::parse '. $this->template );

    $reLegacy = '~<\?=?([^>]+)\?>~';
    $reConditions = '~\n?\{if ([^\}]+)\}\n??(.*)\n?\{\/if\}\n?~sU';
    $re = '~\{([^}]+)\}~';

    $this->content = preg_replace_callback( $reLegacy, array($this, 'legacy'), $this->content );
    // echo "<h2>post parse/legacy:</h2><pre>". htmlspecialchars($this->content). "</pre>";

		// echo "<hr>loop depth: ". print_r($this->maxLoopDepth,true);

    for( $i=0; $i<=$this->maxLoopDepth; $i++ )
    {
      $reLoops = '~\n?\{foreach'. $i. ' (\$[\w\.]+) as (\$[\w]+)\}\n?(.*)\n?\{\/foreach'. $i. '\}\n?~sU';
			// echo "<hr>reLoops: ". print_r($reLoops,true);
      $this->content = preg_replace_callback( $reLoops, array($this, 'loops'), $this->content );
    }
    // echo "<h2>post parse/loops:</h2><pre>". htmlspecialchars($this->content). "</pre>";

    for( $i=0; $i<=$this->maxIfDepth; $i++ )
    {
      $reConditions = '~[\s\r\n]?\{if('. $i. ')([^\}]+)\}\n??(.*)\n?\{\/if'. $i. '\}[\s\r\n]?~sU';
      $this->content = preg_replace_callback( $reConditions, array($this, 'conditions'), $this->content );
    }
    // echo "<h2>post parse/conditions:</h2><pre>". htmlspecialchars($this->content). "</pre>";

    $this->content = preg_replace_callback( $re, array($this, 'replace'), $this->content );
    // echo "<h2>post parse/replace:</h2><pre>". htmlspecialchars($this->content). "</pre>";

		// Log::endTimer( 'Template::parse '. $this->template );
  }

  public function setCleanup( $cleanup ) { $this->cleanup = $cleanup; }

  public function cleanup()
  {
    $this->content = preg_replace( '~([\r\n]{2,})~', "", $this->content );
  }

  public function fetch()
  {
    return $this->content( false );
  }

  public function setContent( $content ) { $this->content = $content; }

  public function content( $fullHTML = true )
  {
    // Log::debug( "begin template engine" );

    // TODO: don't always auto-include html framework, desired template
    // output could just as well be another format (json, xml, ...)
    $content = null;
    if ( $fullHTML )
    {
      $content = "{include 'html'}". PHP_EOL;
      $content .= "{include 'head'}". PHP_EOL;
    }

    if ( !$this->template )
      $this->template = 'pages/default';

    // Log::beginTimer( "Template::content ". $this->template );

    // Don't load a template when setContent has been used.
    $content .= $this->content ? $this->content : file_get_contents($this->file($this->template)). PHP_EOL;

    if ( $fullHTML )
    {
      $content .= "{include 'html-end'}";
    }

    $this->content = $content;

    // Log::debug( "content: ". $this->content );

		$this->data['kiki']['flashBag'] = \Kiki\Core::getFlashBag()->getData();
		Log::debug( "flashbag: ". print_r($this->data['kiki']['flashBag'], true) );

    $this->normalise( $this->data );
		$this->preparse();
    $this->parse();

    if ( $this->cleanup )
      $this->cleanup();

    // Log::debug( "done parsing" );
    // Log::debug( "content: ". $this->content );

		// Log::endTimer( "Template::content ". $this->template );

		if ( $fullHTML )
		{
			\Kiki\Core::getFlashBag()->reset();
		}

    return $this->content;
  }

  private function legacy( $input )
  {
    return eval($input[1]);
    return "[legacy:". htmlspecialchars($input[1]). "]";
  }

  private function parseCondition( $condition )
  {
    $condition = trim($condition);
    if ( $condition[0] == '!' )
      return !$this->getVariable( substr($condition, 1) );

    return $this->getVariable( $condition );
  }

  private function preIfs( $input )
  {
    if ( !isset($input[3]) )
      $input[3] = '';

    if ( $input[2]=='/' )
      $this->ifDepth--;

		if ( $this->ifDepth < 0 )
		{
			$error = "fatal error: unexpected {/if}";
			Log::error($error);
			echo $error;
			exit;
		}

    $output = "{". $input[1]. $this->ifDepth. $input[3]. "}". PHP_EOL;

    if ( $input[2]!='/' )
    {
      $this->ifDepth++;
      if ( $this->ifDepth > $this->maxIfDepth )
        $this->maxIfDepth = $this->ifDepth;
    }

    return $output;
  }

  private function preLoops( $input )
  {
    // Log::debug( print_r( $input, true ) );

    if ( !isset($input[3]) )
      $input[3] = '';

    if ( $input[2]=='/' )
      $this->loopDepth--;

    $output = "{". $input[1]. $this->loopDepth. $input[3]. "}". PHP_EOL;

    // Log::debug( print_r( $output, true ) );

    if ( $input[2]!='/' )
    {
      $this->loopDepth++;
      if ( $this->loopDepth > $this->maxLoopDepth )
        $this->maxLoopDepth = $this->loopDepth;
    }

    return $output;
  }

  private function preElse( $input )
  {
    return preg_replace( '~\{else\}~', "{else". $input[1]. "}", $input[0] );
  }
  
  private function conditions( $input )
  {
    $ifElseSplit = preg_split( '~\{else'. $input[1]. '\}~', $input[3] );

    if ( $result = $this->parseCondition($input[2]) )
      return $ifElseSplit[0];
    else
      return isset($ifElseSplit[1]) ? $ifElseSplit[1] : null;
  }

  private function includes( $input )
  {
    $re = '~\{([^}]+)\}~';
    // $file = preg_replace_callback( $re, array($this, 'replace'), $input[1] );
    // return trim( file_get_contents($file) );
    return file_exists( $this->file($input[1]) ) ? trim( file_get_contents( $this->file($input[1]) ) ) : null;
  }

  private function loops( $input )
  {
    // Log::debug( print_r( $input, true ) );

		// echo "<hr>loops, input: ". print_r($input,true);

		$array = substr( $input[1], 1 );
    $named = substr( $input[2], 1 );

		// echo "<hr>array: $array, named: $named";
    $content = null;

    if ( isset($this->data[$array]) && is_array($this->data[$array]) )
		{
      $data = $this->data[$array];
			// echo 1;
		}
    else
    {
			// echo 2;
      $parts = explode(".", $array);
      $data = $this->data;
      foreach( $parts as $part )
      {
			// echo 3;
				//echo "<br>part: [$part]";
				// Make data array unassociative if part is numeric
				$data = is_numeric($part) ? array_values($data) : $data;
        if ( isset($data[$part]) && is_array($data[$part]) )
				{
			// echo 4;
					// echo "<br>data[$part] is array: ". print_r($data[$part],true);
          $data = $data[$part];
				}
        else
				{
					// echo "<br>part [$part] not set in array: ". print_r($data,true);
					unset($data);
			// echo 5;
				}
      }

			// echo 6;

      if ( !isset($data) )
        return $content;
    }

		// echo "<hr>data: ". print_r($data,true);
		// echo 7;

		$i=0;

		foreach( $data as $key => $$named )
    {
			// echo 8;
			if (!ctype_alpha($key)) $key = $i;

			// echo "<hr>value of key $key, named $named: ". print_r($$named,true);
			// echo "<hr>input[3]". $input[3];
			$tmp = $input[3];	
		
      // Substitute the full key path for the local alias in variables, conditions and loops

      $pattern = "~\{((if|foreach)\d\s\!?)?\\\$${named}((\||\.)[^\}]+)?\}~";
      $replace = "{\\1\$". $array. ".$key". "\\3}";
			//echo "<hr>pattern: $pattern";
			//echo "<hr>replace". $replace;

      $tmp = preg_replace( $pattern, $replace, $tmp );
			//echo "<hr>tmp: ". $tmp;

      $pattern = "~\{((if|foreach)\d\s)?\\\$${array}\.${key}\.i\}~";
      $replace = "{\${1}\"". $key. "\"\\3}";
			// Log::debug( "pattern $i: $pattern" );
			// Log::debug( "replace $i: $replace" );
			//echo "<hr>pattern: $pattern";
			//echo "<hr>replace". $replace;

      $tmp = preg_replace( $pattern, $replace, $tmp );
			//echo "<hr>tmp: ". $tmp;


			$content .= $tmp;

			// echo "<hr>content: ". $content;
			$i++;
    }

    return $content;
  }

  private function replace( $input )
  {
    $key = $input[1];
    if ( preg_match( "#^ ?(\*|\")( )?((')?([^']+)(')?)?#", $key, $matches ) )
    {
      switch( $matches[1] )
      {
        case '*':
          return null;
          break;

        case '"':
          if ( strstr($key, '|') )
            list( $key, $mods ) = explode( '|', $key );
          else
            list( $key, $mods ) = array( $key, null );
          return $this->modify( substr($key, 1, -1), $mods );

        default:;
      }
    }

    if ( $key[0] != "\$" )
		{
      return $input[0];
		}

		$value = $this->getVariable( $key );
		return ($value !== null) ?  $value : null; // $input[0];
  }

  private function modify( $input, $mods )
  {
		// echo "input: $input, mods: $mods". PHP_EOL;

    if ( ! ($mods = trim($mods)) )
      return $input;

		// $input = (string) $input;

    $mods = explode( ',', $mods );

    foreach( $mods as $mod )
    {
      $parts = explode( ":", $mod );
      $mod = array_shift($parts);
      $fmt = array_shift($parts);

      switch($mod)
      {
        // Parse the contents of a template as a template itself.
        case 'parse':
          $template = new Template();
          $template->setContent($input);
          $input = $template->fetch();
          break;

        // Transform BB-code-like syntax to HTML.
        case 'markup':
          $input = Misc::markup($input);
          break;

        case 'date':
          $input = date($fmt, $input);
          break;

        // Insert formatting contents between base part and extension in an image URL. 
        case 'thumb':
          list ($base, $ext) = Storage::splitExtension($input);
          $input = "${base}.${fmt}.${ext}";
          break;

        // Creates a summary of $fmt paragraphs.
        case 'summary':
          $input = Misc::textSummary( $input, $fmt, true );
          break;

        case 'escape':
          $input = htmlentities($input, ENT_COMPAT, mb_internal_encoding());
          break;

        case 'i18n':
          $input = _($input);
          break;

        case 'trim':
          $input = trim($input);
          break;

        case 'strip':
          $input = strip_tags($input);
          break;

        case 'lower':
          $input = strtolower($input);
          break;

        case 'count':
          $input = count($input);
          break;
          
        case 'dump':
          $input = "<pre>". print_r($input,true). "</pre>";
          break;

        default:;
      }
    }
    return $input;
  }

  private function getVariable( $var )
  {
    if ( $var[0] != "\$" )
		{
			$text = trim($var, '"');
			return $text!=$var ? $text : null;
		}

    $var = substr( $var, 1 );

    if ( strstr($var, '|') )
      list( $var, $mods ) = explode( '|', $var );
    else
      list( $var, $mods ) = array( $var, null );

    // Log::debug( "replace $var" );
		// echo "<hr>replace var $var";

    // Loop through the array and store a flattened value. Could possibly be
    // done in normalise.
    if ( !array_key_exists( $var, $this->data ) )
    {
			// echo "not exist in flat structure";
      $parts = explode( ".", $var );
      $container = $this->data;
      $value = null;
      while( ( $part = array_shift($parts) ) !== null )
      {
				if(is_numeric($part))
					$container = array_values($container);
				if (is_object($container))
					$container = (array)$container;
				// echo "handling part $part type ". gettype($container). ":". print_r($container,true);

        if ( !is_array($container) || !isset($container[$part]) )
        {
					// echo "return null";
          // Log::debug( "return null" );
          return null;
        }

        $value = $container[$part];
        $container = $container[$part];
      }

      if ( !array_key_exists( $var, $this->data ) )
        $this->data[$var] = $value;
    }

    if ( is_array($this->data[$var]) )
		{
      // return count($this->data[$var]);
		}

    // Log::debug( "return modify $var -> ". $this->data[$var] );
		// echo "return modify this->data[$var] (". $this->data[$var] . ")";
    return $this->modify( $this->data[$var], $mods );
  }
}

?>