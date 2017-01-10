<?php
/**
 *	class to represent a table in a database
 *
 *	@package mydb
 *	@version 0.1
 *	@author Thapelo Moeti
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 * @note This program is distributed in the hope that it will be useful - WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 */
class mydb_table {

	/**
	 *	save the db object
	 *	@var	mydb
	 */
	private $db = null;
	
	/**
	 *	save the table name
	 *	@var	string
	 */
	private $name = null;
	
	/**
	 *	save the limit for the next query
	 *	@var	string
	 */
	private $limit = null;
	
	/**
	 *	save the table fields info
	 *	@var	array
	 */
	private $fields_info = array();
	
	/**
	 *	save the table field names
	 *	@var	array
	 */
	private $field_names = array();
	
	/**
	 *	save the primary key fields
	 *	@var	array
	 */
	private $primary_keys = array();
	
	/**
	 *	save the required fields
	 *	@var	array
	 */
	private $required_fields = array();
	
	/**
	 *	save the where data
	 *	@var	array
	 */
	private $where = array();
	
	/**
	 *	construct
	 *	@param	mydb			object used to connect to the database
	 *	@param	string			table name
	 *	@return 	mydb_table	the current mydb_table object (this)
	 */
	function __construct( mydb &$db, $table_name ) {
		$database_name = $db->get_db();
		if( empty( $database_name ) )
			trigger_error( 'MyDB_TABLE: No database set', E_USER_ERROR );
		
		if( ! $db->is_table( $table_name ) )
			trigger_error( "MyDB_TABLE: Table {$table_name} doesn't exist", E_USER_ERROR );
		
		$this->db = $db;
		
		$this->name = $table_name;
		
		$this->load_fields();
		
		return $this;
	}
	
	/**
	 *	loads all table fields
	 */
	private function load_fields() {
		$query = "SHOW COLUMNS FROM {$this->name}";
		
		$fields = $this->db->query( $query )->get();
		
		if( empty( $fields ) )
			return;
		
		$this->fields_info = $fields;
		
		foreach( $fields as $f ) {
			$this->field_names[] = $f[ 'Field' ];
			
			if( $f[ 'Key' ] == 'PRI' )
				$this->primary_keys[] = $f[ 'Field' ];
			
			if( $f[ 'Null' ] == 'No' ) {
				if( ! empty( $f[ 'Default' ] ) || $f[ 'Extra' ] != 'auto_increment' )
					$this->required_fields[] = $f[ 'Field' ];				
			}
		}
	}
	
	/**
	 *	it gets a single field info
	 *	@param	string		field name
	 *	@return	boolean	false if field not found, or array with field info
	 */
	function get_field( $name ) {
		foreach( $this->fields_info as $f ) {
			if( $f[ 'Field' ] === $name )
				return $f;
		}
		
		return false;
	}
	
	/**
	 *	escapes some data to input to the database
	 *	@param	string	field name
	 *	@param	mixed	field value
	 *	@return	mixed	escaped field value
	 */
	function escape_field( $field_name, $value ) {
		$field = $this->get_field( $field_name );
		
		if( empty( $field ) )
			return;
		
		$type = '';
		if( ($pos = strpos( $field[ 'Type' ], " " ) ) !== false )
			$type = substr( $field[ 'Type' ], 0, $pos );
		else
			$type = $field[ 'Type' ];
		
		if( strpos( $type, "int" ) !== false )
			return (int) $value;
		elseif ( stripos( $type, "time") !== false || stripos( $type, "date") !== false || stripos( $type, "year" ) !== false )
			return sprintf( "'%s'", $this->db->escape_string( $value ) );
		elseif ( stripos( $type, "varchar") !== false || stripos( $type, "text") !== false || stripos( $type, "blob" ) !== false )
			return sprintf( "'%s'", $this->db->escape_string( $value ) );
		elseif ( stripos( $type, "float") !== false || stripos( $type, "double") !== false )
			return (float) $value;
		
		return $value;
	}
	
	/**
	 *	escapes some data to input to the database
	 *	@param	array	with data
	 *	@return	array	with escaped data
	 */
	function escape_fields( array $data ) {
		$escaped = array();
		
		foreach( $data as $k => $v ) {
			if( in_array( $k, $this->field_names, true ) )
				$escaped[ $k ] = $this->escape_field( $k, $v );
		}
		
		return $escaped;
	}
	
	/**
	 *	runs a query, then reset some fields
	 *	@param	string	query string to run
	 */
	private function query( $query_string ) {
		$this->db->query( $query_string );
		
		$this->where = array();
		$this->limit = null;
		$this->insert_id = null;
	}
	
	/**
	 *	add new row to table
	 *	@param	array		to populate fields
	 *	@return	boolean	true if success, false if can't
	 */
	function add( array $data ) {
		$data = $this->strip( $data );
		
		$dk = array_keys( $data );
		$dif = array_diff( $this->required_fields, $data );
		
		if( count( $dif ) ) {
			$this->db->log_error( 'mydb_table->add error: not all required fields supplied' );
			return false;
		}
		
		$data = $this->escape_fields( $data );
		
		if( empty( $data ) ) {
			$this->db->log_error( 'mydb_table->add error: no data after escaping fields' );
			return false;
		}
		
		$keys = array_keys( $data );
		$keys = implode( ', ', $keys );
		
		$values = implode( ', ', $data );
		
		$query = "INSERT INTO {$this->name} ({$keys}) VALUES ({$values})";
		
		$this->query( $query );
		
		if( $this->db->affected_rows() ) {
			if( $this->db->insert_id() )
				$this->insert_id = $this->db->insert_id();
			
			return true;
		}
		else
			return false;
	}
	
