<?php
namespace Primal\Database;
use \PDO;
use \InvalidArgumentException, \DomainException;
use \DateTime;

/**
 * Primal\Database\Pool - PDO Database link management library
 * 
 * @package Primal
 * @author Jarvis Badgley
 * @copyright 2008 - 2012 Jarvis Badgley
 */

abstract class Record extends \ArrayObject {
	
	/**
	 * Name of the database table this model will be interfacing with.
	 *
	 * @var string
	 * @access protected
	 */
	protected $tablename;


	/**
	 * Object describing the table structure
	 *
	 * @var string
	 */
	protected $schema = false;


	/**
	 * PDO object used for communication with the server
	 * If this is not set by the descendent, Record attempts to use Connection::Link()
	 *
	 * @var array
	 * @access protected
	 */
	protected $pdo;
	
	
	/**
	 * Record was found upon loading.
	 *
	 * @var boolean
	 */
	public $found = null;
	
		
	/**
	 * Class constructor.
	 *
	 * @param string $pdo 
	 * @param string $search Optional value to load a record based on.  If string or integer, will treat as primary key. If array, will treat as key/value pairs of column/data.
	 */
	public function __construct($pdo = null, $search = null, $field = null) {
		
		if ($pdo !== null) {
			if (!($pdo instanceof PDO)) {
				throw new InvalidArgumentException("Expected PDO link for first argument, found ".get_class($pdo));
			}
			$this->setPDO($pdo);
		}
		
		if ($search !== null) {
			$this->load($search, $field);
		}
		
	}
	
	/**
	 * Sets the internal PDO link
	 *
	 * @param PDO $pdo 
	 * @return Record
	 */
	public function setPDO($pdo) {
		if (!($pdo instanceof PDO)) {
			throw new InvalidArgumentException("Expected PDO link, found ".get_class($pdo));
		}
		
		$this->pdo = $pdo;
		
		return $this;
	}
	
		
/**
	Content manipulation functions
*/
	
	/**
	 * Exports the record contents as a named array.
	 *
	 * @return array
	 */	
	public function export() {
		return $this->getArrayCopy();
	}

	/**
	 * Imports a named array into the record contents.
	 *
	 * @param array $in
	 */	
	public function import($in) {
		if ($in instanceof Record) { //if it's a fellow record, get the record's data
			$in = $in->export();
		} elseif (!is_array($in)) { //otherwise, make sure that we have an array
			throw new InvalidArgumentException("Expected an array, found ".get_class($in));
		}
		$this->exchangeArray( array_merge($this->export(), $in) );
		return $this;
	}
	
	/**
	 * Removes the named columns from the record set (blacklist)
	 *
	 * @param string|array $fields Column name(s) to remove
	 * @return Record
	 */
	public function filter() {
		foreach (func_get_args() as $column) {
			unset($this[$column]);
		}
		return $this;
	}


	/**
	 * Removes all columns not included in the passed set (whitelist)
	 *
	 * @param string|array $fields Column name(s) to remove
	 * @return Record
	 */
	public function allow() {
		foreach (array_diff(array_keys($this->export()), func_get_args()) as $column) {
			unset($this[$column]);
		}
		return $this;
	}
	
	
	/**
	 * Identifies if the row this object represents exists in the database
	 *
	 * @return void
	 */
	public function exists() {
		return $this->found;
	}
	
	
	/**
	 * Set Magic Method, alias to array contents
	 *
	 * @param string $column 
	 * @param string $value 
	 * @return void
	 */
	public function __set($column, $value) {
		$this[$column] = $value;
	}
	
	/**
	 * Get Magic Method, alias to array contents
	 *
	 * @param string $column 
	 * @return mixed
	 */
	public function __get($column) {
		return $this[$column];
	}
	
	/**
	 * IsSet magic Method, alias to array contents
	 *
	 * @param string $column 
	 * @return boolean
	 */
	public function __isset($column) {
		return isset($this[$column]);
	}
	
	
/**
	Data loading functions
*/
	
