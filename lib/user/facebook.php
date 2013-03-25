<?php

/**
 * Facebook connection user class.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

if ( isset(Config::$facebookSdkPath) )
{
  require_once Config::$facebookSdkPath. "/src/facebook.php"; 
}
 
class User_Facebook extends User_External
{
  private function enabled()
  {
    return ( extension_loaded('curl') && class_exists('Facebook') && Config::$facebookApp );
  }

  protected function connect()
  {
    if ( !$this->enabled() )
      return;

    // TODO: use not the default Facebook class extending BaseFacebook, but
    // our own, so we can catch connection error exceptions.
    $this->api = new Facebook( array(
      'appId'  => Config::$facebookApp,
      'secret' => Config::$facebookSecret,
      'cookie' => true
      ) );

    if ( $this->externalId && $this->token )
    {
      // Support both old storage (serialized) as new (unserialized)
      $token = @unserialize($this->token);
      if ( $token !== false )
        $this->api->setAccessToken($token['access_token']);
      else
        $this->api->setAccessToken($this->token);
    }

    if ( isset($_GET['perms']) )
    {
      // TODO: check whether this is a list with values, or a list of enabled perms
      $perms = explode(",", $_GET['perms'] );
      foreach( $perms as $perm )
        self::storePerm($perm, true);
    }
  } 

  public function identify()
  {
    if ( !$this->externalId = $this->detectLoginSession() )
    {
      $cookie = $this->cookie();
      if ( $cookie )
        $this->externalId = $cookie['uid'];
    }
  }

  public function authenticate()
  {
  }

  protected function detectLoginSession()
  {
    // Only detect a session when a login request has been made explicitely.
    // Otherwise, the Facebook API will still recognise the session even
    // after clearing the local cookie, effectively making it impossible to
    // logout.

    // WARNING: This might break permission updates, as those probably do
    // require fetching the new sesion token.
    if ( !isset($_GET['state'], $_GET['code']) )
      return;

    try
    {
      $this->externalId = $this->api()->getUser();
      $this->token = $this->api()->getAccessToken();
    }
    catch ( UserApiException $e )
    {
      Log::error( "UserApiException: $e" );
    }

    return $this->externalId;
  }

  public function verifyToken()
  {
    $apiToken = $this->api()->getAccessToken();
    if ( $apiToken != $this->token )
      $this->token = $apiToken;
  }

  // Returns entire verified cookie as array, or null if not valid or no cookie present
  protected function cookie()
  {
    $args = array();

    $cookieId = "fbs_". Config::$facebookApp;
    if ( isset($_COOKIE[$cookieId]) )
      parse_str( trim($_COOKIE[$cookieId], '\\"'), $args );\

    ksort($args);
    
    $payload = '';
    foreach ( $args as $key => $value )
      if ( $key != 'sig' )
        $payload .= $key. '='. $value;

    if ( !isset($args['sig']) || md5($payload. Config::$facebookSecret) != $args['sig'] )
      return null;

    return $args;
  }

  // TODO: load user_id, external_id, service, ctime, mtime, accesstoken, secret, name, screen, picture
  public function loadRemoteData( $api = null )
  {
    $data = null;

    try
    {
      $data = $api ? $api->api('/'. $this->externalId) : $this->api()->api('/me');
    }
    catch ( UserApiException $e )
    {
      Log::error( "UserApiException: $e" );
    }
    catch ( FacebookApiException $e )
    {
      Log::error( "FacebookApiException: $e" );
    }

    if ( !$data )
    {
      Log::debug( "failed, no data" );
      return;
    }

    // $this->externalId = $data['id'];
    $this->name = $data['name'];
    $this->screenName = $data['username'];
    $this->picture = "http://graph.facebook.com/". $this->externalId. "/picture";
  }

  public function getSubAccounts()
  {
    $this->subAccounts = array();

    if ( $this->hasPerm('manage_pages') )
    {
      $rs = $this->api()->api( '/me/accounts' );
			Log::debug( "SLOW: load /me/accounts" );
      if ( isset($rs['data']) && count($rs['data']) )
      {
        foreach( $rs['data'] as $page )
        {
          if ( !isset($page['perms']) )
            continue;


          // Log::debug( "page: ". print_r($page,true) );
          $this->subAccounts[] = $page;
          // $connection->api()->setAccessToken( $page['access_token'] );
          // $fbRs = $connection->api()->api('/me/feed', 'post', $attachment);
        }
      }
    }
  }

	public function getPermissions()
	{
		$this->permissions = array();

		$supportedPermissions = array(
			'read_stream'=> "Jouw news feed lezen",
			'publish_stream' => "Namens jou berichten publiceren",
			'user_events' => "Jouw events inzien",
			'create_event' => "Namens jou een event aanmaken",
			'manage_pages'=> "Pagina (Page) beheer",
		);

		foreach( $supportedPermissions as $key => $description )
		{
			$this->permissions[] = array(
				'key' => $key,
				'description' => $description,
				'value' => $this->hasPerm($key),
				'revokeUrl' => Config::$kikiPrefix. "/facebook-revoke.php?id=". $this->id. "&permission=". $key,
				'requestUrl' => Config::$kikiPrefix. "/facebook-grant.php?id=". $this->id. "&permission=". $key
			);
		}		

	}

  public function post( $objectId, $msg, $link='', $name='', $caption='', $description = '', $picture = '' )
  {
    $result = new stdClass;
    $result->id = null;
    $result->url = null;
    $result->error = null;

/*
    if ( !$this->authenticated || !$this->api )
    {
      $result->error = "Facebook user not authenticated.";
      return $result;
    }
*/

    $attachment = array(
      'message' => $msg,
      'link' => $link, 
      'name' => $name,
      'caption' => $caption,
      'description' => $description,
      'picture' => $picture,
      'privacy' => json_encode( array('value' => 'EVERYONE') )
		);

    // TODO: allow choice of EVERYONE, CUSTOM, ALL_FRIENDS, NETWORKS_FRIENDS, FRIENDS_OF_FRIENDS, SELF.

    Log::debug( "attachment: ". print_r( $attachment, true ) );

    $publication = new Publication();
    $publication->setObjectId( $objectId );
    $publication->setConnectionId( $this->externalId );
    $publication->setBody( serialize($attachment) );

    try
    {
      $fbRs = $this->api()->api('/me/feed', 'post', $attachment);
    }
    catch ( UserApiException $e )
    {
      Log::error( "UserApiException: $e" );
    }
    catch ( FacebookApiException $e )
    {
      $result->error = $e;
      Log::debug( "error: $result->error" );
      return $result;
    }

    $publication->setResponse( serialize($fbRs) );

    if ( isset($fbRs['id']) )
    {
      $result->id = $fbRs['id'];
      list( $uid, $postId ) = explode( "_", $fbRs['id']);
      $result->url = "http://www.facebook.com/$uid/posts/$postId";
      $publication->setExternalId( $postId );
    }
    else
    {
      $result->error = $fbRs;
      Log::debug( "error: $result->error" );
    }

    $publication->save();
    
    return $result;
  }

  public function postArticle( &$article )
  {
    $msg = '';
    $link = $article->url();
    $title = $article->title();
    $caption = str_replace( "http://", "", $link );
    $description = strip_tags( Misc::textSummary( $article->body(), 400 ) );
    $storageId = $article->topImage();

    // 500x500 cropped is good enough for Facebook
    $picture = $storageId ? Storage::url( $storageId, 500, 500, true ) : Config::$siteLogo;

    $result = $this->post( $article->objectId(), $msg, $link, $title, $caption, $description, $picture );
    return $result;
  }

	public function postPicture()
	{
	}

	public function postAlbum( &$album, $newPictures = 0 )
	{
		Log::debug( print_r($album,true) );
		Log::debug( print_r($newPictures,true) );

		$this->api()->setFileUploadSupport(true);

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

		$attachment = array(
			'source' => "@". Storage::localFile($storageId),
			'message' => $msg
		);

		$target = "/". $this->externalId. "/photos";

		Log::debug( print_r( $attachment,true) );

    $publication = new Publication();
    $publication->setObjectId( $album->objectId() );
    $publication->setConnectionId( $this->externalId );
    $publication->setBody( serialize($attachment) );

		// TODO: store Publication(s), requires objectId for pictures and albums
		$rs = $this->api()->api( $target, 'POST', $data );

		$publication->setResponse( serialize($rs) );

    if ( isset($rs['id']) )
    {
      $result->id = $fbRs['id'];
      list( $uid, $postId ) = explode( "_", $fbRs['id']);
      $result->url = "http://www.facebook.com/$uid/posts/$postId";
      $publication->setExternalId( $postId );
		}

		$publication->save();

		Log::debug(	print_r($rs,true) );

		return $rs;
	}

  public function postEvent( &$event )
  {
    $rs = $this->createEvent( $event->objectId(), $event->title(), strtotime($event->start()), strtotime($event->end()), $event->location(), $event->description(), Storage::localFile($event->topImage()) );

    // TODO: post a link on the event wall. Disabled because of a bug in Facebook:
    // https://developers.facebook.com/bugs/225344230889618/

    /*
    if ( isset($rs['id']) )
    {
      $attachment = array(
        'message' => "Officiële event pagina",
        'link' => $event->url()
      );
      $rsPost = $this->api()->api( $rs['id']. "/feed", 'post', $attachment);
      Log::debug( "rsPost:". print_r($rsPost,true) );
    }
    */

    $result = new stdClass;
    $result->id = isset($rs['id']) ? $rs['id'] : null;
    $result->url = isset($rs['id']) ? "http://www.facebook.com/". $rs['id'] : null;
    $result->error = isset($rs['error']) ? $rs['error'] : null;
    return $result;
  }

  /**
   * Creates an event on Facebook.
   *
   * @param string $title Title of the event.
   * @param int $start Epoch timestamp for the event start.
   * @param int $end Epoch timestamp for the event end.
   * @param string $location Location of the event.
   * @param string $description Description of the event.
   * @param string $picture Local path (no URL) of the picture for the event.
   *
   * @return array Result set of the Facebook API call.
   * @todo Standardise the result sets of connection API calls.
   */
  public function createEvent( $objectId, $title, $start, $end, $location, $description, $picture=null )
  {
    // Privacy types: OPEN, CLOSED, SECRET

    $start = is_numeric($start) ? date( "c", $start ) : $start;
    $end = is_numeric($end) ? date( "c", $end ) : $end;

    $attachment = array(
      "privacy_type" => "OPEN",
      "name" => $title,
      "host" => "Me",
      "start_time" => $start,
      "end_time" => $end,
      "location" => $location,
      "description" => $description
    );

    if ( $picture )
    {
      $attachment[basename($picture)] = "@". realpath($picture);
      $this->api()->setFileUploadSupport(true);
    }

    try 
    {
      // TODO: support pages (page Id instead of "me")
      $rs = $this->api()->api('me/events', 'post', $attachment);
    }
    catch ( UserApiException $e )
    {
      Log::error( "UserApiException: $e" );
    }
    catch ( FacebookApiException $e )
    {
      Log::debug( "FacebookApiException $e, ". print_r($attachment, true). print_r($this, true) );
      return null;
    }

    $publication = new Publication();
    $publication->setObjectId( $objectId );
    $publication->setConnectionId( $this->externalId );
    $publication->setBody( serialize($attachment) );

    $publication->setResponse( serialize($rs) );

    if ( isset($rs['id']) )
    {
      list( $uid, $postId ) = explode( "_", $rs['id']);
      $publication->setExternalId( $postId );
    }
    else
    {
      $result->error = $rs;
      Log::debug( "error: $result->error" );
    }

    $publication->save();

    return $rs;

    // TODO: invites
    // $fb->api( array(
    //   'method' => 'events.invite',
    //   'eid' => $event_id,
    //   'uids' => $id_array,
    //   'personal_message' => $message,
    // ) );
  }

  public function uploadPhoto()
  {
    // $attachment = array( 'message' => $caption );    
    // $attachment[basename($file_path)] = '@' . realpath($file_path);
    // $result = $facebook->api('me/photos','post',$attachment);
  }

  public function hasPerm( $perm, $verify=false )
  {
    // TODO: actually use verify prior to api calls that require permissions
    if ( !$verify )
    {
      $q = $this->db->buildQuery( "SELECT perm_value FROM facebook_user_perms WHERE perm_key='%s' AND facebook_user_id=%d", $perm, $this->externalId );
      $value = $this->db->getSingleValue($q);
      
      if ( $value!==null )
        return $value;
    }

    $value = false;

    // FIXME: port to /me/permissions
    try
    {
      $value = $this->api()->api( array( 'method' => 'users.hasapppermission', 'ext_perm' => $perm ) );
    }
    catch ( UserApiException $e )
    {
      Log::error( "UserApiException: $e" );
    }
    catch( FacebookApiException $e )
    {
      Log::debug( "error in fb api call for hasPerm ($e), guessing user doesn't have it then" );
    }

    self::storePerm( $perm, $value );
    return $value;
  }

  public function storePerm( $perm, $value=false, $deleteWhenFalse=false )
  {
    $qPerm = $this->db->escape( $perm );

    if ( $deleteWhenFalse && !$value )
    {
      $q = $this->db->buildQuery(
        "DELETE from facebook_user_perms WHERE facebook_user_id=%d AND perm_key='%s'",
        $this->externalId, $perm
      );
    }
    else
    {
      $q = $this->db->buildQuery(
        "INSERT INTO facebook_user_perms (facebook_user_id, perm_key, perm_value) VALUES (%d, '%s', %d) ON DUPLICATE KEY UPDATE perm_value=%d",
        $this->externalId, $perm, $value, $value
      );
    }
    $this->db->query($q);
  }

  public function revokePerm( $perm, $deleteStoredValue=false )
  {
    // Tell Facebook to revoke permission
    try
    {
      $fbRs = $this->api()->api( "/me/permissions/$perm", $method = 'DELETE' );
    }
    catch ( UserApiException $e )
    {
      Log::error( "UserApiException: $e" );
    }

    // Remove permission from database
    self::storePerm( $perm, false, $deleteStoredValue );

    // Remove user access_token and cookie to force retrieval of a new access token with correct permissions
    $q = $this->db->buildQuery(
      "UPDATE connections set token=null where service='%s' and external_id='%s'",
      get_class($this), $this->externalId );
    $this->db->query($q);

    $cookieId = "fbs_". Config::$facebookApp;
    setcookie( $cookieId, "", time()-3600, "/", $_SERVER['SERVER_NAME'] );
  }

  public function clearPermissions()
  {
    $q = $this->db->buildQuery(
      "DELETE from facebook_user_perms WHERE facebook_user_id=%d",
      $this->externalId
    );
    $this->db->query($q);
  }
    
  public function getLoginUrl( $params, $force = false )
  {
    if ( !$params )
      $params = array();
    else if ( !is_array($params) )
    {
      Log::debug( "called with non-array argument" );
      $params = array();
    }
    // $params['display'] = "popup";

    // Force a login URL through the app API, not the user connection.
    // Without first verifying the required permission Facebook otherwise
    // thinks everything is ok because the user is already logged in.
    if ( $force )
    {
      $connection = Factory_ConnectionService::getInstance('Facebook');
      return $connection->loginUrl($params);
    }
    
    return $this->api ? $this->api->getLoginUrl( $params ) : null;
  }
              
}

/*

Facebook permission overview:

user_about_me			user_activities			user_birthday
user_checkins			user_education_history		user_events
user_games_activity		user_groups			user_hometown
user_interests			user_likes			user_location
user_location_posts		user_notes			user_online_presence
user_photo_video_tags		user_photos			user_questions
user_relationship_details	user_relationships		user_religion_politics
user_status			user_subscriptions		user_videos
user_website			user_work_history

friends_about_me		friends_activities		friends_birthday
friends_checkins		friends_education_history	friends_events
friends_games_activity		friends_groups			friends_hometown
friends_interests		friends_likes			friends_location
friends_location_posts		friends_notes			friends_online_presence
friends_photo_video_tags	friends_photos			friends_questions
friends_relationship_details	friends_relationships		friends_religion_politics
friends_status			friends_subscriptions		friends_videos
friends_website			friends_work_history

ads_management			create_event			create_note
email				export_stream			manage_friendlists
manage_notifications		manage_pages			offline_access
photo_upload			publish_actions			publish_checkins
publish_stream			read_friendlists		read_insights
read_mailbox			read_requests			read_stream
rsvp_event			share_item			sms
status_update			video_upload			xmpp_login

*/
