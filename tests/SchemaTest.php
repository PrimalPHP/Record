<?php 

include_once __DIR__.'/../lib/Primal/Database/Record.php';

class SchemaTestImplementation extends \Primal\Database\Record {
	
	protected $tablename = true;
	protected $schema = true;
	
}

class SchemaTest extends PHPUnit_Framework_TestCase {
	
	public function testSchemaImportSinglePrimaryKey() {
		$describe = require('support/describe-singlekey.php');
		
		$record = new SchemaTestImplementation();
		$schema = $record->buildTableSchema($describe);
		
		//verify schema elements are there
		$this->assertArrayHasKey('columns', $schema);
		$this->assertArrayHasKey('primaries', $schema);
		$this->assertArrayHasKey('auto_increment', $schema);
		$this->assertArrayHasKey('loaded', $schema);
		
		//verify primary key and auto-increment
		$this->assertEquals(array('member_id'), $schema['primaries']);
		$this->assertEquals('member_id', $schema['auto_increment']);
		
		//verify total columns
		$this->assertCount(15, $schema['columns']);
		
		//verify nulls
		$this->assertArrayHasKey('member_id', $schema['columns']);
		$this->assertArrayHasKey('username',  $schema['columns']);
		$this->assertFalse($schema['columns']['member_id']['null']);
		$this->assertTrue( $schema['columns']['username']['null']);
		
		//test numeric parses
		$this->assertArrayHasKey('industry', $schema['columns']);
		$this->assertEquals('int',    $schema['columns']['industry']['type']);
		$this->assertEquals('number', $schema['columns']['industry']['format']);
		$this->assertEquals(0,        $schema['columns']['industry']['precision']);


		$this->assertArrayHasKey('balance', $schema['columns']);
		$this->assertEquals('decimal',      $schema['columns']['balance']['type']);
		$this->assertEquals('number',       $schema['columns']['balance']['format']);
		$this->assertEquals(2,              $schema['columns']['balance']['precision']);
		

		//test enum parse
		$this->assertArrayHasKey('membership_type', $schema['columns']);
		$this->assertArrayHasKey('options', $schema['columns']['membership_type']);
		$this->assertEquals('enum', $schema['columns']['membership_type']['type']);
		$this->assertEquals('enum', $schema['columns']['membership_type']['format']);
		$this->assertEquals(array('Free','None','Credited','Monthly','Yearly'), $schema['columns']['membership_type']['options']);
	}
	
	public function testSchemaImportMultiplePrimaryKey() {
		$describe = require('support/describe-multikey.php');
		
		$record = new SchemaTestImplementation();
		$schema = $record->buildTableSchema($describe);
		
		//verify primary key and auto-increment
		$this->assertEquals(array('member_id','type'), $schema['primaries']);
		$this->assertFalse($schema['auto_increment']);
		
	}
}
