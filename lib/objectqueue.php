<?php

namespace Kiki;

use Kiki\Core;
use Kiki\Log;

class ObjectQueue
{
    private $db = null;

    public function __construct( &$db = null )
    {
        $this->db = $db ?? Core::getDb();
    }

    // TODO: add $action (also in db), to allow different types of handlers
    // TODO: use ltime
    // TODO: use tries
    static public function store( $object, $priority = 10 )
    {
        $db = Core::getDb();

        $q = "INSERT INTO `object_queue` (`object_id`, `priority`)
            VALUES (%d, %d)
            ON DUPLICATE KEY UPDATE `lock_id`=null, `processed`=false, `priority`=%d";
        $q = $db->buildQuery( $q, $object->objectId(), $priority, $priority );

        $rs = $db->query($q);
        return $db->lastInsertId($rs);
    }

    public function getNext( $lockId )
    {
        $q = "UPDATE `object_queue` SET `lock_id`='$lockId' WHERE `lock_id` IS NULL AND `processed`=false ORDER BY `priority` DESC, `ctime` ASC LIMIT 1";
	$rs = $this->db->query($q);

	$q = "SELECT `id`, `object_id` FROM `object_queue` WHERE `lock_id`='$lockId' ORDER BY `priority` DESC, `ctime` ASC LIMIT 1";
	$o = $this->db->getSingleObject($q);

	if ( $o )
	    self::lock( $o->id, $lockId );

        return $o;
    }

    private function lock( $id, $lockId )
    {
        $q = $this->db->buildQuery( "UPDATE `object_queue` SET `lock_id`='%s' WHERE `id`=%d AND `lock_id` IS NULL", $lockId, $id );
        $rs = $this->db->query($q);

        return $this->db->affectedRows($rs);
    }

    private function unlock( $id )
    {
        $q = $this->db->buildQuery( "UPDATE `object_queue` SET `lock_id`=null WHERE `id`=%d AND `lock_id` IS NOT NULL", $id );
        $rs = $this->db->query($q);

        return $this->db->affectedRows($rs);
    }

    public function markDone( $id )
    {
        $q = $this->db->buildQuery( "UPDATE `object_queue` SET `processed`=true WHERE `id`=%d", $id );
        $this->db->query($q);

        self::unlock($id);
    }

    static public function deleteObject( $objectId )
    {
        $db = Core::getDb();

        $q = $db->buildQuery( "DELETE FROM `object_queue` WHERE `object_id`=%d", $objectId );
        $db->query($q);  
    }

    public function cleanup( $pid )
    {
        Log::debug( "cleaning up pid $pid" );

        $q = $this->db->buildQuery( "UPDATE `object_queue` SET `lock_id`=null WHERE `lock_id`='lock_%d'", $pid );
        $this->db->query($q);  
    }
}
