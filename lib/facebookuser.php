<?

class FacebookUser
{
  private $db;
  public $fb;

  public $id, $accessToken, $name, $authenticated;

  public function __construct( $id = null )
  {
    $this->db = $GLOBALS['db'];
    if ( Config::$facebookApp && extension_loaded('curl') )
    {
      $this->fb = new Facebook( array(
        'appId'  => Config::$facebookApp,
        'secret' => Config::$facebookSecret,
        'cookie' => true
        ) );
    }

    $this->reset();

    if ( $id )
      $this->load( $id );
  }

  public function reset()
  {
    $this->id = 0;
    $this->accessToken = "";
    $this->name = "";
    $this->authenticated = null;
  }

  public function load( $id )
  {
    $qId = $this->db->escape( $id );
    $q = "select access_token, name from facebook_users where id='$qId'";
    $o = $this->db->getSingle($q);
    if ( !$o )
      return;

    $this->id = $id;
    $this->accessToken = @unserialize($o->access_token);
    $this->name = $o->name;
  }

  public function identify( $id = 0 )
  {
    if ( $id )
      $this->id = $id;
    else
    {
      $cookie = $this->cookie();
      Log::debug( "fbCookie: ". print_r( $cookie, true ) );
      if ( $cookie )
        $this->id = $cookie['uid'];

      if ( $this->fb )
      {
        $fbSession = $this->fb->getSession();
        Log::debug( "fbSession: ". print_r( $fbSession, true ) );
        if ( isset($fbSession['uid']) )
          $this->id = $fbSession['uid'];
      }

    }

    if ( $this->id )
      Log::debug( "FacebookUser->identify $id -> ". $this->id );

    $this->load( $this->id );
  }

  public function authenticate()
  {
    if ( !$this->id )
      return;

    if ( !$this->fb )
      return;

    Log::debug( "FacebookUser::authenticate" );

    $fbSession = $this->fb->getSession();
    if ( !isset($fbSession['uid']) && $this->accessToken )
    {
      Log::debug( "FacebookUser->authenticate setSession" );
      $this->fb->setSession( $this->accessToken );
    }

    $fbSession = $this->fb->getSession();
    if ( $fbSession )
    {
      try
      {
        $this->id = $this->fb->getUser();
        if ( !$this->id )
          return;

        Log::debug( "FacebookUser->authenticate: authenticated" );
        $this->authenticated = true;

        if ( !$this->accessToken || $this->accessToken != $fbSession )
          $this->registerAuth();

        if ( array_key_exists( 'session', $_GET ) )
        {
          $this->registerAuth();

          // Redirect to avoid ?session= request to appear in Analytics etc
          header( "Location: ". $_SERVER['SCRIPT_URL'], true, 301 );
          exit();
        }
      }
      catch ( FacebookApiException $e )
      {
        Log::debug( "FacebookUser->authenticate error: $e" );
        error_log($e);
      }
    }

  }

  // Returns entire verified cookie as array, or null if not valid or no cookie present
  private function cookie()
  {
    $args = array();

    $cookieId = "fbs_". Config::$facebookApp;
    if ( array_key_exists( $cookieId, $_COOKIE ) )
      parse_str( trim($_COOKIE[$cookieId], '\\"'), $args );\

    ksort($args);
    
    $payload = '';
    foreach ( $args as $key => $value )
      if ( $key != 'sig' )
        $payload .= $key. '='. $value;

    if ( !array_key_exists( 'sig', $args ) || md5($payload. Config::$facebookSecret) != $args['sig'] )
      return null;

    return $args;
  }

