<?

/**
 * Utility class for database operations. Offers SQL injection prevention.
 * Use one instance of this class for each separate database connection
 * required.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2006-2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

class Database
{
	private $host, $port, $user, $pass, $name;
	private $dbh;

	/**
	* Initialises this instance.
	* @param array $confArray configuration details (host, port, database name, user, password)
	*/
	public function __construct( &$confArray )
	{
		list( $this->host, $this->port, $this->name, $this->user, $this->pass ) = array_values($confArray);
	}

	/**
	* Creates (and stores) a persistant database connection, and selects the configured database name.
	*/
	function connect()
	{
		$this->dbh = @mysql_pconnect( "$this->host:$this->port", $this->user, $this->pass );
		if ( $this->dbh )
			@mysql_select_db( $this->name, $this->dbh );
	}

	/**
	* Informs whether the database connection is available. Attempts a
	* new connection first if none is available.
	* @return boolean true is the database is connected
	*/
	public function connected()
	{
		if ( !$this->dbh )
			$this->connect();

		return $this->dbh ? true : false;
	}

	/**
	* Retrieves the database connection link.
	* @return resource the connection
	*/
	public function dbh()
	{
		return $this->dbh;
	}

	/**
	* Sets or switches the active database for the current connection.
	* Attempts a new connection first if none is available.
	*/
	function setDatabase($name)
	{
		if ( !$this->dbh )
			$this->connect();

		$this->name = $name;
		mysql_select_db( $this->name, $this->dbh );
	}

	/**
	* Builds a database query. Query parameters are properly guarded against SQL injections.
	* @todo Research how to properly document variable arguments.
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
		if ( !$this->dbh )
		{
			$this->connect();
			if ( !$this->dbh )
				return null;
		}

		$rs = mysql_query( $q, $this->dbh );
		if ( $rs === FALSE )
			Log::error( "no rs for query [$q]" );
		return $rs;
	}

	/**
	* Retrieves the next object of a resource/result set returned by a succesful query.
	* @todo Research if the (re)connection attempt can be removed, if
	*   not, document it.  It seems likely that the provided resource is
	*   no longer valid and will not return any objects if the
	*   connection is interrupted between executing the query and
	*   retrieving the results.
	* @param resource $rs resource/result set
	* @return object the retrieved object, or null if none is available.
	*/
	function fetchObject( $rs )
	{
		if ( !$this->dbh )
			$this->connect();

		return mysql_fetch_object($rs);
	}

	/**
	* Retrieves the amount of results for a resource/result set.
	* @todo Research if the (re)connection attempt can be removed, if
	*   not, document it.  It seems likely that the provided resource is
	*   no longer valid and will not return anything useful if the
	*   connection is interrupted between executing the query and
	*   retrieving the results.
	* @param resource $rs resource/result set
	* @return int number of rows/results
	*/
	function numRows( $rs )
	{
		if ( !$rs )
			return 0;
		if ( !$this->dbh )
			$this->connect();

		return mysql_num_rows($rs);
	}

	/**
	* Retrieves the ID of the resource inserted with the last insert query (to be used with MySQL's auto_increment statement).
	* @todo Research if the (re)connection attempt can be removed, if
	*   not, document it.  It seems likely that the provided resource is
	*   no longer valid and will not return anything useful if the
	*   connection is interrupted between executing the query and
	*   retrieving the results.
	* @param resource $rs resource/result set
	* @return int ID of the inserted resource, or zero in case the resource is invalid.
	*/
	function lastInsertId( $rs )
	{
		if ( !$rs )
			return 0;
		if ( !$this->dbh )
			$this->connect();
		
		return mysql_insert_id($this->dbh);
	}

	/**
	* Retrieves the number of rows affected by the last insert or update query.
	* @todo Research if the (re)connection attempt can be removed, if
	*   not, document it.  It seems likely that the provided resource is
	*   no longer valid and will not return anything useful if the
	*   connection is interrupted between executing the query and
	*   retrieving the results.
	* @param resource $rs resource/result set
	* @return int number of rows affected, zero is the resource is invalid.
	*/
	function affectedRows( $rs )
	{
		if ( !$rs )
			return 0;
		if ( !$this->dbh )
			$this->connect();

		return mysql_affected_rows($this->dbh);
	}

	/**
	* Protects a string against SQL injection. Attempts a new connection first is none is available.
	* @param string $str query parameter
	* @return string the protected string, or null if no connection is available
	*/
	function escape( $str )
	{
		if ( !$this->dbh )
			$this->connect();
		if ( !$this->dbh )
			return null;

		return mysql_real_escape_string( $str, $this->dbh );
	}

	/**
	* Executes a query an returns a single object
	* @param string $q query
	* @return object the first object in the resource/result set
	*/
	function getSingle( $q )
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
		if ( gettype($rs) == "resource" && mysql_num_fields($rs) == 1 )
		{
			$row = mysql_fetch_row($rs);
			return $row[0];
		}
		return null;
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