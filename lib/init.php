<?php

  ini_set( 'display_errors', true );

  ini_set( 'session.cookie_secure', true );
  ini_set( 'session.cookie_httponly', true );
  ini_set( 'session.cookie_samesite', 'Strict' );

/**
 * Initialisation/bootloader script for Kiki.
 *
 * This file should be included (in fact, required) on top of all project
 * files.  It sets paths, provides the autoloader and initialises
 * configuration settings, logging, the database object and user
 * authentication.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2010-2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

  // Find and store the Kiki install path
  $installPath = str_replace( "/lib/init.php", "", __FILE__ );
  include_once $installPath. "/lib/core.php";
  Kiki\Core::setInstallPath( $installPath );

  if ( php_sapi_name() == "cli" )
  {
    // FIXME: we shouldn't assume /var/www/server_name/ as site root.
    Kiki\Core::setRootPath( "/var/www/". $_SERVER['SERVER_NAME'] );
  }
  else
  {
    $rootPath = str_replace( "/htdocs", "", $_SERVER['DOCUMENT_ROOT'] );
    Kiki\Core::setRootPath( $rootPath );
  }
  $installPath = str_replace( "/lib/init.php", "", __FILE__ );

  // Classhelper already needs this
  include_once $installPath. "/lib/config.php";

  spl_autoload_register( function($className)
  {
    include_once Kiki\Core::getInstallPath(). "/lib/classhelper.php";

    $classPaths = array();
    // echo "<br>1$className";
    if ( Kiki\ClassHelper::isInKikiNamespace($className) )
    {
      // echo "<br>2$className";
      $classPaths[] = Kiki\Core::getInstallPath();
    }
    else if( Kiki\ClassHelper::isInCustomNamespace($className) )
    {
      // echo "<br>3$className";
      $classPaths[] = Kiki\Core::getRootPath();
    }
    else
    {
      // Don't handle other namespaces.
      $classPaths[Kiki\Config::$namespace] = Kiki\Core::getRootPath();
      $classPaths['Kiki'] = Kiki\Core::getInstallPath();
      // return;
    }

    $trueClassName = $className;
    foreach( $classPaths as $key => $classPath )
    {
      $className = $key ? ($key. '\\'. $trueClassName) : $trueClassName;
      $classFile = Kiki\ClassHelper::classToFile($className);
      // echo "<br>4$classPath/lib/$classFile";

      $includeFile = $classPath. "/lib/". $classFile;
      if ( file_exists($includeFile) )
      {
        include_once "$includeFile";

        // if ( class_exists($className, false) || class_exists('Kiki\\'. $className, false) )
        if ( class_exists($className) )
        {
          // echo "<br>5$className";
          return;
        }

        trigger_error( sprintf( "file %s should but does not define class %s", $includeFile, $className ), E_USER_ERROR );
        exit;
      }
      // echo "<br>6$className, $trueClassName";
    }

    if ( !class_exists($className, false) && !class_exists('Kiki\\'. $className, false) )
    {
      // trigger_error( sprintf( "could not load class %s from local path %s nor %s from Kiki install path %s", $className, Kiki\Core::getRootPath(), 'Kiki\\'. $className, Kiki\Core::getInstallPath() ), E_USER_ERROR );
      // exit;
    }
  } );

  use Kiki\Core;
  use Kiki\Config;
  use Kiki\Log;

  use Kiki\I18n;

  // SCRIPT_URL, not REQUEST_URI. Query parameters should be handled from $_GET explicitely.
  if ( php_sapi_name() == "cli" )
    $requestPath = $argv[2] ?? null;
  else
  {
    $urlParts = parse_url( $_SERVER['REQUEST_URI'] );
    $requestPath = $urlParts['path'] ?? null;
  }

  Log::init();
  Config::init();

  if ( Config::$defaultTimezone )
    date_default_timezone_set( Config::$defaultTimezone );

  Log::beginTimer( $requestPath );

  // Optimisation: pre-recognise built-in static files (skips i18n, database
  // and user handling in init.php)
  //
  // Init has to be called though, for semi-static files such as Thumbnails
  // which require the class autoloader, for example.
  //
  // TODO: remove?  static files can be excluded from web server
  // configuration and needn't be served by framework
  $staticFile = preg_match( '#^/kiki/(.*)\.(css|gif|jpg|js|png)#', $requestPath );
  if ( isset($staticFile) && $staticFile )
    return; 

  I18n::init();

  // Set locale when URI starts with a two-letter country code.
  // TODO: support locale setting by TLD or subdomain.
  // TODO: adjust element lang attributes based on chosen locale.
  if ( preg_match('#^/([a-zA-Z]{2})/#', $requestPath, $matches) )
  {
    if ( I18n::setLocale($matches[1]) )
    {
      // TODO: finish the Kiki controller and/or let the rewrite rule take
      // locale URIs into account.  Also, rewrite Kiki\Config::kikiPrefix based on
      // locale.
      $requestPath = substr( $requestPath, 3 );
    }
  }
  else
  {
    I18n::setLocale( Kiki\Config::$language );
  }

  // Database
  $db = Core::getDb();

  // Connection services
  Config::loadConnectionServices();

  // Memcache
  Core::getMemcache();

  // User(s)
  $user = Core::getUser();
  $user->authenticate();
