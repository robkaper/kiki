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

  // protected $uriAlias = null;
  
  protected $visible = false;

  // FIXME: is this generic enough for BaseObject? Historically made sense for Article/Page when Kiki was primarily used for simple sites and blogs
  protected $sectionId = 0;

  // FIXME: deprecate or reinstate
  // protected $publications;

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

    return;

    $this->uriAlias = null;

    $this->visible = false;
    $this->sectionId = 0;

    $this->publications = null;
  }

  abstract public function load();

  protected function setFromObject( $o )
  {
    if ( !$o )
      return;

    $this->id = $o->id;
    $this->object_id = $o->object_id;

    // $this->title = $o->title;
    // $this->uriAlias = $o->uriAlias;

    $this->ctime = $o->ctime;
    $this->mtime = $o->mtime;
    
    $this->object_name = $o->object_name ?? null;
    $this->user_id = $o->user_id ?? 0;
    
    // FIXME: should these be here
    // return;

    // FIXME: issets are necessary because not all Object child classes have these set in their queries e.g. load(), i.e. User.
    $this->visible = isset($o->visible) ? $o->visible : false;
    $this->sectionId = isset($o->section_id) ? $o->section_id : 0;
  }

  final public function save()
  {
    if ( !$this->object_id )
    {
      // $q = $this->db->buildQuery( "INSERT INTO objects (type,visible,user_id,section_id) values('%s',%d,%d,%d)", get_class($this), $this->visible, $this->user_id, $this->sectionId );
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
/*
    $q = $this->db->buildQuery(
      "UPDATE objects SET visible=%d, user_id=%d, section_id=%d WHERE object_id=%d",
      $this->visible, $this->user_id, $this->sectionId, $this->object_id
    );
*/

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

  final public function setVisible( $visible ) { $this->visible = $visible; }
  final public function visible() { return $this->visible; }
  final public function setUserId( $user_id ) { $this->user_id = $user_id; }
  final public function userId() { return $this->user_id; }
  final public function setSectionId( $sectionId ) { $this->sectionId = $sectionId; }
  final public function sectionId() { return $this->sectionId; }

  final public function getMetaData()
  {
    if ( !isset($this->metaData) )
      $this->metaData = new ObjectMetaData( $this->object_id );

    return $this->metaData;
  }

  final public function loadPublications()
  {
    $this->publications = array();

    // FIXME: deprecate? currently Kiki has no connections that actually allow for publications
    // TODO: check Mastodon API
    return;

    $q = $this->db->buildQuery( "SELECT publication_id, p.connection_id, p.external_id, uc.service FROM publications p LEFT JOIN user_connections uc ON uc.external_id=p.connection_id WHERE p.object_id=%d", $this->object_id );
    $rs = $this->db->query($q);
    while( $o = $this->db->fetchObject($rs) )
    {
      $publication = new Publication();
      $publication->setFromObject($o);
      $this->publications[$o->publication_id] = $publication;
    }    
  }

  // FIXME: should not directly return templateData, but Like objects (which have a templatData method)
  final public function likes()
  {
    // FIXME: refactor to internal likes
    return array();

    $q = $this->db->buildQuery( "SELECT external_id AS id FROM likes LEFT JOIN user_connections uc ON likes.user_connection_id=uc.id WHERE object_id=%d", $this->object_id );
    $likeUsers = $this->db->getObjectIds($q);

    $likes = array();
    foreach( $likeUsers as $externalId )
    {
      $likeUser = User\Factory::getInstance( 'Deprecated', $externalId );
      $likes[] = array(
        'userName' => $likeUser->name(),
        'serviceName' => 'Deprecated',
        'pictureUrl' => $likeUser->picture()
      );
    }
    return $likes;
  }

  final public function publications()
  {
    if ( !isset($this->publications) )
      $this->loadPublications();

    return $this->publications;
  }

  abstract public function url();
}
