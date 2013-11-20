<?php

namespace Kiki\User;

use Kiki\Log;

if ( isset(\Kiki\Config::$twitterOAuthPath) )
{
  require_once \Kiki\Config::$twitterOAuthPath. "/twitteroauth/twitteroauth.php";
}

class Twitter extends External
{
  private $oAuthToken = null;
  // private $oAuthVerifier = null;

  private function enabled()
  {
		return class_exists('\TwitterOAuth');

  }

  protected function connect()
  {
    if ( !$this->enabled() )
      return;

    if ( !($this->token && $this->secret) )
      return;

    // Create TwitteroAuth object with app key/secret and token key/secret from default phase
    $this->api = new \TwitterOAuth(\Kiki\Config::$twitterApp, \Kiki\Config::$twitterSecret, $this->token, $this->secret);

		// Use 1.1 API (now that 1.0 is deprecated)
		$this->api->host = "https://api.twitter.com/1.1/";

    // $this->externalId = $this->oAuthToken['user_id'];
    $this->screenName = $this->oAuthToken['screen_name'];
  }

  public function identify()
  {
    if ( !$this->externalId = $this->detectLoginSession() )
      $this->externalId = $this->cookie();
  }
  
  public function authenticate()
  {
  }

  protected function detectLoginSession()
  {
    if ( !isset($_REQUEST['oauth_token']) )
      return 0;

    if ( !isset($_SESSION) )
      session_start();

    if ( !isset($_SESSION['oauth_token']) || !isset($_SESSION['oauth_token_secret']) )
      return 0;
      
    if ( $_SESSION['oauth_token'] !== $_REQUEST['oauth_token'] )
    {
      \Kiki\Log::error( "SNH: twitter oauth token mismatch" );
      return 0;
    }

    $this->token = $_SESSION['oauth_token'];
    $this->secret = $_SESSION['oauth_token_secret'];
    unset($_SESSION['oauth_token']);
    unset($_SESSION['oauth_token_secret']);

		$this->connect();

    \Kiki\Log::debug( "getting access token and secret from request verifier ". $_REQUEST['oauth_verifier'] );
    $this->oAuthToken = $this->api->getAccessToken( $_REQUEST['oauth_verifier'] );
    if ( !$this->oAuthToken )
      return 0;

    \Kiki\Log::debug( "oAuthToken: ". print_r($this->oAuthToken, true ) );
    $this->token = $this->oAuthToken['oauth_token'];
    $this->secret = $this->oAuthToken['oauth_token_secret'];

    \Kiki\Log::debug( "this->token: $this->token" );
    return $this->oAuthToken['user_id'];
  }

  public function verifyToken()
  {
    $apiToken = $this->api()->getAccessToken( isset($_REQUEST['oauth_verifier']) ? $_REQUEST['oauth_verifier'] : null );
    if ( isset($apiToken['oauth_token']) && $apiToken['oauth_token'] != $this->token )
      $this->token = $apiToken['oauth_token'];
  }

  protected function cookie()
  {
    if ( array_key_exists( 'twitter_anywhere_identity', $_COOKIE ) )
    {
      list( $id, $sig ) = explode( ":", $_COOKIE['twitter_anywhere_identity'] );
      $hexDigest = sha1($id. \Kiki\Config::$twitterSecret);
      $valid = ($sig == $hexDigest);
      return $valid ? $id : 0;
    }
    return 0;
  }

  public function loadRemoteData()
  {
		$data = null;

		if (!$this->api->host)
			$this->connect();

    try
    {
      $data = $this->api()->get( 'users/lookup', array( "user_id" => $this->externalId ) );
    }
    catch ( Exception $e )
    {
      \Kiki\Log::error( "Exception: $e" );
    }

    if ( empty($data) )
    {
      \Kiki\Log::debug( "failed, no data" );
      return;
    }
		elseif( isset($data['errors']) )
		{
      \Kiki\Log::debug( "failed, errors: ". print_r($data['errors'],true) );
      return;
		}

    $this->name = $data[0]->name;
    $this->screenName = $data[0]->screen_name;
    $this->picture = $data[0]->profile_image_url;
  }

