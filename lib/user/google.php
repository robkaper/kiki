<?php

namespace Kiki\User;

use Kiki\Config;
use Kiki\Log;

if ( isset(Config::$googleApiClientPath) )
{
  require_once Config::$googleApiClientPath. "/vendor/autoload.php";
}

class Google extends External
{
  private function enabled()
  {
    return ( class_exists('Google_Client') && isset( Config::$googleApiClientId ) );
  }

  protected function connect()
  {
    if ( !$this->enabled() )
      return;

    // FIXME: use ConnectionService\Google instead, it has - or should have - all of this
    $this->client = new \Google_Client();
    $this->client->setClientId( Config::$googleApiClientId );
    $this->client->setClientSecret( Config::$googleApiClientSecret );
    $this->client->setRedirectUri( 'https://'. $_SERVER['HTTP_HOST']. '/login' );
    $this->client->addScope("email");
    $this->client->addScope("profile");

    if ( $this->externalId && $this->token )
    {
      $this->client->setAccessToken( $this->token );
      $this->loadRemoteData();
    }
  }

  public function authenticate()
  {
    $this->detectLoginSession();
  }
  
  protected function detectLoginSession()
  {
    // Only detect a session when a login request has been made explicitely. 
    // Otherwise, the API client might recognise the session even without
    // the Kiki auth cookie, effectively making it impossible to logout.

    if ( !isset($_GET['code']) )
      return null;

    $token = $this->client()->fetchAccessTokenWithAuthCode( $_GET['code'] );
    if ( !$token || !isset($token['access_token']) )
      return null;
      
    $this->token = $token['access_token'] ?? null;
    $this->client()->setAccessToken( $this->token );

    // Possibly overkill, but seems to be the only way to get externalId from Google
    $this->loadRemoteData();
  }

  public function verifyToken()
  {
    // Not applicable for Google OAuth.
    return null;
  }

  protected function cookie()
  {
    // Google API client doesn't set a cookie after an auth request, so
    // there's nothing to return or parse.
    return null;
  }

  public function loadRemoteData()
  {
    // Requires $this->client from connect(), with valid setAccessToken()
    if ( !$this->client || !$this->token )
      return;

    // Get profile info
    $googleOAuth = new \Google_Service_Oauth2( $this->client() );
    $googleUserInfo = $googleOAuth->userinfo->get();

    Log::debug( print_r( $googleUserInfo, true ) );

    $this->externalId = $googleUserInfo->id;
    $this->email = $googleUserInfo->email;
    $this->name = $googleUserInfo->name;
    $this->picture = $googleUserInfo->picture; // is URL

/*
Google\Service\Oauth2\Userinfo Object (
  [modelData:protected] => Array (
    [verified_email] => 1
    [given_name] => Rob
    [family_name] => Kaper
  )
  [processed:protected] => Array ( )
  [email] => rjkaper@gmail.com
  [familyName] => Kaper
  [gender] =>
  [givenName] => Rob
  [hd] =>
  [id] => 105849520568183941965
  [link] =>
  [locale] => en-GB
  [name] => Rob Kaper
  [picture] => https://lh3.googleusercontent.com/a/AEdFTp537ZVTL5wf8z33lN2h8U3B231X8SFN0pqqb4P4jHA=s96-c
  [verifiedEmail] => 1
)
*/
  }
}
