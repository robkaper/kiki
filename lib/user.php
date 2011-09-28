<?

include_once( $GLOBALS['kiki']. '/lib/twitteroauth/twitteroauth.php');

class User extends Object
{
  private $email = null;
  private $password = null;

  private $authToken;
  public $mailAuthToken;
  private $isAdmin;

  private $connections = array();
  private $identifiedConnections = null;

  public function reset()
  {
    parent::reset();

    $this->authToken = "";
    $this->mailAuthToken = "";
    $this->isAdmin = false;
  }

  public function name()
  {
    if ( !count($this->connections) )
      return "User ". $this->id;

    reset($this->connections);
    return $this->connections[key($this->connections)]->name();
  }

  public function picture()
  {
    if ( !count($this->connections) )
      return null;

    reset($this->connections);
    return $this->connections[key($this->connections)]->picture();
  }

  public function serviceName()
  {
    if ( !count($this->connections) )
      return null;

    reset($this->connections);
    return $this->connections[key($this->connections)]->serviceName();
  }

  public function isAdmin()
  {
    return $this->isAdmin;
  }

  public function load( $id = 0 )
  {
    if ( $id )
      $this->id = $id;

    // FIXME: provide an upgrade path removing ctime/atime from table, use objects table only, same for saving
    // TODO: todo email
    $q = $this->db->buildQuery( "select id, o.object_id, u.ctime, u.mtime, email, auth_token, mail_auth_token, admin from users u LEFT JOIN objects o on o.object_id=u.object_id where id=%d or o.object_id=%d", $this->id, $this->objectId );
    $this->setFromObject( $this->db->getSingle($q) );

    // TODO: make sure this doesn't load remote data
    // TODO: add Cache support for connections, although no longer really urgent as User class itself can be stored in Cache
    $this->getStoredConnections();
  }

  public function setFromObject( &$o )
  {
    parent::setFromObject($o);

    if ( !$o )
      return;

    $this->email = $o->email;
    $this->authToken = $o->auth_token;
    $this->mailAuthToken = $o->mail_auth_token;
    $this->isAdmin = $o->admin;
  }

  public function dbUpdate()
  {
    parent::dbUpdate();

    $q = $this->db->buildQuery(
      "UPDATE users SET object_id=%d, ctime='%s', mtime=now(), email='%s', auth_token='%s', admin=%d where id=%d",
      $this->objectId, $this->ctime, $this->email, $this->authToken, $this->isAdmin, $this->id
    );

    $this->db->query($q);
  }
  
  public function dbInsert()
  {
    $q = $this->db->buildQuery(
      "INSERT INTO users(object_id, ctime, mtime, email, auth_token, admin) values (%d, now(), now(), '%s', '%s', %d)",
      $this->objectId, $this->email, $this->authToken, $this->isAdmin
    );
    
    $rs = $this->db->query($q);
    if ( $rs )
      $this->id = $this->db->lastInsertId($rs);

    return $this->id;
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
        $this->connections[$user->uniqId()] = $user;
      }
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
      $this->getStoredConnections();
      // Log::debug( "connections (stored): ". print_r($this->connections, true) );
      foreach( $this->identifiedConnections as $id => $user )
      {
        if ( isset($this->connections[$id]) )
        {
          Log::debug( "identified $id, already linked in store" );
          // TODO: compare connections and only load remote data if old is missing
          // $storedConnection = $this->connections[$id];

          // Identified user, no need to connect before link?
          $user->loadRemoteData();

          // Re-link connection to ensure the latest data is used (especially access token)
          // TODO: compare connections and only relink when token changed or remote data was loaded
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

    $this->load();
  }

  // WARNING: deprecated
  public function authenticate()
  {
    $this->identify();
  }

  // TODO: deprecate, or refactor
  public function storeNew( $email, $password, $admin = false )
  {
    $this->email = $email;
    $this->password = $password;
    $this->authToken = Auth::passwordHash( $password );
    $this->admin = (int) $admin;
    
    $this->save();

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

  public function url()
  {
    // FIXME: made-up, implement the actual URL, preferably using a
    // conroller with configurable base URI and not hardcoded inside Kiki htdocs.
    return Config::$kikiPrefix. "/users/". $this->id;
  }
}

?>