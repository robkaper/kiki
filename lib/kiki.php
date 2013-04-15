<?php

class Kiki
{
	private static $path = null;
	private static $rootPath = null;

	private static $db = null;
	private static $user = null;

	private static $templateData = null;

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

	public static function getDb()
	{
		if ( !isset(self::$db) )
		{
			self::$db = new Database( Config::$db );
		}

		return self::$db;
	}

	public static function setDb( &$db )
	{
		self::$db = $db;
	}

	public static function getUser()
	{
		if ( !isset(self::$user) )
		{
			self::$user = new User();
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

		// Config values
    self::$templateData['config'] = array();
    foreach( get_class_vars( 'Config' ) as $configKey => $configValue )
    {
			// Lame security check, but better safe than sorry until a proper
			// audit has been done that in no way unauthorised user content get
			// parsed as template itself, through parsing recursion or otherwise. 
			// Should mostly be careful about direct assignment of any of it to
			// 'content'.
      if ( !preg_match( '~(^db|pass|secret)~i', $configKey ) )
        self::$templateData['config'][$configKey] = $configValue;
    }

    if ( Config::$customCss )
      self::$templateData['stylesheets'] = array( Config::$customCss );

		// Is that all we want?
    self::$templateData['server'] = array(
      'requestUri' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ""
    );

		// Port to User::templateData()
    self::$templateData['user'] = array(
      'id' => self::$user->id(),
      'admin' => self::$user->isAdmin(),
      'activeConnections' => array(),
      'inactiveConnections' => array(),
      'emailUploadAddress' => self::$user->emailUploadAddress()
    );

		// Active connections. Only typing laziness explains why this isn't simply in {$user.connections}.

    self::$templateData['activeConnections'] = array();

    $connectedServices = array();
    foreach( self::$user->connections() as $connection )
    {
      self::$templateData['activeConnections'][] = array( 'serviceName' => $connection->serviceName(), 'screenName' => $connection->screenName(), 'userName' => $connection->name(), 'pictureUrl' => $connection->picture(), 'subAccounts' => $connection->subAccounts(), 'permissions' => $connection->permissions() );
      $connectedServices[] = $connection->serviceName();
    }

		// Inactive connections. Might as well be in {$user) as well,
		// potentially in {$user.connections} with an {active} switch, although
		// the separation at this level is not the worst.

    foreach( Config::$connectionServices as $name )
    {
      if ( !in_array( $name, $connectedServices ) )
      {
        $connection = Factory_ConnectionService::getInstance($name);
        self::$templateData['inactiveConnections'][] = array( 'serviceName' => $connection->name(), 'loginUrl' => $connection->loginUrl() );
      }
    }

		// Menu and submenu. This feels like it the default controller (with the
		// option for children to reimplement or amend) should do through a Menu
		// class.

    self::$templateData['menu'] = Boilerplate::navMenu(self::$user);
    self::$templateData['subMenu'] = Boilerplate::navMenu(self::$user, 2);

		// @todo Allow starttime and execution time from Log(::init) to be
		// queried and assign them.  Just in case someone wants to output it in
		// a template.

		self::$templateData['now'] = time();
	}

}