	/**
	 * Loads a record from the database.
	 *
	 * Syntax:
	 * 		$o->load()
	 *			Loads the record using primary keys already present in the record array.
	 *
	 * 		$o->load(value)
	 *			Loads the record using a single primary key value
	 *
	 * 		$o->load(value, columnName)
	 *			Loads the record using a single value in the named column.  If multiple rows are found with that value the first row returned is used.
	 *
	 * 		$o->load(array('columnName'=>'value', ... ))
	 *			Loads the record using a sequence of columnName/Value pairs.  Necessary if your table contains a multi-column primary key.
	 *
	 * @param integer|string|array $search optional The value of the primary key, an array of key/value pairs to search for.  If absent, the function will attempt to load using information already present in the record.
	 * @param string $field optional If $value is an integer or string, $field may be used to define a specific column name to search within.
	 * @return boolean True if a matching record was found
	 */
	public function load($search=null, $field=null) {
		$this->checkSchema();
	
		$lookup = array();
	
		$pkeycount = count($this->schema['primaries']);
		
		if ($search === null) {
			if ($pkeycount == 0) {
				throw new MissingKeyException("Could not load record using existing data; table has no primary keys.");
			}
			
			foreach ($this->schema['primaries'] as $pkey) {
				if (!isset($this[$pkey])) {
					throw new MissingKeyException("Could not load record, required primary key value was absent: $pkey");
				} else {
					$lookup[$pkey] = $this->parseColumnDataForQuery($pkey, $this[$pkey]);
				}
			}
		} elseif (is_array($search)) {
			
			if (empty($search)) {
				throw new MissingKeyException("Could not load record using empty array.");
			}
			
			if (array_values($search) === $search) {
				throw new MissingKeyException("Loading by array requires an associative array of column/value pairs.");
			}
			
			$lookup = array();
			foreach ($search as $column=>$param) {
				$lookup[$column] = $this->parseColumnDataForQuery($column, $param);
			}
			
		} elseif (is_scalar($search)) {

			if ($field === null) {
			
				if ($pkeycount != 1) {
					throw new MissingKeyException("Could not load record using single value; table more than one primary key.");
				}
			
				$pkey = reset($this->schema['primaries']);
				$lookup[$pkey] = $this->parseColumnDataForQuery($pkey, $search);
				
			} elseif (is_string($field)) {

				$lookup[$field] = $this->parseColumnDataForQuery($field, $search);
				
			} else {
				
				throw new MissingKeyException("Could not load record using passed field; expected string, found ".gettype($field));
				
			}
		}
		
		list($query, $data) = $this->buildSelectQuery($this->tablename, $lookup);
		
		return $this->found = $this->loadRecord($query, $data);
		
		
	}
	
	
	/**
	 * Loads a record from the database using a developer provided query string
	 *
	 * @param string $query 
	 * @param array $data Named parameters for binding
	 * @return void
	 */
	public function loadUsingQuery($query, $data = null) {
		
		return $this->found = $this->loadRecord($query, $data);
		
	}
	
	/**
	 * Executes the passed query and, if successful, loads the result into the object record
	 *
	 * @param string $query 
	 * @param string $data Named parameters for binding
	 * @return void
	 */
	protected function loadRecord($query, $data = null) {
		if ( $result = $this->executeQuery($query, $data) ) {			

			$this->import($result->fetch(PDO::FETCH_ASSOC));
			return true;

		} else {

			return false;

		}
		
	}
	
	/**
	 * Saves the contents of the record as a new row in the database, updating the auto_increment value if one exists.
	 *
	 * @param boolean $replace Perform the insert as a REPLACE INTO
	 * @return boolean
	 */
	public function insert($replace = false) {
		$this->checkSchema();
		$this->testColumnDataFormats();
		
		$write = array();
		foreach ($this as $column=>$data) {
			if (isset($this->schema['columns'][$column])) {
				$write[$column] = $this->parseColumnDataForQuery($column, $data);
			}
		}
		
		list($query, $data) = $this->buildInsertQuery($this->tablename, $write, $replace);
		
		if ( $this->executeQuery($query, $data) ) {
			
			if (isset($this->schema['auto_increment']) && $this->schema['auto_increment']) {
				$this[$this->schema['auto_increment']] = $this->pdo->lastInsertId();
			}
			
			$this->found = true;
			
			return true;
			
		} else {
			
			return false;
			
		}
		
	}
	
