<?

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
    $this->name = $this->screenName = $data['name'];
    $this->screenName = $data['username'];
    $this->picture = "http://graph.facebook.com/". $this->externalId. "/picture";
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
      'privacy' => json_encode( array('value' => ($link ? 'EVERYONE' : 'ALL_FRIENDS') ) )

      // TODO: allow choice of EVERYONE, CUSTOM, ALL_FRIENDS, NETWORKS_FRIENDS, FRIENDS_OF_FRIENDS, SELF.
    );

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
    $storageId = $article->headerImage();

    // 500x500 cropped is good enough for Facebook
    $picture = $storageId ? Storage::url( $storageId, 500, 500, true ) : Config::$siteLogo;

    $result = $this->post( $article->objectId(), $msg, $link, $title, $caption, $description, $picture );
    return $result;
  }

  public function postEvent( &$event )
  {
    $rs = $this->createEvent( $event->objectId(), $event->title(), strtotime($event->start()), strtotime($event->end()), $event->location(), $event->description(), Storage::localFile($event->headerImage()) );

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

    // Remove timezome because otherwise Facebook will convert it to
    // Pacific.  Just let it assume our time is indeed Pacific time (even
    // when it isn't) because that is what Facebook displays anway.
    $start = substr( date( "c", $start ), 0, -6 );
    $end = substr( date( "c", $end ), 0, -6 );

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

  // FIXME: port
  public function revokePerm( $perm, $deleteStoredValue=false )
  {
    // Tell Facebook to revoke permission
    try
    {
      // FIXME: port to /me/permissions/$perm (HTTP DELETE)
      $fbRs = $this->api()->api( array( 'method' => 'auth.revokeExtendedPermission', 'perm' => $perm ) );
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

?>
