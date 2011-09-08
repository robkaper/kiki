<?

/**
* @class Router
* Router class. Sort of.
* @author Rob Kaper <http://robkaper.nl/>
* @section license_sec License
* Released under the terms of the MIT license.
*/

class Router
{
  private static function getBaseURIs()
  {
    $db = $GLOBALS['db'];

    $baseUris = array();
    $q = "select id, base_uri, type, instance_id from router_base_uris";
    $rs = $db->query($q);
    if ( $rs && $db->numrows($rs) )
      while( $o = $db->fetchObject($rs) )
        $baseUris[$o->base_uri] = $o;

    return $baseUris;
  }

  public static function getBaseUri( $type, $instanceId )
  {
    $q = $db->buildQuery( "select base_uri from router_base_uris where type='%s' and instance_id=%d", $type, $instanceId );
    return $db->getSingleValue($q);
  }

  public static function findHandler( $uri )
  {
    $db = $GLOBALS['db'];

    $baseUris = self::getBaseUris();
    if ( !count($baseUris) )
      return false;

    $pattern = "#^(". join("|", array_keys($baseUris)). ")([^/\?]+)?(.*)#";
    $subject = $uri;
    $replace = "$1:$2";

    if ( !($result = preg_filter($pattern, $replace, $subject)) )
      return false;

    list($matchedUri, $remainder) = explode(":", $result);
    if ( !$matchedUri )
      return false;

    $route = $baseUris[$matchedUri];

    $handler = new stdClass;
    $handler->type = $route->type;
    $handler->instanceId = $route->instance_id;
    $handler->remainder = $remainder;

    Log::debug( "Router::findHandler, type ". $handler->type. ", instanceId ". $handler->instanceId. ", remainder ". $remainder );

    return $handler;
  }
}
  
?>