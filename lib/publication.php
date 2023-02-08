<?php

// FIXME: make sure all publications reference an object. This means all social updates must also have their own unique object id.

namespace Kiki;

class Publication
{
  private $id = 0;
  private $ctime = 0;

  private $objectId = 0;
  private $connectionId = 0;
  private $externalId = 0;
  private $service = null;
  
  private $body = null;
  private $response = null;

  private $db = null;  

  public function __construct()
  {
    $this->db = Core::getDb();
  }

  public function reset()
  {
    $this->id = 0;
    $this->ctime = 0;

    $this->objectId = 0;
    $this->connectionId = 0;
    $this->externalId = 0;
    $this->service = $o->service;
  
    $this->body = null;
    $this->response = null;
  }

  public function load( $id = 0 )
  {
  }

  public function setFromObject( $o )
  {
    $this->id = $o->publication_id;
    $this->connectionId = $o->connection_id;
    $this->externalId = $o->external_id;
    $this->service = $o->service;
  }
   
  public function save()
  {
    $this->id ? $this->dbUpdate() : $this->dbInsert();
  }

  private function dbInsert()
  {
    $qCtime = (isset($this->ctime) && is_numeric($this->ctime) && $this->ctime) ? sprintf( "'%s'", date("Y-m-d H:i:s", $this->ctime) ) : "now()";

    $q = $this->db->buildQuery( "INSERT INTO publications (ctime,object_id,connection_id,external_id,body,response) values ($qCtime, %d, %d, '%s', '%s', '%s')",
      $this->objectId, $this->connectionId, $this->externalId, $this->body, $this->response
    );

    $rs = $this->db->query($q);
    if ( $rs )
      $this->id = $this->db->lastInsertId($rs);

    return $this->id;
  }
  
  private function dbUpdate()
  {
  }

  public function setCtime( $ctime ) { $this->ctime = $ctime; }
  public function setObjectId( $objectId ) { $this->objectId = $objectId; }
  public function setConnectionId( $connectionId ) { $this->connectionId = $connectionId; }
  public function setExternalId( $externalId ) { $this->externalId = $externalId; }
  public function setBody( $body ) { $this->body = $body; }
  public function setResponse( $response ) { $this->response = $response; }

  public function service()
  {
    if ( strstr( $this->service, "\\" ) )
    {
      $parts = explode( "\\", $this->service );
      return end($parts);
    }
    return $this->service;
  }

  public function url()
  {
    switch( $this->service() )
    {
      default:
        return null;
    }
    return null;
  }

  public function templateData()
  {
    return array(
      'url' => $this->url(),
      'service' => $this->service()
    );
  }
}
