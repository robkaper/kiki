<?php

/**
 * Abstract class to deal with users from external sources.
 *
 * This class has to be extended to specify each external platform's
 * specific identification, authentication and communication services. 
 * Local functionality such as storage is standardised and the abstract
 * methods ensure a unified local interface against external users.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

namespace Kiki\User;

use Kiki\Log;

class Exception extends \Exception {}
 
abstract class External
{
  protected $db;

  protected $client = null;

  protected $token = null;
  protected $secret = null;
  
  protected $id = 0;
  protected $externalId = 0;
  protected $kikiUserIds = array();

  protected $email = null;
  protected $name = null;
  protected $screenName = null;
  protected $picture = null;

  protected $connected = false;
  protected $authenticated = false;

  public function __construct( $id=0, $kikiUserId = 0 )
  {
    $this->db = \Kiki\Core::getDb();	

    if ( $this->externalId = $id )
    {
      $this->load($kikiUserId);
      if ( !$kikiUserId )
        $this->loadKikiUserIds();
    }
    else
    {
      $this->authenticate();
      $this->load();
      $this->loadKikiUserIds();
    }
  }

  abstract protected function connect();
  abstract public function authenticate();
  abstract protected function cookie();
  abstract protected function detectLoginSession();
  abstract public function verifyToken();

  // abstract public function getLoginUrl();
  public function client()
  {
    if ( !$this->client )
      $this->connect();

    if ( !$this->client )
    {
      throw new Exception( 'Client API for external user service '. $this->serviceName(). ' called but not available' );
    }

    return $this->client;
  }

  public function id()
  {
    return $this->id;
  }

  public function externalId()
  {
    return $this->externalId;
  }
  
  public function serviceName()
  {
    $parts = explode( "\\", get_class($this) );
    return end( $parts );
  }
  
  public function uniqId()
  {
    return get_class($this). '_'. $this->externalId;
  }

  public function setName( $name ) { $this->name = $name; }

  public function name()
  {
    return $this->name;
  }

  public function setEmail( $email ) { $this->email = $email; }

  public function email()
  {
    return $this->email;
  }

  public function setScreenName( $screenName ) { $this->screenName = $screenName; }

  public function screenName()
  {
    return $this->screenName;
  }

  public function setPicture( $picture ) { $this->picture = $picture; }

  public function picture()
  {
    return $this->picture;
  }

  public function setToken( $token ) { $this->token = $token; }

  public function token()
  {
    return $this->token;
  }

  private function loadKikiUserIds()
  {
    $this->kikiUserIds = array();
    
    $class = get_class($this);

    $q = $this->db->buildQuery( "SELECT user_id FROM user_connections WHERE service='%s' AND external_id='%s'", $class, $this->externalId );
    $rs = $this->db->query($q);
    if ( $rs && $this->db->numrows($rs) )
      while( $o = $this->db->fetchObject($rs) )
      {
        if ( $o->user_id )
          $this->kikiUserIds[] = $o->user_id;
      }

   // $this->kikiUserIds = array_unique( $this->kikiUserIds );
  }

  public function kikiUserIds()
  {
    return $this->kikiUserIds;
  }

  public function kikiUserId()
  {
    $n = count($this->kikiUserIds);
    switch($n)
    {
      case 0:
        return 0;
      case 1:
        return $this->kikiUserIds[0];
      default:
        return false;
    }
  }

  private function load( $kikiUserId = 0 )
  {
    $class = get_class($this);

    if ( $kikiUserId )
      $q = $this->db->buildQuery( "SELECT id, token, secret, email, name, screen_name, picture FROM user_connections WHERE service='%s' AND external_id='%s' AND user_id=%d", $class, $this->externalId, $kikiUserId );
    else
      $q = $this->db->buildQuery( "SELECT id, token, secret, email, name, screen_name, picture FROM user_connections WHERE service='%s' AND external_id='%s'", $class, $this->externalId );
    
    $o = $this->db->getSingleObject($q);
    if ( !$o )
      return;

    $this->kikiUserIds[] = $kikiUserId;

    $this->id = $o->id;
    
    if ( !$this->token )
      $this->token = $o->token;

    if ( !$this->secret )
      $this->secret = $o->secret;

    $this->email = $o->email;
    $this->name = $o->name;
    $this->screenName = $o->screen_name;
    $this->picture = $o->picture;
  }

  public function link( $kikiUserId = null )
  {
    if ( $kikiUserId === null )
    {
      $kikiUserId = $this->kikiUserId();
    }
    else
    {
      $this->kikiUserIds[] = $kikiUserId;
    }

    if ( $this->id )
    {
      $q = $this->db->buildQuery(
        "UPDATE user_connections SET user_id=%d, external_id='%s', service='%s', mtime=now(), token='%s', secret='%s', email='%s', name='%s', screen_name='%s', picture='%s' WHERE id=%d",
        $kikiUserId, $this->externalId, get_class($this), $this->token, $this->secret, $this->email, $this->name, $this->screenName, $this->picture, $this->id
      );
      
      $this->db->query($q);
    }
    else
    {
      $q = $this->db->buildQuery(
        "INSERT INTO user_connections (user_id, external_id, service, ctime, mtime, token, secret, email, name, screen_name, picture)
          VALUES (%d, '%s', '%s', now(), now(), '%s', '%s', '%s', '%s', '%s', '%s')
        ",
        $kikiUserId, $this->externalId, get_class($this), $this->token, $this->secret, $this->email, $this->name, $this->screenName, $this->picture
      );

      $rs = $this->db->query($q);
      if ( $rs )
        $this->id = $this->db->lastInsertId($rs);
    }
  }
}
