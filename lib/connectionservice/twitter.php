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

namespace Kiki\ConnectionService;

if ( isset(\Kiki\Config::$twitterOAuthPath) )
{
  require_once \Kiki\Config::$twitterOAuthPath. "/twitteroauth/twitteroauth.php"; 
}

class Twitter
{
  private $enabled = false;

  public function __construct()
  {
    if ( !class_exists('TwitterOAuth') )
		{
			if ( isset(\Kiki\Config::$twitterOAuthPath) )
			{
				Log::error( "could not instantiate TwitterOAuth class from ". \Kiki\Config::$twitterOAuthPath. "/twitteroauth/twitteroauth.php" );
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
    return \Kiki\Config::$kikiPrefix. "/twitterRedirect";
  }
}

?>
