<?

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

class Config
{
	public static $debug = false;

	public static $siteName = null;
	public static $copyOwner = "Kiki website framework";
	public static $copySince = null;
	public static $address = null;

	public static $geoLocation = null;

	public static $language = "en";

	public static $dbHost = "localhost";
	public static $dbPort = 3306;
	public static $dbName = null;
	public static $dbUser = null;
	public static $dbPass = null;
	public static $db = null;

	public static $mailerQueue = false;

	public static $smtpHost = null;
	public static $smtpPort = 25;
	public static $smtpUser = null;
	public static $smtpPass = null;
	public static $smtpAuthType = 'plain';

	public static $tinyHost = null;

	public static $kikiPrefix = "/kiki";
	public static $staticPrefix = "/static";

	public static $siteLogo = null;
	public static $customCss = null;

	public static $clEditor = false;

    	public static $iconSetColor = "black";
    	public static $iconPrefix = null;

	public static $googleSiteVerification = null;
	public static $googleAnalytics = null;
	public static $googleAdSense = null;

	public static $connectionServices = array();
	public static $facebookSdkPath = null;
	public static $facebookApp = null;
	public static $facebookSecret = null;
	public static $twitterApp = null;
	public static $twitterSecret = null;
	public static $twitterAnywhere = false;
	public static $flickrApp = null;
	public static $flickrSecret = null;

	public static $mailToSocialAddress = null;

	public static $adminUsers = array();

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

	const dbVersionRequired = "0.1.24";

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
		self::$siteName = $_SERVER['SERVER_NAME'];
		self::$copySince = date("Y");

		self::$siteLogo = self::$kikiPrefix. "/img/kiki-inverse-74x50.png";

		self::$iconPrefix = self::$kikiPrefix. "/img/iconic/". Config::$iconSetColor;
	}

	public static function loadDbConfig( &$db )
	{
		// TODO: Actually get these from the database
		if ( self::$facebookApp )
			self::$connectionServices[] = 'Facebook';
		if ( self::$twitterApp )
			self::$connectionServices[] = 'Twitter';
	}

	/**
	* Provides the full path of the configuration file.
	* @return string full path of the configuration file
	* @todo Search multiple locations and don't assume config.php is in
	*   $GLOBALS['root'].
	*/
	public static function configFile()
	{
		return $GLOBALS['root']. "/config.php";
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
			Log::debug( "configuration file not found: $file" );
			return;
		}
		include_once "$file";
	}
}

?>