	/**
	 * Saves the contents of the record into an existing row using the values of the primary keys
	 *
	 * @return boolean
	 */
	public function update() {
		$this->checkSchema();
		$this->testColumnDataFormats();
		
		if (empty($this->schema['primaries'])) {
			throw new MissingKeyException("Could not update record; table has no primary keys.");
		}
		
		$lookup = array();
		foreach ($this->schema['primaries'] as $pkey) {
			if (!isset($this[$pkey])) {
				throw new MissingKeyException("Could not update record, required primary key value was absent: $pkey");
			} else {
				$lookup[$pkey] = $this->parseColumnDataForQuery($pkey, $this[$pkey]);
			}
		}
		
		$write = array();
		foreach ($this as $column=>$data) {
			if (isset($this->schema['columns'][$column])) {
				$write[$column] = $this->parseColumnDataForQuery($column, $data);
			}
		}
		
		list($query, $data) = $this->buildUpdateQuery($this->tablename, $write, $lookup);

		return (boolean)$this->executeQuery($query, $data);
		
	}
	
	/**
	 * Saves the contents of the record, automatically determining if an update or insert is needed.
	 *
	 * @param boolean $replace Forces a REPLACE INTO insert.
	 * @return boolean
	 */
	public function save($replace = false) {
		
		if ($replace) {
			
			return $this->insert(true);
			
		} elseif ($this->found === null) {
			//we don't know if this record exists, so we need to find out
			
			$this->checkIfExists();
		}
		
		if ($this->found) {
			return $this->update();
		} else {
			return $this->insert();
		}
			
	}
	
	/**
	 * Changes a single value on the record and immediately updates the database with that value. Performs an insert if no record exists.
	 *
	 * @param string $column 
	 * @param string $value 
	 * @return boolean
	 */
	public function set($column, $value) {
		if (!isset($this->schema['columns'][$column])) {
			throw new ColumnNotInSchemaException("$column is not a column in the {$this->tablename} table.");
		}
		
		$this[$column] = $value;
		
		if ($this->found === null) {
			//we don't know if this record exists, so we need to find out
			
			$this->checkIfExists();
		}
		
		$write = array();
		$write[$column] = $this->parseColumnDataForQuery($column, $value);

		if ($this->found) {
			//record exists, perform a single field update
			
			$lookup = array();
			foreach ($this->schema['primaries'] as $pkey) {
				if (!isset($this[$pkey])) {
					throw new MissingKeyException("Could not update record, required primary key value was absent: $pkey");
				} else {
					$lookup[$pkey] = $this->parseColumnDataForQuery($pkey, $this[$pkey]);
				}
			}
						
			list($query, $data) = $this->buildUpdateQuery($this->tablename, $write, $lookup);

			return (boolean)$this->executeQuery($query, $data);
			
		} else {
			//record doesn't exist, just do a full insert
			
			return $this->insert();
			
		}

	}
	
	/**
	 * Deletes a row from the database based on the values in the record primary keys
	 *
	 * @return void
	 */
	public function delete() {		
		$this->checkSchema();
		$this->testColumnDataFormats();
		
		$lookup = array();
		foreach ($this->schema['primaries'] as $pkey) {
			if (!isset($this[$pkey])) {
				throw new MissingKeyException("Could not load record, required primary key value was absent: $pkey");
			} else {
				$lookup[$pkey] = $this->parseColumnDataForQuery($pkey, $this[$pkey]);
			}
		}
		
		list($query, $data) = $this->buildDeleteQuery($this->tablename, $lookup);
		
		if ($this->executeQuery($query, $data)) {
			$this->found = false;
			return true;
		} else {
			return false;
		}
		
	}
	
	
	/**
	 * Tests to see if the record exists in the database based on the primary key values
	 *
	 * @return boolean
	 */
	public function checkIfExists() {
		$this->checkSchema();
		$this->testColumnDataFormats();
		
		$lookup = array();
		foreach ($this->schema['primaries'] as $pkey) {
			if (!isset($this[$pkey])) {
				return $this->found = false;
			} else {
				$lookup[$pkey] = $this->parseColumnDataForQuery($pkey, $this[$pkey]);
			}
		}
		
		list($query, $data) = $this->buildSelectQuery($this->tablename, $lookup);
		
		return $this->found = (boolean)$this->executeQuery($query, $data);
		
	}
	
/**
	Public Aggrigate Calls
*/	

