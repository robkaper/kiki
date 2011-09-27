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

  protected $ctime = null;
  protected $mtime = null;

  public function __construct( $id = 0 )
  {
    $this->db = $GLOBALS['db'];

    if ( $this->id = $id )
      $this->load();
    else
      $this->reset();
  }

  public function reset()
  {
    $this->id = 0;
    $this->objectId = 0;

    $this->ctime = null;
    $this->mtime = null;
  }

  abstract public function load();

  protected function setFromObject( &$o )
  {
    if ( !$o )
      return;

    $this->id = $o->id;
    $this->objectId = $o->object_id;
    $this->ctime = $o->ctime;
    $this->mtime = $o->mtime;
  }

  final public function save()
  {
    if ( !$this->objectId )
    {
      $q = $this->db->buildQuery( "INSERT INTO objects (type,ctime,mtime) values('%s',now(),now())", get_class($this) );
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

  final public function setId( $id ) { $this->id = $id; }
  final public function id() { return $this->id; }
  final public function setObjectId( $objectId ) { $this->objectId = $objectId; }
  final public function objectId() { return $this->objectId; }

  abstract public function url();
}

?>