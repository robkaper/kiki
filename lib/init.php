<?
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

  // FIXME: rjkcust, we shouldn't assume /www/server_name/ as site root. 
  // Perhaps parent of $_SERVER['DOCUMENT_ROOT'], still an assumption but
  // less specific.  We should also support having the root for these inside
  // the document root (and provide proper htaccess rules), perhaps as
  // fallback.  Used by Config, Log, Storage and Template classes.
  $GLOBALS['root'] = "/www/". $_SERVER['SERVER_NAME'];

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

  Log::init();
  Config::init();

  $reqUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $argv[0];
  if ( !preg_match( '#/(im).php(\?)?#', $reqUri ) )
  {
    $reqMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'CMD';
    $log = sprintf( "%-4s: %s", $reqMethod, $reqUri );
    Log::debug( $log );
  }

  // HACK: fb_xd_fragment parameter bug: sets display:none on body, so redirect without it (also, we don't want it to appear in analytics)
  if ( array_key_exists( 'fb_xd_fragment', $_GET ) )
  {
    header( "Location: ". $_SERVER['SCRIPT_URL'], true, 301 );
    exit();
  }

  $db = $GLOBALS['db'] = new Database( Config::$db );
  Config::loadDbConfig($db);

  // @fixme is this where we want this..
  $q = "select id from users where admin=1"; // and verified=1
  $rs = $db->query($q);
  if ( $rs && $db->numRows($rs) )
    while( $o = $db->fetchObject($rs) )
      Config::$adminUsers[] = $o->id;

  $user = $GLOBALS['user'] = new User();
  $user->authenticate();

  if ( Config::$mailerQueue )
    MailerQueue::init();

  // Don't log trivial and overly frequent requests like IM updates
  $reqUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $argv[0];
  if ( !preg_match( '#/(im).php(\?)?#', $reqUri ) )
  {
    $userTxt = ", user: ". $user->id();
    $connectionsTxt = ", connections: ". join( ", ", $user->connectionIds() );

    $reqMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'CMD';
    $log = sprintf( "%-4s: %s%s%s", $reqMethod, $reqUri, $userTxt, $connectionsTxt );
    Log::debug( $log );
  }
?>