	public static function LoadMultiple($pdo, $query, $data = null) {
		$instance = new static($pdo);
		
		//received an array of column=>value pairs to search on.
		if (is_array($query) && $data === null) {
			
			$lookup = array();
			foreach ($query as $column=>$param) {
				$lookup[$column] = $instance->parseColumnDataForQuery($column, $param);
			}

			list($query, $data) = $instance->buildSelectQuery($instance->tablename, $lookup);
			
		}
		
		
		if (!$query) $query = "SELECT {$tablename}.* FROM {$tablename}";
		else {
			$query_command = explode(' ',$query);
			$query_command = strtoupper(reset($query_command));

			switch ($query_command) {
				case "INSERT":
				case "REPLACE":
				case "DELETE":
					throw new InvalidArgumentException("You should not be using LoadMultiple for {$query_command} queries");

				case "WHERE":
				case "GROUP":
				case "ORDER":
				case "LIMIT":
					$query = "SELECT * FROM {$instance->tablename} {$query}";
					break;
			}
		}
		
		
		$results = array();
		if ($result = $instance->executeQuery($query, $data)) {
			foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $row) {
				$o = new static($pdo);
				$o->import($row);
				$o->found = true;
				$results[] = $o;
			}
		}
		
		return $results;
	}
	
	
	
