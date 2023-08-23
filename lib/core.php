<?php

namespace Kiki;

class Core
{
	private static $path = null;
	private static $rootPath = null;

	private static $baseUrl = null;

	private static $db = null;

	private static $memcache = null;
	private static $cacheAvailable = false;

	private static $user = null;

	private static $templateData = null;
	private static $flashBag = null;

	public static function getInstallPath()
	{
		return self::$path;
	}

	public static function setInstallPath( $path )
	{
		self::$path = $path;
	}

	public static function getRootPath()
	{
		return self::$rootPath;
	}

	public static function setRootPath( $path )
	{
		self::$rootPath = $path;
	}

	public static function getBaseUrl()
	{
		return self::$baseUrl;
	}

	public static function setBaseUrl( $baseUrl )
	{
		self::$baseUrl = $baseUrl;
	}

	public static function getDb( $forceNew = false )
	{
		if ( !isset(self::$db) || $forceNew )
		{
			self::$db = new Database( Config::$db );
		}

		return self::$db;
	}

	public static function setDb( &$db )
	{
		self::$db = $db;
	}

	public static function getMemcache()
	{
		if ( !isset(Config::$memcachedHost) ) 
			return null;

		if ( !isset(self::$memcache) )
		{
			Log::beginTimer( "initMemcache" );
			self::$memcache = new \Memcache();

			self::$cacheAvailable = self::$memcache->connect( Config::$memcachedHost, Config::$memcachedPort );
			Log::endTimer( "initMemcache" );
		}

		return self::$memcache;
	}

	public static function cacheAvailable()
	{
		return self::$cacheAvailable;
	}

	public static function getUser()
	{
		if ( !isset(self::$user) )
		{
			$className = ClassHelper::bareToNamespace( 'User' );
			self::$user = new $className();
		}

		return self::$user;
	}

	public static function setUser( &$user )
	{
		self::$user = $user;
	}

	public static function getTemplateData()
	{
		if ( !isset(self::$templateData) )
		{
			self::setTemplateData();
		}

		return self::$templateData;
	}

	public static function setTemplateData()
	{
    // Basic and fundamental variables that should always be available in templates.

		self::$templateData = array();

		// Request values
		self::$templateData['get'] = $_GET;
		self::$templateData['post'] = $_POST;

		// Config values
    self::$templateData['config'] = array();
    foreach( get_class_vars( 'Kiki\Config' ) as $configKey => $configValue )
    {
			// Lame security check, but better safe than sorry until a proper
			// audit has been done that in no way unauthorised user content get
			// parsed as template itself, through parsing recursion or otherwise. 
			// Should mostly be careful about direct assignment of any of it to
			// 'content'.
      if ( !preg_match( '~(^db|pass|secret|pepper)~i', $configKey ) )
        self::$templateData['config'][$configKey] = $configValue;
    }

    if ( Config::$customCss )
      self::$templateData['stylesheets'] = array( Config::$customCss );

		// Is that all we want?
    self::$templateData['server'] = array(
      'host' => $_SERVER['HTTP_HOST'] ?? null,
      'name' => $_SERVER['SERVER_NAME'] ?? null,
      'requestUri' => $_SERVER['REQUEST_URI'] ?? null,
    );

    self::$templateData['user'] = self::$user ? self::$user->templateData() : null;

		// Account service(s). Although multiple routing entries are technically
		// possible, templateData currently only populates one: the first found or else
		// the internal fallback in the Kiki controller.
		// FIXME: disabled for now, this shouldn't be db-populated anyway
		// $accountServices = array_values( Router::getBaseUris('account') );
		$baseUri = isset($accountServices[0]) ? $accountServices[0]->base_uri : Config::$kikiPrefix. "/account";
		$title = isset($accountServices[0]) ? $accountServices[0]->title : _("Account");
		self::$templateData['accountService'] = array( 'url' => $baseUri, 'title' => $title );
		
		// Active connections. Only typing laziness explains why this isn't simply in {$user.connections}.
    self::$templateData['activeConnections'] = array();

    $connectedServices = array();
    if ( self::$user )
    {
      foreach( self::$user->connections() as $connection )
      {
        self::$templateData['activeConnections'][] = array(
          'serviceName' => $connection->serviceName(),
          'screenName' => $connection->screenName(),
          'userName' => $connection->name(),
          'pictureUrl' => $connection->picture()
        );

        $connectedServices[] = $connection->serviceName();
      }
    }

    // Log::debug( "user cons: ". print_r(self::$user->connections(),true) );

    // Inactive connections. Might as well be in {$user) as well,
    // potentially in {$user.connections} with an {active} switch, although
    // the separation at this level is not the worst.

    // Log::debug( "config: ". print_r(Config::$connectionServices, true) );
    // Log::debug( "connected: ". print_r($connectedServices,true) );

    foreach( Config::$connectionServices as $name )
    {
      if ( !in_array( $name, $connectedServices ) )
      {
        $connection = ConnectionService\Factory::getInstance($name);
        self::$templateData['inactiveConnections'][] = array( 'serviceName' => $connection->name(), 'loginUrl' => $connection->loginUrl() );
      }
    }

    self::$templateData['now'] = time();
  }

  public static function getFlashBag()
  {
    if ( !isset(self::$flashBag) )
      self::$flashBag = new \Kiki\FlashBag();

    return self::$flashBag;
  }

}
