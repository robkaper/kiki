<?

include_once( $GLOBALS['kiki']. '/lib/twitteroauth/twitteroauth.php');

class User
{
  private $db;

  public $id, $ctime, $mtime;
  private $authToken;
  public $mailAuthToken;
  public $fbUser, $twUser;

  public function __construct( $id = null )
  {
    $this->db = $GLOBALS['db'];

    $this->reset();

    $this->fbUser = new FacebookUser();
    $this->twUser = new TwitterUser();
    
    if ( $id )
      $this->load( $id );
  }

  public function reset()
  {
    $this->id = 0;
    $this->ctime = time();
    $this->mtime = time();
    $this->authToken = "";
    $this->mailAuthToken = "";
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
    $q = "select id,facebook_user_id,twitter_user_id,mail_auth_token from users where id=$qId";
    $o = $this->db->getSingle($q);
    if ( !$o )
      return;

    $this->id = $o->id;
    $this->mailAuthToken = $o->mail_auth_token;
    $this->fbUser->load( $o->facebook_user_id );
    $this->twUser->load( $o->twitter_user_id );
  }

  // FIXME: create local sign-in and make it leading
  public function identify()
  {
    $this->fbUser->identify();
    $this->twUser->identify();
  }

  public function authenticate()
  {
    $this->fbUser->authenticate();
    $this->twUser->authenticate();

    $qFbUserId = $this->db->escape( $this->fbUser->id );
    $qTwUserId = $this->db->escape( $this->twUser->id );
    $q = "select id,facebook_user_id,twitter_user_id,mail_auth_token from users where facebook_user_id=$qFbUserId or twitter_user_id=$qTwUserId";
    $rs = $this->db->query($q);
    
    if ( $rs && $rows = $this->db->numrows($rs) )
    {
      if ( $rows!=1 )
      {
        // FIXME: avoid this at all costs, or handle gracefully
        Log::debug( "User::authenticate -- more than one result in db, fbUserId=$qFbUserId, twUserId=$qTwUserId" );
        while( $o = $this->db->fetchObject($rs) )
          Log::debug( "o: ". print_r( $o, true ) );
      }
      else
      {
        $o = $this->db->fetchObject($rs);
        $this->id = $o->id;
        $this->mailAuthToken = $o->mail_auth_token;

        if ( ($this->fbUser->id && !$o->facebook_user_id) || ($this->twUser->id && !$o->twitter_user_id) )
        {
          $q = "update users set mtime=now(), facebook_user_id=$qFbUserId, twitter_user_id=$qTwUserId where id = $o->id";
          $rs = $this->db->query($q);
        }
      }
    }
    else if ( $this->fbUser->id || $this->twUser->id )
    {
      $qFbUserId = $this->fbUser->id ? $this->db->escape( $this->fbUser->id ) : 'NULL';
      $qTwUserId = $this->twUser->id ? $this->db->escape( $this->twUser->id ) : 'NULL';
      $q = "insert into users(ctime, mtime, facebook_user_id, twitter_user_id) values (now(), now(), $qFbUserId, $qTwUserId)";
      $rs = $this->db->query($q);
    }
  }

  // Returns type, name and picture URL
  public function socialData( $type = null )
  {
    if ( (!$type || $type=='facebook') && $this->fbUser->id )
      return array( 'facebook', $this->fbUser->name, "http://graph.facebook.com/". $this->fbUser->id. "/picture" );
    else if ( (!$type || $type=='twitter') && $this->twUser->id )
      return array( 'twitter', $this->twUser->name, $this->twUser->picture );
    else if ( isset($_SERVER['HTTP_USER_AGENT']) && preg_match( "/^Googlebot/", $_SERVER['HTTP_USER_AGENT'] ) )
      return array( null, "Googlebot", null );
    else
      return array( null, null, null );
  }

  public static function anyUser()
  {
    $user = $GLOBALS['user'];
    return ($user->fbUser->authenticated || $user->twUser->authenticated);
  }
  
  public static function allUsers()
  {
    $user = $GLOBALS['user'];
    return ($user->fbUser->authenticated && $user->twUser->authenticated);
  }
}

?>