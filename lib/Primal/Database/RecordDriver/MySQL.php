<?php
namespace Primal\Database\RecordDriver;

/**
 * Primal Record MySQL Data Driver
 * 
 * @package Primal
 * @author Jarvis Badgley
 * @copyright 2008 - 2012 Jarvis Badgley
 */

trait MySQL {


	/**
	 * Function to generate the select query for loading a record
	 *
	 * @param array $lookup Data to control which row is loaded
	 * @return array Tuple containing the query string and parameter data
	 */
	protected function buildSelectQuery($tablename, array $lookup, $limit = 0) {
		$where = array();
		$data = array();
		foreach ($lookup as $column=>$param) {
			$where[] = "`{$column}` = :W$column";
			$data[":W$column"] = $param;
		}
		$where = implode(' AND ', $where);

		$query = "SELECT * FROM {$tablename} WHERE {$where}" . ($limit ? " LIMIT ".(int)$limit : '');

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
			if (isset($lookup[$column])) continue; //don't need to update primary key values

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
	 * Loads the MySQL table structure and returns it as an array
	 * Will be overridden to support other databases
	 *
	 * @return array
	 */
	public function buildTableSchema($tablename = null) {

		if (is_array($tablename)) {
			$results = $tablename;
		} else {
			if ($tablename === null) $tablename = $this->tablename;
			$query = $this->pdo->query("DESCRIBE {$tablename}");
			$results = $query->fetchAll(PDO::FETCH_ASSOC);
		}

		$structure = array(
			'columns'=>array(),
			'primaries'=>array(),
			'auto_increment'=>false,
			'loaded' => true
		);

		foreach ($results as $result) {
			$column_name = $result['Field'];
			$matches = array();

			$column = array(
				'null'      => ($result['Null'] === 'YES'),
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
				$column['options'] = array_map(function ($o) {return substr($o,1,-1);}, explode(',',$matches[1]));
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


}