  private function registerAuth()
  {
    if ( !$this->fb )
      return;

    $fbUser = $this->fb->api('/me');
    if ( !$fbUser )
    {
      Log::debug( "SNH: fbRegisterAuth failed, no fbUser" );
      return;
    }

    $fbSession = $this->fb->getSession();
    if ( $fbSession && $fbSession['expires'] == 0 )
      $qAccessToken = $this->db->escape( serialize($fbSession) );
    else
      $qAccessToken = $this->accessToken;

    $qId = $this->db->escape( $fbUser['id'] );
    $qName = $this->db->escape( $fbUser['name'] );
    $q = "insert into facebook_users (id,ctime,mtime,access_token,name) values( $qId, now(), now(), '$qAccessToken', '$qName') on duplicate key update access_token='$qAccessToken', name='$qName'";
    Log::debug( "FacebookUser->registerAuth q: $q" );
    $this->db->query($q);

    $perms = explode(",", $_GET['perms'] );
    foreach( $perms as $perm )
      self::storePerm($perm);
  }
  
  public function post( $msg, $link='', $name='', $caption='', $description = '', $picture = '' )
  {
    $result = new stdClass;
    $result->id = null;
    $result->url = null;
    $result->error = null;

    if ( !$this->authenticated || !$this->fb )
    {
      $result->error = "Facebook user not authenticated.";
      return $result;
    }

    $attachment = array(
      'message' => $msg,
      'link' => $link, 
      'name' => $name,
      'caption' => $caption,
      'description' => $description,
      'picture' => $picture,
      'privacy' => json_encode( array('value' => 'ALL_FRIENDS') )

      // @todo allow choice of EVERYONE, CUSTOM, ALL_FRIENDS, NETWORKS_FRIENDS, FRIENDS_OF_FRIENDS, SELF.
    );

    Log::debug( "FacebookUser->post: ". print_r( $attachment, true ) );
    try
    {
      $fbRs = $this->fb->api('/me/feed', 'post', $attachment);

      $qPost = $this->db->escape( serialize($attachment) );
      $qResponse = $this->db->escape( serialize($fbRs) );
      $q = "insert into social_updates (ctime,network,post,response) values (now(), 'facebook', '$qPost', '$qResponse')";
      $this->db->query($q);
    }
    catch ( FacebookApiException $e )
    {
      $result->error = $e;
      Log::debug( "fbPost error: $result->error" );
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
      Log::debug( "fbPost error: $result->error" );
    }
    
    return $result;
  }

  public function createEvent( $title, $start, $end, $location, $description )
  {
    if ( !$this->authenticated || !$this->fb )
    {
      echo "not authenticated\n";
      return false;
    }


    // @fixme times must be EDT (Facebook time)
    // Privacy types: OPEN, CLOSED, SECRET
    $event_info = array(
      "privacy_type" => "OPEN",
      "name" => "Facebook/CMS koppeling Testje",
      "host" => "Me",
      "start_time" => $start,
      "end_time" => $end,
      "location" => $location,
      "description" => $description
    );

    return;
    
    // @todo add photo and invite support

    //Path to photo (only tested with relative path to same directory)
    // $file = "end300.jpg";
    // The key part - The path to the file with the CURL syntax
    // $event_info[basename($file)] = '@' . realpath($file);

    // $facebook->setFileUploadSupport(true);
    // $attachment = array( 'message' => $caption );    
    // $attachment[basename($file_path)] = '@' . realpath($file_path);
    // $result = $facebook->api('me/photos','post',$attachment);

    // print_r( $event_info );
    // var_dump($this->fb->api('me/events','post',$event_info));
    // var_dump($facebook->api("$pageId/events", 'post', $event_info));

    // $fb->api( array(
    //   'method' => 'events.invite',
    //   'eid' => $event_id,
    //   'uids' => $id_array,
    //   'personal_message' => $message,
    // ) );
  }

  public function storePerm( $perm )
  {
    $qPerm = $this->db->escape( $perm );
    $q = "insert into facebook_user_perms (facebook_user_id, perm_key, perm_value) values ($this->id, '$qPerm', 1)";
    $this->db->query($q);
  }

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

  public function hasPerm( $perm )
  {
    if ( !$this->fb || !$this->authenticated )
      return false;

    return $this->fb->api( array( 'method' => 'users.hasapppermission', 'ext_perm' => $perm ) );
  }
}

?>
