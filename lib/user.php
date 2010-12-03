<?

include_once( $GLOBALS['kiki']. '/lib/twitteroauth/twitteroauth.php');

class User
{
  private $db;

  public $id, $ctime, $mtime;
  private $authToken;
  public $fbUser, $twUser;

  public function __construct( $id = null )
  {
    $this->db = $GLOBALS['db'];
    $this->reset();
    
    if ( $id )
      $this->load( $id );
  }

  public function reset()
  {
    $this->id = 0;
    $this->ctime = time();
    $this->mtime = time();
    $this->authToken = "";
    $this->fbUser = null;
    $this->twUser = null;
  }

  public function isAdmin()
  {
    return in_array( $this->id, Config::$devUsers );
  }

  public function load( $id )
  {
    $qId = $this->db->escape( $id );
    $q = "select id,facebook_user_id,twitter_user_id from users where id=$qId";
    $o = $this->db->getSingle($q);
    if ( !$o )
      return;

    $this->id = $o->id;
    $this->fbLoad( $o->facebook_user_id );
    $this->twLoad( $o->twitter_user_id );
  }

  private function fbLoad( $id )
  {
    $qId = $this->db->escape( $id );
    $q = "select name from facebook_users where id='$qId'";
    $o = $this->db->getSingle($q);
    if ( !$o )
      return;

    $this->fbUser = new stdClass;
    $this->fbUser->id = $id;
    $this->fbUser->name = $o->name;
  }

  private function twLoad( $id )
  {
    $qId = $this->db->escape( $id );
    $q = "select access_token, secret, name, screen_name, picture from twitter_users where id='$qId' and secret is not null";
    $rs = $this->db->query($q);
    $o = $this->db->getSingle($q);
    if ( !$o )
      return;

    $this->twUser = new stdClass;
    $this->twUser->id = $id;
    $this->twUser->accessToken = $o->access_token;
    $this->twUser->secret = $o->secret;
    $this->twUser->name = $o->name;
    $this->twUser->screenName = $o->screen_name;
    $this->twUser->picture = $o->picture;
    $this->twUser->verified = null;
  }
  
  public function authenticate()
  {
    $this->fbAuthenticate();
    $this->twAuthenticate();

    $qFbUserId = $this->fbUser ? $this->db->escape( $this->fbUser->id ) : 0;
    $qTwUserId = $this->twUser ? $this->db->escape( $this->twUser->id ) : 0;
    $q = "select id,facebook_user_id,twitter_user_id from users where facebook_user_id=$qFbUserId or twitter_user_id=$qTwUserId";
    $rs = $this->db->query($q);
    
    if ( $rs && $rows = $this->db->numrows($rs) )
    {
      if ( $rows!=1 )
      {
        // FIXME: avoid this at all costs, or handle gracefully
        Log::debug( "User::authenticate -- more than one result in db, fbUserId=$qFbUserId, twUserId=$qTwUserId" );
        while( $o = $this->db->fetch_object($rs) )
          Log::debug( "o: ". print_r( $o, true ) );
      }
      else
      {
        $o = $this->db->fetch_object($rs);
        $this->id = $o->id;

        if ( ($this->fbUser && !$o->facebook_user_id) || ($this->twUser && !$o->twitter_user_id) )
        {
          $q = "update users set mtime=now(), facebook_user_id=$qFbUserId, twitter_user_id=$qTwUserId where id = $o->id";
          Log::debug( $q );
          $rs = $this->db->query($q);
        }
      }
    }
    else if ( $this->fbUser || $this->twUser )
    {
      $qFbUserId = $this->fbUser ? $this->db->escape( $this->fbUser->id ) : 'NULL';
      $qTwUserId = $this->twUser ? $this->db->escape( $this->twUser->id ) : 'NULL';
      $q = "insert into users(ctime, mtime, facebook_user_id, twitter_user_id) values (now(), now(), $qFbUserId, $qTwUserId)";
      Log::debug( $q );
      $rs = $this->db->query($q);
    }
  }

