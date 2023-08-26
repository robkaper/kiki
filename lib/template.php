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
 * {include 'other/template/file'}
 *
 * --> Extends:
 * {{extends 'other/template/file'}}
 *
 * Both include and extends check .tpl and .php files in the templates/
 * directory with first Core::getRootPath() and then Core::getInstallPath().
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
  private $noDefault = false;

  private $blocks = array();

  private $data = array();
  private $content = null;

  private $ifDepth = 0;
  private $maxIfDepth = 0;
  private $loopDepth = 0;
  private $maxLoopDepth = 0;

  private $cleanup = true;

  private $db;

  public function __construct( $template = null, $noDefault=false )
  {
    $this->db = Core::getDb();

    $this->template = $template;
    $this->noDefault = $noDefault;
  }

  public static function getInstance()
  {
    if ( !isset(self::$instance) )
      self::$instance = new Template(); // __CLASS__;

    return self::$instance;
  }
  
	public function data( $stripFlattened = false )
	{
		if ( !$stripFlattened )
			return $this->data;

		$data = array();
		foreach( $this->data as $key => $value )
			if ( !strstr( $key, "." ) )
				$data[$key] = $value;

		return $data;
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
    $supportedExtensions = [ '.tpl2', '.tpl', '.php' ];
    $searchPaths = [ Core::getRootPath(). '/templates/', Core::getInstallPath(). '/templates/' ];

    foreach( $searchPaths as $searchPath )
    {
      foreach( $supportedExtensions as $extension )
      {
        $file = $searchPath. $template. $extension;
        if ( file_exists($file) )
          return $file;
      }
    }

    return null;
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

    $reExtend = '~\{\{extends \'([^\']+)\'\}\}~';
    $reDebug = '~\{\{debug\}\}~';
    $reIncludes = '~\{\{include \'([^\']+)\'\}\}~';
    $reIfs = '~\{((\/)?if)([^}]+)?\}~';
    $reLoops = '~\{((\/)?foreach)([^}]+)?\}~';

    // Template engine 2.0:
    // TODO: ensure all captures are {{}} instead of {}

    $extendChain = array();
    $matches = array();

    do
    {
      // Capture and store blocks
      // echo "<h2>pre captureBlocks:</h2><pre>". htmlspecialchars($this->content). "</pre>";
      $this->captureBlocks();

      // While extending, replace template with extended template.
      if ( count($matches) )
      {
        // Load the content of the extended template
        $extendedTemplateName = $matches[1];

        // Store extended file in array to avoid race condition
        if ( in_array( $extendedTemplateName, $extendChain ) )
          break;
        $extendChain[] = $extendedTemplateName;

        $fileName = self::file( $matches[1] );
        $this->content = file_get_contents( $fileName );

        // echo "<h2>post extends $this->template to $matches[1]: </h2><pre>". htmlspecialchars($this->content). "</pre>";
      }
    }
    while( preg_match( $reExtend, $this->content, $matches) );

      // {{debug}}
    $this->content = preg_replace( $reDebug, "<pre>". print_r($this->data, true). "</pre>", $this->content );

    // echo "<h2>post captureBlocks/extends:</h2><pre>". htmlspecialchars($this->content). "</pre>";

    $this->fillBlocks();

    // echo "<h2>post fullBlocks:</h2><pre>". htmlspecialchars($this->content). "</pre>";
    // print_r( $this->blocks );

    // echo "<h2>post preparse blocks:</h2><pre>". htmlspecialchars($this->content). "</pre>";

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

  public function captureBlocks()
  {
    $matches = array();
    $reBlocks = '/\{\{block \'([^\']+)\'\}}(.*?)\{\{\/block\}\}/s';
    preg_match_all($reBlocks, $this->content, $matches, PREG_SET_ORDER);
    foreach( $matches as $match )
    {
      $blockName = $match[1];
      $blockContent = $match[2];

      // Store the block content in the $this->blocks array
      $this->blocks[$blockName] = $blockContent;

      // Remove the matched block tag and its content
      $this->content = preg_replace($reBlocks, '', $this->content, 1);
    }
  }

  public function parse()
  {
		// Log::beginTimer( 'Template::parse '. $this->template );

    $reLegacy = '~<\?=?([^>]+)\?>~';
    $reConditions = '~\n?\{if ([^\}]+)\}\n??(.*)\n?\{\/if\}\n?~sU';
    $re = '~\{([^}]+)\}~';
    $reDouble = '~\{{([^}]+)\}}~';

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

    $this->content = preg_replace_callback( $reDouble, array($this, 'replace'), $this->content );
    // echo "<h2>post parse/replace:</h2><pre>". htmlspecialchars($this->content). "</pre>";

		// Log::endTimer( 'Template::parse '. $this->template );
  }

  public function fillBlocks()
  {
    $reBlocks = '/\{\{block \'([^\']+)\'\}}(.*?)\{\{\/block\}\}/s';

    // Replace block contents from the $this->blocks array
    foreach ( $this->blocks as $blockName => $blockContent )
    {
      $this->content = preg_replace( "/\{\{block '$blockName'\}\}.*?\{\{\/block\}\}/s", $blockContent, $this->content );
      // echo "<h2>post fillBlocks replace for $blockName:</h2><pre>". htmlspecialchars($this->content). "</pre>";
    }

    // Remove block opening tags
    $this->content = preg_replace( "/\{\{block '[^']+'\}\}/", '', $this->content );

    // Remove block closing tags
    $this->content = preg_replace( "/\{\{\/block\}\}/", '', $this->content );
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

    if ( !$this->template && !$this->noDefault )
      $this->template = 'pages/default';

    $ext = pathinfo( $this->file($this->template), PATHINFO_EXTENSION );
    if ( $ext == 'php' )
    {
      ob_start();
      include_once $this->file($this->template);
      $this->content = ob_get_contents();
      ob_end_clean();

      \Kiki\Core::getFlashBag()->reset();
    
      return $this->content;
    }

    // TODO: don't always auto-include html framework, desired template
    // output could just as well be another format (json, xml, ...)
    $content = null;
    if ( $fullHTML )
    {
      $content = "{include 'html'}". PHP_EOL;
      $content .= "{include 'head'}". PHP_EOL;
    }

    if ( !$this->template && !$this->noDefault )
      $this->template = 'pages/default';

    // Log::beginTimer( "Template::content ". $this->template );

    // Don't load a template when setContent has been used.
    if ( $this->content )
      $content .= $content;
    else
    {
      $file = $this->file($this->template);
      if ( file_exists($file) )
        $content .= file_get_contents($file);
      else
        \Kiki\Log::fatal( "could not load template file $file" );
    }

    if ( $fullHTML )
    {
      $content .= "{include 'html-end'}";
    }

    $this->content = $content;

    // Log::debug( "content: ". $this->content );

		$this->data['kiki']['flashBag'] = array(
			'notice' => \Kiki\Core::getFlashBag()->get('notice', false),
			'warning' => \Kiki\Core::getFlashBag()->get('warning', false),
			'error' => \Kiki\Core::getFlashBag()->get('error', false)
		);

    $this->normalise( $this->data );
		$this->preparse();
    $this->parse();

    if ( $this->cleanup )
      $this->cleanup();

    // Log::debug( "done parsing, content: ". $this->content );

		if ( $fullHTML )
		{
			\Kiki\Core::getFlashBag()->reset();
		}

    return $this->content;
  }

  private function legacy( $input )
  {
    return $input[1];
    return eval('?>'. $input[1]. '<?php');
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
    $templateFile = $this->file($input[1]);
    if ( !file_exists($templateFile) )
      return sprintf( '<span class="red">template file <q>%s</q>: file not found</span>', $input[1] );

    $extension = StorageItem::getExtension($templateFile);
    switch( $extension )
    {
      case 'tpl2':
        return file_get_contents( $templateFile );
        break;

      case 'tpl':
        return file_get_contents( $templateFile );
        break;

      case 'php':
        include_once $templateFile;
        break;
    }
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

      $replace = "{{\${1}}\"". $key. "\"\\3}";
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
    if ( ! ($mods = trim($mods)) )
      return $input;

    $mods = explode( ',', $mods );

    foreach( $mods as $mod )
    {
      $parts = explode( ":", $mod );
      $mod = array_shift($parts);
      $fmt = implode( ":", $parts );

      switch($mod)
      {
        // Parse the contents of a template as a template itself.
        case 'parse':
          $template = new Template();
          $template->setContent($input);
          $input = $template->fetch();
          break;

        case 'break':
          $input = preg_replace( '/(\r?\n)/', '<br>', $input );
          break;

        case 'date':
          $input = date($fmt, $input);
          break;

        // Insert formatting contents between base part and extension in an image URL. 
        case 'thumb':
          list ($base, $ext) = Storage::splitExtension($input);
          $input = "${base}.${fmt}.${ext}";
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

        case 'upper':
          $input = strtoupper($input);
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