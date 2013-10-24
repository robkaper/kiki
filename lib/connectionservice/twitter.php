<?php

/**
 * Twitter connection service.
 *
 * @class ConnectionService_Twitter
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2011-2013 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

if ( isset(Config::$twitterOAuthPath) )
{
  require_once Config::$twitterOAuthPath. "/twitteroauth/twitteroauth.php"; 
}

class ConnectionService_Twitter
{
  private $enabled = false;

  public function __construct()
  {
    if ( !class_exists('TwitterOAuth') )
		{
			if ( isset(Config::$twitterOAuthPath) )
			{
				Log::error( "could not instantiate TwitterOAuth class from ". Config::$twitterOAuthPath. "/twitteroauth/twitteroauth.php" );
			}
      return;
		}

    $this->enabled = true;
  }

  public function enabled() { return $this->enabled; }

  public function name()
  {
    return "Twitter";
  }

  public function loginUrl()
  {
    return Config::$kikiPrefix. "/twitterRedirect";
  }
}

?>
