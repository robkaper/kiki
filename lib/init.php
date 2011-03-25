<?
  // FIXME: add i18 support for base strings

  $GLOBALS['root'] = "/www/". $_SERVER['SERVER_NAME'];
  $GLOBALS['kiki'] = str_replace( "/lib/init.php", "", __FILE__ );

  function __autoload( $className )
  {
    $local = $GLOBALS['root']. "/lib/". strtolower( $className ). ".php";
    if ( 0 && file_exists($local) )
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

  // HACK: fb_xd_fragment parameter bug: sets display:none on body, so redirect without it (also, we don't want it to appear in analytics)
  if ( array_key_exists( 'fb_xd_fragment', $_GET ) )
  {
    header( "Location: ". $_SERVER['SCRIPT_URL'], true, 301 );
    exit();
  }

  $db = $GLOBALS['db'] = new Database( Config::$db );
  $mvc = $GLOBALS['mvc'] = new MVC();

  $fb = new Facebook( array(
    'appId'  => Config::$facebookApp,
    'secret' => Config::$facebookSecret,
    'cookie' => true
   ) );

  $user = $GLOBALS['user'] = new User();
  if ( Config::$singleUser )
    $user->load( Config::$singleUser );
  else
    $user->identify();

  $user->authenticate();

  $fbUser = $user->fbUser;
  $twUser = $user->twUser;
  $anyUser = ($fbUser->authenticated || $twUser->authenticated);
  $allUsers = ($fbUser->authenticated && $twUser->authenticated);
  Log::debug( "anyUser: $anyUser, allUsers: $allUsers" );

  // Don't log trivial and overly frequent requests like IM updates
  $reqUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null;
  if ( !preg_match( '#/(im).php(\?)?#', $reqUri ) )
  {
    $fbUserTxt = $fbUser ? ", fbUser($fbUser->authenticated): $fbUser->id ($fbUser->name)" : "";
    $twUserTxt = $twUser ? ", twUser($twUser->authenticated): $twUser->id ($twUser->name)" : "";

    $reqMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : null;
    $log = sprintf( "%4s: %s%s%s", $reqMethod, $reqUri, $fbUserTxt, $twUserTxt );
    Log::debug( $log );
  }
?>