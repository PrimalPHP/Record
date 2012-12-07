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
	protected $found = null;
	
		
	/**
	 * Class constructor.
	 *
	 * @param string $pdo 
	 * @param string $search Optional value to load a record based on.  If string or integer, will treat as primary key. If array, will treat as key/value pairs of column/data.
	 */
	public function __construct($pdo = null, $search = null) {
		
		if ($pdo !== null) {
			if (!($pdo instanceof PDO)) {
				throw new InvalidArgumentException("Expected PDO link for first argument, found ".get_class($pdo));
			}
			$this->setPDO($pdo);
		}
		
		if ($search != null) {
			$this->load($search);
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
	Schema Processing and Query construction Functions
*/

	/**
	 * Intended access point for table structure vs loadTableStructure. 
	 * Uses a static local variable (which is retained at the subclass level) to retain structure between instances
	 * Override this in a subclass if you wish to use a more persistent cache.
	 *
	 * @return array Table structure
	 */
	protected function getCachedSchema() {
		static $structure;
	
		return $structure ?: $structure = $this->loadTableStructure();
	}


	/**
	 * Loads the MySQL table structure and returns it as an array
	 * Will be overridden to support other databases
	 *
	 * @return array
	 */
	protected function loadTableSchema() {

		$structure = array(
			'columns'=>array(),
			'primaries'=>array(),
			'auto_increment'=>array(),
			'loaded' => true
		);

		$query = $this->pdo->query("DESCRIBE {$this->tablename}");
		$results = $query->fetchAll(PDO::FETCH_ASSOC);
		
		foreach ($results as $result) {
			$column_name = $result['Field'];
			$matches = array();
			
			$column = array(
				'null'      => ($result['Null'] == 'YES'),
				'unsigned'  => preg_match('/unsigned/', $result['Type']),
				'format'    => 'string',
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

	
	protected function checkSchema($writing = false) {
		if (isset($this->schema['loaded'])) return; //this schema was loaded from the DB, we know it'll all be there.
		
		//iterate through all potential data absenses that would mean we need to get the table definition
		do {
			if (!$this->schema) break;
			
			if (!isset($this->schma['primaries'])) break;
			
			if (!$this->schma['primaries']) break;
			
			if (!$writing) return; 
			//conditions for reading rows are setup and the originating call doesn't need to write, so we can stop here
			
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

	protected function checkPrimaryKeyes() {
		foreach ($this->schema['primaries'] as $pkey) {
			if (!isset($this[$pkey]) || $this[$pkey]===null) return false;
		}
	}
	
	protected function testColumnDataFormats() {
		foreach ($this as $column=>$data) {
			if (isset($this->schema['columns'][$column])) {
				$this->checkDataFormat($column, $data);
			}
		}
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
		
			if (in_array($data, array('','null','none'))) return; //these are all acceptable values for our dates
			
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
	
	protected static function IsStringable($item) {
	   return  !is_array( $item ) &&
	    ( ( !is_object( $item ) && settype( $item, 'string' ) !== false ) ||
	    ( is_object( $item ) && method_exists( $item, '__toString' ) ) );
	}

	
}