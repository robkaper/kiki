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

  protected $api = null;

  protected $token = null;
  protected $secret = null;
  
  protected $id = 0;
  protected $externalId = 0;
  protected $kikiUserIds = array();

  protected $name = null;
  protected $screenName = null;
  protected $picture = null;

  protected $subAccounts = null;
	protected $permissions = null;

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
      $this->identify();
      $this->load();
      $this->loadKikiUserIds();
    }
  }

  abstract protected function connect();
  abstract public function identify();
  abstract public function authenticate();
  abstract protected function cookie();
  abstract protected function detectLoginSession();
  abstract public function verifyToken();

  abstract public function getSubAccounts();
	abstract public function getPermissions();

  // abstract public function getLoginUrl();
  abstract protected function post( $objectId, $msg, $link='', $name='', $caption='', $description = '', $picture = '' );
  abstract protected function postArticle( &$article );
  abstract protected function createEvent( $objectId, $title, $start, $end, $location, $description, $picture=null );

  public function api()
  {
    if ( !$this->api )
      $this->connect();

    if ( !$this->api )
    {
      throw new Exception( 'External user API for '. $this->serviceName(). ' called but not available' );
    }

    return $this->api;
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

    $q = $this->db->buildQuery( "SELECT user_id FROM connections WHERE service='%s' AND external_id='%s'", $class, $this->externalId );
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
      $q = $this->db->buildQuery( "SELECT id, token, secret, name, screenname, picture FROM connections WHERE service='%s' AND external_id=%d AND user_id=%d", $class, $this->externalId, $kikiUserId );
    else
      $q = $this->db->buildQuery( "SELECT id, token, secret, name, screenname, picture FROM connections WHERE service='%s' AND external_id=%d", $class, $this->externalId );
    
    $o = $this->db->getSingleObject($q);
    if ( !$o )
      return;

    $this->kikiUserIds[] = $kikiUserId;

    $this->id = $o->id;
    
    if ( !$this->token )
      $this->token = $o->token;

    if ( !$this->secret )
      $this->secret = $o->secret;

    $this->name = $o->name;
    $this->screenName = $o->screenname;

    if ( get_class($this) == '\Kiki\User\Twitter' )
      $this->picture = str_replace( "_normal.", "_bigger.", $o->picture );
    else
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
        "UPDATE connections set user_id=%d, external_id=%d, service='%s', mtime=now(), token='%s', secret='%s', name='%s', screenname='%s', picture='%s' WHERE id=%d",
        $kikiUserId, $this->externalId, get_class($this), $this->token, $this->secret, $this->name, $this->screenName, $this->picture, $this->id
      );
      
      $this->db->query($q);
    }
    else
    {
      $q = $this->db->buildQuery( "insert into connections(user_id, external_id, service, ctime, mtime, token, secret, name, screenname, picture ) values ( %d, %d, '%s', now(), now(), '%s', '%s', '%s', '%s', '%s' )", $kikiUserId, $this->externalId, get_class($this), $this->token, $this->secret, $this->name, $this->screenName, $this->picture );

      $rs = $this->db->query($q);
      if ( $rs )
        $this->id = $this->db->lastInsertId($rs);
    }
  }

  public function subAccounts()
  {
    if ( !isset($this->subAccounts) )
    {
      $this->getSubAccounts();
    }
    return $this->subAccounts;
  }

	public function permissions()
	{
		if ( !isset($this->permissions) )
		{
			$this->getPermissions();
		}
		return $this->permissions;
	}
}

?>