	/**
	 *	get rows from table
	 *	@param	array	fields to get
	 *	@param	string	limit number of rows to get
	 *	@return	array	with data
	 */
	function get( array $filter_fields = array(), $limit = null ) {
		$fields = '*';
		
		if( ! empty( $filter_fields ) ) {
			$first = true;
			
			foreach( $filter_fields as $f ) {
				if( ! in_array( $f, $this->field_names, true ) ) {
					$this->db->log_error( 'mydb_table->get warning: invalid filter field: ' . $f );
					continue;
				}
				
				if( ! $first )
					$fields .= ', ';
				else
					$fields = '';
				
				$fields .= $f;
			}
		}
		
		if( ! empty( $limit ) )
			$this->set_limit( $limit );
		
		$where = $this->get_where();
		
		$limit = $this->get_limit();
		
		$query = "SELECT {$fields} FROM {$this->name}{$where}{$limit}";
		
		$this->query( $query );
		
		return $this->db->get();
	}
	
	/**
	 *	deletes a row from table
	 *	@param	array		with at least one key value pair, or primary keys if table has one
	 *	@return	boolean	true if success, false if can't
	 */
	function delete( array $data = array() ) {
		$deleted = false;
		$data = $this->strip( $data );
		
		if( empty( $data ) && empty( $this->where ) ) {
			$this->db->log_error( 'mydb_table->delete error: no data supplied' );
			return false;
		}
		
		if( ! empty( $data ) )
			$this->where( $data );
		
		// if table has primary key(s), verify that they are in the where clause
		if( $this->primary_keys && ! $this->where_has_primary() ) {
			$this->db->log_error( 'mydb_table->delete error: all primary keys required' );
			return false;
		}
		
		$where = $this->get_where();
		
		$query = "DELETE FROM {$this->name}{$where}";
		
		$this->query( $query );
		
		if( $this->db->affected_rows() )
			$deleted = true;
		
		return $deleted;
	}
	
	/**
	 *	updates an entry to the table
	 *	@param	array		data to update
	 *	@return	boolean	true if success, false if can't
	 */
	function update( array $data ) {
		$data = $this->strip( $data );
		
		if( $this->primary_keys ) {
			$dk = array_keys( $data );
			$dif = array_diff( $this->primary_keys, $dk );
			
			if( count( $dif ) ) {
				// primary key not in data, but might be in where, lets check
				if(  ! $this->where_has_primary() ) {
					$this->db->log_error( 'mydb_table->update error: primary keys required' );
					return false;					
				}
			} else {			
				$pk = array_flip( $this->primary_keys );	
				
				$where = array_intersect_key( $data, $pk );
				
				$this->where( $where );
				
				// remove primary_keys from $data
				$data = array_diff_key( $data, $pk );				
			}
		}
		
		if( empty( $data ) ) {
			$this->db->log_error( 'mydb_table->update error: no set data supplied' );
			return false;
		}
		
		$data = $this->escape_fields( $data );
		
		$set = '';
		
		$first = true;
		foreach( $data as $k => $v ) {
			if( $first )
				$first = false;
			else
				$set .= ', ';
			 
			$set .= sprintf( '%s=%s', $k, $v );
		}
		
		$where = $this->get_where();
		
		$query = sprintf( 'UPDATE %s SET %s%s', $this->name, $set, $where );
		
		$this->query( $query );
		
		if( $this->db->affected_rows() ) {
			return true;
		} else {
			return false;
		}
		
	}
	
	/**
	 *	gets where clause
	 *	@return string with where clause
	 */
	private function get_where() {
		$where = '';
		
		if( ! empty( $this->where ) ) {
			$first = true;
			
			$where = ' WHERE ';
			
			$where .= implode( ' AND ', $this->where );
		}
		
		return $where;
	}
	
	/**
	 *	checks if where clause array has table primary keys
	 *	@return	boolean	true if all keys are there, false if not
	 */
	private function where_has_primary() {		
		if( $this->primary_keys ) {
			$keys = count( $this->primary_keys );
			$found = 0;
			
			foreach( $this->where as $w ) {
				$field = stristr( $w, ' ', true );
				
				if( in_array( $field, $this->primary_keys, true ) )
					$found++;
			}
			
			// all found?
			if( $keys == $found )
				return true;
			else
				return false;
		} else {
			// table doesn't have primary keys
			return true;
		}
	}
	
	/**
	 *	adds to the where variable
	 *	@param	array			with data
	 *	@param	string			to join where
	 *	@return	mydb_table	this object
	 */
	function where( array $data, $operator = '=' ) {
		foreach( $data as $k => $v ) {
			if( ! $this->get_field( $k ) )
				continue;
			
			$this->where[] = "{$k} {$operator} " . $this->escape_field( $k, $v ); 
		}
		
		return $this;
	}
	
	/**
	 *	sets limit
	 *	@param	string			limit string
	 *	@return	mydb_table	@this
	 */
	function set_limit( $limit ) {
		if( preg_match( '/^(\d+)\s?(,\s?\d+)?$/', $limit ) )
			$this->limit = $limit;
		
		return $this;
	}
	
	/**
	 *	gets limit
	 *	@return	string	limit string
	 */
	function get_limit() {
		if( empty( $this->limit ) )
			return '';
		else
			return " LIMIT {$this->limit}";
	}
	
	/**
	 *	strips an array to only the keys that match the fields of the table
	 *	@param	array	full of data
	 *	@return	array	with only table fields
	 */
	function strip( array $data ) {
		if( empty( $data ) )
			return array();
		
		$tf = array_flip( $this->field_names );
		
		return array_intersect_key( $data, $tf );
	}
	
}



