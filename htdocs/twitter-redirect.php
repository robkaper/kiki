<?

/**
* @file htdocs/twitter-redirect.php
* Builds a Twitter authorisation URL and redirects to it.
* @author Rob Kaper <http://robkaper.nl/>
* @section license_sec License
* Released under the terms of the MIT license.
*/

include_once "../lib/init.php";

session_start();

// Build TwitterOAuth object with client credentials.
$connection = new TwitterOAuth( Config::$twitterApp, Config::$twitterSecret );
 
// Get temporary credentials.
$request_token = $connection->getRequestToken( $_SERVER['HTTP_REFERER'] );

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