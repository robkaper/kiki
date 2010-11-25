<?
  include_once "../lib/init.php";
  session_start();

  if ( isset($_REQUEST['oauth_token']) && $_SESSION['oauth_token'] !== $_REQUEST['oauth_token'] )
    Log::error( "SNH: twitter-auth token mismatch" );

  // Create TwitteroAuth object with app key/secret and token key/secret from default phase
  $connection = new TwitterOAuth( Config::$twitterApp, Config::$twitterSecret, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret'] );
  $httpCode = $connection ? $connection->http_code : null;

  $accessToken = $connection->getAccessToken($_REQUEST['oauth_verifier']);
  $twApiUser = $connection ? $connection->get('account/verify_credentials') : null;

  $qId = $db->escape( $accessToken['user_id'] );
  $qAccessToken = $db->escape( $accessToken['oauth_token'] );
  $qSecret = $db->escape( $accessToken['oauth_token_secret'] );
  $qName = $twApiUser ? $db->escape( $twApiUser->name ) : "";
  $qScreenName = $db->escape( $accessToken['screen_name'] );
  $qPicture = $twApiUser ? $db->escape( $twApiUser->profile_image_url ) : "";
  if ( $qId )
  {
    $q = "insert into twitter_users (id,access_token,secret,name,screen_name,picture) values( $qId, '$qAccessToken', '$qSecret', '$qName', '$qScreenName', '$qPicture') on duplicate key update access_token='$qAccessToken', secret='$qSecret', name='$qName', screen_name='$qScreenName', picture='$qPicture'";
    Log::debug( "twitter-auth q: $q" );
    $db->query($q);
  }

  // Remove no longer needed request tokens
  unset($_SESSION['oauth_token']);
  unset($_SESSION['oauth_token_secret']);

  // HACK: not all browsers (read: my HTC Hero) store the cookie Twitter sets, (re-)set it here ourselves
  $twUid = $accessToken['user_id'];
  $twSig = sha1( $twUid. Config::$twitterSecret );
  $expire = 0; // time() + (7*24*3600);
  setcookie( "twitter_anywhere_identity", "$twUid:$twSig", $expire, "/" );

  if ( $httpCode == 200 || $twUid )
  {
    $ref = array_key_exists( 'HTTP_REFERER', $_SERVER ) ? $_SERVER['HTTP_REFERER'] : "";
    Log::debug( "twitter-auth redirect: $ref" );
    header( 'Location: '. ($ref ? $ref : "/") );
  }
  else
    Log::error( "twitter-auth failed, httpCode=$httpCode" );
?>