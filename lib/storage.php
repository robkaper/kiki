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

    $extension = substr( $fileName, strrpos( $fileName, '.' )+1 );
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
    chmod( 0644, $fileName );

    return $id;    
  }

}

?>
