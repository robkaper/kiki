<?php

/**
 * Class providing routing functionalities such as redirection and base URI detection.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

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
    $db = $GLOBALS['db'];

    $baseUris = array();
    $qType = $type ? $db->buildQuery("where type='%s'", $type) : null;
    // Sort DESC when no sort order is requested for non-greedy matching (e.g. /a/b/ before /a/)
    $qSort = $sort ? "order by base_uri asc" : "order by base_uri desc";
    $q = "select id, base_uri, type, title from sections $qType $qSort";
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
    $db = $GLOBALS['db'];

    $q = $db->buildQuery( "select base_uri from sections where type='%s' and id=%d", $type, $id );
    return $db->getSingleValue($q);
  }

  public static function findSection( $uri )
  {
    $db = $GLOBALS['db'];

    $baseUris = self::getBaseUris();
    if ( !count($baseUris) )
      return false;

    $trailingSlash = false;

    $result = self::matchBaseUri($uri);
    if ( !$result )
    {
      $trailingSlash = true;

      $result = self::matchBaseUri($uri,true);
      if ( !$result )
        return false;
    }

    Log::debug( "uri: $uri --> result: $result" );
    
    list($matchedUri, $remainder, $q ) = explode(":", $result);
    if ( !$matchedUri )
      return false;

		// Ensure trailing slash for all handler indices.
		// TODO: consider *removing* trailing slashes instead: collection
		// handlers are found either way, pages only without.  Removing them
		// allows graceful collection to page migration of URLs, adding them
		// does not.
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

		Log::debug( "match, type: ". $route->type. ", instanceId: ". $route->id. ", remainder: ". $remainder. ", q: ". $q );

    return $controller;
  }

  public static function findPage( $uri, $sectionId = 0 )
  {
    $db = $GLOBALS['db'];

    $uri = trim( $uri, '/' );

		if ( strstr($uri, '/') )
			return false;

    if ( !$uri )
      $uri = 'index';

    $q = $db->buildQuery( "SELECT id FROM articles a LEFT JOIN objects o ON o.object_id=a.object_id WHERE cname='%s' AND section_id=%d", $uri, $sectionId );
    $pageId = $db->getSingleValue($q);

    Log::debug( "section: $sectionId, cname: $uri --> pageId: $pageId" );

    if ( !$pageId )
      return false;

		$controller = Controller::factory('page');
		$controller->setInstanceId( $pageId );
		// $controller->setObjectId( $remainder );

		Log::debug( "match, type: page, instanceId: $pageId, remainder: , q: " );
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
      $db = $GLOBALS['db'];

      if ( $baseUri[0] != '/' )
        $baseUri = '/'. $baseUri;

      $q = $db->buildQuery( "INSERT INTO sections (id, base_uri, title, type) VALUES (%d, '%s', '%s', '%s') ON DUPLICATE KEY UPDATE type='%s', title='%s'",
        $instanceId, $baseUri, $title, $type, $type, $title
      );

      $db->query($q);
  }

}
  
?>