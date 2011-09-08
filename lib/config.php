<?

/**
* @file lib/config.php
* Provides the Config class.
* @class Config
* Ensures availability of core configuration values with reasonable defaults.
* Values can be overriden in config.php, although the intention id to move
* most of these to the config table in the database.
* @author Rob Kaper <http://robkaper.nl/>
* @section license_sec License
* Released under the terms of the MIT license.
*/

class Config
{
	public static $siteName = null;
	public static $copyOwner = "Kiki website framework";
	public static $copySince = null;
	public static $address = null;
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

	public static $kikiPrefix = "/kiki";
	public static $staticPrefix = "/static";

	public static $template = 'default';
	public static $headerLogo = null;
	public static $customCss = null;

    	public static $iconSetColor = "black";
    	public static $iconPrefix = null;

	public static $googleSiteVerification = null;
	public static $googleAnalytics = null;
	public static $googleAdSense = null;

	public static $facebookApp = null;
	public static $facebookSecret = null;
	public static $twitterApp = null;
	public static $twitterSecret = null;
	public static $twitterCallback = null;
	public static $twitterAnywhere = false;
	public static $flickrApp = null;
	public static $flickrSecret = null;

	public static $mailToSocialAddress = null;

	public static $singleUser = 0;
	public static $devUsers = array();

	// @warning Check these in setup, they *must* be set.
	public static $passwordHashPepper =  null;
	public static $passwordHashIterations = 0;
	public static $authCookiePepper = null;
	public static $authCookieName = 'kikiAuth';

	const dbVersionRequired = "0.1.3";

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

		self::$headerLogo = self::$kikiPrefix. "/img/kiki-inverse-74x50.png";

		self::$iconPrefix = self::$kikiPrefix. "/img/iconic/". Config::$iconSetColor;
        
		self::$twitterCallback = 'http://'. $_SERVER['SERVER_NAME']. self::$kikiPrefix. '/twitter-callback.php';
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