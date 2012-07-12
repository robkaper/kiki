<?

/**
 * Object base class.
 *
 * Provides all properties and methods shared by all Objects.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

abstract class Object
{
  protected $db;

  protected $type = null;
  
  protected $id = 0;
  protected $objectId = 0;

  protected $name = null;
  protected $uriAlias = null;
  
  protected $ctime = null;
  protected $mtime = null;

  protected $publications;

  public function __construct( $id = 0, $objectId = 0 )
  {
    $this->db = $GLOBALS['db'];

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

    $this->name = null;
    $this->uriAlias = null;

    $this->ctime = null;
    $this->mtime = null;

    $this->publications = null;
  }

  abstract public function load();

  protected function setFromObject( &$o )
  {
    if ( !$o )
      return;

    $this->id = $o->id;
    $this->objectId = $o->object_id;

    // $this->name = $o->name;
    // $this->uriAlias = $o->uriAlias;

    $this->ctime = $o->ctime;
    $this->mtime = $o->mtime;
  }

  final public function save()
  {
    if ( !$this->objectId )
    {
      $qCtime = (isset($this->ctime) && is_numeric($this->ctime) && $this->ctime) ? sprintf( "'%s'", date("Y-m-d H:i:s", $this->ctime) ) : "now()";

      $q = $this->db->buildQuery( "INSERT INTO objects (type,ctime,mtime) values('%s',$qCtime,now())", get_class($this) );
      $rs = $this->db->query($q);
      if ( $rs )
        $this->objectId = $this->db->lastInsertId($rs);
    }

    $this->id ? $this->dbUpdate() : $this->dbInsert();
  }

  protected function dbUpdate()
  {
    $q = $this->db->buildQuery(
      "UPDATE objects SET ctime='%s', mtime=now() where object_id=%d",
      $this->ctime, $this->objectId
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

  final public function publications()
  {
    if ( !isset($this->publications) )
      $this->loadPublications();

    return $this->publications;
  }

  abstract public function url();
}

?>