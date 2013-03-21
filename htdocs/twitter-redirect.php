<?php

/**
 * Builds a Twitter authorisation URL using the generic application token
 * and referer as callback URL, then redirects to it.
 *
 * Would be nice if this were turned into a Controller module.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2010-2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

//session_start();

if ( isset(Config::$twitterOAuthPath) )
{
  require_once Config::$twitterOAuthPath. "/twitteroauth/twitteroauth.php";
}
else
{
	// If this were a Controller module, we could at least tell this to the
	// user. Even if it should never happen. 
  Log::debug( "SNH: twitter-redirect called without Twitter configuration" );
  exit();
}

// Build TwitterOAuth object with app credentials.
$connection = new TwitterOAuth( Config::$twitterApp, Config::$twitterSecret );
 
// Get temporary credentials. Even though a callback URL is specified here,
// it must be set in your Twitter application settings as well to avoid a
// PIN request (it can be anything actually, just not empty).
$callbackUrl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
$request_token = $connection->getRequestToken( $callbackUrl );

// Save temporary credentials to session.
$_SESSION['oauth_token'] = $token = $request_token['oauth_token'];
$_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];

switch ($connection->http_code)
{
  case 200:
    // Build authorize URL and redirect user to Twitter.
    Router::redirect( $connection->getAuthorizeURL($token) ) && exit();
    break;
  default:
    // Show notification if something went wrong. But why so crude?
    echo 'Could not connect to Twitter. Refresh the page or try again later.';
}

?>