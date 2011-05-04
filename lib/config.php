<?

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

	public static $dbVersionRequired = "0.1.0";

	public static function init()
	{
		self::setDefaults();
		self::load();

//		if ( self::$dbName && self::$dbUser )
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
			Log::debug( "configuration file not found: $file" );
			return;
		}
		include_once "$file";
	}
}

?>