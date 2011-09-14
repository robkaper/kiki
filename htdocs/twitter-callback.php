<?

/**
* @file htdocs/twitter-callback.php
* Provides the callback URL required for Twitter OAuth authorisation and
* redirects back to referer if present, or main page otherwise.
* @author Rob Kaper <http://robkaper.nl/>
* @section license_sec License
* Released under the terms of the MIT license.
*/

  session_start();

  include_once "../lib/init.php";

  if ( isset($_REQUEST['oauth_token']) && $_SESSION['oauth_token'] !== $_REQUEST['oauth_token'] )
    Log::error( "SNH: twitter-callback token mismatch" );

  Log::debug( "registerAuth etc can be removed here as User_Twitter should handle this" );
  // $accessToken = $user->twUser->registerAuth();

  // Remove no longer needed request tokens
  unset($_SESSION['oauth_token']);
  unset($_SESSION['oauth_token_secret']);

  if ( $accessToken )
  {
    $twUid = $accessToken['user_id'];
    if ( $twUid )
    {
      // @bug Kiki should not set this cookie but instead should solely rely
      // on its own cookies.  Or use the Javascript API of Twitter and let
      // Twitter set this cookie themselves.  This could however create a
      // conflict in combination with the Facebook API when both authorise a
      // different account.  Solution would be to make internal cookies
      // leading and to allow the user to only use one external auth
      // provider automatically.
      $twSig = sha1( $twUid. Config::$twitterSecret );
      $expire = time() + (7*24*3600);
      setcookie( "twitter_anywhere_identity", "$twUid:$twSig", $expire, "/" );
    }
  }

  $ref = array_key_exists( 'HTTP_REFERER', $_SERVER ) ? $_SERVER['HTTP_REFERER'] : "";
  Log::debug( "twitter-callback redirect: $ref" );
  header( 'Location: '. ($ref ? $ref : "/") );
?>