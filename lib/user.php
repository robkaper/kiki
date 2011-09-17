<?

include_once( $GLOBALS['kiki']. '/lib/twitteroauth/twitteroauth.php');

class User
{
  private $db;

  public $id, $ctime, $mtime;
  private $authToken;
  public $mailAuthToken;
  private $isAdmin;

  private $connections = array();
  private $identifiedConnections = null;

  public function __construct( $id = null )
  {
    $this->db = $GLOBALS['db'];

    $this->reset();

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
  }

  public function id()
  {
    return $this->id;
  }

  public function name()
  {
    if ( isset($this->connections[0]) )
      return $this->connections[0]->name();
    else
      return "User ". $this->id;
  }

  public function picture()
  {
    if ( isset($this->connections[0]) )
      return $this->connections[0]->picture();
    else
      return null;
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

    // @todo make sure this doesn't load remote data
    $this->getStoredConnections();
  }

  public function getStoredConnections()
  {
    $connections = array();

    $q = $this->db->buildQuery( "select external_id, service from users_connections where user_id=%d order by ctime asc", $this->id );
    $rs = $this->db->query($q);
    if ( $rs && $this->db->numRows($rs) )
      while( $o = $this->db->fetchObject($rs) )
      {
        $user = Factory_User::getInstance( $o->service, $o->external_id, $this->id );
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
    foreach( Config::$connectionServices as $service )
    {
      $user = Factory_User::getInstance( 'User_'. $service );
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
      // Log::debug( "connections (stored): ". print_r($this->connections, true) );
      foreach( $this->identifiedConnections as $id => $user )
      {
        if ( isset($this->connections[$id]) )
        {
          Log::debug( "identified $id, already linked in store" );
          // @todo compare connections and only load remote data if old is missing
          // $storedConnection = $this->connections[$id];

          // Identified user, no need to connect before link?
          $user->loadRemoteData();

          // Re-link connection to ensure the latest data is used (especially access token)
          // @todo compare connections and only relink when token changed or remote data was loaded
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
    // Log::debug( "identifiedConnections: ". print_r($this->identifiedConnections, true) );
    // Log::debug( "connections: ". print_r($this->connections, true) );

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

  public function anyUser()
  {
    return count($this->connections);
  }

  public function connections()
  {
    return $this->connections;
  }

  public function connectionIds()
  {
    $ids = array();
    foreach( $this->connections as $connection )
      $ids[] = $connection->uniqId();
    return $ids;
  }

  public function getConnection( $id )
  {
    return isset($this->connections[$id]) ? $this->connections[$id] : null;
  }
}

?>