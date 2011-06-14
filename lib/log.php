<?

/**
* @class Log
* Facilitates error and debug logging.
* @author Rob Kaper <http://robkaper.nl/>
* @section license_sec License
* Released under the terms of the MIT license.
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
	* Initialises the class.
	* @see init()
	*/
	public function __construct()
	{
		self::$uniqId = uniqid();
		self::$ctime = microtime(true);
	}

	/**
 	* This method is provided for convenience to allow execution of the
 	* constructor when desired, prior to the first debug entry (which
 	* isn't necesarily at the initialisation of the script).
 	* @see __construct()
 	*/
	public static function init() {}

	/**
	* Logs a message (plus request URI) to Apache's error_log.
	* @param $msg [string] message to log
	* @param $alsoDebug [bool] (optional) whether to also record the message in the debug log
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
	* @param $msg [string] message to log
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
		$logStr = date( "Y-m-d H:i:s" ). " [". self::$uniqId. "] [+$step] [$total] $msg\n";

		fwrite( $fp, $logStr );
		fclose( $fp );
	}
}

?>