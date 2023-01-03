<?php

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

	// echo "<pre>";

  // Find and store the Kiki install path
  $installPath = str_replace( "/lib/init.php", "", __FILE__ );
  include_once $installPath. "/lib/core.php";
  Kiki\Core::setInstallPath( $installPath );

  // FIXME: rjkcust, we shouldn't assume /var/www/server_name/ as site root.
  // FIXME: allow setups where Config, Log, Storage and Template are part of DOCUMENT_ROOT.
  Kiki\Core::setRootPath( "/var/www/". $_SERVER['SERVER_NAME'] );

  function __autoload( $className )
  {
    include_once Kiki\Core::getInstallPath(). "/lib/classhelper.php";

    if ( !Kiki\ClassHelper::isInKikiNamespace($className) )
      return;
   
    $classFile = Kiki\ClassHelper::classToFile($className);

    // Try local customisations first, but fallback no Kiki's base classes,
    // allows developers to easily rewrite/extend.
    $classPaths = array( Kiki\Core::getRootPath(), Kiki\Core::getInstallPath() );

    foreach( $classPaths as $classPath )
    {
      $includeFile = $classPath. "/lib/". $classFile;
      // echo "$includeFile". PHP_EOL;
      if ( file_exists($includeFile) )
      {
        include_once "$includeFile";

        if ( class_exists($className, false) || class_exists('Kiki\\'. $className, false) )
          return;

				trigger_error( sprintf( "file %s should but does not define class %s", $includeFile, $className ), E_USER_ERROR );
				exit;
    	}
		}

		if ( !class_exists($className, false) && !class_exists('Kiki\\'. $className, false) )
		{
				trigger_error( sprintf( "could not load class %s from local path %s nor Kiki install path %s", $className, Kiki\Core::getRootPath(), Kiki\Core::getInstallPath() ), E_USER_ERROR );
				exit;
		}
  }

	// SCRIPT_URL, not REQUEST_URI. Query parameters should be handled from $_GET explicitely.
  $requestPath = isset($_SERVER['SCRIPT_URL']) ? $_SERVER['SCRIPT_URL'] : isset($argv) ? $argv[0] : null;
  if ( !$requestPath )
  {
    $urlParts = parse_url( $_SERVER['REQUEST_URI'] );
    $requestPath = $urlParts['path'];
  }

  Kiki\Log::init();
  Kiki\Config::init();
  
  if ( isset($staticFile) && $staticFile )
    return; 

  Kiki\I18n::init();

  // Set locale when URI starts with a two-letter country code.
  // TODO: support locale setting by TLD or subdomain.
  // TODO: adjust element lang attributes based on chosen locale.
  if ( preg_match('#^/([a-zA-Z]{2})/#', $requestPath, $matches) )
  {
    if ( Kiki\I18n::setLocale($matches[1]) )
    {
      // TODO: finish the Kiki controller and/or let the rewrite rule take
      // locale URIs into account.  Also, rewrite Kiki\Config::kikiPrefix based on
      // locale.
      $requestPath = substr( $requestPath, 3 );
    }
  }
  else
  {
    Kiki\I18n::setLocale( Kiki\Config::$language );
  }

  // Database
  $db = Kiki\Core::getDb();
  Kiki\Config::loadDbConfig($db);

	// Memcache
	Kiki\Core::getMemcache();

  // User(s)
  // FIXME: is this where we want this..
  $q = "select id from users where admin=1"; // and verified=1
  $rs = $db->query($q);
  if ( $rs && $db->numRows($rs) )
    while( $o = $db->fetchObject($rs) )
      Kiki\Config::$adminUsers[] = $o->id;

  $user = Kiki\Core::getUser();
  $user->authenticate();
