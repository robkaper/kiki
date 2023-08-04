<?php

namespace Kiki;

use Kiki\Core;
use Kiki\Storage;

class Picture extends BaseObject
{
  private $width;
  private $height;
  private $title;
  private $description;
  private $storageId;

  public function reset()
  {
    parent::reset();

    $this->width = 0;
    $this->height = 0;

    $this->title = null;
    $this->description = null;
    $this->storageId = null;
  }

  public function load( $id = 0 )
  {
    if ( $id )
    {
      $this->id = $id;
      $this->object_id = 0;
    }

    $qFields = "id, width, height, title, description, storage_id, o.object_id, o.ctime, o.mtime, o.user_id";
    $q = $this->db->buildQuery( "SELECT $qFields FROM pictures p LEFT JOIN objects o ON o.object_id=p.object_id WHERE p.id=%d", $this->id );
    $this->setFromObject( $this->db->getSingleObject($q) );
  }
  
  public function setFromObject( $o )
  {
    parent::setFromObject($o);

    if ( !$o )
      return;

    $this->width = $o->width;
    $this->height = $o->height;

    $this->title = $o->title;
    $this->description = $o->description;

    $this->storageId = $o->storage_id;
  }

  public function dbUpdate()
  {
    parent::dbUpdate();

    $q = $this->db->buildQuery(
      "UPDATE pictures set object_id=%d, width=%d, height=%d, title='%s', description='%s', storage_id=%d WHERE id=%d",
      $this->object_id, $this->width, $this->height, $this->title, $this->description, $this->storageId, $this->id
    );

    $this->db->query($q);
  }

  public function dbInsert()
  {
    $q = $this->db->buildQuery(
      "INSERT INTO pictures (object_id, width, height, title, description, storage_id) VALUES (%d, '%s', '%s', %d)",
      $this->object_id, $this->width, $this->height, $this->title, $this->description, $this->storageId
    );

    $rs = $this->db->query($q);

    if ( $rs )
      $this->id = $this->db->lastInsertId($rs);

    return $this->id;
  }

  public function delete()
  {
    $user = Core::getUser();

    if ( $user->id() != $this->user_id )
      return false;

    parent::delete();

    // Delete picture from album(s)
    $qAlbumPictures = $this->db->buildQuery( "DELETE FROM album_pictures WHERE picture_id=%d", $this->id );
    $this->db->query($qAlbumPictures);

    // Remove highlight ID
    $qHighlight = $this->db->buildQuery( "UPDATE albums SET highlight_id=NULL WHERE highlight_id=%d", $this->id );
    $this->db->query($qHighlight);

    // Delete storage item
    $qStorageId = $this->db->buildQuery( "SELECT storage_id FROM pictures WHERE id=%d", $this->id );
    $storageId = $this->db->getSingleValue( $qStorageId );
    Storage::delete( $storageId );

    // Delete picture itself
    $qPicture = $this->db->buildQuery( "DELETE from pictures WHERE id=%d", $this->id );
    $this->db->query($qPicture);

    return true;
  }

  public function url() { return null; }

  public function setWidth( $width ) { $this->width = $width; }
  public fuction width() { return $this->width; }
  public function setHeight( $height ) { $this->height = $height; }
  public function height() { return $this->height; }

  public function setTitle( $title ) { $this->title = $title; }
  public function title() { return $this->title; }
  public function setDescription( $description ) { $this->description = $description; }
  public function description() { return $this->description; }

  public function setStorageId( $storageId ) { $this->storageId = $storageId; }
  public function storageId() { return $this->storageId; }
}
