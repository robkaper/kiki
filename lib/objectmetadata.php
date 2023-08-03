<?php

namespace Kiki;

class ObjectMetaData
{
    private $objectId = 0;

    private $data = null;
    
    public function __construct( $objectId = 0 )
    {
        $this->reset();

        $this->objectId = $objectId;
        
        if ( $this->objectId )
            $this->load();
    }

    public function reset()
    {
        $this->objectId = 0;

        $this->data = array();
    }

    public function load()
    {
        if ( !$this->objectId )
            return;

        $db = Core::getDb();

        $q = "SELECT `key`, `value` FROM `object_metadata` WHERE `object_id` = %d";
        $q = $db->buildQuery( $q, $this->objectId );
        $rs = $db->query($q);
        if ( $rs)
            while( $o = $db->fetchObject($rs) )
            {
                if ( $o->value )
                    $this->data[$o->key] = $o->value;
            }
    }

    public function save()
    {
        $db = Core::getDb();

        foreach( $this->data as $key => $value )
        {
            $q = "INSERT INTO `object_metadata` (`object_id`, `key`, `value`) VALUES( %d, '%s', '%s' ) ON DUPLICATE KEY UPDATE `value` = '%s'";
            $q = $db->buildQuery( $q, $this->objectId, $key, $value, $value );
            $db->query($q);
        }
    }
    
    public function delete()
    {
        $db = Core::getDb();

        $q = "DELETE FROM `object_metadata` WHERE object_id = %d";
        $q = $db->buildQuery( $q, $this->objectId );
        $db->query($q);

        $this->reset();
    }

    public function setValue( $key, $value )
    {
        $this->data[$key] = $value;
    }

    public function deleteValue( $key )
    {
        $db = Core::getDb();

        $q = "DELETE FROM `object_metadata` WHERE object_id = %d AND `key` = '%s'";
        $q = $db->buildQuery( $q, $this->objectId, $key );
        $db->query($q);

        unset( $this->data[$key] );
    }

    public function getValue( $key )
    {
        return $this->data[$key] ?? null;
    }
}
