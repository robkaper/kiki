<?

/**
* @file htdocs/router.php
* Router script for Kiki. Should be called for all URI's where there is no
* direct local file match.  Relays content handling to various controllers
* based on URI configurations.
*
* @todo extend to minimise or totally make mod_rewrite optional
* @warning album URLs currently broken
*
* @todo purify this code, 1. identify the required controller (one of
* tinyurl, /kiki, /storage, dynamically loaded (base_uri's for blogs, pages
* and albums) or default fallback where everything is a 404), 2.  get
* remainder (optionally pre-split a la mvc into id/action/remainder), 3. 
* get content (view)
*
*
* @author Rob Kaper <http://robkaper.nl/>
* @section license_sec License
* Released under the terms of the MIT license.
*/

  include_once "../lib/init.php";

  Log::debug( "router.php: $reqUri" );

  // TinyURLs
  if ( preg_match('#^/[0-9a-zA-Z]{3}$#', $reqUri) )
  {
    Log::debug( "Controller::tinyUrl ". substr($reqUri, 1). "/". TinyUrl::lookup62(substr($reqUri, 1)) );
    Controller::redirect( TinyUrl::lookup62(substr($reqUri, 1)) ) && exit();
  }

  // Kiki base
  // @todo support DirectoryIndex equivalent
  else if ( preg_match('#^/kiki/(.*)#', $reqUri, $matches) )
  {
    Log::debug( "Controller: KIKI, remainder ". $matches[1] );
    $kikiFile = $GLOBALS['kiki']. "/htdocs/". $matches[1];
    if ( file_exists($kikiFile) )
    {
      $ext = Storage::getExtension($kikiFile);
      switch($ext)
      {
        case 'css':
        case 'gif':
        case 'jpg':
        case 'js':
        case 'png':
          header('Content-Type: '. Storage::getMimeType($ext) );
          exit( file_get_contents($kikiFile) );
          break;
        case 'php':
          include_once($kikiFile);
          exit();
          break;
        default:;
      }
      Log::debug( "unsupported extension $ext for kiki htdocs file $kikiFile" );
    }
    else
      Log::debug( "non-existant kikiFile $kikiFile" );
    // Controller::kikiBase($matches) && exit();
  }

  // Automatic thumbnails for storage files
  else if ( preg_match('#^/storage/([^\.]+)\.([^x]+)x([^\.]+)\.((c?))?#', $reqUri, $matches) )
  {
    Log::debug( "Controller::missingThumbnail" );
    Controller::missingThumbnail($matches) && exit();
  }

  // Album controller
  // @todo give albums base_uris just like blogs and move URIs outside of kiki base
  // @todo unify base_uris into single controller table

  // Article base URIs
  // @todo make this nicer
  $articleUris = array();
  $q = $db->buildQuery( "select id,base_uri from sections" );
  $rs = $db->query("select id,base_uri from sections");
  if ( $rs && $db->numrows($rs) )
    while( $o = $db->fetchObject($rs) )
      $articleUris[$o->base_uri] = $o->id;

  if ( count($articleUris) )
  {
    $pattern = "#^(". join("|", array_keys($articleUris)). ")([^/\?]+)(.*)#";
    $subject = $reqUri;
    $replace = "$1:$2";

    if ( $result = preg_filter($pattern, $replace, $subject) )
    {
      list($matchedUri, $remainder) = explode(":", $result);
      if ( $matchedUri )
      {
        $sectionId = $articleUris[$matchedUri];
        Log::debug( "Controller: BLOG section $sectionId, remainder $remainder" );
        // @todo generate the content and settings but don't let the controller create the page..
        Controller::articles( $sectionId, $remainder );
        // @todo don't exit here, in case of 404
        exit();
      }
    }
  }

  // @todo /kiki/album/
  // @todo content pages + test
  // $routes["contact"] = array( "type" => "page", "id" => "contact.php" );
  // @todo test physical pages
  // @todo test directoryindices
  // @todo test missing directoryindices (most notably /)
  // @todo custom db redirects + test

//RewriteRule ^/kiki/album(/)?$ /www/git/kiki/htdocs/album/index.php [L]
//RewriteRule ^/kiki/album/([^/]+)(/)?$ /www/git/kiki/htdocs/album/index.php [E=albumId:$1,L]
//RewriteRule ^/kiki/album/([^/]+)/([^/]+)(/)?$ /www/git/kiki/htdocs/album/index.php [E=albumId:$1,E=pictureId:$2,L]

  // Nothing found? 
  $page = new Page();
  $page->setTitle( "Kiki 404" );
  $page->setHttpStatus(404);
  $page->html();
  exit();
  
  /// @bug rjkcust, these routes should be generated or queried from the
  /// database (where all dynamic pages should be defined).  This data here
  /// is just for testing.

  /// @todo re-use this testing code when purifying the above
  function testRoute( $url )
  {
    global $routes;

    // echo "routing $url\n";

    $parsedUrl = parse_url( $url );
    $pathParts = array_values( array_filter( explode('/', $parsedUrl['path']) ) );

    $handler = array( "type" => "404default" );

    if ( !count($pathParts) )
    {
      // echo "no pathParts\n";
      $handler = array( "type" => "mainpage" );
      // echo "returning handler: ". print_r($handler, true). "\n";
      return $handler;
    }

    $curRoutes = $routes;
    $depth = 0;
    $pathDepth = count($pathParts);
    foreach( $pathParts as $pathPart )
    {
      $depth++;
      if ( isset($curRoutes[$pathPart]) )
      {
        // echo "found $pathPart in route, setting handler\n";
        $handler = $curRoutes[$pathPart];
        if ( !isset($handler["routes"]) )
          $handler["routes"] = array();

        // echo "depth: $depth, pathdepth: $pathDepth\n";

        if ( $depth == $pathDepth )
        {
          // echo "depth and pathDepth match, return\n";
          // echo "returning handler: ". print_r($handler, true). "\n";
          return $handler;
        }

        // echo "preparing next depth, setting new curRoutes\n";
        $curRoutes = $handler["routes"];
      }
      else
      {
        // echo "part $pathPart not found in route, returning handler plus remainder of path\n";
        if ( $handler["type"]=="blog" )
        {
          // blog code parses remainder itself
        }
        else
        {
          // extra nonsense in the path by default should result in a 404
          $handler = array( "type" => "404" );
        }
        $handler["remainder"] = join( "/", array_slice( $pathParts, $depth-1 ) );
        // echo "remainder path: ". print_r($handler["remainder"], true). "\n";
        // echo "returning handler: ". print_r($handler, true). "\n";
        return $handler;
      }
    }

    echo "end of loop??\n";
    // echo "returning handler: ". print_r($handler, true). "\n";
    return $handler;
  }

  $handler = testRoute( $reqUri );

  if ( !isset($handler["id"]) )
    $handler["id"] = null;
  if ( !isset($handler["remainder"]) )
    $handler["remainder"] = null;

  echo "url: $url\n";
  echo "handler type:$handler[type], id:$handler[id], remainder:$handler[remainder]\n";
?>
</pre>
<?
  $page->endContent();

  switch( $handler["type"] )
  {
  case "mainpage":
  case "page":
  case "blog":
    $page->setHttpStatus(200);
    break;
  case "404":
  default:
    $page->setTitle( "Kiki 404" );
    $page->setHttpStatus(404);
    break;
  }

  $page->html();
?>
