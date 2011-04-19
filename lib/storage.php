<?

class Storage
{

  public static function localFile( $id )
  {
    return sprintf( "%s/storage/%s", $GLOBALS['root'], self::uri($id) );
  }

  public static function uri( $id )
  {
    $db = $GLOBALS['db'];
    $qId = $db->escape($id);
    $o = $db->getSingle( "select hash,extension from storage where id=$qId" );
    return sprintf( "%s.%s", $o->hash, $o->extension );
  }

  public static function splitExtension( $name )
  {
    $pos = strrpos( $name, '.' );
    if ( $pos === FALSE )
      return array( $name, null );

    $base = substr( $name, 0, $pos );
    $ext = substr( $name, $pos+1 );
    return array( $base, $ext );
  }

  public static function getBase( $name )
  {
    list( $base ) = self::splitExtension($name);
    return $base;
  }

  public static function getExtension( $name )
  {
    list( $base, $ext ) = self::splitExtension($name);
    return $ext;
  }
  
  public static function data( $id )
  {
    return file_get_contents( self::localFile($id) );
  }

  public static function url( $id )
  {
    return "http://". $_SERVER['SERVER_NAME']. "/storage/". self::uri($id);
  }

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
