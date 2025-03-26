<?php

namespace Kiki;

// @package Kiki
// @author Rob Kaper <https://robkaper.nl/>
// @copyright 2011-2023 Rob Kaper <https://robkaper.nl/>
// @license Released under the terms of the MIT license.
//
// Router script for Kiki. By the grace of Nginx's try_files or Apache's
// mod_rewrite, it should be called for all URI's where there is no direct
// local file or directory match in the document root.
//
// Scans the routing array to find a controller, with some built-in reserved
// patterns as fallback and ultimately defaults to the 404 controller if
// nothing matches.
//
// Finally, the controller is excecuted and output is generated.
//
// There are some legacy assumptions in here and in the controllers
// themselves, expecting a HTTP environment and defaulting to HTML output.
// A main exception to this is a CLI environment: the output will then be a
// print_r of template data. For alternate content type, controllers should
// set a mime type in altContentType and output content directly with a hard
// exit.

require_once preg_replace('~/htdocs/(.*)\.php~', '/lib/init.php', __FILE__ );

Router::detectAltRoute();
$altRoute = Router::altRoute();

Log::debug( sprintf( 'START router [alt:%s][path:%s][staticFile:%d]',
  $altRoute,
  $requestPath,
  (int) $staticFile
) );

$controller = null;
$matches = [];

$routeConfig = $altRoute ? ( Config::$routing[$altRoute] ?? Config::$routing ) : Config::$routing;
if ( is_array($routeConfig) )
foreach( $routeConfig as $route => $routeController )
{
  // echo "<br>parsing routing [$route][$requestPath]";
  if ( preg_match( "#^$route(/(.*))?$#", $requestPath, $matches ) )
  {
    // echo "<br>found route from routing config $route". PHP_EOL;
    $controller = is_array($routeController) ? $routeController[0] : $routeController;
    $capture = null;
    $action = is_array($routeController) ? ( $routeController[1] ?? null ) : null;
    $context = $routeController['context'] ?? null;
    // Log::debug( print_r( $matches, true ) );
    // echo "<br>[". count($matches). "]";
    switch( count($matches) )
    {
      case 0:
        // SNH, because regexp would've returned false
        $controller = 'NotFound404';
        break;

      case 1:
        // Exact match, no capture in route itself
        break;

      case 2:
        // Exact match, capture in route itself
        $capture = $matches[1];
        break;

      case 3:
        // SNH: Match, but action(s) given
        $action = $matches[2];
        break;

      case 4:
        // Match, capture in route itself, action(s) given
        $capture = $matches[1];
        $action = $matches[3];

        if ( !$action && $matches[2] )
        {
          $url = sprintf( '%s%s%s',
            rtrim($requestPath, '/'),
            isset($urlParts['query']) ? '?' : null,
            $urlParts['query'] ?? null
          );
          Router::redirect( $url, 301 );
        }
        break;
    }

    Log::debug( "routing table match [controller:$controller][capture:$capture][action:$action] from [path:$requestPath][route:$route][matches:". count($matches). "]" );

    $controller = Controller::factory($controller);
    if ( $context && $capture )
    {
      $controller->setContext($context);
      $controller->setAction($capture);
    }
    if ( $context )
      $controller->setContext($context);
    else if ( $capture )
      $controller->setContext($capture);
    if ( $action )
      $controller->setAction($action);
    break;
  }
}

$phpFile = !$controller ? Core::getRootPath(). "/htdocs${requestPath}.php" : null;
// Sourcing this file itself or non-existing ones makes no sense.
if ( $phpFile == $_SERVER['SCRIPT_FILENAME'] || !file_exists($phpFile) )
  $phpFile = null;

// Dummy entry to avoid further handling when a controller from Config was found
if ( $controller )
{
}

// Source local PHP files
else if ( $phpFile )
{
  Log::debug( "SOURCE router: $phpFile" );
  include_once "$phpFile";
}

// Kiki built-in modules and files
//
// Could be matched dynamically outside of Kiki as well (configurable in
// database, section should perhaps have a required flag for system modules
// that cannot be removed).  And all sorts of static files, from PHP scripts
// (CMS modules are not true modules yet lacking more sophisticated action
// and view recognition in the controller) to built-in stylesheets,
// Javascript and images.

else if ( preg_match('#^/kiki/(.*)#', $requestPath, $matches) )
{
  $moduleMatches = array();
  if ( preg_match('#^(album)/(.*)#', $matches[1], $moduleMatches) )
  {
    // This targets individual albums. For proper referencing,
    // Album container controllers should be written.  These
    // could then be inserted here as default locations within Kiki
    // without setup, but there's no reason these should eventually not be
    // part of the database in the sections table.  Kiki does support that
    // already, after all, just the CMS is lacking.
    $controller = Controller::factory( $moduleMatches[1] );
    $controller->setObjectId( $moduleMatches[2] );
  }
  else
  {
    // For all static files
    $controller = Controller::factory('Kiki');
    $controller->setObjectId( $matches[1] );
  }
}

// Automatic thumbnails for storage files
//
// This is one of the nicer and more useful features built-in features of
// Kiki: templates can request any dimension of storage items (currently
// Storage::getThumbnail only supports images) and this controller will
// simply create it.  This makes the very first request to such a resource
// more expensive, but also makes it much easier to actually make changes:
// any newly requested format instantly works and the cache can be safely
// cleared at any time.

else if ( preg_match('#^/storage/([^\.]+)\.([^x]+)x([^\.]+)(\.(c))?\.(.*)#', $requestPath, $matches) )
//else if ( preg_match('#^/storage/#', $requestPath, $matches) )
{
  $controller = Controller::factory('Thumbnails');
  $controller->setObjectId($matches);
}

// Check if URI contains a base handled by a dynamic controller.
//
// The findPage and findSection separation is mainly due to giving page
// URLs preference over sections when both exist and quite possibly
// unnecessary legacy for when the Page controller didn't use the same
// routing table as the Sections (and actually all modules, now that
// sections are truly dynamic in loading any Controller type.

// FIXME: disabled for now.  Would be nice if users can create routed items
// from the database, but need to rethink all of that now that main routing
// has been moved to Config array
else // if ( !($controller = Router::findPage($requestPath)) && !($controller = Router::findSection($requestPath)) )
{
  // Nothing? 404.
  $controller = Controller::factory("NotFound404");
}

if ( !$phpFile && $controller )
{
  $controller->exec();
  $controller->postExec();
  $controller->output();
}

if ( !$staticFile )
{
  $timers = Log::getTimers();
  $timersStr = null;
  if ( count($timers) > 0 )
  {
    $times = array();
    foreach( $timers  as $timer => $time )
      $times[] = sprintf("%s: %.7f", $timer, $time);
    $timersStr = implode( ", ", $times );
  }

  if ( $controller )
    Log::debug( "END router ". $controller->status(). ": $requestPath [". $controller->class(). "->". $controller->method(). "][". $controller->template(). "][". $controller->instanceId(). "][". $controller->objectId(). "], timers[". $timersStr. "]" );
}
