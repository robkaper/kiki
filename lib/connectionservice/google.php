<?php

namespace Kiki\ConnectionService;

use Kiki\Log;
use Kiki\Config;

if ( isset(Config::$googleApiClientPath) )
{
  require_once Config::$googleApiClientPath. "/vendor/autoload.php";
}

class Google
{
  private $enabled = false;

  public function __construct()
  {
    if ( !class_exists('Google_Client') )
    {
      if ( isset(Config::$googleApiClientPath) )
      {
        Log::error( "could not instantiate Google_Client class from ". Config::$googleApiClientPath. "/vendor/autoload.php" );
      }
      return;
    }

    if ( !isset( Config::$googleApiClientId ) || !isset( Config::$googleApiClientSecret ) )
      return;

    // Redirect URI could technically be anything as all routed pages end up in $user->authenticate()
    // FIXME: /login is hardcoded, instead use router config for Controller\Account::loginAction with / as fallback
    $redirectUri = 'https://'. $_SERVER['HTTP_HOST']. '/login';
  
    $this->api = new \Google_Client();
    $this->api->setClientId( Config::$googleApiClientId );
    $this->api->setClientSecret( Config::$googleApiClientSecret );
    $this->api->setRedirectUri( $redirectUri );
    $this->api->addScope( "email" );
    $this->api->addScope("profile");

    $this->enabled = true;
  }

  public function enabled() { return $this->enabled; }

  public function name()
  {
    return "Google";
  }

  public function loginUrl()
  {
    return ($this->enabled && $this->api) ? $this->api->createAuthUrl() : null;
  }
}
