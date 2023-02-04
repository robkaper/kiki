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
	public static $namespace = null;
	public static $debug = false;

	public static $offlineMode = false;

	public static $routing = null;

	public static $siteName = null;
	public static $copyOwner = "Kiki website framework";
	public static $copySince = null;
	public static $address = null;

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

	public static $siteLogo = null;
	public static $responsive = false;
	public static $customCss = null;

	public static $i18n = true;

	public static $googleSiteVerification = null;
	public static $googleAnalytics = null;
	public static $googleAdSense = null;

	public static $piwikHost = null;
	public static $piwikSiteId = 0;

	public static $connectionServices = array();

	public static $facebookSdkPath = null;
	public static $facebookApp = null;
	public static $facebookSecret = null;

	public static $twitterOAuthPath = null;
	public static $twitterApp = null;
	public static $twitterSecret = null;

	public static $flickrApp = null;
	public static $flickrSecret = null;

	public static $mailToSocialAddress = null;

	public static $cspNonce = null;

	// FIXME: Add check in setup, pepper *must* be changed from the
	// defaults to avoid rainbow table lookups.
	// WARNING: Changing these values will invalidate all user
	// passwords already stored as hash.
	public static $passwordHashPepper = '';
	public static $passwordHashIterations = 5;

	// FIXME: Add check in setup, pepper *must* be changed from the
	// defaults to avoid rainbow table lookups.
	public static $authCookiePepper = '';
	public static $authCookieName = 'kikiAuth';

	const dbVersionRequired = "0.1.33";

	/**
	* Initialises configuration values. Loads the defaults first and
	* then loads the site-specific configuration file.
	*/
	public static function init()
	{
		self::setDefaults();
		self::load();

		// TODO: error-handling, a database might not be configured
		// (which is noted on the /kiki/ status page, but such a
		// fatal error that we should probably handle it more
		// prominently.
		// if ( self::$dbName && self::$dbUser )
		self::$db = array( 'host' => self::$dbHost, 'port' => self::$dbPort, 'name' => self::$dbName, 'user' => self::$dbUser, 'pass' => self::$dbPass );
	}

	/**
	* Sets defaults that depend on code and cannot be set in the member
	* declarations.
	*/
	private static function setDefaults()
	{
		self::$routing = array();

		self::$siteName = $_SERVER['SERVER_NAME'];
		self::$copySince = date("Y");

		self::$mailSender = isset($_SERVER['SERVER_ADMIN']) ? $_SERVER['SERVER_ADMIN'] : null;

		self::$siteLogo = self::$kikiPrefix. "/img/kiki-inverse-74x50.png";
	}

	public static function loadConnectionServices()
	{
		// TODO: make this more dynamic, and store the actual service instances, not by name
		if ( isset(self::$facebookSdkPath) && isset(self::$facebookApp) )
		{
			$service = new ConnectionService\Facebook();
			if ( $service->enabled() )
				self::$connectionServices[] = 'Facebook';
		}

		if ( isset(self::$twitterOAuthPath) && isset(self::$twitterApp) )
		{
			$service = new ConnectionService\Twitter();
			if ( $service->enabled() )
				self::$connectionServices[] = 'Twitter';
		}
	}

	/**
	* Provides the full path of the configuration file.
	* @return string full path of the configuration file
	* @todo Search multiple locations and don't assume config.php is in
	*   Core::getRootPath().
	*/
	public static function configFile()
	{
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
	}
}
