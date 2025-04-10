<?php

/**
 * Class providing routing functionalities such as redirection and base URI detection.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

namespace Kiki;

class Router
{
  private static $altRoute = null;

  public static function altRoute() { return self::$altRoute; }

  public static function detectAltRoute()
  {
    $altRoutes = Config::ini('I18n:i18n');
    if ( !is_array($altRoutes) )
      return;

    if ( $key = array_search( $_SERVER['SERVER_NAME'], $altRoutes ) )
    {
      self::$altRoute = $key;
      return;
    }

    $pathParts = explode( '/', $_SERVER['REQUEST_URI'] );
    array_shift($pathParts);
    if ( !count($pathParts) )
      return;

    if ( $key = array_search( $_SERVER['SERVER_NAME']. "/". $pathParts[0], $altRoutes ) )
      self::$altRoute = $key;
  }

  /**
   * Sends a redirect header
   *
   * @param string URL of target location
   * @param boolean whether redirect is permanent
   * @return boolean true when redirect header was sent
   */
  public static function redirect( $url, $statusCode = 302 )
  {
    if ( !$url )
      return false;

    Http::redirect( $url, $statusCode );
    return true;
  }

  /**
   * Returns base URIs linked to a specific controller type and instance.
   *
   * @param string $type Filter by this controller type.
   * @param boolean $sort Sort URIs alphabetically.
   * @return array Base URI configurations indexed by base URI.
   */
  public static function getBaseURIs( $type = null, $sort = false )
  {
    // FIXME: port to routing config, or get rid entirely database-based
    // routes are probably going to be abandoned

    $db = Core::getDb();

    $baseUris = array();

    // Sort DESC when no sort order is requested for non-greedy matching (e.g. /a/b/ before /a/)
		if ( $type )
	    $q = $db->buildQuery( "SELECT id, base_uri, type, title FROM sections WHERE type='%s' ORDER BY base_uri %s", $type, $sort ? "ASC" : "DESC" );
		else
	    $q = $db->buildQuery( "SELECT id, base_uri, type, title FROM sections ORDER BY base_uri %s", $sort ? "ASC" : "DESC" );

    $rs = $db->query($q);

    if ( $rs && $db->numrows($rs) )
      while( $o = $db->fetchObject($rs) )
      {
        $last = substr( $o->base_uri, -1 );
        if ( $last == "/" )
          $o->base_uri =  substr( $o->base_uri, 0, -1 );
        $baseUris[$o->base_uri] = $o;
      }

    return $baseUris;
  }

  /**
   * Returns the base URI for a specific controller type and instance.
   *
   * @param string $type Controller type.
   * @param int $instanceId Controller instance.
   * @return string Base URI.
   */
  public static function getBaseUri( $type, $id )
  {
    foreach( Config::$routing as $route => $controller )
    {
      $controllerType = $controller[0] ?? null;
      $controllerId = $controller[1] ?? null;
      if ( $controllerType == $type && $controllerId == $id )
        return $route;
    }

    // Database fallback

    $db = Core::getDb();

    $q = $db->buildQuery( "select base_uri from sections where type='%s' and id=%d", $type, $id );

    return $db->getSingleValue($q);
  }

  public static function matchBaseUri( $uri, $trailingSlash = false )
  {
    $baseUris = self::getBaseUris();

    if ( $trailingSlash )
    {
      // pakt alles met een slash
      $pattern = "#^(". join("|", array_keys($baseUris)). ")/([^/\?]+)?(.*)#";
      $replace = "$1:$2:$3";
    }
    else
    {
      // moet alles zonder slash pakken, met slash hoeft niet meer
      $pattern = "#^(". join("|", array_keys($baseUris)). ")(\?(.*))?$#";
      $replace = "$1::$2";
    }

    $result = preg_filter($pattern, $replace, $uri);
    // Log::debug( "$uri, pattern $pattern result: ". print_r($result, true) );

    return $result;
  }

  public static function storeBaseUri( $baseUri, $title, $type, $instanceId = 0 )
  {
      $db = Core::getDb();

      if ( $baseUri[0] != '/' )
        $baseUri = '/'. $baseUri;

      $q = $db->buildQuery( "INSERT INTO sections (id, base_uri, title, type) VALUES (%d, '%s', '%s', '%s') ON DUPLICATE KEY UPDATE type='%s', title='%s'",
        $instanceId, $baseUri, $title, $type, $type, $title
      );

      $db->query($q);
  }

}
  
?>