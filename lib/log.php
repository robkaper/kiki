<?

class Log
{
	private static $uniqId;
	private static $ctime;
	private static $mtime;

	// Not done as __construct to remind ourselves that Log::init() must
	// be called explicitely, while the constructor would implicitely be
	// called when registering the first log entry which is most likely
	// not the actual initialisation of the script.
	public static function init()
	{
		self::$uniqId = uniqid();
		self::$ctime = microtime(true);
	}

	// Logs an error, using Apache's error_log
	public static function error( $str, $alsoDebug = true )
	{
		if ( $alsoDebug )
			Log::debug($str);
		error_log( $_SERVER['REQUEST_URI']. ": ". $str );
	}

	// Logs a debug message including context and execution time since
	// init and previous message
	public static function debug( $str )
	{
		$logFile = $GLOBALS['root']. "/debug.txt";
		$fp = @fopen( $logFile, "a" );
		if ( !$fp )
		{
			Log::error( "cannot write to $logFile: $str", false );
			return;
		}

		$last = self::$mtime ? self::$mtime : self::$ctime;
		self::$mtime = microtime(true);

		$step = sprintf( "%3.7f", self::$mtime - $last );
		$total = sprintf( "%3.7f", self::$mtime - self::$ctime );
		$logStr = date( "Y-m-d H:i:s" ). " [". self::$uniqId. "] [+$step] [$total] $str\n";

		fwrite( $fp, $logStr );
		fclose( $fp );
	}
}

?>