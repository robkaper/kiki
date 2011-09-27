<?

/**
 * Facilitates debug and error logging.
 * 
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2008-2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

class Log
{
	/**
	* @var string unique identifier to separate different script instances (such as concurrent HTTP requests)
	*/ 
	private static $uniqId;

	/**
	* @var float timestamp with microseconds of initialisation
	*/ 
	private static $ctime;

	/**
	* @var float timestamp with microseconds of last debug call
	*/ 
	private static $mtime;

	/**
 	* Initialises a unique identifier and starting timestamp.
 	*/
	public static function init()
	{
		self::$uniqId = uniqid();
		self::$ctime = microtime(true);
	}

	/**
	* Logs a message (plus request URI) to Apache's error_log.
	* @param string $msg message to log
	* @param boolean $alsoDebug (optional) whether to also record the message in the debug log
	*/
	public static function error( $msg, $alsoDebug = true )
	{
		if ( $alsoDebug )
			Log::debug($msg);
		error_log( $_SERVER['REQUEST_URI']. ": ". $msg );
	}

	/**
	* Logs a message to Kiki's debug log file, including request method,
	* URI and execution times since init() and previous log entry.
	* @param string $msg message to log
	*/
	public static function debug( $msg )
	{
		$logFile = $GLOBALS['root']. "/debug.txt";
		$fp = @fopen( $logFile, "a" );
		if ( !$fp )
		{
			Log::error( "cannot write to $logFile: $msg", false );
			return;
		}

		$last = self::$mtime ? self::$mtime : self::$ctime;
		self::$mtime = microtime(true);

		$step = sprintf( "%3.7f", self::$mtime - $last );
		$total = sprintf( "%3.7f", self::$mtime - self::$ctime );

		$trace = debug_backtrace();
		$caller = array_shift($trace);
		while( isset($caller['class']) && $caller['class'] == 'Log' )
			$caller = array_shift($trace);

		if ( isset($caller['class']) )
			$callerStr = $caller['class']. '::'. $caller['function'];
		else
			$callerStr = '';
		
		$logStr = date( "Y-m-d H:i:s" ). " [". self::$uniqId. "] [+$step] [$total] [$callerStr] $msg\n";

		fwrite( $fp, $logStr );
		fclose( $fp );
	}

	/**
	* @deprecated Still referenced by the Daemon class which expects a
	*   syslog compatible Log class, which Kiki currently doesn't provide.
	*/
	public static function info( $msg )
	{
		self::debug( $msg );
	}
}

?>