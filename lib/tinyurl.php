<?

class TinyUrl
{

  public static function lookup( $id )
  {
    $db = $GLOBALS['db'];
    $qId = $db->escape( $id );
    $q = "select url from tinyurl where id='$qId'";
    Log::debug($q);
    return $db->getSingleValue( "select url from tinyurl where id='$qId'" );
  }

  public static function lookup62( $id )
  {
    return TinyUrl::lookup( Base62::decode($id) );
  }
  
  public static function insert( $url )
  {
    $db = $GLOBALS['db'];
    $qUrl = $db->escape( $url );
    $q = "insert into tinyurl(url) values('$qUrl')";
    Log::debug( $q );
    $rs = $db->query($q);
    $id = $db->lastInsertId($rs);
    return $id;
  }

  public static function get( $url )
  {
    $db = $GLOBALS['db'];
    $qUrl = $db->escape( $url );
    $q = "select id from tinyurl where url='$qUrl'";
    Log::debug($q);
    $id = $db->getSingleValue($q);
    if ( !$id )
      $id = TinyUrl::insert($url);

    return sprintf( "http://%s/%03s", $_SERVER['SERVER_NAME'], sprintfBase62::encode($id) );
  }
}

?>