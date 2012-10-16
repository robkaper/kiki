<?

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
 * Nesting supported, but no conditions other than variable values: no ands,
 * or, buts or maybes.
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

class Template
{
  private static $instance;

  private $template = null;
  private $data = array();
  private $content = null;

  private $ifDepth = 0;
  private $maxIfDepth = 0;

  private $cleanup = true;

  private $user;
  private $db;

  public function __construct( $template = null )
  {
    $this->user = $GLOBALS['user'];
    $this->db = $GLOBALS['db'];

    $this->template = $template;
  }
  
  private function loadData()
  {
    // Handle some basic stuff that should always be available in templates.
    // Should probably be done more elegantly.
    $this->data['config'] = array();
    foreach( get_class_vars( 'Config' ) as $configKey => $configValue )
    {
      if ( !preg_match( '~(^db|pass|secret)~i', $configKey ) )
        $this->data['config'][$configKey] = $configValue;
    }

    // FIXME: $page does not exist at this point. Maybe get rid of
    // Page class and just assign title, description and tagLine to the
    // template engine directly.
    /*
    $page = $GLOBALS['page'];
    $this->data['page'] = array(
      'description' => $page->description()
    );
    */

    $this->data['server'] = array(
      'requestUri' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ""
    );

    $this->data['user'] = array(
      'activeConnections' => array(),
      'inactiveConnections' => array()
    );

    if ( $this->user->id() )
      $this->data['user']['id'] = $this->user->id();

    $this->data['activeConnections'] = array();
    $this->data['inactiveConnections'] = array();

    $connectedServices = array();
    foreach( $this->user->connections() as $connection )
    {
      $this->data['activeConnections'][] = array( 'serviceName' => $connection->serviceName(), 'userName' => $connection->name(), 'pictureUrl' => $connection->picture() );
      $connectedServices[] = $connection->serviceName();
    }

    foreach( Config::$connectionServices as $name )
    {
      if ( !in_array( $name, $connectedServices ) )
      {
        $connection = Factory_ConnectionService::getInstance($name);
        $this->data['inactiveConnections'][] = array( 'serviceName' => $connection->name(), 'loginUrl' => $connection->loginUrl() );
      }
    }

    $this->data['menu'] = Boilerplate::navMenu($this->user);
    $this->data['subMenu'] = Boilerplate::navMenu($this->user, 2);

    // Log::debug( "template data: ". print_r($this->data, true) );
  }

  public static function getInstance()
  {
    if ( !isset(self::$instance) )
      self::$instance = new Template(); // __CLASS__;

    return self::$instance;
  }

  /**
  * Resolves a template filename. Looks for a site-specific file first and falls back on the Kiki default.
  * @param $template [string] name of the template
  * @return string filename of the template
  */
  public static function file( $template )
  {
    // Try site-specific version of template
    $file = $GLOBALS['root']. '/templates/'. $template. '.tpl';
    if ( file_exists($file) )
      return $file;

    // Fallback to Kiki base version of template
    return $GLOBALS['kiki']. '/templates/'. $template. '.tpl';
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
    $reIncludes = '~\{include \'([^\']+)\'\}~';
    $reIfs = '~\{((\/)?if)([^}]+)?\}~';

    //echo "<h2>pre preparse includes:</h2><pre>". htmlspecialchars($this->content). "</pre>";

    while( preg_match($reIncludes, $this->content) )
    {
      $this->content = preg_replace_callback( $reIncludes, array($this, 'includes'), $this->content );
    }
    //echo "<h2>post preparse includes:</h2><pre>". htmlspecialchars($this->content). "</pre>";

    $this->content = preg_replace_callback( $reIfs, array($this, 'preIfs'), $this->content );
    //echo "<h2>post preparse ifs:</h2><pre>". htmlspecialchars($this->content). "</pre>";

    for( $i=$this->maxIfDepth; $i>=0; $i-- )
    {
      $reConditions = '~\n?\{if('. $i. ')([^\}]+)\}\n??(.*)\n?\{\/if'. $i. '\}\n?~sU';
      $this->content = preg_replace_callback( $reConditions, array($this, 'preElse'), $this->content );
    }
    // echo "<h2>post preparse elses:</h2><pre>". htmlspecialchars($this->content). "</pre>";
  }

