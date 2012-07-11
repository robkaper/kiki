<?

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
    $this->db = $GLOBALS['db'];
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
    $q = $this->db->buildQuery( "INSERT INTO publications (ctime,object_id,connection_id,external_id,body,response) values (now(), %d, %d, %d, '%s', '%s')",
      $this->objectId, $this->connectionId, $this->externalId, $this->body, $this->response
    );

    $rs = $this->db->query($q);
    Log::debug($q);
    if ( $rs )
      $this->id = $this->db->lastInsertId($rs);

    return $this->id;
  }
  
  private function dbUpdate()
  {
  }

  public function setObjectId( $objectId ) { $this->objectId = $objectId; }
  public function setConnectionId( $connectionId ) { $this->connectionId = $connectionId; }
  public function setExternalId( $externalId ) { $this->externalId = $externalId; }
  public function setBody( $body ) { $this->body = $body; }
  public function setResponse( $response ) { $this->response = $response; }

  public function service() { return str_replace( "User_", "", $this->service ); }
  public function url()
  {
    switch( $this->service() )
    {
      case 'Twitter':
        return "http://www.twitter.com/$this->connectionId/statuses/$this->externalId";
        break;
      case 'Facebook':
        return "http://www.facebook.com/$this->connectionId/posts/$this->externalId";
        break;
    }
    return false;
  }
}