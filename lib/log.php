<?

class Log
{
	private static $uniqId;
	private static $ctime;
	private static $mtime;

	public static function init()
	{
		self::$uniqId = uniqid();
		self::$ctime = microtime(true);
	}

	public static function error( $str )
	{
		error_log( $_SERVER['REQUEST_URI']. ": ". $str );
	}

	public static function debug( $str )
	{
		$logFile = $GLOBALS['root']. "/debug.txt";
		$fp = fopen( $logFile, "a" );
		if ( !$fp )
		{
			Log::error( "cannot write to $logFile: $str" );
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