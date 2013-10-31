<?php

/**
 * Facebook connection service.
 *
 * @class ConnectionService_Facebook
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2011-2013 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

namespace Kiki\ConnectionService;

if ( isset(\Kiki\Config::$facebookSdkPath) )
{
  require_once \Kiki\Config::$facebookSdkPath. "/src/facebook.php"; 
}

class Facebook
{
  private $api;
  private $enabled = false;

  public function __construct()
  {
    if ( !class_exists('Facebook') )
		{
			if ( isset(\Kiki\Config::$facebookSdkPath) )
			{
				Log::error( "could not instantiate Facebook class from ". \Kiki\Config::$facebookSdkPath. "/src/facebook.php" );
			}
      return;
		}

    $this->api = new \Facebook( array(
      'appId'  => \Kiki\Config::$facebookApp,
      'secret' => \Kiki\Config::$facebookSecret,
      'cookie' => false
      ) );

    $this->enabled = true;
  }

  public function enabled() { return $this->enabled; }

  public function name()
  {
    return "Facebook";
  }
  
  public function loginUrl( $params = null )
  {
    if ( !$params )
      $params = array();
    else if ( !is_array($params) )
    {
      Log::debug( "called with non-array argument" );
      $params = array();
    }
    // $params['display'] = "popup";

    return $this->api ? $this->api->getLoginUrl($params) : null;
  }
}

?>
