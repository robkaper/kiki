<?

/**
* @class TinyUrl
* Creates, stores and resolves local tiny URLs
* @todo Allow lookups of full URLs
* @author Rob Kaper <http://robkaper.nl/>
*/

class TinyUrl
{

  /**
  * Looks up the full URL for a tinyURL database entry.
  * @param $id [int] Database ID.
  * @return string full URL of the resource
  */
  public static function lookup( $id )
  {
    $db = $GLOBALS['db'];
    $qId = $db->escape( $id );
    $q = "select url from tinyurl where id='$qId'";
    return $db->getSingleValue( "select url from tinyurl where id='$qId'" );
  }

  /**
  * Looks up the full URL for a tinyURL ID.
  * @param $id [string] tinyURL ID (just the local part of the URI, not the full URL with protocol or hostname)
  * @return string full URL of the resource
  */
  public static function lookup62( $id )
  {
    return TinyUrl::lookup( Base62::decode($id) );
  }
  
  /**
  * Stores a URL resource into the database.
  * @param $url [string] URL of the resource
  * @return int ID of the database entry
  */
  public static function insert( $url )
  {
    $db = $GLOBALS['db'];
    $qUrl = $db->escape( $url );
    $q = "insert into tinyurl(url) values('$qUrl')";
    $rs = $db->query($q);
    $id = $db->lastInsertId($rs);
    return $id;
  }

  /**
  * Retrieves a tinyURL for a full URL. Tries a lookup first and creates a new tinyURL upon failure.
  * @param $url [string] URL of the resource
  * @return string tinyURL for the resource
  */
  public static function get( $url )
  {
    $db = $GLOBALS['db'];
    $qUrl = $db->escape( $url );
    $q = "select id from tinyurl where url='$qUrl'";
    $id = $db->getSingleValue($q);
    if ( !$id )
      $id = TinyUrl::insert($url);

    // @todo support an alternative domain to be used here (for now do however assume that domain points to the same virtual host)
    return sprintf( "http://%s/%03s", $_SERVER['SERVER_NAME'], Base62::encode($id) );
  }
}

?>