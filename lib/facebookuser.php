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
      if ( $cookie )
        $this->id = $cookie['uid'];
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
    $q = "insert into facebook_users (id,access_token,name) values( $qId, '$qAccessToken', '$qName') on duplicate key update access_token='$qAccessToken', name='$qName'";
    Log::debug( "FacebookUser->registerAuth q: $q" );
    $this->db->query($q);
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
      'picture' => $picture
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
      list( $uid, $postId ) = split( "_", $fbRs['id']);
      $result->url = "http://www.facebook.com/$uid/posts/$postId";
    }
    else
    {
      $result->error = $fbRs;
      Log::debug( "fbPost error: $result->error" );
    }
    
    return $result;
  }

  public function revoke( $perm )
  {
    if ( !$this->fb )
      return;

    // Tell Facebook to revoke permission
    $fbRs = $this->fb->api( array( 'method' => 'auth.revokeExtendedPermission', 'perm' => $perm ) );

    // Remove permission from database
    // TODO: insert permission when it is granted
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
    if ( !$this->fb )
      return false;

    return $this->fb->api( array( 'method' => 'users.hasapppermission', 'ext_perm' => $perm ) );
  }

}

?>
