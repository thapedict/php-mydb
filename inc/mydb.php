<?php
/**
 *	class wrapper for the actual db object (driver)
 *
 *	@package mydb
 *	@version 0.1
 *	@author Thapelo Moeti
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 * @note This program is distributed in the hope that it will be useful - WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 */

/*
 *	define some defaults (to connect to WAMP)
 */
if( ! defined( 'MyDB_HOST' ) )
	define( 'MyDB_HOST', 'localhost' );

if( ! defined( 'MyDB_USERNAME' ) )
	define( 'MyDB_USERNAME', 'root' );

if( ! defined( 'MyDB_PASSWORD' ) )
	define( 'MyDB_PASSWORD', '' );

/*
 *	uncomment the following to set a default database
*/ 
// if( ! defined( 'MyDB_DATABASE' ) )
	// define( 'MyDB_DATABASE', 'mysql' );

/*
 *	uncomment the following to set a default log file
*/ 
// if( ! defined( 'MyDB_LOG_FILE' ) )
	// define( 'MyDB_LOG_FILE', __DIR__ . '/mydb_errors.log' );


class mydb {
	
	/**
	 *	to save the db object
	 *	@var	mysqli
	 */
	private $db = null;
	
	/**
	 *	to save the database name
	 *	@var	string
	 */
	private $database_name = null;
	
	/**
	 *	to save all queries
	 *	@var	array
	 */
	private $queries = array();
	
	/**
	 *	to save the results object
	 *	@var	mysqli_result
	 */	
	private $results = null;
	
	/**
	 *	to save the last insert id
	 *	@var	int
	 */	
	private $insert_id = null;
	
	/**
	 *	to save the log file path
	 *	@var	string
	 */	
	private $log_file = null;
	
	/**
	 *	for singleton operations
	 *
	 *	@param	string	server host name
	 *	@param	string	username to use to connect
	 *	@param	string	password to use to connect
	 *	@param	string	database to use
	 *	@return	mydb	this object
	 */
	function __construct( $host = null, $username = null, $password = null, $database = null ) {
		$host = is_null( $host ) ? ( defined( 'MyDB_HOST' ) ? MyDB_HOST: null ): $host;
		$username = is_null( $username ) ? ( defined( 'MyDB_USERNAME' ) ? MyDB_USERNAME: null ): $username;
		$password = is_null( $password ) ? ( defined( 'MyDB_PASSWORD' ) ? MyDB_PASSWORD: '' ): $password;
		$database = is_null( $database ) ? ( defined( 'MyDB_DATABASE' ) ? MyDB_DATABASE: '' ): $database;
		
		// check for common missing connection errors
		if( empty( $host ) )
			trigger_error( 'MyDB Connection Error: Host Required', E_USER_ERROR );
		
		if( empty( $username ) )
			trigger_error( 'MyDB Connection Error: Username Required', E_USER_ERROR );
		
		$this->db = new mysqli( $host, $username, $password, $database );
		
		// check for incorrect connection details
		if( $this->db->errno )
			throw new Exception( 'Error: MyDB Connection Details' );
		
		if( ! empty( $database ) )
			$this->database_name = $database;
	
		return $this;	
	}
	
	/**
	 *	returns the current database name
	 *	@return	string	with database name
	 */
	function get_db() {
		return $this->database_name;
	}
	
