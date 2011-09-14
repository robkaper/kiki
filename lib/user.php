<?

include_once( $GLOBALS['kiki']. '/lib/twitteroauth/twitteroauth.php');

class User
{
  private $db;

  public $id, $ctime, $mtime;
  private $authToken;
  public $mailAuthToken;
  private $isAdmin;

  private $connections = null;
  private $identifiedConnections = null;

  public $fbUser, $twUser;
  private $connectServices;

  public function __construct( $id = null )
  {
    $this->db = $GLOBALS['db'];

    $this->reset();

    $this->fbUser = new FacebookUser();
    $this->twUser = new TwitterUser();

    // @fixme Move these configurations to the database, to be filled in by
    // an administrator account.
    if ( Config::$facebookApp )
      $this->connectServices[] = 'User_Facebook';
    if ( Config::$twitterApp )
      $this->connectServices[] = 'User_Twitter';

    $this->load( $id );
  }

  public function reset()
  {
    $this->id = 0;
    $this->ctime = time();
    $this->mtime = time();
    $this->authToken = "";
    $this->mailAuthToken = "";
    $this->isAdmin = false;
    $this->fbUser = null;
    $this->twUser = null;
  }

  public function isAdmin()
  {
    return $this->isAdmin;
  }

  public function load( $id = 0 )
  {
    if ( !$id )
      return;

    $q = $this->db->buildQuery( "select id,mail_auth_token,admin from users where id=%d", $id );
    $o = $this->db->getSingle($q);
    if ( !$o )
    {
      $this->id = 0;
      return;
    }

    $this->id = $o->id;
    $this->mailAuthToken = $o->mail_auth_token;
    $this->isAdmin = $o->admin;
  }

  public function getStoredConnections()
  {
    $connections = array();

    $q = $this->db->buildQuery( "select external_id, service from users_connections where user_id=%d", $this->id );
    $rs = $this->db->query($q);
    if ( $rs && $this->db->numRows($rs) )
      while( $o = $this->db->fetchObject($rs) )
      {
        $user = UserFactory::getInstance( $o->service, $o->external_id, $this->id );
        $connections[$user->uniqId()] = $user;
      }

    return $connections;
  }

  public function identifyConnections()
  {
    if ( $this->identifiedConnections )
    {
      Log::debug( "not reidentifying, already did it.." );
      return;
    }

    // Identify third party users
    $this->identifiedConnections = array();
    foreach( $this->connectServices as $service )
    {
      Log::debug( "starting identification for service $service" );
      $user = UserFactory::getInstance($service);
      if ( !$user || !$user->id() )
        continue;

      if ( !$user->token() )
      {
        Log::debug( "identified but no token, don't actually add to identified" );
        continue;
      }

      $this->identifiedConnections[$user->uniqId()] = $user;
    }
  }

  public function identify()
  {
    if ( !$this->id )
    {
      $this->id = Auth::validateCookie();
      $this->load($this->id);
      Log::debug( "cookie: ". $this->id );
    }

    $this->identifyConnections();

    if ( $this->id )
    {
      $this->connections = $this->getStoredConnections();
      Log::debug( "connections (stored): ". print_r($this->connections, true) );
      foreach( $this->identifiedConnections as $id => $user )
      {
        if ( isset($this->connections[$id]) )
        {
          Log::debug( "identified $id, already linked in store" );

          // Identified user, no need to connect before link?
          $user->loadRemoteData();

          // Re-link connection to ensure the latest data is used (especially access token)
          $user->unlink( $this->id );
          $user->link($this->id );

          // Use identified connection, it is the latest
          $this->connections[$id] = $user;
        }
        else
        {
          Log::debug( "identified $id, store link" );

          // Identified user, no need to connect before link?
          $user->loadRemoteData();

          $user->link($this->id);
          $this->connections[$id] = $user;
        }
      }
    }
    else if ( count($this->identifiedConnections) )
    {
      $possibleUsers = array();
      foreach( $this->identifiedConnections as $id => $user )
        $possibleUsers = array_merge( $possibleUsers, $user->kikiUserIds() );

      $possibleUsers = array_unique($possibleUsers);
      $n = count($possibleUsers);
      switch( $n )
      {
        case 0:
          Log::debug( "register new user for found connections" );

          // Register new user. Use random password, user must change it
          // (and set email) before he/she can login with just a local ID.
          $this->storeNew( uniqid(), uniqid() );

          // Link the connection
          $user->loadRemoteData();
          $user->link($this->id);
          $this->connections[] = $user;
          break;

        case 1:
          // deducted user, rerun self so unknown connections can be stored
          $this->id = $possibleUsers[0];
          Log::debug( "deducted user ". $this->id. " recalling identify to check for unstored connections" );
          Auth::setCookie($this->id);
          $this->identify();
          return;
          break;

        default:
          Log::debug( "cannot detect user, multiple candidates" );
          // cannot detect user, multiple candidates
      }
    }
    else
    {
      Log::debug( "no user, no connections" );
    }

    Log::debug( "id: ". $this->id );
    Log::debug( "identifiedConnections: ". print_r($this->identifiedConnections, true) );
    Log::debug( "connections: ". print_r($this->connections, true) );

    $this->load( $this->id );
  }

  // @deprecated
  public function authenticate()
  {
    $this->identify();
  }

  public function storeNew( $email, $password, $admin = false )
  {
    $qEmail = $this->db->escape( $email );
    $qAuthToken = Auth::passwordHash($password);
    $qAdmin = $this->admin = (int) $admin;
    $q = "insert into users(ctime, mtime, email, auth_token, admin) values (now(), now(), '$qEmail', '$qAuthToken', $qAdmin)";
    $rs = $this->db->query($q);

    $this->id = $this->db->lastInsertId($rs);
    Auth::setCookie($this->id);
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

  public function anyUser()
  {
    return count($this->connections);
  }
}

?>