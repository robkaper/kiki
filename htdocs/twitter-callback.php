<?
  include_once "../lib/init.php";
  session_start();

  if ( isset($_REQUEST['oauth_token']) && $_SESSION['oauth_token'] !== $_REQUEST['oauth_token'] )
    Log::error( "SNH: twitter-callback token mismatch" );

  $accessToken = $user->twUser->registerAuth();

  // Remove no longer needed request tokens
  unset($_SESSION['oauth_token']);
  unset($_SESSION['oauth_token_secret']);

  if ( $accessToken )
  {
    $twUid = $accessToken['user_id'];
    if ( $twUid )
    {
      // HACK: not all browsers (read: my HTC Hero) store the cookie Twitter sets, (re-)set it here ourselves
      $twSig = sha1( $twUid. Config::$twitterSecret );
      $expire = 0; // time() + (7*24*3600);
      setcookie( "twitter_anywhere_identity", "$twUid:$twSig", $expire, "/" );
    }
  }

  $ref = array_key_exists( 'HTTP_REFERER', $_SERVER ) ? $_SERVER['HTTP_REFERER'] : "";
   Log::debug( "twitter-callback redirect: $ref" );
   header( 'Location: '. ($ref ? $ref : "/") );
?>
