<?php

/**
 * Utility class for database operations. Offers SQL injection prevention.
 * Use one instance of this class for each separate database connection
 * required.
 *
 * @class Database
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2006-2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

namespace Kiki;

class Database
{
	private $host, $user, $pass, $name, $port;
	private $mysqli = null;

	private $lastQuery = null;

	/**
	* Initialises this instance.
	* @param array $confArray configuration details (host, port, database name, user, password)
	*/
	public function __construct( &$confArray )
	{
		mysqli_report(MYSQLI_REPORT_STRICT);
		// mysqli_report(MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ALL);

		list( $this->host, $this->port, $this->name, $this->user, $this->pass ) = array_values($confArray);
		$this->connect();
	}

	/**
	* Creates (and stores) a persistant database connection, and selects the configured database name.
	*/
	function connect()
	{
		if ( !$this->host || !$this->port || !$this->name )
			return false;

		$this->mysqli = new \mysqli( $this->host, $this->user, $this->pass, $this->name, $this->port );
		
		if ( isset($this->mysqli->connect_errno) && !empty($this->mysqli->connect_error) )
		{
			Log::fatal( "connection to database failed: [". $this->mysqli->connect_errno. "] ". $this->mysqli->connect_error );
			$this->mysqli = null;
			return false;
		}

		$this->mysqli->set_charset('utf8mb4');
		// $this->query("set names utf8mb4" );
	}

	/**
	* Informs whether the database connection is available. Attempts a
	* new connection first if none is available.
	* @return boolean true is the database is connected
	*/
	public function connected()
	{
		if ( !$this->mysqli || !is_object($this->mysqli) || get_class($this->mysqli) !== 'mysqli' )
		{
			Log::debug( "reconnecting mysql" );
			$this->connect();
		}

		return $this->mysqli ? true : false;
	}

	public function ping()
	{
		return $this->mysqli->ping();
	}

	/**
	* Retrieves the database connection link.
	* @return resource the connection
	*/
	public function mysqli()
	{
		return $this->mysqli;
	}

	/**
	* Sets or switches the active database for the current connection.
	* Attempts a new connection first if none is available.
	*/
	function setDatabase($name)
	{
		if ( !$this->mysqli )
			$this->connect();

		$this->name = $name;
		$this->mysqli->select_db( $this->name, $this->mysqli );
	}

	/**
	* Builds a database query. Query parameters are properly guarded against SQL injections.
	*
	* Works like (s)printf, for example:
	*
	* $q = $db->buildQuery( "SELECT id FROM table WHERE foo=%s AND bar=%d", $strFoo, $intBar );
	*/
	public function buildQuery()
	{
		$argv = func_get_args();
		$format = array_shift($argv);

		foreach( $argv as &$arg )
			$arg = $this->escape($arg);

		return vsprintf( $format, $argv );
	}

	/** 
	* Executes a query against the active database. Attempts a new
	* connection first if none is available.  Generates an log error
	* when the query is invalid.
	* @param string $q SQL query to be executed
	* @return resource the resource (or result set) of the query, null in case of an error or no connection
	*/
	function query( $q )
	{
		if ( !$this->connected() )
		{
			$this->connect();
			if ( !$this->mysqli )
				return null;
		}

		Log::beginTimer('db');

		$cacheId = md5($q);

		$rs = Core::cacheAvailable() ? Core::getMemcache()->get($cacheId) : false;

		if ( $rs === false )
		{
			$this->lastQuery = $q;
			try {
				$rs = $this->connected() ? $this->mysqli->query($q) : null;
			}
			catch (mysqli_sql_exception $e) {
				Log::error($e);
			}

			if ( $rs === false )
			{
				Log::error( "no rs for query [$q]" );
				Log::error( $this->mysqli->errno. ": ". $this->mysqli->error );
				if( $this->mysqli->errno == 1927 )
				{
					// Connection was killed
					unset($this->mysqli);
				}
				else if( $this->mysqli->errno == 2006 )
				{
					// MySQL server has gone away
					unset($this->mysqli);
				}
				// exit;
			}
			else if ( Core::cacheAvailable() )
				Core::getMemcache()->set($cacheId, $rs);
		}

		Log::endTimer('db');

		return $rs;
	}

	function begin()
	{
		if ( !$this->mysqli )
			$this->connect();

		$this->mysqli->autocommit(false);
	}

	function commit( $alsoEnd = false )
	{
		if ( !$this->mysqli )
			return false;

		$this->mysqli->commit();

		if ( $alsoEnd )
			$this->end();
	}

	function end()
	{
		if ( !$this->mysqli )
			return false;

		$this->mysqli->autocommit(true);
	}

	/**
	* Retrieves the next object of a resource/result set returned by a succesful query.
	* @param resource $rs resource/result set
	* @return object the retrieved object, or null if none is available.
	*/
	function fetchObject( $rs )
	{
		if ( !$this->mysqli )
			return null;

		return $rs->fetch_object();
	}

	/**
	* Retrieves the amount of results for a resource/result set.
	* @param resource $rs resource/result set
	* @return int number of rows/results
	*/
	function numRows( $rs )
	{
		if ( !$rs || !$this->mysqli )
			return 0;

		return $rs->num_rows;
	}

	/**
	* Retrieves the ID of the resource inserted with the last insert query (to be used with MySQL's auto_increment statement).
	* @param resource $rs resource/result set
	* @return int ID of the inserted resource, or zero in case the resource is invalid.
	*/
	function lastInsertId( $rs )
	{
		if ( !$rs || !$this->mysqli )
			return 0;
		
		return $this->mysqli->insert_id;
	}

	/**
	* Retrieves the number of rows affected by the last insert or update query.
	* @param resource $rs resource/result set
	* @return int number of rows affected, zero is the resource is invalid.
	*/
	function affectedRows( $rs )
	{
		if ( !$rs || !$this->mysqli )
			return 0;

		return $this->mysqli->affected_rows;
	}

	/**
	* Protects a string against SQL injection. Attempts a new connection first is none is available.
	* @param string $str query parameter
	* @return string the protected string, or null if no connection is available
	*/
	function escape( $str )
	{
		if ( !$this->mysqli )
			$this->connect();

		if ( !$this->mysqli )
			return null;

		return $this->mysqli->real_escape_string($str);
	}

	static function nullable( $val )
	{
		return isset($val) && !empty($val) ? $val : 'null';
	}
	
	/**
	* Executes a query an returns a single object
	* @param string $q query
	* @return object the first object in the resource/result set
	*/
	function getSingleObject( $q )
	{
		$rs = $this->query($q);
		if ( $rs && $this->numRows($rs) )
			return $this->fetchObject($rs);
		return null;
	}

	/**
	* Executes a query an returns a single value
	* @param string $q query
	* @return string the value of the first column of the first object in the resource/result set
	*/
	function getSingleValue( $q )
	{
		$rs = $this->query($q);
		if ( $rs && $this->mysqli->field_count == 1 )
		{
			$row = $rs->fetch_row();
			if ( $row )
				return $row[0];
		}
		return null;
	}

	/**
	* Executes a query and returns an array of all objects
	* @param string $q query
	* @return array an array of database objects
	*/
	public function getObjects( $q, $keyField=null )
	{
		$ret = array();
		$rs = $this->query($q);
		if ( $rs && $this->numRows($rs) )
			while( $o = $this->fetchObject($rs) )
			{
				if ( $keyField && isset($o->{$keyField}) )
					$ret[$o->{$keyField}] = $o;
				else
					$ret[] = $o;
			}

		return $keyField ? $ret : array_values($ret);
	}

	/**
	* Executes a query and returns the ID of all objects
	* @param string $q query
	* @return array an array of database object IDs
	*/
	public function getObjectIds( $q )
	{
		$ret = array();
		$rs = $this->query($q);
		if ( $rs && $this->numRows($rs) )
			while( $o = $this->fetchObject($rs) )
				$ret[] = isset($o->id) ? $o->id : 0;

		return array_values($ret);
	}

	/**
	 * Implodes an array of IDs for use in "WHERE IN ()" parts of queries
	 * @param array an array of IDs
	 * @return string a comma-separated list of IDs, or null if the array was empty
	 */
	static public function implode( $arrIds )
	{
		return count($arrIds) ? implode( ",", $arrIds ) : "null";
	}
	
	/**
	* Retrieves the currently selected database
	* @return string name of the database
	*/
	function currentDatabase()
	{
		return $this->getSingleValue( "select database()" );
	}
}

?>