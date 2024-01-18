<?php

namespace Kiki;

use Kiki\Storage;

class StorageItem
{
    private $id;
    private $hash;
    private $userId;

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
        $this->userId = null;

        $this->original_name = null;
        $this->extension = null;
        $this->size = 0;

        $this->data = null;
    }

    public function load( $id )
    {
        $db = Core::getDb();

        $q = "SELECT id, hash, user_id, original_name, extension, size FROM storage WHERE id=%d";
        $q = $db->buildQuery($q, $id );
        $o = $db->getSingleObject($q);

        // Also allow loading by hash        
        if ( !$o )
        {
            $q = "SELECT id, hash, user_id, original_name, extension, size FROM storage WHERE hash='%s'";
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
        $this->userId = $o->user_id;

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

        $qUserId = Database::nullable( $this->userId) ;

        $q = "INSERT INTO storage(hash, user_id, original_name, extension, size) VALUES('%s', %s, '%s', '%s', %d)";
        $q = $db->buildQuery( $q, $this->hash, $qUserId, $this->original_name, $this->extension, $this->size );
        $rs = $db->query($q);

        $this->id = $db->lastInsertId($rs);

        if( !$this->id )
            return false;

        $localFile = self::localFile($this->id);

        file_put_contents( $localFile, $this->data );
        chmod( $localFile, 0664 );

        $mimeType = self::getMimeType( $localFile );
        switch( $mimeType )
        {
            case 'image/jpeg':
                $extension  = 'jpg';
                break;

            case 'image/png':
                $extension  = 'png';
                break;

            default:
                $extension = $this->extension;
        }

        if ( $extension != $this->extension )
        {
            $this->extension = $extension;
            $this->update();

            rename( $localFile, self::localFile($this->id) );
        }

        return $this->id;
    }
    
    public function update()
    {
        $db = Core::getDb();

        $qUserId = Database::nullable( $this->userId) ;

        $q = "UPDATE storage SET hash='%s', user_id=%s, original_name='%s', extension='%s', size=%d WHERE id=%d";
        $q = $db->buildQuery( $q, $this->hash, $qUserId, $this->original_name, $this->extension, $this->size, $this->id );
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
        list( , $ext ) = self::splitExtension( $fileName );
        return $ext;
    }

    public static function getMimeType( $fileName )
    {
        if ( !file_exists($fileName) )
            return null;

        $finfo = finfo_open( FILEINFO_MIME_TYPE );

        $mimeType = finfo_file( $finfo, $fileName );

        finfo_close( $finfo );

        return $mimeType;
    }

    public function id() { return $this->id; }
    public function hash() { return $this->hash; }

    public function setUserId( $userId ) { $this->userId = $userId; }
    public function userId() { return $this->userId; }

    public function setOriginalName( $originalName )
    {
        $this->original_name = $originalName;

        $this->extension = self::getExtension($originalName);
    }

    public function originalName() { return $this->original_name; }

    public function setData( $data )
    {
        $this->data = $data;
        $this->size = strlen($data);

        // Creates a unique hash.  It's somewhat predictable but that's okay
        // for the purpose of storage items.
        $this->hash = sha1( $this->size. Config::$namespace. $data );
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

    public function uri( $w=0, $h=0, $crop=false, $convertTo=null )
    {
        $extra = ($w && $h) ? ( ".${w}x${h}". ($crop ? ".c" : null) ) : null;
        return sprintf( "%s%s.%s", $this->hash, $extra, $convertTo ?? $this->extension );
    }

    public function url( $w=0, $h=0, $crop=false, $extension = null )
    {
        return "https://". $_SERVER['SERVER_NAME']. "/storage/". $this->uri($w,$h,$crop,$extension);
    }
}