/**
	Schema Processing and Query construction Functions
*/

	/**
	 * Runs the passed query using the internal PDO link.  Returns the PDOStatement object if successful
	 *
	 * @param string $query 
	 * @param array $data 
	 * @return false|PDOStatement
	 */
	protected function executeQuery($query, $data) {
		$result = $this->pdo->prepare($query);
		
		if ( (is_array($data) ? $result->execute($data) : $result->execute()) && $result->rowCount() > 0) {
						
			return $result;
			
		} else {
			
			return false;
			
		}
		
	}
	
	/**
	 * Function to generate the select query for loading a record
	 *
	 * @param array $lookup Data to control which row is loaded
	 * @return array Tuple containing the query string and parameter data
	 */
	protected function buildSelectQuery($tablename, array $lookup, $limit = true) {
		$where = array();
		$data = array();
		foreach ($lookup as $column=>$param) {
			$where[] = "`{$column}` = :W$column";
			$data[":W$column"] = $param;
		}
		$where = implode(' AND ', $where);
		
		$query = "SELECT * FROM {$tablename} WHERE {$where}" . ($limit ? " LIMIT 1" : '');
		
		return array($query, $data);
	}


	/**
	 * Function to generate the insert query for saving a new record
	 *
	 * @param string $tablename 
	 * @param array $write Data to be stored
	 * @param boolean $replace Should the insert be performed as a replacement
	 * @return array Tuple containing the query string and parameter data
	 */
	protected function buildInsertQuery($tablename, array $write, $replace = false) {
		$set = array();
		$data = array();
		foreach ($write as $column=>$param) {
			$set[] = "`{$column}` = :S$column";
			$data[":S$column"] = $param;
		}
		$set = implode(', ', $set);
		
		$query = ($replace ? "REPLACE" : "INSERT") . " INTO {$tablename} SET {$set}";
		
		return array($query, $data);
	}
	
	/**
	 * Function to generate the update query for saving an existing record
	 *
	 * @param string $tablename 
	 * @param array $write Data to be stored
	 * @param array $lookup Data to control which row is updated
	 * @return void
	 */
	protected function buildUpdateQuery($tablename, array $write, array $lookup) {
		$set = array();
		$where = array();
		$data = array();
		
		foreach ($write as $column=>$param) {
			$set[] = "`{$column}` = :S$column";
			$data[":S$column"] = $param;
		}
		
		foreach ($lookup as $column=>$param) {
			$where[] = "`{$column}` = :W$column";
			$data[":W$column"] = $param;
		}
		
		$set = implode(', ', $set);
		$where = implode(' AND ', $where);
		
		$query = "UPDATE {$tablename} SET {$set} WHERE {$where}";
		
		return array($query, $data);
	}


	/**
	 * Function to generate the delete query for removing an existing record
	 *
	 * @param string $tablename 
	 * @param array $write Data to be stored
	 * @param array $lookup Data to control which row is updated
	 * @return void
	 */
	protected function buildDeleteQuery($tablename, array $lookup) {
		$where = array();
		$data = array();
		
		foreach ($lookup as $column=>$param) {
			$where[] = "`{$column}` = :W$column";
			$data[":W$column"] = $param;
		}
		
		$where = implode(' AND ', $where);
		
		$query = "DELETE FROM {$tablename} WHERE {$where}";
		
		return array($query, $data);
	}

	/**
	 * Intended access point for table structure vs buildTableSchema. 
	 * Uses a static local variable (which is retained at the subclass level) to retain structure between instances
	 * Override this in a subclass if you wish to use a more persistent cache.
	 *
	 * @return array Table structure
	 */
	protected function getCachedSchema() {
		static $structure;
	
		return $structure ?: $structure = $this->buildTableSchema();
	}


	/**
	 * Loads the MySQL table structure and returns it as an array
	 * Will be overridden to support other databases
	 *
	 * @return array
	 */
	protected function buildTableSchema($tablename = null) {

		if ($tablename === null) $tablename = $this->tablename;

		$structure = array(
			'columns'=>array(),
			'primaries'=>array(),
			'auto_increment'=>array(),
			'loaded' => true
		);

		$query = $this->pdo->query("DESCRIBE {$tablename}");
		$results = $query->fetchAll(PDO::FETCH_ASSOC);
		
		foreach ($results as $result) {
			$column_name = $result['Field'];
			$matches = array();
			
			$column = array(
				'null'      => ($result['Null'] == 'YES'),
				'unsigned'  => preg_match('/unsigned/', $result['Type']),
				'format'    => 'string',
				'precision' => 0
			);
			
			switch (true) {
			case $result['Type'] === 'date':
				$column['type'] = 'date';
				$column['format'] = 'date';
				break;
			
			case preg_match('/^(datetime|timestamp)$/',                    $result['Type'], $matches):
				$column['type'] = $result['Type'];
				$column['format'] = 'datetime';
				break;
			
			case preg_match('/^decimal\((\d+),(\d+)\)/',                   $result['Type'], $matches):
				$column['type'] = 'decimal';
				$column['format'] = 'number';
				$column['precision'] = $matches[2];
				break;
				
			case preg_match('/^float\((\d+),(\d+)\)/',                     $result['Type'], $matches):
				$column['type'] = 'float';
				$column['format'] = 'number';
				$column['precision'] = $matches[2];
				break;
				
			case preg_match('/^((?:big|medium|small|tiny)?int)\((\d+)\)/', $result['Type'], $matches):
				$column['type'] = $matches[1];
				$column['format'] = 'number';
				break;
				
			case preg_match('/^enum\((.*)\)/',                             $result['Type'], $matches):
				$column['type'] = 'enum';
				$column['format'] = 'enum';
				$column['options'] = array_map(function ($o) {return substr($o,1,-1);}, explode($matches[1]));
				break;
				
			case preg_match('/^((?:var)?char)\((\d+)\)/',                  $result['Type'], $matches):
				//string types
				$column['type'] = $matches[1];
				$column['length'] = $matches[2];
				break;
				
			default:
				$explode = explode('(', $result['Type']);
				$column['type'] = $explode[0];
				break;
			}
			
			
			$structure['columns'][$column_name] = $column;
			
			if ($result['Key'] == 'PRI') {
				$structure['primaries'][] = $column_name;
			}
			
			if ($result['Extra'] == 'auto_increment') {
				$structure['auto_increment'] = $column_name;
			}
		}

		return $structure;
	}

	
	/**
	 * Verifies if the class schema has been defined for the needs of the current task, and loads it if not.
	 *
	 * @param boolean $writing
	 * @return void
	 */
	protected function checkSchema() {
		if (isset($this->schema['loaded'])) return; //this schema was loaded from the DB, we know it'll all be there.
		
		//iterate through all potential data absenses that would mean we need to get the table definition
		do {
			if (!$this->schema) break;
			
			if (!isset($this->schma['primaries'])) break;
			
			if (!is_array($this->schma['primaries'])) break;
			
			if (!isset($this->schema['columns'])) break;
			
			if (!$this->schema['columns']) break;
			
			if (empty($this->schema['columns'])) break;
			
			$this->schema['loaded'] = true; //structure should fully exist, set the loaded property so we don't need to check everything next time
			return;
			
		} while (false);
		
		$this->schema = $this->getCachedSchema();
		
	}
	
