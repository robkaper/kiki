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
  // @todo support DirectoryIndex equivalent, or else simply rely on
  // mod_rewrite and remove this
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

  // Check if URI contains a base handled by a dynamic controller
  $handler = Router::findHandler($reqUri);
  if ( $handler )
  {
    if ( !$handler->trailingSlash )
    {
      if ( $handler->type=='page' )
      {
        Log::debug( "Router: show page ". $handler->instanceId );
       // @todo page handler
      }
      else
      {
        $url = $handler->matchedUri. "/". $handler->remainder. $handler->q;
        Log::debug( "Router: 301 $url" );
        Controller::redirect($url, true) && exit();
      }
    }
    else
    {
      // @todo replace with a nice factory
      if ( $handler->type=='articles' )
      {
        Log::debug( "Router: show ". $handler->type. " collection ". $handler->instanceId. " ". $handler->remainder. " ". $handler->q ); 
        // @todo generate the content and settings but don't let the controller create the page..
        Controller::articles( $handler->instanceId, $handler->remainder );
        // @todo don't exit here, in case of 404
        exit();
      }
    }
  }

  // @todo /kiki/album/
  // @todo content pages + test
  // $routes["contact"] = array( "type" => "page", "id" => "contact.php" );
  // @todo test missing directoryindices (most notably / when moving content to database)
  // @todo custom db redirects + test

//RewriteRule ^/kiki/album(/)?$ /www/git/kiki/htdocs/album/index.php [L]
//RewriteRule ^/kiki/album/([^/]+)(/)?$ /www/git/kiki/htdocs/album/index.php [E=albumId:$1,L]
//RewriteRule ^/kiki/album/([^/]+)/([^/]+)(/)?$ /www/git/kiki/htdocs/album/index.php [E=albumId:$1,E=pictureId:$2,L]

  // Nothing found? 
  $page = new Page();
  $page->setHttpStatus(404);
  $page->setTitle( "404! Vierhonderdvier! Four hundred and four! Cuatrocientoscuatro! N&eacute;gysz&aacute;mn&eacute;vn&eacute; Nelj&auml;sataanelj&auml;" );
  $page->beginContent();
?>
<p>
Tja, dat is pech hebben. Deze pagina bestaat dus (niet) meer.</p>
<?
  $page->endContent();
  $page->html();

  // @fixme rjkcust for debugging
  $msg = $reqUri. "\nhandler: ". print_r( $handler, true ). "\n\n_REQUEST: ". print_r( $_REQUEST, true ). "\n\n_SERVER: ". print_r( $_SERVER, true ); 
  mail( "rob@robkaper.nl", "Kiki 503/404: $reqUri", $msg );
?>
