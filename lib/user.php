<?php

namespace Kiki;

include_once "baseobject.php";
include_once "auth.php";

class User extends BaseObject
{
  private $email = null;
  private $password = null;

  private $authToken;
  public $mailAuthToken;

  private $isVerified = false;
  private $isAdmin = false;

  private $name = null;

  private $connections = array();
  private $identifiedConnections = null;

/*
  public function __construct( $id = 0, $object_id = 0 )
  {
    parent::__construct( $id, $object_id );
    
    $this->reset();
    
    // $this->getStoredConnections();
  }
*/

  public function reset()
  {
    parent::reset();

    $this->email = null;
    $this->password = null;

    $this->authToken = null;
    $this->mailAuthToken = null;

    $this->isVerified = false;
    $this->isAdmin = false;

    $this->connections = array();
    $this->identifiedConnections = null;
  }

  public function setName( $name ) { $this->name = $name; }

  public function name()
  {
    if ( !count($this->connections) && empty($this->name) )
      return "User ". $this->id;
    
    if ( !empty($this->name) )
      return $this->name;

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

  public function email() { return $this->email; }

  public function getAuthToken() { return $this->authToken; }
  public function setIsAdmin( $isAdmin ) { $this->isAdmin = $isAdmin; }
  public function isAdmin() { return $this->isAdmin; }
  public function setIsVerified( $isVerified ) { $this->isVerified = $isVerified; }
  public function isVerified() { return $this->isVerified; }

  public function load( $id = 0 )
  {
    if ( $id )
    {
      $this->id = $id;
      $this->object_id = 0;
    }
    else if ( !$this->id && !$this->object_id )
      return;

    $fields = array( 'id', 'o.object_id', 'o.ctime', 'o.mtime', 'o.object_name', 'email', 'auth_token', 'mail_auth_token', 'verified', 'admin', 'name' );

    if ( $this->id )
      $q = $this->db->buildQuery( "SELECT %s FROM users u, objects o WHERE o.object_id=u.object_id AND u.id=%d", implode( ', ', $fields), $this->id );
    else
      $q = $this->db->buildQuery( "SELECT %s FROM users u, objects o WHERE o.object_id=u.object_id AND o.object_id=%d", implode( ', ', $fields), $this->object_id );
    $o = $this->db->getSingleObject($q);
    if ( !$o )
    {
      $this->reset();
      return;
    }
    
    $this->setFromObject( $o );

    // TODO: make sure this doesn't load remote data
    // TODO: add Cache support for connections, although no longer really urgent as User class itself can be stored in Cache
    $this->getStoredConnections();
  }

  public function loadByObjectName( $object_name )
  {
    $this->id = 0;
    $this->object_id = 0;

    $this->reset();

    $q = "SELECT u.id FROM `users` u, `objects` o WHERE o.object_id=u.object_id AND o.object_name = '%s'";
    $q = $this->db->buildQuery( $q, $object_name );
    $uid = $this->db->getSingleValue($q);

    if ( $uid )
      $this->load($uid);
  }

  public function setFromObject( $o )
  {
    parent::setFromObject($o);

    if ( !$o )
      return;

    $this->email = $o->email;
    $this->authToken = $o->auth_token;
    $this->mailAuthToken = $o->mail_auth_token;
    $this->isAdmin = $o->admin;
    $this->isVerified = $o->verified;
    
    $this->name = $o->name;
  }

  public function dbUpdate()
  {
    parent::dbUpdate();

    $q = $this->db->buildQuery(
      "UPDATE users SET object_id=%d, email='%s', mail_auth_token='%s', auth_token='%s', admin=%d, verified=%d,
        name='%s'
        WHERE id=%d",
      $this->object_id, $this->email, $this->mailAuthToken, $this->authToken, $this->isAdmin, $this->isVerified, $this->name, $this->id
    );

    $this->db->query($q);
  }
  
  public function dbInsert()
  {
    $q = $this->db->buildQuery(
      "INSERT INTO users(object_id, email, mail_auth_token, auth_token, admin, verified, name) VALUES (%d, '%s', '%s', '%s', %d, %d, '%s')",
      $this->object_id, $this->email, $this->mailAuthToken, $this->authToken, $this->isAdmin, $this->isVerified, $this->name
    );

    $rs = $this->db->query($q);
    if ( $rs )
      $this->id = $this->db->lastInsertId($rs);

    return $this->id;
  }

  public function getStoredConnections()
  {
    return false;
    $this->connections = array();

    $q = $this->db->buildQuery( "select external_id, service from connections where user_id=%d order by ctime asc", $this->id );
    $rs = $this->db->query($q);
    if ( $rs && $this->db->numRows($rs) )
      while( $o = $this->db->fetchObject($rs) )
      {
        $user = User\Factory::getInstance( $o->service, $o->external_id, $this->id );
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
      $user = User\Factory::getInstance($service);
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

          // Identified user

          // Re-link connection to ensure the latest data is used (especially access token)
          $user->loadRemoteData();
          $user->verifyToken();
          $user->link( $this->id );

          // Use identified connection, it is the latest
          $this->connections[$id] = $user;
        }
        else
        {
          Log::debug( "identified $id, store link" );

          // Identified user
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
          Auth::setCookie($this->id);

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
  public function storeNew( $email, $password, $admin = false, $verified = false, $passwordIsHash = false )
  {
    // TODO: implement verification of email

    // Save to get an ID to generate a temporary name
    $this->save();
    $this->name = 'User '. $this->id;
    $this->setObjectName( 'u'. $this->id );

    $this->email = $email;
    $this->password = $password;
    $this->authToken = $passwordIsHash ? $password : Auth::passwordHash( $password );
    $this->isAdmin = (int) $admin;
    $this->isVerified = (int) $verified;
    
    $this->save();
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

	public function getIdByEmail( $email )
	{
		$q = $this->db->buildQuery( "SELECT id FROM users WHERE email='%s' LIMIT 1", $email );
		return $this->db->getSingleValue($q);
	}

	public function getIdByLogin( $email, $password )
	{
		$q = $this->db->buildQuery( "SELECT id FROM users WHERE email='%s' AND auth_token='%s'", $email, Auth::passwordHash($password) );
		return $this->db->getSingleValue($q);
	}

	public function getIdByToken( $token )
	{
		$q = $this->db->buildQuery( "SELECT id FROM users WHERE auth_token='%s' LIMIT 1", $token );
		return $this->db->getSingleValue($q);
	}

	public function templateData()
	{
		return array( 
      'id' => $this->id,
      'admin' => $this->isAdmin,
      'activeConnections' => array(),
      'inactiveConnections' => array(),
      'emailUploadAddress' => $this->emailUploadAddress()
    );
	}
}
