<?php

/**
 * Object base class.
 *
 * Provides all properties and methods shared by all Objects.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2011-2013 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

// FIXME: move cname here, and add unique key sectionId/cname to db scheme

namespace Kiki;

abstract class Object
{
  protected $db;

  protected $type = null;
  
  protected $id = 0;
  protected $objectId = 0;

  protected $title = null;
  protected $uriAlias = null;
  
  protected $ctime = null;
  protected $mtime = null;

	protected $visible = false;
	protected $userId = 0;
	protected $sectionId = 0;

  protected $publications;

  public function __construct( $id = 0, $objectId = 0 )
  {
    $this->db = Core::getDb();

    $this->id = $id;
    $this->objectId = $objectId;
    if ( $this->id || $this->objectId )
      $this->load();
    else
      $this->reset();
  }

  public function reset()
  {
    $this->id = 0;
    $this->objectId = 0;

    $this->title = null;
    $this->uriAlias = null;

    $this->ctime = null;
    $this->mtime = null;

		$this->visible = false;
		$this->userId = 0;
		$this->sectionId = 0;

    $this->publications = null;
  }

  abstract public function load();

  protected function setFromObject( $o )
  {
    if ( !$o )
      return;

    $this->id = $o->id;
    $this->objectId = $o->object_id;

    // $this->title = $o->title;
    // $this->uriAlias = $o->uriAlias;

    $this->ctime = $o->ctime;
    $this->mtime = $o->mtime;

		// FIXME: issets are necessary because not all Object child classes have these set in their queries e.g. load(), i.e. User.
		$this->visible = isset($o->visible) ? $o->visible : false;
		$this->userId = isset($o->user_id) ? $o->user_id : 0;
		$this->sectionId = isset($o->section_id) ? $o->section_id : 0;
  }

  final public function save()
  {
    if ( !$this->objectId )
    {
      $qCtime = (isset($this->ctime) && is_numeric($this->ctime) && $this->ctime) ? sprintf( "'%s'", date("Y-m-d H:i:s", $this->ctime) ) : "now()";

      $q = $this->db->buildQuery( "INSERT INTO objects (type,ctime,mtime,visible,user_id,section_id) values('%s',$qCtime,now(),%d,%d,%d)", get_class($this), $this->visible, $this->userId, $this->sectionId );
      $rs = $this->db->query($q);
      if ( $rs )
        $this->objectId = $this->db->lastInsertId($rs);
    }

    $this->id ? $this->dbUpdate() : $this->dbInsert();
  }

  protected function dbUpdate()
  {
    $q = $this->db->buildQuery(
      "UPDATE objects SET ctime='%s', mtime=now(), visible=%d, user_id=%d, section_id=%d WHERE object_id=%d",
      $this->ctime, $this->visible, $this->userId, $this->sectionId, $this->objectId
    );

    $this->db->query($q);
  }

  final public function type() { return get_class($this); }
  final public function setId( $id ) { $this->id = $id; }
  final public function id() { return $this->id; }
  final public function setObjectId( $objectId ) { $this->objectId = $objectId; }
  final public function objectId() { return $this->objectId; }
  final public function setCtime( $ctime ) { $this->ctime = $ctime; }
  final public function ctime() { return $this->ctime; }

  final public function setVisible( $visible ) { $this->visible = $visible; }
  final public function visible() { return $this->visible; }
  final public function setUserId( $userId ) { $this->userId = $userId; }
  final public function userId() { return $this->userId; }
  final public function setSectionId( $sectionId ) { $this->sectionId = $sectionId; }
  final public function sectionId() { return $this->sectionId; }

  final public function loadPublications()
  {
    $this->publications = array();

    $q = $this->db->buildQuery( "SELECT publication_id, p.connection_id, p.external_id, c.service FROM publications p LEFT join connections c ON c.external_id=p.connection_id WHERE p.object_id=%d", $this->objectId );
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
    $q = $this->db->buildQuery( "SELECT external_id AS id FROM likes LEFT JOIN connections ON likes.user_connection_id=connections.id WHERE object_id=%d", $this->objectId );
    $likeUsers = $this->db->getObjectIds($q);

    $likes = array();
    foreach( $likeUsers as $externalId )
    {
      $fbUser = User\Factory::getInstance( 'Facebook', $externalId );
      $likes[] = array(
        'userName' => $fbUser->name(),
        'serviceName' => 'Facebook',
        'pictureUrl' => $fbUser->picture()
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

?>