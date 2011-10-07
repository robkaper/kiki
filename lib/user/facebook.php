<?

/**
 * Facebook connection user class.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */
  
class User_Facebook extends User_External
{
  protected function connect()
  {
    if ( Config::$facebookApp && extension_loaded('curl') )
    {
      $this->api = new Facebook( array(
        'appId'  => Config::$facebookApp,
        'secret' => Config::$facebookSecret,
        'cookie' => true
        ) );
    }

    if ( $this->id && $this->token )
    {
      Log::debug( "connecting facebook api with stored token" );
      $this->token = @unserialize($this->token);
      $this->api->setSession($this->token);
    }
    else if ( isset($_GET['session']) )
    {
      Log::debug( "connecting facebook api with session" );
      $session = $this->api->getSession();
      if ( $session && $session['expires'] == 0 )
      {
        Log::debug( "endless token from session, need to link/store?" );
        $this->token = serialize($session);
      }
    }

    // FIXME: debug and re-enable
    return;

    $perms = explode(",", $_GET['perms'] );
    foreach( $perms as $perm )
      self::storePerm($perm);
  } 

  public function identify()
  {
    if ( !$this->id = $this->detectLoginSession() )
    {
      $cookie = $this->cookie();
      if ( $cookie )
        $this->id = $cookie['uid'];
    }

    Log::debug( "id -> ". $this->id );
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

    // // WARNING: This might break permission updates, as those probably do
    // require fetching the new sesion token.
    if ( !isset($_GET['session']) )
      return;

    if ( !$this->api )
    {
      $this->api = new Facebook( array(
        'appId'  => Config::$facebookApp,
        'secret' => Config::$facebookSecret,
        'cookie' => true
        ) );
    }

    $session = $this->api->getSession();
    if ( !$session )
      return 0;

    if ( !isset($session['uid']) )
      return 0;

    $this->id = $session['uid'];
    $this->token = serialize($session);
    return $session['uid'];
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
  public function loadRemoteData()
  {
    $data = null;

    try
    {
      $data = $this->api ? $this->api->api('/me') : null;
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

    // $this->id = $data['id'];
    $this->name = $this->screenName = $data['name'];
    $this->picture = "http://graph.facebook.com/". $this->id. "/picture";
  }

  public function post( $msg, $link='', $name='', $caption='', $description = '', $picture = '' )
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
      'privacy' => json_encode( array('value' => 'ALL_FRIENDS') )

      // TODO: allow choice of EVERYONE, CUSTOM, ALL_FRIENDS, NETWORKS_FRIENDS, FRIENDS_OF_FRIENDS, SELF.
    );

    Log::debug( "attachment: ". print_r( $attachment, true ) );
    try
    {
      $fbRs = $this->api->api('/me/feed', 'post', $attachment);

      $qPost = $this->db->escape( serialize($attachment) );
      $qResponse = $this->db->escape( serialize($fbRs) );
      $q = "insert into social_updates (ctime,network,post,response) values (now(), 'facebook', '$qPost', '$qResponse')";
      $this->db->query($q);
    }
    catch ( FacebookApiException $e )
    {
      $result->error = $e;
      Log::debug( "error: $result->error" );
      return $result;
    }

    if ( isset($fbRs['id']) )
    {
      $result->id = $fbRs['id'];
      list( $uid, $postId ) = explode( "_", $fbRs['id']);
      $result->url = "http://www.facebook.com/$uid/posts/$postId";
    }
    else
    {
      $result->error = $fbRs;
      Log::debug( "error: $result->error" );
    }
    
    return $result;
  }

  public function postArticle( &$article )
  {
    $msg = '';
    $link = $article->url();
    $title = $article->title();
    $caption = $_SERVER['SERVER_NAME'];
    $description = Misc::textSummary( $article->body(), 400 );
    $storageId = $article->headerImage();
    $picture = $storageId ? Storage::url( $storageId ) : Config::$siteLogo;

    $result = $this->post( $msg, $link, $title, $caption, $description, $picture );
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
  public function createEvent( $title, $start, $end, $location, $description, $picture=null )
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
      $this->api->setFileUploadSupport(true);
    }

    try 
    {
      // TODO: support pages (page Id instead of "me")
      $rs = $this->api->api('me/events', 'post', $attachment);
      return $rs;
    }
    catch ( FacebookApiException $e )
    {
      Log::debug( "FacebookApiException $e, ". print_r($attachment, true). print_r($this, true) );
      return null;
    }

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
    if ( !$this->api )
      return false;

    // TODO: this is awfully slow (as expected from remote calls),
    // implement verify and only do a remote check prior to posting.  Or
    // just catch the error and always check the local store.
    try {
      $perm = $this->api->api( array( 'method' => 'users.hasapppermission', 'ext_perm' => $perm ) );
      return $perm;
    }
    catch( FacebookApiException $e )
    {
      Log::debug( "error in fb api call for hasPerm ($e), guessing user doesn't have it then" );
      // TODO: should then be unlinked for this user..
      return false;
    }
  }

  // FIXME: port
  public function storePerm( $perm )
  {
    $qPerm = $this->db->escape( $perm );
    $q = "insert into facebook_user_perms (facebook_user_id, perm_key, perm_value) values ($this->id, '$qPerm', 1)";
    $this->db->query($q);
  }

  // FIXME: port
  public function revokePerm( $perm )
  {
    if ( !$this->fb )
      return;

    // Tell Facebook to revoke permission
    $fbRs = $this->fb->api( array( 'method' => 'auth.revokeExtendedPermission', 'perm' => $perm ) );

    // Remove permission from database
    $qPerm = $this->db->escape( $perm );
    $q = "update facebook_user_perms set perm_value=0 where perm_key='$qPerm' and facebook_user_id=$this->id";
    $this->db->query($q);

    // Remove user access_token and cookie to force retrieval of a new access token with correct permissions
    $q = "update facebook_users set access_token=null where id=$this->id";
    $this->db->query($q);

    $cookieId = "fbs_". Config::$facebookApp;
    setcookie( $cookieId, "", time()-3600, "/", $_SERVER['SERVER_NAME'] );
  }
  
  public function getLoginUrl( $params )
  {
    return $this->api->getLoginUrl( $params );
  }
              
}

?>
