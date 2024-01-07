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

    static public function store( $object, $action = 'default', $priority = 10 )
    {
        $db = Core::getDb();

        $q = "INSERT INTO `object_queue` (`object_id`, `action`, `priority`)
            VALUES (%d, '%s', %d)
            ON DUPLICATE KEY UPDATE `lock_id`=null, `processed`=false, `priority`=%d";
        $q = $db->buildQuery( $q, $object->objectId(), $action, $priority, $priority );

        $rs = $db->query($q);
        return $db->lastInsertId($rs);
    }

    public function getNext( $lockId )
    {
        $maxTries = 20;

        $q = $this->db->buildQuery(
            "UPDATE `object_queue` SET `lock_id`='$lockId', `ltime` = NOW() WHERE `lock_id` IS NULL AND `processed`=false AND (`qtime` IS NULL OR `qtime`<=NOW()) AND `tries`<=%d ORDER BY `priority` DESC, `ctime` ASC LIMIT 1",
            $maxTries
        );
	$rs = $this->db->query($q);

	$q = "SELECT `id`, `object_id`, `action`, `tries` FROM `object_queue` WHERE `lock_id`='$lockId' ORDER BY `priority` DESC, `ctime` ASC LIMIT 1";
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

    static private function unlock( $id )
    {
        $db = Core::getDb();

        $q = $db->buildQuery( "UPDATE `object_queue` SET `lock_id`=null WHERE `id`=%d AND `lock_id` IS NOT NULL", $id );
        $rs = $db->query($q);

        return $db->affectedRows($rs);
    }

    public function markDone( $id )
    {
        $q = $this->db->buildQuery( "UPDATE `object_queue` SET `tries`=`tries`+1, `processed`=true WHERE `id`=%d", $id );
        $this->db->query($q);

        self::unlock($id);
    }

    public function markFailed( $id, $tries )
    {
        // This is sufficient to for up to 35 retries, with the last try being ~6 months after the penultimate.
        $fibonacci = [ 1, 2, 3, 5, 8, 13, 21, 34, 55, 89, 144, 233, 377,
            610, 987, 1597, 2584, 4181, 6765, 10946, 17711, 28657, 46368, 75025,
            121393, 196418, 317811, 514229, 832040, 1346269, 2178309, 3524578,
            5702887, 9227465, 14930352 ];
        $queueTime = date( 'Y-m-d H:i:s', time() + $fibonacci[$tries] );

        $q = $this->db->buildQuery( "UPDATE `object_queue` SET `tries`=%d, `qtime`='%s' WHERE `id`=%d", $tries, $queueTime, $id );
        $this->db->query($q);

        self::unlock($id);
    }

    static public function delay( $id, $queueTime )
    {
        $db = Core::getDb();

        $q = $db->buildQuery( "UPDATE `object_queue` SET `qtime`='%s' WHERE `id`=%d", $queueTime, $id );
        $db->query($q);

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
