<?

/**
 * Builds a Twitter authorisation URL using the generic application token
 * and referer as callback URL, then redirects to it.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2010-2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

include_once "../lib/init.php";

session_start();

// Build TwitterOAuth object with client credentials.
$connection = new TwitterOAuth( Config::$twitterApp, Config::$twitterSecret );
 
// Get temporary credentials. Even though a callback URL is specified here,
// it must be set in your Twitter application settings as well to avoid a
// PIN request.
$callbackUrl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
$request_token = $connection->getRequestToken( $callbackUrl );

// Save temporary credentials to session.
$_SESSION['oauth_token'] = $token = $request_token['oauth_token'];
$_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];
 
// If last connection failed don't display authorization link.
switch ($connection->http_code)
{
  case 200:
    // Build authorize URL and redirect user to Twitter.
    $url = $connection->getAuthorizeURL($token);
    Log::debug( "twitter-redirect: $url" );
    header('Location: ' . $url); 
    break;
  default:
    // Show notification if something went wrong.
    echo 'Could not connect to Twitter. Refresh the page or try again later.';
}

?>