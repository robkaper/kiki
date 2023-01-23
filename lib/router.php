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
  /**
   * Sends a redirect header
   *
   * @param string URL of target location
   * @param boolean whether redirect is permanent
   * @return boolean true when redirect header was sent
   *
   * @todo add header to (new) Http object instead of sending it here
   */
  public static function redirect( $url, $statusCode = 302 )
  {
    if ( !$url )
      return false;

    Log::debug( "EXIT: redirect to $url [$statusCode]" );
    header( "Location: $url", true, $statusCode );
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
    $db = Core::getDb();

    $q = $db->buildQuery( "select base_uri from sections where type='%s' and id=%d", $type, $id );
    return $db->getSingleValue($q);
  }

  public static function findSection( $uri )
  {
    $baseUris = self::getBaseUris();
    if ( !count($baseUris) )
      return null;

    $trailingSlash = false;

    $result = self::matchBaseUri($uri, $trailingSlash);
    if ( !$result )
    {
      $trailingSlash = true;
      $result = self::matchBaseUri($uri,$trailingSlash);
      if ( !$result )
        return null;
    }

    Log::debug( "lookup for uri: $uri --> result: $result" );
    
    list($matchedUri, $remainder, $q ) = explode(":", $result);
    if ( !$matchedUri )
      return null;

		// Ensure trailing slash for all handler indices.  TODO: consider
		// *removing* trailing slashes instead if not page exists: collection
		// handlers are found either way, pages only without.  Removing them
		// allows graceful collection-to-page migration of URLs, adding them
		// does not.  This would however overrule pages with sections unless a
		// check is made first no page exists with the same trailingslashless URI.
  	if ( !$trailingSlash )
		{
			$url = $matchedUri. "/". $remainder. $q;
  	  self::redirect($url, 302) && exit();
    }

    $route = $baseUris[$matchedUri];

		$controller = Controller::factory($route->type);
		$controller->setInstanceId($route->id);
		$controller->setObjectId($remainder);
		// $controller->setQuery($q);
		// $controller->setMatchedUri( $matchedUri );

		Log::debug( "matches $matchedUri (type: $route->type, id: $route->id), remainder: ". $remainder. ", q: ". $q );

    return $controller;
  }

  public static function findPage( $uri, $sectionId = 0 )
  {
    $db = Core::getDb();

    $uri = trim( $uri, '/' );

		if ( strstr($uri, '/') )
			return null;

    if ( !$uri )
      $uri = 'index';

    $q = $db->buildQuery( "SELECT id FROM articles a LEFT JOIN objects o ON o.object_id=a.object_id WHERE cname='%s' AND section_id=%d", $uri, $sectionId );
    $pageId = $db->getSingleValue($q);

    Log::debug( "lookup for section: $sectionId, cname: $uri --> pageId: $pageId" );

    if ( !$pageId )
      return null;

		$controller = Controller::factory('page');
		$controller->setInstanceId( $pageId );
		// $controller->setObjectId( $remainder );

		Log::debug( "matches $uri (type: page, id: $pageId), remainder: , q: " );
		return $controller;
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