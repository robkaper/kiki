<?

include_once( $GLOBALS['kiki']. '/lib/twitteroauth/twitteroauth.php');

class User
{
  private $db;

  public $id, $ctime, $mtime;
  private $authToken;
  public $mailAuthToken;

  public $linkedAccounts;

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
    $q = $this->db->buildQuery( "select id,facebook_user_id,twitter_user_id,mail_auth_token from users where id=%d", $id );
    $o = $this->db->getSingle($q);
    if ( !$o )
      return;

    $this->id = $o->id;
    $this->mailAuthToken = $o->mail_auth_token;

    $this->fbUser->load( $o->facebook_user_id );
    $this->twUser->load( $o->twitter_user_id );
  }

  public function identify()
  {
    // @todo restore logical separate steps: 1 identify, 2 authenticate, 3 register/update if necessary

    // Identify Kiki IDs
    // Identify third party IDs

    // Kiki 1, 3rd 0: user logged in, show connect options, verify stored 3rd party tokens
    // Kiki 0, 3rd 1: register user and log it in, result is Kiki also 1
    // Kiki 1, 3rd 1: user logged in, may have connected, check added perms/auth for 3rd parties
    // Kiki 0, 3rd 0: nada, show login options
  }

  public function authenticate()
  {
    // Check Kiki's own cookie first, it's more authoritive than third parties.
    if ( $userId = Auth::validateCookie() )
    {
      Log::debug( "User::authenticate valid cookie for $userId, user authenticated" );
      $this->load($userId);
      // @todo Update cookie to prevent it from expiring for regular users.

      // @warning check facebook permissions
      if ( $this->fbUser->id )
      {
        Log::debug( "authenticating facebook user..." );
        $this->fbUser->authenticate();
      }
      if ( $this->twUser->id )
      {
        Log::debug( "authenticating twitter user..." );
        $this->twUser->authenticate();
      }

      return;
    }
    else
      Log::debug( "User::authenticate no (valid) cookie" );

    if ( $this->fbUser->identify() )
      $this->fbUser->authenticate();

    if ( $this->twUser->identify() )
      $this->twUser->authenticate();
      
    // Start third-party authentication.
    // $this->fbUser->authenticate();
    // $this->twUser->authenticate();

    if ( !($this->fbUser->id || $this->twUser->id) )
    {
      Log::debug( "no identification/authentication of third-party users" );
      return;
    }

    $qFbUserId = $this->db->escape( $this->fbUser->id );
    $qTwUserId = $this->db->escape( $this->twUser->id );
    $q = "select id,facebook_user_id,twitter_user_id,mail_auth_token from users where facebook_user_id=$qFbUserId or twitter_user_id=$qTwUserId";
    $rs = $this->db->query($q);
    if ( $rs && $rows = $this->db->numrows($rs) )
    {
      if ( $rows!=1 )
      {
        // FIXME: avoid this at all costs, or handle gracefully
        Log::debug( "User::authenticate -- more than one result in db for q [$q], fbUserId=$qFbUserId, twUserId=$qTwUserId" );
        while( $o = $this->db->fetchObject($rs) )
          Log::debug( "o: ". print_r( $o, true ) );
      }
      else
      {
        $o = $this->db->fetchObject($rs);
        $this->id = $o->id;
        $this->mailAuthToken = $o->mail_auth_token;

        Log::debug( "user $o->id authenticated by third party by q [$q]" );
        Auth::setCookie($o->id);

        if ( ($this->fbUser->id && !$o->facebook_user_id) || ($this->twUser->id && !$o->twitter_user_id) )
        {
          $q = "update users set mtime=now(), facebook_user_id=$qFbUserId, twitter_user_id=$qTwUserId where id = $o->id";
          $rs = $this->db->query($q);
          Log::debug( "updated 3rd party connections [$q]" );
        }
        else
          Log::debug( "no need to update" );
      }
    }
    else if ( $this->fbUser->id || $this->twUser->id )
    {
      // User is created based on third-party login, authtoken is therefore
      // a dummy because user has not set a password yet.
      $qAuthToken = Auth::hashPassword( uniqid() );

      $qFbUserId = $this->fbUser->id ? $this->db->escape( $this->fbUser->id ) : 'NULL';
      $qTwUserId = $this->twUser->id ? $this->db->escape( $this->twUser->id ) : 'NULL';
      $q = "insert into users(ctime, mtime, auth_token, facebook_user_id, twitter_user_id) values (now(), now(), $qAuthToken, $qFbUserId, $qTwUserId)";
      $rs = $this->db->query($q);

      $userId = $this->db->lastInsertId($rs);
      Log::debug( "user $userId created by third party, fbUserId=$qFbUserId, twUserId=$qTwUserId" );
      Auth::setCookie($userId);
    }
    else
      Log::debug( "no user found for third party query [$q]" );
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