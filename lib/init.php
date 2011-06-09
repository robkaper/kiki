<?

/**
* @package Kiki CMS
* Initialisation/bootloader script for Kiki CMS.
* @todo add i18n support for Kiki's base strings (not necessarily a
*   bootloader issue but I need to document this somewhere...)
* @todo find a way to properly document a bootloader script in Doxygen
*/

  // Find the Kiki install path
  $GLOBALS['kiki'] = str_replace( "/lib/init.php", "", __FILE__ );

  // FIXME: rjkcust, we shouldn't assume /www/server_name/ as site root. 
  // Perhaps parent of $_SERVER['DOCUMENT_ROOT'], still an assumption but
  // less specific.  We should also support having the root for these inside
  // the document root (and provide proper htaccess rules), perhaps as
  // fallback.  Used by Config, Log, Storage and Template classes.
  $GLOBALS['root'] = "/www/". $_SERVER['SERVER_NAME'];

  function __autoload( $className )
  {
    // Try local customisations first, but fallback no Kiki's base classes,
    // allows developers to easily rewrite/extend.
    $local = $GLOBALS['root']. "/lib/". strtolower( $className ). ".php";
    if ( file_exists($local) )
    {
      include_once "$local";
      return;
    }

    $kiki = $GLOBALS['kiki']. "/lib/". strtolower( $className ). ".php";
    if ( file_exists($kiki) )
    {
      include_once "$kiki";
    }
  }

  Log::init();
  Config::init();

  $reqUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $argv[0];
  if ( !preg_match( '#/(im).php(\?)?#', $reqUri ) )
  {
    $reqMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'CMD';
    $log = sprintf( "%-4s: %s", $reqMethod, $reqUri );
    Log::debug( $log );
  }

  // HACK: fb_xd_fragment parameter bug: sets display:none on body, so redirect without it (also, we don't want it to appear in analytics)
  if ( array_key_exists( 'fb_xd_fragment', $_GET ) )
  {
    header( "Location: ". $_SERVER['SCRIPT_URL'], true, 301 );
    exit();
  }

  $db = $GLOBALS['db'] = new Database( Config::$db );
  $user = $GLOBALS['user'] = new User();

  Config::$singleUser ? $user->load(Config::$singleUser): $user->identify();
  $user->authenticate();

  // Convenient, but necessary?
  $fbUser = $user->fbUser;
  $twUser = $user->twUser;
  $anyUser = ($fbUser->authenticated || $twUser->authenticated);
  $allUsers = ($fbUser->authenticated && $twUser->authenticated);

  // Don't log trivial and overly frequent requests like IM updates
  $reqUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $argv[0];
  if ( !preg_match( '#/(im).php(\?)?#', $reqUri ) )
  {
    $fbUserTxt = $fbUser ? ", fbUser($fbUser->authenticated): $fbUser->id ($fbUser->name)" : "";
    $twUserTxt = $twUser ? ", twUser($twUser->authenticated): $twUser->id ($twUser->name)" : "";

    $reqMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'CMD';
    $log = sprintf( "%-4s: %s%s%s", $reqMethod, $reqUri, $fbUserTxt, $twUserTxt );
    Log::debug( $log );
  }
?>