<?php

/**
 * Ensures availability of core configuration properties, with reasonable
 * defaults.
 *
 * Values can be overriden in the config.php file.
 *
 * @todo Move user/site-specific configuration options to the config table
 * in the database, this class should solely provide static properties for
 * core options such as database configuration itself.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

namespace Kiki;

class Config
{
	private static $ini = [];

	public static $namespace = null;
	public static $debug = false;

	public static $offlineMode = false;

	public static $routing = null;

	public static $language = "en";
	public static $defaultTimezone = null;

	public static $dbHost = "localhost";
	public static $dbPort = 3306;
	public static $dbName = null;
	public static $dbUser = null;
	public static $dbPass = null;
	public static $db = null;

	public static $memcachedHost = null;
	public static $memcachedPort = 11211;

	public static $mailerQueue = false;

	public static $mailSender = null;
	public static $mailSenderName = null;
	public static $smtpHost = null;
	public static $smtpPort = 25;
	public static $smtpUser = null;
	public static $smtpPass = null;
	public static $smtpAuthType = 'plain';

	public static $tinyHost = null;

	public static $kikiPrefix = "/kiki";
	public static $staticPrefix = "/static";

	public static $i18n = true;

	public static $googleSiteVerification = null;
	public static $googleAnalytics = null;
	public static $googleAdSense = null;

	public static $piwikHost = null;
	public static $piwikSiteId = 0;

	public static $connectionServices = array();

	public static $googleApiClientPath = null;
	public static $googleApiClientId = null;
	public static $googleApiClientSecret = null;
	
	public static $cspNonce = null;

	// WARNING: Changing these values will invalidate all user passwords
	// already stored as hash.
	public static $passwordHashPepper = '';

	public static $authCookieName = 'kikiAuth';
	public static $authCookiePepper = '';
	public static $authCookieExpireTime = 14 * 86400;

	const dbVersionRequired = "0.1.33";

	/**
	* Initialises configuration values. Loads the defaults first and
	* then loads the site-specific configuration file.
	*/
	public static function init()
	{
		self::setDefaults();
		self::load();

		// Fatal because it's preferable to have installations without
		// users setting a dummy value, than having any
		// installations with unsafe settings.
		if ( empty(self::$passwordHashPepper) )
		{
			Log::fatal( "Config::\$passwordHashPepper is empty: user account password hashes are susceptible to rainbow table lookups." );
		}
		if ( empty(self::$authCookiePepper) )
		{
			Log::fatal( "Config::\$authCookiePepper is empty: cookie hashes are susceptible to rainbow table lookups." );
		}

		self::$db = array( 'host' => self::$dbHost, 'port' => self::$dbPort, 'name' => self::$dbName, 'user' => self::$dbUser, 'pass' => self::$dbPass );
	}

	/**
	* Sets defaults that depend on code and cannot be set in the member
	* declarations.
	*/
	private static function setDefaults()
	{
		self::$routing = array(
			'/' => array( 'Page', '_kiki/index' ),
		);

		self::$mailSender = isset($_SERVER['SERVER_ADMIN']) ? $_SERVER['SERVER_ADMIN'] : null;
	}

	public static function loadConnectionServices()
	{
		// TODO: make this more dynamic, and store the actual service instances, not by name
		if ( isset(self::$googleApiClientPath) && isset(self::$googleApiClientId) )
		{
			$service = new ConnectionService\Google();
			if ( $service->enabled() )
				self::$connectionServices[] = 'Google';
		}
	}

	/**
	* Provides the full path of the configuration file.
	* @return string full path of the configuration file
	*/
	public static function configFile()
	{
		// TODO: return an array, so config loading can cascade in order: /etc default /etc/site root/default root/site
		if ( isset($_SERVER['SERVER_NAME']) )
		{
			$file = Core::getRootPath(). "/config-". $_SERVER['SERVER_NAME']. ".php";
			if ( file_exists($file) )
				return $file;

			$file = "/etc/kiki/config-". $_SERVER['SERVER_NAME']. ".php";
			if ( file_exists($file) )
				return $file;
		}
		return Core::getRootPath(). "/config.php";
	}

	/**
	* Loads the configuration file, if it exists.
	*/
	private static function load()
	{
		$file = self::configFile();
		if ( !file_exists($file) )
		{
			// This should probably be an error, no configuration means no database means no functional website.
			// On the other hand, the framework itself should run just fine without any data available.
			Log::fatal( "configuration file not found: $file" );
			Log::debug( "configuration file not found: $file" );
			return;
		}
		include_once "$file";

		$iniFile = str_replace( '.php', '.ini', $file );
		if ( file_exists($iniFile) )
		{
			self::$ini = (object) parse_ini_file( $iniFile, true, INI_SCANNER_TYPED );
		}
	}

  public static function ini( $key )
  {
    $args = explode( ':', $key );
    $val = null;
    foreach( $args as $arg )
    {
      $val = ($val ? $val[$arg] : self::$ini->$arg) ?? null;
    }
    return $val;
  }
}
