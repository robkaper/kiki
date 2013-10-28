<?php

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

  // Optimisation: pre-recognise built-in static files (skips i18n, database
  // and user handling in init.php)
  $staticFile = preg_match( '#^/kiki/(.*)\.(css|gif|jpg|js|png)#', $_SERVER['SCRIPT_URL'] );

  require_once preg_replace('~/htdocs/(.*)\.php~', '/lib/init.php', __FILE__ );

	if ( !$staticFile )
	{
	  Log::debug( "START router: $requestPath", $staticFile );
	}

  // Redirect requests with parameters we don't want visible for the user or
  // Analytics.
	//
	// These are inherent to the built-in Facebook login/app support. Checking
	// for the fragment parameter might be deprecated, that seems to be a
	// remnant from when all Apps were embedded Pages on Facebook using FBML,
	// that no longer seems to be the default how apps work, with Facebook
	// heavily investing into the single sign-on and API features of Apps.
	//
	// State and code are sent back after login and might have to be analysed
	// because some states may require handling instead of a redirect.

  if ( isset($_GET['fb_xd_fragment']) || (isset($_GET['state']) && isset($_GET['code'])) )
  {
    Router::redirect( $_SERVER['SCRIPT_URL'], 301 ) && exit();
  }

  $controller = null;

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

  if ( preg_match('#^/[0-9a-zA-Z]{3}$#', $requestPath) )
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

  else if ( !($controller = Router::findPage($requestPath)) && !($controller = Router::findSection($requestPath)) )
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

    $controller = new Controller_404();
	}

  $controller->exec();

  $controller->output();

	if ( !$staticFile )
	{
	  Log::debug( "END router ". $controller->status(). ": $requestPath [". $controller->type(). "][". $controller->instanceId(). "][". $controller->objectId(). "]" );

		foreach( Log::getTimers() as $timer => $time )
			Log::debug( "timer $timer: ". sprintf("%3.7f", $time) );
	}