	/**
	 *	sets the current database
	 *	@return	boolean	true if can successfully select the db, false if can't
	 */
	function select_db( $database_name ) {
		if( $this->db->select_db( $database_name ) ) {
			$this->database_name = $database_name;
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 *	executes a query
	 *	@param	string	query string
	 *	@return	mydb	the current mydb object ( $this )
	 */
	function query( $query ) {
		$key = count( $this->queries );
		
		$this->queries[ $key ] = array( 'query' => '', 'error' => '', 'errno' => 0, 'affected_rows' => 0 );
		
		$this->queries[ $key ][ 'query' ] = $query;
		
		$this->results = $this->db->query( $query );
		
		if( $this->results !== false ) {
			$this->queries[ $key ][ 'affected_rows' ] = $this->db->affected_rows;	
		} else {
			$this->queries[ $key ][ 'error' ] = $this->db->error;
			$this->queries[ $key ][ 'errno' ] = $this->db->errno;			
		}
		
		if(  ( stripos( $query, 'INSERT' ) !== false ) && ( stripos( $query, 'INSERT' ) === 0 ) ) {
			$this->insert_id = $this->db->insert_id;
		}
		
		return $this;
	}
	
	/**
	 *	escapes a string
	 *	@param	string	the string to escape
	 *	@return	string	escaped string
	 */
	function escape_string( $string ) {
		return $this->db->real_escape_string( $string );
	}
	
	/**
	 *	returns the number of affected rows of the last query
	 *	@return	boolean	true if no errors were found, false if they were
	 */
	function affected_rows() {
		if( empty( $this->queries ) )
			return false;
		
		$last = end( $this->queries );
		
		return $last[ 'affected_rows' ];
	}
	
	/**
	 *	the last insert ID
	 *	@return	int	last insert ID
	 */
	function get_insert_id() {
		return $this->insert_id;
	}
	
	/**
	 *	gets all the queries
	 *	@return	array	with queries
	 */
	function get_queries() {
		return $this->queries;
	}
	
	/**
	 *	a results array
	 *	@return	array	with results
	 */
	function get() {
		$results = array();
		
		if( empty( $this->results ) )
			return $results;
		
		$results = $this->results->fetch_all( MYSQLI_ASSOC );
		
		$this->results->free();
		
		return $results;
	}
	
	/**
	 *	checks is given name is a valid database
	 *	@param	string		database name
	 *	@return	boolean	true if valid, false if not
	 */
	function is_database( $database_name ) {
		$databases = $this->query( 'SHOW DATABASES' )->get();
		
		if( ! empty( $databases ) ) {
			foreach( $databases as $database ) {
				if( current( $database ) == $database_name )
					return true;
			}			
		}
		
		return false;
	}	
	
	/**
	 *	checks if given table_name is valid for database
	 *	@param	string		table name
	 *	@param	string		database name
	 *	@return	boolean	true if valid, false if not
	 */
	function is_table( $table_name, $database_name = null ) {
		// default to current database if no database passed
		if( empty( $database_name ) )
			$database_name = $this->database_name;
		
		if( empty( $database_name ) ) {
			$this->log_error( 'mydb->is_table error: no database passed' );
			return false;
		}
		
		$query = "SHOW TABLES FROM {$database_name}";
		
		$tables = $this->query( $query )->get();
		
		if( ! empty( $tables ) ) {
			foreach( $tables as $table ) {
				if( current( $table ) == $table_name )
					return true;
			}			
		}
		
		return false;
	}
	
	/**
	 *	adds an error message to the log file
	 *	@param	string		error message to log
	 *	@return	boolean	false on failure, true on success
	 */
	function log_error( $errormsg ) {
		$file_path = empty( $this->log_file ) ? ( defined( 'MyDB_LOG_FILE' ) ? MyDB_LOG_FILE: null ): $this->log_file;
		
		// no path set
		if( empty( $file_path ) )
			return false;
		
		$date = date( 'Y-m-d H:i:s' );
		if( file_put_contents( $file_path, sprintf( "%s\n%s\n---\n", $date, $errormsg ), FILE_APPEND ) )
			return true;
		else
			return false;
	}
	
	/**
	 *	sets the log file path
	 *	@param	string	log file path
	 */
	function set_log_file( $file_name ) {
		$this->log_file = strval( $file_name );
	}
	
	/**
	 *	this method closes the connection whenever it's no longer needed
	 */
	function __destruct() {
		$this->db->close();
	}
	
}




