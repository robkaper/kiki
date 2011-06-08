<?

// This class ensures reasonable defaults for configuration values that can
// be overridden in config.php, although the intention is to move most of
// these to the config table in the database.

class Config
{
	public static $siteName = null;
	public static $copyOwner = "Kiki CMS";
	public static $copySince = null;
	public static $address = null;

	public static $dbHost = "localhost";
	public static $dbPort = 3306;
	public static $dbName = null;
	public static $dbUser = null;
	public static $dbPass = null;
	public static $db = null;

	public static $smtpHost = null;
	public static $smtpPort = 25;
	public static $smtpUser = null;
	public static $smtpPass = null;
	public static $smtpAuthType = 'plain';

	public static $kikiPrefix = "/kiki";
	public static $staticPrefix = "/static";

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

	public static $dbVersionRequired = "0.1.1";

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
	
	private static function setDefaults()
	{
		self::$siteName = $_SERVER['SERVER_NAME'];
		self::$copySince = date("Y");

		self::$headerLogo = self::$kikiPrefix. "/img/kiki-logo-50.png";

		self::$iconPrefix = self::$kikiPrefix. "/img/iconic/". Config::$iconSetColor;
        
		self::$twitterCallback = 'http://'. $_SERVER['SERVER_NAME']. self::$kikiPrefix. '/twitter-callback.php';
	}

	public static function configFile()
	{
		return $GLOBALS['root']. "/config.php";
	}

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