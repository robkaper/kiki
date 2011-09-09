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
  public static function getBaseURIs()
  {
    $db = $GLOBALS['db'];

    $baseUris = array();
    $q = "select id, base_uri, type, instance_id from router_base_uris";
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
    // @todo accept pages, otherwise add and 301
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