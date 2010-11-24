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

  $user = $GLOBALS['user'] = new User();
  $user->authenticate();

  $fbUser = $user->fbUser;
  $twUser = $user->twUser;
  $anyUser = ($fbUser || $twUser);
  $allUsers = ($fbUser && $twUser);

  // Don't log trivial and overly frequent requests like IM updates
  if ( !preg_match( '#/(im).php(\?)?#', $_SERVER['REQUEST_URI'] ) )
  {
    $fbUserTxt = $fbUser ? ", fbUser: $fbUser->id ($fbUser->name)" : "";
    $twUserTxt = $twUser ? ", twUser: $twUser->id ($twUser->name)" : "";

    $log = sprintf( "%4s: %s%s%s", $_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $fbUserTxt, $twUserTxt );
    Log::debug( $log );
  }
?>