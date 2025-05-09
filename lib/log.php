<?php

/**
 * Facilitates debug and error logging.
 * 
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2008-2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

namespace Kiki;

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
  * @var array with time spent per timer
  */
	private static $timers = array();

	/**
  * @var array with timestamps per timer
  */
	private static $timerMtimes = array();

	/**
	* @var queue array to store delayed log messages
	*/
	private static $queue;

	/**
 	* Initialises a unique identifier and starting timestamp.
 	*/
	public static function init()
	{
		self::$uniqId = uniqid();
		self::$ctime = microtime(true);
		self::$queue = array();
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
		$reqUri = isset( $_SERVER['REQUEST_URI'] ) ?  $_SERVER['REQUEST_URI'] : null;
		error_log( $reqUri. ": ". $msg );
	}

	/**
	* Logs an error and generates a fatal exit.
	* @param string $msg message to log
	* @param boolean $alsoDebug (optional) whether to also record the message in the debug log
	*/
	public static function fatal( $msg, $alsoDebug = true )
	{
		self::error( $msg, $alsoDebug );
		echo "<hr><strong>Kiki fatal error:</strong><br /><blockquote>$msg</blockquote><hr>". PHP_EOL;
		exit;
	}

	/**
	* Logs a message to Kiki's debug log file, including request method,
	* URI and execution times since init() and previous log entry.
	* @param string $msg message to log
	*/
	public static function debug( $msg, $queue = false, $includeBacktrace = false )
	{
		if ( isset($_SERVER['nginx_log_ip']) && $_SERVER['nginx_log_ip'] == false )
			return;

		if ( !Config::$debug )
			return;

		if ( !self::$uniqId )
			self::init();

		$last = self::$mtime ? self::$mtime : self::$ctime;
		self::$mtime = microtime(true);

		$step = sprintf( "%3.7f", self::$mtime - $last );
		$total = sprintf( "%3.7f", self::$mtime - self::$ctime );

		$trace = debug_backtrace();

		$location = str_replace( Core::getInstallPath(). "/", '', $trace[0]['file'] ). ':'. $trace[0]['line'];

		$caller = array_shift($trace);
		while( isset($caller['class']) && $caller['class'] == 'Kiki\Log' )
			$caller = array_shift($trace);

		if ( isset($caller['class']) )
			$callerStr = $caller['class']. '::'. $caller['function'];
		else
			$callerStr = '';
		
		$logStr = date( "Y-m-d H:i:s" ). " [". self::$uniqId. "] [+$step] [$total] [$location] [$callerStr] $msg". PHP_EOL;

		if ( $includeBacktrace )
		{
			foreach( $trace as $traceItem )
				$logStr .= sprintf( "\t[%s][%s][%s][%s]". PHP_EOL, $traceItem['file'] ?? null, $traceItem['line'] ?? null, $traceItem['class'] ?? null, $traceItem['function'] );
		}

		self::$queue[] = $logStr;

		if ( !$queue )
			self::write();
	}

	private static function write()
	{
		if ( php_sapi_name() == "cli" )
		{
			$processUser = posix_getpwuid(posix_geteuid());
			$logFile = Core::getRootPath(). "/log/kiki-debug-". $processUser['name']. ".txt";
		}
		else
			$logFile = Core::getRootPath(). "/log/kiki-debug-www.txt";

		$fp = @fopen( $logFile, "a" );
		if ( !$fp )
			Log::error( "cannot write to $logFile", false );

		while( $str = array_shift(self::$queue) )
		{
			if ( $fp )
				fwrite( $fp, $str );
			else
				Log::error( $str );
		}
		if ( $fp )
			fclose( $fp );
	}

	/**
	* @deprecated Still referenced by the Daemon class which expects a
	* syslog compatible Log class, which Kiki currently doesn't provide.
	*
	* @param string $msg message to log
	*/
	public static function info( $msg )
	{
		self::debug( $msg );
	}

	public static function beginTimer( $timer )
	{
		if ( isset(self::$timerMtimes[$timer]) )
		{
			Log::error( "timer $timer already initialised" );
			return;
		}

		self::$timerMtimes[$timer] = microtime(true);
	}

	public static function endTimer( $timer )
	{
		if ( !isset(self::$timerMtimes[$timer]) )
			return;

		$add = microtime(true) - self::$timerMtimes[$timer];

		if ( !isset(self::$timers[$timer]) )
			self::$timers[$timer] = 0;

		self::$timers[$timer] += $add;

		unset(self::$timerMtimes[$timer]);
	}

	public static function getTimers()
	{
		return self::$timers;
	}
}