  public function fbAuthenticate()
  {
    $fbUserId = 0;

    global $fb, $fbSession;

    $fb = new Facebook( array(
      'appId'  => Config::$facebookApp,
      'secret' => Config::$facebookSecret,
      'cookie' => true
     ) );

    $fbSession = $fb->getSession();
    if ( $fbSession )
    {
      try {
        $fbUserId = $fb->getUser();

        if ( array_key_exists( 'session', $_GET ) )
        {
          $this->fbRegisterAuth( &$fb );

          // Redirect to avoid ?session= request to appear in Analytics etc
          header( "Location: ". $_SERVER['SCRIPT_URL'], true, 301 );
          exit();
        }
      } catch ( FacebookApiException $e ) {
        error_log($e);
      }
    }

    if ( !$fbUserId )
      return;

    $this->fbLoad( $fbUserId );
  }

  // FIXME: Only use verify when post permissions are required (make a second call when needed). We ordinarily ignore verify_credentials because the request takes a painful ~800ms, check if @Anywhere with json-update callback could fix this later in the event queue
  public function twAuthenticate( $verify = false )
  {

    if ( !$this->twUser )
    {

      $twUserId = $this->twCookie();
      if ( !$twUserId )
        return;
      $this->twLoad( $twUserId );
      if ( !$this->twUser )
        return;
    }

    global $tw;
    $tw = $this->twUser->accessToken ? new TwitterOAuth(Config::$twitterApp, Config::$twitterSecret, $this->twUser->accessToken, $this->twUser->secret) : null;
    if ( !$tw )
      return;

    if ( !$verify )
      return;

    $this->twUser->verified = false;

    $twApiUser = $tw ? $tw->get('account/verify_credentials') : null;
    if ( $twApiUser )
    {
      if (isset($twApiUser->error) )
      {
         Log::error( "twAuthenticate error: $twApiUser->error, twUserId: $twUserId" );
         return;
      }
      // FIXME: inspect twApiUser to check if user is verified
      $this->twUser->verified = true;
    }
  }

  // Returns a Twitter user ID, or 0 if not valid or no cookie present
  private function twCookie()
  {
    if ( array_key_exists( 'twitter_anywhere_identity', $_COOKIE ) )
    {
      list( $twUserId, $twSig ) = split( ":", $_COOKIE['twitter_anywhere_identity'] );
      $hexDigest = sha1($twUserId. Config::$twitterSecret);
      $valid = ($twSig == $hexDigest);
      return $valid ? $twUserId : 0;
    }
    return 0;
  }

  // Returns entire verified cookie as array, or null if not valid or no cookie present
  // Should not be necessary, Facebook API does it all, but kept for reference
  private function fbCookie()
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

  private function fbRegisterAuth( &$fb )
  {
    Log::debug( "fbRegisterAuth" );
    $fbUser = $fb->api('/me');
    Log::debug( 'fb /me' );
    $qId = $fbUser ? $this->db->escape( $fbUser['id'] ) : 0;
    $qName = $fbUser ? $this->db->escape( $fbUser['name'] ) : "";
    $q = "insert into facebook_users (id,name) values( $qId, '$qName') on duplicate key update name='$qName'";
    Log::debug( "fbRegisterAuth q: $q" );
    $this->db->query($q);
  }

  private function twRegisterAuth()
  {
  }

  // Returns type, name and picture URL
  public function socialData( $type = null )
  {
    if ( (!$type || $type=='facebook') && $this->fbUser )
      return array( 'facebook', $this->fbUser->name, "http://graph.facebook.com/". $this->fbUser->id. "/picture" );
    else if ( (!$type || $type=='twitter') && $this->twUser )
      return array( 'twitter', $this->twUser->name, $this->twUser->picture );
    else if ( isset($_SERVER['HTTP_USER_AGENT']) && preg_match( "/^Googlebot/", $_SERVER['HTTP_USER_AGENT'] ) )
      return array( null, "Googlebot", null );
    else
      return array( null, null, null );
  }
}

?>