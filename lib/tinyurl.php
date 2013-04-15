<?php

/**
 * Creates, stores and resolves local tiny URLs.
 *
 * @class TinyUrl
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

class TinyUrl
{

  /**
   * Looks up the full URL for a tinyURL database entry.
   *
   * @param int $id Database ID.
   *
   * @return string full URL of the resource
   */
  public static function lookup( $id )
  {
    $db = Kiki::getDb();
    $q = $db->buildQuery( "select url from tinyurl where id='%s'", $id );
    return $db->getSingleValue($q);
  }

  /**
   * Looks up the full URL for a tinyURL ID.
   *
   * @param string $id tinyURL ID (just the local part of the URI, not the
   * full URL with protocol or hostname)
   *
   * @return string full URL of the resource
   */
  public static function lookup62( $id )
  {
    return TinyUrl::lookup( Base62::decode($id) );
  }
  
  /**
   * Stores a URL resource into the database.
   *
   * @param string $url URL of the resource
   *
   * @return int ID of the database entry
   */
  public static function insert( $url )
  {
    $db = Kiki::getDb();
    $q = $db->buildQuery( "insert into tinyurl(url) values('%s')", $url );
    $rs = $db->query($q);
    $id = $db->lastInsertId($rs);
    return $id;
  }

  /**
   * Retrieves a tinyURL for a full URL. Tries a lookup first and creates a
   * new tinyURL upon failure.
   *
   * @param string $url URL of the resource
   *
   * @return string tinyURL for the resource
   */
  public static function get( $url )
  {
    $db = Kiki::getDb();
    $q = $db->buildQuery( "select id from tinyurl where url='%s'", $url );
    $id = $db->getSingleValue($q);
    if ( !$id )
      $id = TinyUrl::insert($url);

    $host = Config::$tinyHost ? Config::$tinyHost : $_SERVER['SERVER_NAME'];
    return sprintf( "http://%s/%03s", $host, Base62::encode($id) );
  }
}

?>