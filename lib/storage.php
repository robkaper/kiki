<?

/**
* @class Storage
* Stores and retrieves local filesystem data offering database and URI references.
* @author Rob Kaper <http://robkaper.nl/>
*/ 

class Storage
{

  /**
  * Looks up the local filename of a stored resource.
  * @param $id [int] ID of the database entry
  * @return string full path of the resource
  */
  public static function localFile( $id )
  {
    return sprintf( "%s/storage/%s", $GLOBALS['root'], self::uri($id) );
  }

  /**
  * Looks up the local URI a stored resource.
  * @param $id [int] ID of the database entry
  * @return string local URI of the resource
  */
  public static function uri( $id )
  {
    $db = $GLOBALS['db'];
    $qId = $db->escape($id);
    $o = $db->getSingle( "select hash,extension from storage where id=$qId" );
    return $o ? sprintf( "%s.%s", $o->hash, $o->extension ) : null;
  }

  /**
  * Splits a filename into a base part and extension.
  * @param $name [string] name of the file (should not contain a path)
  * @return array base name and extension of the file
  */
  public static function splitExtension( $name )
  {
    $pos = strrpos( $name, '.' );
    if ( $pos === FALSE )
      return array( $name, null );

    $base = substr( $name, 0, $pos );
    $ext = substr( $name, $pos+1 );
    return array( $base, $ext );
  }

  /**
  * Retrieve the base part of a filename.
  * @param $name [string] name of the file (should not contain a path)
  * @return string base name of the file
  */
  public static function getBase( $name )
  {
    list( $base ) = self::splitExtension($name);
    return $base;
  }

  /**
  * Retrieve the extension part of a filename.
  * @param $name [string] name of the file
  * @return string extension of the file
  */
  public static function getExtension( $name )
  {
    list( $base, $ext ) = self::splitExtension($name);
    return $ext;
  }
  
  /**
  * Retrieves the raw data of a stored resource.
  * @param $id [int] database ID of the resource
  * @return string raw data of the resource
  */
  public static function data( $id )
  {
    return file_get_contents( self::localFile($id) );
  }

  /**
  * Generates a URL for a stored resource.
  * @param $id [int] database ID of the resource
  * @return string Full URL (protocol, host, local URI) of the resource
  */
  public static function url( $id )
  {
    return "http://". $_SERVER['SERVER_NAME']. "/storage/". self::uri($id);
  }

  /**
  * Stores a resource.
  * @param $fileName [string] original filename
  * @param $data [string] file data
  * @return int ID of the database entry created
  */
  public static function save( $fileName, $data )
  {
    $db = $GLOBALS['db'];

    $extension = self::getExtension( $fileName );
    $hash = sha1( uniqid(). $data );

    $qHash = $db->escape( $hash );
    $qName = $db->escape( $fileName );
    $qExt = $db->escape( $extension );
    $qSize = $db->escape( sizeof($data) );

    $q = "insert into storage(hash, original_name, extension, size) values('$qHash', '$qName', '$qExt', $qSize)";
    $rs = $db->query($q);
    $id = $db->lastInsertId($rs);

    $fileName = self::localFile($id);
    file_put_contents( $fileName, $data );
    chmod( $fileName, 0644 );

    return $id;    
  }

}

?>
