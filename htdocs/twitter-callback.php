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

  // @todo Possibly remove this callback URL, User_Twitter::identify() and
  // connect() should simply handle everything that has been done here.

  // Remove no longer needed request tokens
  unset($_SESSION['oauth_token']);
  unset($_SESSION['oauth_token_secret']);

  // @fixme referer never exists, either use _SESSION (from
  // twitter-redirect) or let twitter relay it if possible.
  $ref = array_key_exists( 'HTTP_REFERER', $_SERVER ) ? $_SERVER['HTTP_REFERER'] : "";
  Log::debug( "twitter-callback redirect: $ref" );
  header( 'Location: '. ($ref ? $ref : "/") );
?>