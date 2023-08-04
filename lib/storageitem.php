<?php

namespace Kiki;

use Kiki\Storage;

class StorageItem
{
    private $id;

    private $hash;
    private $original_name;
    private $extension;
    private $size;

    private $data;

    public function __construct( $id = 0 )
    {
        $this->reset();

        if ( $id )
        {
            $this->id = $id;
            $this->load($id);
        }
    }

    public function reset()
    {
        $this->id = 0;

        $this->hash = null;
        $this->original_name = null;
        $this->extension = null;
        $this->size = 0;

        $this->data = null;
    }

    public function load( $id )
    {
        $db = Core::getDb();

        $q = "SELECT id, hash, original_name, extension, size FROM storage WHERE id=%d";
        $q = $db->buildQuery($q, $id );
        $o = $db->getSingleObject($q);

        // Also allow loading by hash        
        if ( !$o )
        {
            $q = "SELECT id, hash, original_name, extension, size FROM storage WHERE hash='%s'";
            $q = $db->buildQuery($q, $id );
            $o = $db->getSingleObject($q);
        }

        if ( $o )
            $this->setFromObject($o);
    }

    public function setFromObject( $o )
    {
        $this->id = $o->id;

        $this->hash = $o->hash;
        $this->original_name = $o->original_name;
        $this->extension = $o->extension;
        $this->size = $o->size;
    }

    public function save()
    {
        $this->id ? $this->update() : $this->insert();
    }

    public function insert()
    {
        $db = Core::getDb();

        $q = "INSERT INTO storage(hash, original_name, extension, size) VALUES('%s', '%s', '%s', %d)";
        $q = $db->buildQuery( $q, $this->hash, $this->original_name, $this->extension, $this->size );
        $rs = $db->query($q);

        $this->id = $db->lastInsertId($rs);

        if( !$this->id )
            return false;

        $localFile = self::localFile($this->id);

        Log::debug( "makeDirectory $localFile" );
        file_put_contents( $localFile, $this->data );
        chmod( $localFile, 0666 );

        return $this->id;
    }
    
    public function update()
    {
        $db = Core::getDb();

        $q = "UPDATE storage SET hash='%s', original_name='%s', extension='%s', size=%d WHERE id=%d";
        $q = $db->buildQuery( $q, $this->hash, $this->original_name, $this->extension, $this->size, $this->id );
        $rs = $db->query($q);
    }

    public function delete()
    {
        $db = Core::getDb();

        $localFile = $this->localFile();
        unlink($localFile);

        $q = "DELETE FROM storage WHERE id=%d";
        $q = $db->buildQuery( $q, $this->id );
        $db->query($q);

        $this->reset();
    }

    public static function splitExtension( $fileName )
    {
        $pos = strrpos( $fileName, '.' );
        if ( $pos === FALSE )
            return array( $fileName, null );

        $base = substr( $fileName, 0, $pos );
        $ext = substr( $fileName, $pos+1 );
        return array( $base, $ext );
    }

    public static function getBase( $fileName )
    {
        list( $base ) = self::splitExtension( $fileName );
        return $base;
    }

    public static function getExtension( $fileName )
    {
        // FIXME: care about actual mimetypes, not extensions
        list( , $ext ) = self::splitExtension( $fileName );
        return $ext;
    }

    public function id() { return $this->id; }
    public function hash() { return $this->hash; }

    public function setOriginalName( $originalName )
    {
        $this->original_name = $originalName;
        $this->extension = $this->getExtension( $this->original_name );
    }

    public function originalName() { return $this->original_name; }

    public function setData( $data )
    {
        $this->data = $data;
        $this->hash = sha1( uniqid(). $data );
        $this->size = strlen($data);
    }

    public function localFile()
    {
        $uri = $this->uri();

        $fileName = sprintf( "%s/storage/%s/%s/%s", Core::getRootPath(), $uri[0], $uri[1], $uri );

        // Move files directly under storage/ to better-scaling storage/0/f/ parallel directory structure
        $legacyFileName = sprintf( "%s/storage/%s", Core::getRootPath(), $uri );
        if ( !file_exists($fileName) && file_exists($legacyFileName) )
        {
            $dirName = Storage::makeDirectory($fileName);
            if ( file_exists($dirName) && is_dir($dirName) )
            {
                rename( $legacyFileName, $fileName);
                Log::debug( "moved $legacyFileName to $fileName" );
            }
        }

        return $fileName;
    }

    public function uri( $w=0, $h=0, $crop=false )
    {
        $extra = ($w && $h) ? ( ".${w}x${h}". ($crop ? ".c" : null) ) : null;
        return sprintf( "%s%s.%s", $this->hash, $extra, $this->extension );
    }

    public function url( $w=0, $h=0, $crop=false, $secure = true )
    {
        return "http". ($secure ? "s" : null). "://". $_SERVER['SERVER_NAME']. "/storage/". $this->uri($w,$h,$crop);
    }
}
