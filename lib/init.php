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

  // Find the Kiki install path
  $GLOBALS['kiki'] = str_replace( "/lib/init.php", "", __FILE__ );

  // FIXME: rjkcust, we shouldn't assume /home/www/server_name/ as site root.
  // FIXME: allow setups where Config, Log, Storage and Template are part of DOCUMENT_ROOT.
  $GLOBALS['root'] = "/home/www/". $_SERVER['SERVER_NAME'];

  function __autoload( $className )
  {
    // Try local customisations first, but fallback no Kiki's base classes,
    // allows developers to easily rewrite/extend.
    $local = $GLOBALS['root']. "/lib/". strtolower( str_replace("_", "/", $className) ). ".php";
    if ( file_exists($local) )
    {
      include_once "$local";
      return;
    }

    $kiki = $GLOBALS['kiki']. "/lib/". strtolower( str_replace("_", "/", $className) ). ".php";
    if ( file_exists($kiki) )
    {
      include_once "$kiki";
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
  $db = $GLOBALS['db'] = new Database( Config::$db );
  Config::loadDbConfig($db);

  // User(s)
  // FIXME: is this where we want this..
  $q = "select id from users where admin=1"; // and verified=1
  $rs = $db->query($q);
  if ( $rs && $db->numRows($rs) )
    while( $o = $db->fetchObject($rs) )
      Config::$adminUsers[] = $o->id;

  $user = $GLOBALS['user'] = new User();
  $user->authenticate();
?>