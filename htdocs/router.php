<?

/**
 * Router script for Kiki. Should be called for all URI's where there is no
 * direct local file match.  Relays content handling to various controllers
 * based on URI configurations.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 *
 * @todo extend to minimise or totally make mod_rewrite optional
 * @warning album URLs currently broken
 */

  include_once "../lib/init.php";

  Log::debug( "router.php: $reqUri" );

  $controller = null;

  // TinyURLs
  if ( preg_match('#^/[0-9a-zA-Z]{3}$#', $reqUri) )
  {
    $controller = Controller::factory('TinyUrl');
    $controller->setObjectId( substr($reqUri, 1) );
  }

  // Kiki base files
  else if ( preg_match('#^/kiki/(.*)#', $reqUri, $matches) )
  {
    $controller = Controller::factory('Kiki');
    $controller->setObjectId( $matches[1] );
  }

  // Automatic thumbnails for storage files
  else if ( preg_match('#^/storage/([^\.]+)\.([^x]+)x([^\.]+)\.((c?))?#', $reqUri, $matches) )
  {
    $controller = Controller::factory('Thumbnails');
    $controller->setObjectId($matches);
  }

  // Check if URI contains a base handled by a dynamic controller
  else if ( $handler = Router::findHandler($reqUri) )
  {
    // Ensure trailing slash for all content except pages
    // FIXME: Find only collection controllers here, to keep baseURI matching simple, move page controller downwards
    if ( !$handler->trailingSlash && $handler->type != 'page' )
    {
      $url = $handler->matchedUri. "/". $handler->remainder. $handler->q;
      Router::redirect($url, true) && exit();
    }
       
    // TODO: this is nearly one on one, might as well let findHandler return the right controller..
    $controller = Controller::factory($handler->type);
    $controller->setInstanceId($handler->instanceId);
    $controller->setObjectId($handler->remainder);
  }

  // Nothing? Default controller (404 page)
  else
    $controller = new Controller();

  // TODO: /kiki/album/
  //RewriteRule ^/kiki/album(/)?$ /www/git/kiki/htdocs/album/index.php [L]
  //RewriteRule ^/kiki/album/([^/]+)(/)?$ /www/git/kiki/htdocs/album/index.php [E=albumId:$1,L]
  //RewriteRule ^/kiki/album/([^/]+)/([^/]+)(/)?$ /www/git/kiki/htdocs/album/index.php [E=albumId:$1,E=pictureId:$2,L]
  // TODO: test missing directoryindices (most notably / when moving content to database)

  $controller->exec();
  // Log::debug( print_r($controller, true) );

  if ( $controller->status() == 301 )
    Router::redirect($controller->content(), true) && exit();

  $page = new Page();
  $page->setHttpStatus( $controller->status() );
  $page->setTitle( $controller->title() );
  $page->setBodyTemplate( $controller->template() );
  $page->setContent( $controller->content() );
  $page->html();
?>