/**
	Data validation routines
**/


	/**
	 * Tests that data is present for all primary keys.
	 *
	 * @return void
	 */
	protected function checkPrimaryKeyes() {
		if (!isset($this->schema['primaries']) || !is_array($this->schema['primaries'])) {
			return true;
		}
		
		foreach ($this->schema['primaries'] as $pkey) {
			if (!isset($this[$pkey]) || $this[$pkey]===null) return false;
		}
		return true;
	}
	
	/**
	 * Loops through all record values, verifying that they work with the data type for that column
	 * Exceptions will be thrown if they cannot.
	 *
	 * @return void
	 */
	protected function testColumnDataFormats() {
		foreach ($this as $column=>$data) {
			if (isset($this->schema['columns'][$column])) {
				$this->checkDataFormat($column, $data);
			}
		}
		return true;
	}
	
	protected function testColumnDataFormat($column, $data, $schema = null) {
		//Function is an inversion: returns once the data type is found to be valid.
		//Will throw a DomainException if the data type is invalid.
		
		if ($schema === null) $schema = $this->schema['columns'][$column];

		if ($data === null && !$schema['null']) {
			throw new DomainException("Column $column does not allow a value of null");
		}
		
		$type = $schema['type'];
		switch ($schema['format']) {
		case 'number':
			if (is_numeric($data)) {
				return;
			}
			break;
			
		case 'date':
		case 'datetime':
		
			if (in_array($data, array('','none'))) return; //these are all acceptable values for our dates
			
			try {
				if (new DateTime($data)) {
					return;
				}
			} catch (\Exception $e) {
				//we'll throw the correct exception further down
			}
			break;
		
		case 'enum':
			if (static::IsStringable($data)) {
				if (!in_array((string)$data, $schema['options'])) {
					throw new DomainException("$data is no a valid value for enum column $column.");
				} else {
					return;
				}
			}
			break;
		
		case 'string':
			if (static::IsStringable($data)) {
				return;
			}
		
		}
		
		throw new DomainException("Value for column $column is invalid for the $type column type.");
		
	}
	
	
	/**
	 * Internal function for testing if a passed value can be converted to a string safely
	 *
	 * @param string $item 
	 * @return void
	 */
	protected static function IsStringable($item) {
	   return  !is_array( $item ) &&
	    ( ( !is_object( $item ) && settype( $item, 'string' ) !== false ) ||
	    ( is_object( $item ) && method_exists( $item, '__toString' ) ) );
	}


	/**
	 * Using table schema, process a data value to match the column type
	 *
	 * @param string $schema 
	 * @param string $data 
	 * @return void
	 */
	protected function parseColumnDataForQuery($schema, $data) {
		if (!is_array($schema)) $schema = $this->schema['columns'][$schema];
		
		switch ($format) {
		case 'number':
			return number_format($data, $schema['precision'], '.', '');
			
		case 'date':
		case 'datetime':
		
			if ($data === '' || in_array($data, array('','none'), true)) {
				return "00-00-00 00:00:00";
			}
			
			if (is_integer($value)) {
				return date('Y-m-d H:i:s', $value);
			}
			
			if (is_string($value)) {
				//if the value is a string, convert it to a DateTime and let it trickle down
				try {
					$value = new DateTime($value);
				} catch (\Exception $e) {
				}
			}
			
			if ($value instanceof DateTime) {
				return $value->format('Y-m-d H:i:s');
			}

			//this shouldn't ever happen, given testColumnDataFormat should have seen this coming
			throw new DomainException("Value could not be converted to a valid DateTime object.");			
		
		case 'enum':
		case 'string':
		default:
			return (string)$data;
		
		}
	}
		
	
}


class MissingKeyException extends DomainException {}