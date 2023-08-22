<?php

/**
 * BaseObject base class.
 *
 * Provides all properties and methods shared by all BaseObjects.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2011-2013 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

// FIXME: move cname here, and add unique key sectionId/cname to db scheme

namespace Kiki;

abstract class BaseObject
{
  protected $db = null;

  protected $type = null;
  
  protected $id = 0;
  protected $object_id = 0;

  protected $ctime = null;
  protected $mtime = null;

  protected $object_name = null;
  protected $user_id = 0;

  private $metaData;

  public function __construct( $id = 0, $object_id = 0 )
  {
    $this->db = Core::getDb();

    $this->id = $id;
    $this->object_id = $object_id;

    if ( $this->id || $this->object_id )
      $this->load();
    else
      $this->reset();
  }

  public function reset()
  {
    $this->id = 0;
    $this->object_id = 0;
    $this->ctime = null;
    $this->mtime = null;

    $this->object_name = null;
    $this->user_id = 0;

    $this->metaData = null;
  }

  abstract public function load();

  protected function setFromObject( $o )
  {
    if ( !$o )
      return;

    $this->id = $o->id;
    $this->object_id = $o->object_id;

    $this->ctime = $o->ctime;
    $this->mtime = $o->mtime;
    
    $this->object_name = $o->object_name ?? null;
    $this->user_id = $o->user_id ?? 0;
  }

  public function save()
  {
    if ( !$this->object_id )
    {
      $q = "INSERT INTO objects (`object_name`, `user_id`, `type`) values('%s', %d, '%s')";
      $q = $this->db->buildQuery( $q, $this->object_name, $this->user_id, get_class($this) );
      $rs = $this->db->query($q);
      if ( $rs )
        $this->object_id = $this->db->lastInsertId($rs);
    }

    $this->id > 0 ? $this->dbUpdate() : $this->dbInsert();
  }

  protected function dbUpdate()
  {
    $q = $this->db->buildQuery(
      "UPDATE objects SET object_name='%s', user_id=%d WHERE object_id=%d",
      $this->object_name, $this->user_id, $this->object_id
    );

    $this->db->query($q);
  }

  protected function delete()
  {
    $oMeta = $this->getMetaData();
    $oMeta->delete();

    $q = $this->db->buildQuery( "DELETE FROM `objects` WHERE `object_id` = %d", $this->object_id );
    $this->db->query($q);
  }

  final public function setId( $id ) { $this->id = $id; }
  final public function id() { return $this->id; }
  final public function setObjectId( $object_id ) { $this->object_id = $object_id; }
  final public function objectId() { return $this->object_id; }
  final public function setCtime( $ctime ) { $this->ctime = $ctime; }
  final public function ctime() { return $this->ctime; }

  final public function setObjectName( $object_name ) { $this->object_name = $object_name; }
  public function objectName() { return $this->object_name; }

  final public function type() { return get_class($this); }

  final public function setUserId( $user_id ) { $this->user_id = $user_id; }
  final public function userId() { return $this->user_id; }

  final public function getMetaData()
  {
    if ( !isset($this->metaData) )
      $this->metaData = new ObjectMetaData( $this->object_id );

    return $this->metaData;
  }

  final public function likes( $userId = 0 )
  {
    $qLikes = "SELECT SUM(1) AS count, SUM(user_id=%d) AS active, GROUP_CONCAT(user_id) as users FROM object_likes WHERE object_id = %d";
    $qLikes = $this->db->buildQuery( $qLikes, $userId, $this->object_id );
    return $this->db->getSingleObject( $qLikes );
  }

  abstract public function url();
}
