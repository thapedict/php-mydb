<?php
/**
 *	class to represent database object
 *
 *	@package mydb
 *	@version 0.1
 *	@author Thapelo Moeti
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 * @note This program is distributed in the hope that it will be useful - WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 */
class mydb_database {

	/**
	 *		to save the db object
	 *		@var mydb
	 */
	private $db;
	
	/**
	 *		to save the current database table names
	 *		@var array
	 */	
	private $table_names = array();
	
	/**
	 *		to save the current database actual tables objects
	 *		@var array
	 */	
	private $tables = array();
	
	/**
	 *		the constructor
	 */
	function __construct( mydb &$db, $database_name = null ) {
		// if the database_name is passed, try and set it
		if( ! is_null( $database_name ) ) {
			if( ! $db->select_db( $database_name ) )
				trigger_error( 'MyDB_DATABASE: Invalid database name', E_USER_ERROR );			
		}
		
		// we need a valid database, trigger error if not set
		$database_name = $db->get_db();
		if( empty( $database_name ) )
			trigger_error( 'MyDB_DATABASE: No database set', E_USER_ERROR );
		
		$this->db = $db;
		
		// load tables
		$this->load_tables();
		
		return $this;
	}
	
	/**
	 *		loads up the current database tables
	 */
	private function load_tables() {
		$tables = $this->db->query( 'SHOW TABLES' )->get();
		
		if( ! empty( $tables ) ) {
			foreach( $tables as $t ) {
				$name = $this->table_names[] = current( $t );
				
				$this->tables[ $name ] = new mydb_table( $this->db, $name );
			}			
		}
	}	
	
	/*
	 *		returns the current loaded table names
	 *		@return array with all the table's names
	 */
	function get_table_names() {
		return $this->table_names;
	}
	
	/*
	 *		returns the current loaded tables
	 *		@return array with mydb_table objects
	 */
	function get_tables() {
		return $this->tables;
	}
	
	/**
	 *		returns the a mydb_table object
	 *		@return mydb_table object if name is a valid table name, or false if no table with that name is found
	 */
	function get_table( $name ) {
		if( ! $this->is_table( $name ) )
			return false;
		
		return $this->tables[ $name ];
	}
	
	/**
	 *		check if given name is valid table
	 *		@return boolean
	 */
	function is_table( $name ) {
		if( in_array( $name, $this->table_names ) )
			return true;
		else
			return false;
	}
	
	/**
	 *		catches all table requests
	 *		@return mydb_table object
	 */	
	function __get( $property ) {
		// if valid table name
		if( in_array( $property, $this->table_names ) )
			return $this->tables[ $property ];
		
		// trigger error with data to help track down the script causing it
		$trace = debug_backtrace();
		trigger_error( "MyDB_DATABASE Error: Unknown property '{$property}'.<br/>\n[ FILE: {$trace[0]['file']}]<br/>\n[LINE:{$trace[0]['line']}]<br/>\n", E_USER_ERROR );
	}
	
}



