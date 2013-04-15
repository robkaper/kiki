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

  // Find and store the Kiki install path
  $installPath = str_replace( "/lib/init.php", "", __FILE__ );
	include_once $installPath. "/lib/kiki.php";
	Kiki::setInstallPath( $installPath );

  // FIXME: rjkcust, we shouldn't assume /home/www/server_name/ as site root.
  // FIXME: allow setups where Config, Log, Storage and Template are part of DOCUMENT_ROOT.
  Kiki::setRootPath( "/home/www/". $_SERVER['SERVER_NAME'] );

  function __autoload( $className )
  {
		include_once Kiki::getInstallPath(). "/lib/classhelper.php";

		$classFile = ClassHelper::classToFile($className);

    // Try local customisations first, but fallback no Kiki's base classes,
    // allows developers to easily rewrite/extend.
		$classPaths = array( Kiki::getRootPath(), Kiki::getInstallPath() );

		foreach( $classPaths as $classPath )
		{
			$includeFile = $classPath. "/lib/". $classFile;
	    if ( file_exists($includeFile) )
	    {
  	    include_once "$includeFile";

				if ( class_exists($className, false) )
		      return;
    	}
		}

		if ( !class_exists($className, false) )
		{
			Log::error( "could not load class $className from local path nor Kiki install path" );
		}
  }

  $reqUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $argv[0];

  Log::init();
  Config::init();
    
  if ( isset($staticFile) && $staticFile )
    return; 

  I18n::init();

  // Set locale when URI starts with a two-letter country code.
  // TODO: support locale setting by TLD or subdomain.
  // TODO: adjust element lang attributes based on chosen locale.
  if ( preg_match('#^/([a-zA-Z]{2})/#', $reqUri, $matches) )
  {
    if ( I18n::setLocale($matches[1]) )
    {
      // TODO: finish the Kiki controller and/or let the rewrite rule take
      // locale URIs into account.  Also, rewrite Config::kikiPrefix based on
      // locale.
      $reqUri = substr( $reqUri, 3 );
    }
  }
  else
  {
    I18n::setLocale( Config::$language );
  }

  // Database
  $db = Kiki::getDb();
  Config::loadDbConfig($db);

  // User(s)
  // FIXME: is this where we want this..
  $q = "select id from users where admin=1"; // and verified=1
  $rs = $db->query($q);
  if ( $rs && $db->numRows($rs) )
    while( $o = $db->fetchObject($rs) )
      Config::$adminUsers[] = $o->id;

  $user = Kiki::getUser();
  $user->authenticate();
?>