<?

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
  public static function redirect( $url, $permanent = true )
  {
    if ( !$url )
      return false;

    header( "Location: $url", true, $permanent ? 301 : 302 );
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
    $qSort = $sort ? "order by base_uri asc" : null;
    $q = "select id, base_uri, type, instance_id from router_base_uris $qType $qSort";
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
  public static function getBaseUri( $type, $instanceId )
  {
    $db = $GLOBALS['db'];

    $q = $db->buildQuery( "select base_uri from router_base_uris where type='%s' and instance_id=%d", $type, $instanceId );
    return $db->getSingleValue($q);
  }

  public static function findHandler( $uri )
  {
    $db = $GLOBALS['db'];

    $baseUris = self::getBaseUris();
    if ( !count($baseUris) )
      return false;

    // No trailing slash
    // TODO: accept pages, otherwise add and 301
    $trailingSlash = false;
    $result = self::matchBaseUri($uri);
    if ( $result )
    {
      // Log::debug( "Router-/ $uri, result: $result, accept: pages=show, else=add and 301" );
    }
    else
    {
      $trailingSlash = true;
      $result = self::matchBaseUri($uri,true);
      if ( !$result )
        return false;
      // Log::debug( "Router+/ $uri, result: $result, accept: albums,articles," );
    }
    
    list($matchedUri, $remainder, $q ) = explode(":", $result);
    if ( !$matchedUri )
      return false;

    $route = $baseUris[$matchedUri];

    $handler = new stdClass;
    $handler->matchedUri = $matchedUri;
    $handler->type = $route->type;
    $handler->instanceId = $route->instance_id;
    $handler->trailingSlash = $trailingSlash;
    $handler->remainder = $remainder;
    $handler->q = $q;

    // Log::debug( "Router::findHandler, type:". $handler->type. ", /:". $handler->trailingSlash. ", instanceId:". $handler->instanceId. ", remainder:". $remainder. ", q:$q" );

    return $handler;
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
    Log::debug( "$uri, pattern $pattern result: ". print_r($result, true) );

    return $result;
  }
}
  
?>