  public function getSubAccounts()
  {
    // Twitter has no subaccounts.
    $this->subAccounts = array();
  }

	public function getPermissions()
	{
		$this->permissions = array();

		// Twitter has permissions (read OR read+write), but these are not
		// individually managable, not even per-user, just per app.  This array
		// is hardcoded (Kiki requires read+write for admins thus for all users)
		// to be at least informative.

		$supportedPermissions = array(
			'read_stream'=> "Jouw news feed lezen",
			'publish_stream' => "Namens jou berichten publiceren",
		);

		foreach( $supportedPermissions as $key => $description )
		{
			$this->permissions[] = array(
				'key' => $key,
				'description' => $description,
				'value' => true,
				'revokeUrl' => null,
				'grantUrl' => null,
			);
		}		
	}

  public function post( $objectId, $msg, $link='', $name='', $caption='', $description = '', $picture = '' )
  {
    $result = new \stdClass;
    $result->id = null;
    $result->url = null;
    $result->error = null;

/*
    if ( !$this->authenticated || !$this->api )
    {
      $result->error = "Twitter user not authenticated.";
      return $result;
    }
*/

    \Kiki\Log::debug( "msg: $msg" );
    try
    {
      $twRs = $this->api()->post( 'statuses/update', array( 'status' => $msg ) );
    }
    catch ( Exception $e )
    {
      \Kiki\Log::error( "Exception: $e" );
    }

    $publication = new \Kiki\Publication();
    $publication->setObjectId( $objectId );
    $publication->setConnectionId( $this->externalId );
    $publication->setBody( $msg );
    $publication->setResponse( serialize($twRs) );
    $publication->setExternalId( isset($twRs->id) ? $twRs->id : 0 );
    $publication->save();

    if ( !$twRs )
    {
      $result->error = "Twitter status update failed.";
      return $result;
    }

    if ( isset($twRs->error) )
    {
      $result->error = $twRs->error;
      \Kiki\Log::debug( "twPost error: $result->error" );
    }
    else
    {
      $result->id = $twRs->id;
      $result->url = "http://www.twitter.com/". $twRs->user->screen_name. "/status/". $result->id;
    }
    
    return $result;
  }

  public function postArticle( &$article )
  {
    $tinyUrl = \Kiki\TinyUrl::get( $article->url() );

    $msg = sprintf( "%s %s %s", $article->title(), $tinyUrl, $article->hashtags() );
    $result = $this->post( $article->objectId(), $msg );
    return $result;
  }

	public function postAlbum( &$album, $newPictures = 0 )
	{
		$pictureCount = count($newPictures);
		$pictureId = ( $pictureCount > 0 ) ? $newPictures[0]['id'] : $album->firstPicture();

    $q = $this->db->buildQuery( "SELECT storage_id FROM pictures WHERE id=%d", $pictureId );
    $storageId = $this->db->getSingleValue($q);

		$msg = null;
		switch( $pictureCount )
		{
			case 0:
				$msg = sprintf( "%d new pictures in album %s (%s)", $pictureCount, $album->title(), $album->url() );
				break;
			case 1:
				$msg = sprintf( "%d new picture in album %s (%s)", $pictureCount, $album->title(), $album->url() );
				break;
			default:
				$msg = sprintf( "%d new pictures in album %s (%s)", $pictureCount, $album->title(), $album->url() );
				break;
		}

		// TODO: (also?) store publication for individual pictures
		$rs = $this->post( $album->objectId(), $msg );

		\Kiki\Log::debug(	print_r($rs,true) );

		return $rs;

	}

  public function postEvent( &$event )
  {
    $tinyUrl = \Kiki\TinyUrl::get( $event->url() );
    $msg = sprintf( "%s %s %s", $event->title(), $tinyUrl, $event->hashtags() );
    $result = $this->post( $event->objectId(), $msg );
    return $result;
  }

  public function createEvent( $objectId, $title, $start, $end, $location, $description, $picture=null )
  {
    return null;
  }
  
}

?>