  public function parse()
  {
    $reLegacy = '~<\?=?([^>]+)\?>~';
    $reLoops = '~\n?\{foreach (\$[\w\.]+) as (\$[\w]+)\}\n?(.*)\n?\{\/foreach\}\n?~sU';
    $reConditions = '~\n?\{if ([^\}]+)\}\n??(.*)\n?\{\/if\}\n?~sU';
    $re = '~\{([^}]+)\}~';

    $this->content = preg_replace_callback( $reLegacy, array($this, 'legacy'), $this->content );
    // echo "<h2>post parse/legacy:</h2><pre>". htmlspecialchars($this->content). "</pre>";
    $this->content = preg_replace_callback( $reLoops, array($this, 'loops'), $this->content );
    // echo "<h2>post parse/loops:</h2><pre>". htmlspecialchars($this->content). "</pre>";

    for( $i=0; $i<=$this->maxIfDepth; $i++ )
    {
      $reConditions = '~[\s\r\n]?\{if('. $i. ')([^\}]+)\}\n??(.*)\n?\{\/if'. $i. '\}[\s\r\n]?~sU';
      $this->content = preg_replace_callback( $reConditions, array($this, 'conditions'), $this->content );
    }
    // echo "<h2>post parse/conditions:</h2><pre>". htmlspecialchars($this->content). "</pre>";
    $this->content = preg_replace_callback( $re, array($this, 'replace'), $this->content );
    // echo "<h2>post parse/replace:</h2><pre>". htmlspecialchars($this->content). "</pre>";
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
    $this->loadData();

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

    // Don't load a template when setContent has been used.
    $content .= $this->content ? $this->content : file_get_contents($this->file($this->template)). PHP_EOL;

    if ( $fullHTML )
    {
      $content .= "{include 'html-end'}";
    }

    $this->content = $content;

    // Log::debug( "content: ". $this->content );

    $this->normalise( $this->data );

    $this->preparse();
    $this->parse();
    if ( $this->cleanup )
      $this->cleanup();

    // Log::debug( "done parsing" );
    // Log::debug( "content: ". $this->content );
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

    $output = "{". $input[1]. $this->ifDepth. $input[3]. "}". PHP_EOL;

    if ( $input[2]!='/' )
    {
      $this->ifDepth++;
      if ( $this->ifDepth > $this->maxIfDepth )
        $this->maxIfDepth = $this->ifDepth;
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
    return trim( file_get_contents( $this->file($input[1]) ) );
  }

  private function loops( $input )
  {
    $array = substr( $input[1], 1 );
    $named = substr( $input[2], 1 );

    $content = null;

    if ( isset($this->data[$array]) && is_array($this->data[$array]) )
      $data = $this->data[$array];
    else
    {
      $parts = explode(".", $array);
      $data = $this->data;
      foreach( $parts as $part )
      {
        if ( isset($data[$part]) && is_array($data[$part]) )
          $data = $data[$part];
        else
          unset($data);
      }

      if ( !isset($data) )
      {
        Log::debug( "loops returning unmatched: {foreach $array as $named}, part: $part" );
        return $content;
      }
    }

    foreach( $data as $key => $$named )
    {
      $pattern = "~\{(if\d\s)?\\\$${named}(|[^\}]+)?\}~";
      $replace = "{\\1\$". $array. ".$key". "\\2}";
      $content .= preg_replace( $pattern, $replace, $input[3] );
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
      return $input[0];

    $value = $this->getVariable( $key );
    return ($value !== null) ? $value : null; // $input[0];
  }

  private function modify( $input, $mods )
  {
    if ( ! ($mods = trim($mods)) )
      return $input;

    $mods = explode( ',', $mods );
    foreach( $mods as $mod )
    {
      switch($mod)
      {
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
        default:;
      }
    }
    return $input;
  }

  private function getVariable( $var )
  {
    if ( $var[0] != "\$" )
      return null;
    $var = substr( $var, 1 );

    if ( strstr($var, '|') )
      list( $var, $mods ) = explode( '|', $var );
    else
      list( $var, $mods ) = array( $var, null );

    // Log::debug( "replace $var" );

    // Loop through the array and store a flattened value. Could possibly be
    // done in normalise.
    if ( !array_key_exists( $var, $this->data ) )
    {
      $parts = explode( ".", $var );
      $container = $this->data;
      $value = null;
      while( ( $part = array_shift($parts) ) !== null )
      {
        if ( !is_array($container) || !isset($container[$part]) )
        {
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
      return count($this->data[$var]);

    // Log::debug( "return modify $var -> ". $this->data[$var] );
    return $this->modify( (string) $this->data[$var], $mods );
  }
}

?>