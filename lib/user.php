<?

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
    {
      $this->id = $id;
      $this->objectId = 0;
    }

    // FIXME: provide an upgrade path removing ctime/atime from table, use objects table only, same for saving
    // TODO: todo email
    $q = $this->db->buildQuery( "SELECT id, u.object_id, u.ctime, u.mtime, email, auth_token, mail_auth_token, admin FROM users u LEFT JOIN objects o ON o.object_id=u.object_id WHERE u.id=%d OR u.object_id=%d", $this->id, $this->objectId );
    $o = $this->db->getSingle($q);
    if ( !$o )
      return;
    
    $this->setFromObject( $o );

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
      "UPDATE users SET object_id=%d, ctime='%s', mtime=now(), email='%s', mail_auth_token='%s', auth_token='%s', admin=%d where id=%d",
      $this->objectId, $this->ctime, $this->email, $this->mailAuthToken, $this->authToken, $this->isAdmin, $this->id
    );

    $this->db->query($q);
  }
  
  public function dbInsert()
  {
    $q = $this->db->buildQuery(
      "INSERT INTO users(object_id, ctime, mtime, email, mail_auth_token, auth_token, admin) values (%d, now(), now(), '%s', '%s', '%s', %d)",
      $this->objectId, $this->email, $this->mailAuthToken, $this->authToken, $this->isAdmin
    );
    
    $rs = $this->db->query($q);
    if ( $rs )
      $this->id = $this->db->lastInsertId($rs);

    return $this->id;
  }

  public function getStoredConnections()
  {
    $connections = array();

    $q = $this->db->buildQuery( "select external_id, service from connections where user_id=%d order by ctime asc", $this->id );
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
      if ( !$user || !$user->externalId() )
        continue;

      if ( !$user->token() )
      {
        Log::debug( "identified $service but no token, don't actually add to identified" );
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
      // Log::debug( "cookie: ". $this->id );
    }

    $this->identifyConnections();

    if ( $this->id )
    {
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
          $user->verifyToken();
          $user->link( $this->id );

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
          $this->load();
          Log::debug( "deducted user ". $this->id. " recalling identify to check for unstored connections" );
          Auth::setCookie($this->id);
          $this->identify();
          return;
          break;

        default:
          Log::debug( "cannot detect user, multiple candidates" );
          Log::debug( print_r( $this->identifiedConnections, true ) );
          // cannot detect user, multiple candidates
      }
    }
    else
    {
      // Log::debug( "no user, no connections" );
    }

    // Log::debug( "id: ". $this->id );
  }

  // WARNING: deprecated
  public function authenticate()
  {
    $this->identify();
  }

  // TODO: deprecate, or refactor
  public function storeNew( $email, $password, $admin = false, $verified = false )
  {
    // TODO: implement verification of email

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
  
  public function emailUploadAddress( $target = null )
  {
    if ( Config::$mailToSocialAddress )
    {
      if ( !$this->mailAuthToken )
      {
        // Not the most secure hash, but it doesn't matter because it
        // doesn't lead to a password.
        $this->mailAuthToken = sha1( uniqid(). $this->id );
        $this->db->query( "update users set mail_auth_token='$this->mailAuthToken' where id=". $this->id );
      } 

      list( $localPart, $domain ) = explode( "@", Config::$mailToSocialAddress );
      $targetPart = $target ? "+$target" : null;
      return $localPart. "+". $this->mailAuthToken. $targetPart. "@". $domain;
    }
    return false;
  }
}

?>