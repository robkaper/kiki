<?

class Database
{
	private $host, $port, $user, $pass, $name;
	private $dbh;

	function __construct( &$confArray )
	{
		list( $this->host, $this->port, $this->name, $this->user, $this->pass ) = array_values($confArray);
	}

	function connect()
	{
		$this->dbh = @mysql_pconnect( "$this->host:$this->port", $this->user, $this->pass );
		if ( $this->dbh )
			@mysql_select_db( $this->name, $this->dbh );
	}

	function setDatabase($name)
	{
		if ( !$this->dbh )
			$this->connect();

		$this->name = $name;
		mysql_select_db( $this->name, $this->dbh );
	}

	function query( $q, $debug="" )
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

	function fetch_object( $rs )
	{
		return $this->fetchObject($rs);
	}

	function fetchObject( $rs )
	{
		if ( !$this->dbh )
			$this->connect();

		return mysql_fetch_object($rs);
	}

	function numRows( $rs )
	{
		if ( !$rs )
			return 0;
		if ( !$this->dbh )
			$this->connect();

		return mysql_num_rows($rs);
	}

	function last_insert_id( $rs )
	{
		return $this->lastInsertId($rs);
	}

	function lastInsertId( $rs )
	{
		if ( !$rs )
			return 0;
		if ( !$this->dbh )
			$this->connect();
		
		return mysql_insert_id($this->dbh);
	}

	function affectedRows( $rs )
	{
		if ( !$rs )
			return 0;
		if ( !$this->dbh )
			$this->connect();

		return mysql_affected_rows($this->dbh);
	}

	function escape( $str )
	{
		if ( !$this->dbh )
			$this->connect();
		if ( !$this->dbh )
			return null;

		return mysql_real_escape_string( $str, $this->dbh );
	}

	function getSingle( $q )
	{
		$rs = $this->query($q);
		if ( $rs && $this->numRows($rs) )
			return $this->fetchObject($rs);
		return null;
	}

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

	function currentDatabase()
	{
		return $this->getSingleValue( "select database()" );
	}
}

?>