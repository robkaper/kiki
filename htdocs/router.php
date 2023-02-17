<?php

namespace Kiki;

/**
 * Router script for Kiki. By the grace of mod_rewrite, it is called for all
 * URI's where there is no direct local file or directory match in the
 * document root.
 * 
 * Finds a controller based on some built-in reserved patterns and database
 * section (shouldn't that just be called routing) entries and then executes
 * the controller and let it output content.
 *
 * There are some assumptions here and in the controllers that all requests
 * return content to a HTTP environment and HTML content (whereever template
 * content contains HTML), Kiki should detect PHP_CLI and optional other
 * request methods to return sane data even outside web requests.  This can
 * be a powerful debugging feature or step towards refactoring so that
 * extensions in the request can also lead to a different formatting of
 * output.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2011-2013 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 *
 * @todo extend to minimise mod_rewrite even further, or totally make mod_rewrite optional
 */

require_once preg_replace('~/htdocs/(.*)\.php~', '/lib/init.php', __FILE__ );

if ( !$staticFile )
  Log::debug( "START router: $requestPath", $staticFile );
else
  Log::debug( "START router: $requestPath", $staticFile );

$controller = null;

$matches = array();
foreach( Config::$routing as $route => $routeController )
{
  // echo "<br>parsing routing [$route][$requestPath]";
  if ( preg_match( "#^$route(/(.*))?$#", $requestPath, $matches ) )
  {
    // echo "<br>found route from routing config $route". PHP_EOL;
    $controller = is_array($routeController) ? $routeController[0] : $routeController;
    $capture = null;
    $action = is_array($routeController) ? ( $routeController[1] ?? null ) : null;
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
    if ( $capture )
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

// Dummy entry to avoid legacy handling since new routing from Config found a controller already
if ( $controller )
{
}

// FIXME: This feels ugly, but I cannot seem to get nginx to handle extensionless
// PHP files while also falling back to this router file.
// nginx strips ugly stuff like ../ so this might be safe, but not sure
// what happens in Apache.
else if ( $phpFile )
{
  Log::debug( "SOURCE router: $phpFile" );
  include_once "$phpFile";
}

// TinyURLs
//
// @warning This hardcoded pattern prevents all other controllers from
// handling URLs of exactly three alphanumeric characters.  This is only
// really a problem for top-level pages, but a serious one.  Simply
// degrading the priority would on the other hand could leave some
// tinyURLs inaccessible.  That would seems closer to a solution, but
// would also be harder to predict and trace.  Multi-domain use for
// tinyURLs is not yet supported, but this would be a nice feature anyway
// that could solve this (the tinyURL domain would be recognised here in
// the router).
//
// A temporary fix could be to let the page saving handler check the cname
// for top-level pages and force the user to choose between picking
// another name, or showing the target and opting to remove or rename the
// tinyURL reference.

else if ( preg_match('#^/[0-9a-zA-Z]{3}$#', $requestPath) )
{
  $controller = Controller::factory('TinyUrl');
  $controller->setObjectId( substr($requestPath, 1) );
}

// Kiki built-in modules and files
//
// Two built-in modules that could be matched dynamically outside of Kiki
// as well (configurable in database, section should perhaps have a
// required flag for system modules that cannot be removed).  And all
// sorts of static files, from PHP scripts (CMS modules are not true
// modules yet lacking more sophisticated action and view recognition in
// the controller) to built-in stylesheets, Javascript and images.

else if ( preg_match('#^/kiki/(.*)#', $requestPath, $matches) )
{
  if ( preg_match('#^(album|event)/(.*)#', $matches[1], $moduleMatches) )
  {
    // This targets individual albums and events. For proper referencing,
    // Albums and Events container controllers should be written.  These
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
// Storage::generateThumb only supports images) and this controller will
// simply create it.  This makes the very first request to such a resource
// more expensive, but also makes it much easier to actually make changes:
// any newly requested format instantly works and the cache can be safely
// cleared at any time.

else if ( preg_match('#^/storage/([^\.]+)\.([^x]+)x([^\.]+)\.((c?))?#', $requestPath, $matches) )
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
// has been moved *away*
else // if ( !($controller = Router::findPage($requestPath)) && !($controller = Router::findSection($requestPath)) )
{
  // Nothing? 404.
  //
  // @todo Add an alias controller here, functioning quite like the
  // tinyURL controller but then with database stored URI aliases.
  //
  // The CMS module (and possibly the site itself when an administrator is
  // logged in) should then warn about aliases that conflict with
  // top-level pages (and vice versa), and possibly tinyURLs but those
  // might need a technical solution beyond risk management due to their
  // automatic generation.

  $controller = new Controller\NotFound404();
}

if ( !$phpFile && $controller )
{
  $controller->exec();
  $controller->output();
}

if ( !$staticFile )
{
  if ( $controller )
    Log::debug( "END router ". $controller->status(). ": $requestPath [". $controller->type(). "][". $controller->instanceId(). "][". $controller->objectId(). "]" );

  $timers = Log::getTimers();
  if ( count($timers) > 0 )
  {
    $times = array();
    foreach( $timers  as $timer => $time )
      $times[] = sprintf("%s: %3.7f", $timer, $time);
  Log::debug( "timers: ". implode( ", ", $times ) );
  }
}
