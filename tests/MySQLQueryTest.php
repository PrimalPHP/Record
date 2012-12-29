<?php 

class MemberRecordTosser extends \Primal\Database\AbstractRecord {
	use \Primal\Database\RecordDriver\MySQL;

	public function __construct() {
		$this->tablename = "members";
		$describe = require('support/describe-singlekey.php');
		$this->schema = $this->buildTableSchema($describe);
	}


	protected function executeQuery($query, $data) {
		throw new MySQLQueryExecutedException($query, $data);
	}
}

class MemberAddressRecordTosser extends \Primal\Database\AbstractRecord {
	use \Primal\Database\RecordDriver\MySQL;

	public function __construct() {
		$this->tablename = "member_addresses";
		$describe = require('support/describe-multikey.php');
		$this->schema = $this->buildTableSchema($describe);
	}


	protected function executeQuery($query, $data) {
		throw new MySQLQueryExecutedException($query, $data);
	}
}


class MySQLQueryExecutedException extends Exception {
	public $query;
	public $data;
	
	function __construct($query, $data) {
		$this->query = $query;
		$this->data = $data;
	}
}

class MySQLQueryTest extends PHPUnit_Framework_TestCase {

	public function testLoadWithPrimary() {
		try {
			$record = new MemberRecordTosser();
			$record->load(18);
			
			$this->assertTrue(false, "Call did not trigger executeQuery.");
		} catch (MySQLQueryExecutedException $e) {
			
			$this->assertEquals('SELECT * FROM members WHERE `member_id` = :Wmember_id', $e->query);
			$this->assertEquals(array(':Wmember_id' => "18"), $e->data);
			
		}
	}

	public function testLoadWithValueField() {
		try {
			$record = new MemberRecordTosser();
			$record->load('chipersoft','username');

			$this->assertTrue(false, "Call did not trigger executeQuery.");
		} catch (MySQLQueryExecutedException $e) {
			
			$this->assertEquals('SELECT * FROM members WHERE `username` = :Wusername', $e->query);
			$this->assertEquals(array(':Wusername' => "chipersoft"), $e->data);
			
		}
	}

	public function testLoadWithArray() {
		try {
			$record = new MemberRecordTosser();
			$record->load(array(
				'membership_type'=>'Free',
				'industry'=>24
			));

			$this->assertTrue(false, "Call did not trigger executeQuery.");
		} catch (MySQLQueryExecutedException $e) {
			
			$this->assertEquals('SELECT * FROM members WHERE `membership_type` = :Wmembership_type AND `industry` = :Windustry', $e->query);
			$this->assertEquals(array(':Wmembership_type' => "Free", ':Windustry' => '24'), $e->data);
			
		}
	}

	public function testLoadWithRecord() {
		try {
			$record = new MemberRecordTosser();
			$record->import(array(
				'member_id'=>36,
				'industry'=>24
			));
			$record->load();

			$this->assertTrue(false, "Call did not trigger executeQuery.");
		} catch (MySQLQueryExecutedException $e) {
			
			$this->assertEquals('SELECT * FROM members WHERE `member_id` = :Wmember_id', $e->query);
			$this->assertEquals(array(':Wmember_id' => "36"), $e->data);
			
		}
	}

	public function testLoadWithTwoPrimaryRecord() {
		try {
			$record = new MemberAddressRecordTosser();
			$record->import(array(
				'member_id'=>36,
				'type'=>'Billing',
				'address_1'=>"1600 Pennsylvania Ave",
				'city'=>'Washington DC',
				'fluke'=>'bad data'
			));
			$record->load();

			$this->assertTrue(false, "Call did not trigger executeQuery.");
		} catch (MySQLQueryExecutedException $e) {
			
			$this->assertEquals('SELECT * FROM member_addresses WHERE `member_id` = :Wmember_id AND `type` = :Wtype', $e->query);
			$this->assertEquals(array(':Wmember_id' => "36", ':Wtype' => 'Billing'), $e->data);
			
		}
	}
	
	public function testLoadMultipleWithArray() {
		try {
			$results = MemberRecordTosser::LoadMultiple(null, array('industry'=>24));

			$this->assertTrue(false, "Call did not trigger executeQuery.");
		} catch (MySQLQueryExecutedException $e) {
			
			$this->assertEquals('SELECT * FROM members WHERE `industry` = :Windustry', $e->query);
			$this->assertEquals(array(':Windustry' => "24"), $e->data);
			
		}
	}

	public function testLoadMultipleWithTruncatedQuery() {
		try {
			$results = MemberRecordTosser::LoadMultiple(null, "ORDER BY `member_id`");

			$this->assertTrue(false, "Call did not trigger executeQuery.");
		} catch (MySQLQueryExecutedException $e) {
			
			$this->assertEquals('SELECT * FROM members ORDER BY `member_id`', $e->query);
			$this->assertNull($e->data);
			
		}
	}

	public function testLoadMultipleWithFullQuery() {
		try {
			$results = MemberRecordTosser::LoadMultiple(null, "SELECT * FROM members WHERE `industry`=:industry ORDER BY `member_id`", array(':industry'=>24));

			$this->assertTrue(false, "Call did not trigger executeQuery.");
		} catch (MySQLQueryExecutedException $e) {
			
			$this->assertEquals('SELECT * FROM members WHERE `industry`=:industry ORDER BY `member_id`', $e->query);
			$this->assertEquals(array(':industry'=>24), $e->data);
			
		}
	}

	public function testInsert() {
		try {
			$record = new MemberAddressRecordTosser();
			$record->import(array(
				'member_id'=>36,
				'type'=>'Billing',
				'address_1'=>"1600 Pennsylvania Ave",
				'city'=>'Washington DC',
				'fluke'=>'bad data'
			));
			$record->insert();

			$this->assertTrue(false, "Call did not trigger executeQuery.");
		} catch (MySQLQueryExecutedException $e) {
			
			$this->assertEquals('INSERT INTO member_addresses SET `member_id` = :Smember_id, `type` = :Stype, `address_1` = :Saddress_1, `city` = :Scity', $e->query);
			$this->assertEquals(array(':Smember_id' => "36", ':Stype' => 'Billing', ':Saddress_1' => '1600 Pennsylvania Ave', ':Scity' => 'Washington DC'), $e->data);
			
		}
	}

	public function testInsertWithAutoIncrement() {
		try {
			$record = new MemberRecordTosser();
			$record->import(array(
				'username'=>'chipersoft',
				'email'=>'chiper@chipersoft.com',
				'firstname'=>"Jarvis",
				'balance'=>6000.256,
				'fluke'=>'bad data'
			));
			$record->insert();

			$this->assertTrue(false, "Call did not trigger executeQuery.");
		} catch (MySQLQueryExecutedException $e) {
			
			$this->assertEquals('INSERT INTO members SET `username` = :Susername, `email` = :Semail, `firstname` = :Sfirstname, `balance` = :Sbalance', $e->query);
			$this->assertEquals(array(':Susername' => 'chipersoft', ':Semail' => 'chiper@chipersoft.com', ':Sfirstname' => 'Jarvis', ':Sbalance' => '6000.26'), $e->data);
			
		}
	}
	
	public function testUpdate() {
		try {
			$record = new MemberAddressRecordTosser();
			$record->import(array(
				'member_id'=>36,
				'type'=>'Billing',
				'address_1'=>"1600 Pennsylvania Ave",
				'city'=>'Washington DC',
				'fluke'=>'bad data'
			));
			$record->update();

			$this->assertTrue(false, "Call did not trigger executeQuery.");
		} catch (MySQLQueryExecutedException $e) {
			
			$this->assertEquals('UPDATE member_addresses SET `address_1` = :Saddress_1, `city` = :Scity WHERE `member_id` = :Wmember_id AND `type` = :Wtype', $e->query);
			$this->assertEquals(array(':Wmember_id' => "36", ':Wtype' => 'Billing', ':Saddress_1' => '1600 Pennsylvania Ave', ':Scity' => 'Washington DC'), $e->data);
			
		}
	}
	
	public function testSetWhenFound() {
		try {
			$record = new MemberAddressRecordTosser();
			$record->import(array(
				'member_id'=>36,
				'type'=>'Billing',
				'address_1'=>"1600 Pennsylvania Ave",
				'city'=>'Washington DC',
				'fluke'=>'bad data'
			));
			$record->found = true;
			$record->set('city','San Diego');

			$this->assertTrue(false, "Call did not trigger executeQuery.");
		} catch (MySQLQueryExecutedException $e) {
			
			$this->assertEquals('UPDATE member_addresses SET `city` = :Scity WHERE `member_id` = :Wmember_id AND `type` = :Wtype', $e->query);
			$this->assertEquals(array(':Wmember_id' => "36", ':Wtype' => 'Billing', ':Scity' => 'San Diego'), $e->data);
			
		}
	}

	public function testSetWhenNotFound() {
		try {
			$record = new MemberAddressRecordTosser();
			$record->import(array(
				'member_id'=>36,
				'type'=>'Billing',
				'address_1'=>"1600 Pennsylvania Ave",
				'city'=>'Washington DC',
				'fluke'=>'bad data'
			));
			$record->found = false;
			$record->set('city','San Diego');

			$this->assertTrue(false, "Call did not trigger executeQuery.");
		} catch (MySQLQueryExecutedException $e) {
			
			$this->assertEquals('INSERT INTO member_addresses SET `member_id` = :Smember_id, `type` = :Stype, `address_1` = :Saddress_1, `city` = :Scity', $e->query);
			$this->assertEquals(array(':Smember_id' => "36", ':Stype' => 'Billing', ':Saddress_1' => '1600 Pennsylvania Ave', ':Scity' => 'San Diego'), $e->data);
			
		}
	}
	
	public function testSetWithNonColumn() {
		$passed = false;
		try {
			$record = new MemberAddressRecordTosser();
			$record->import(array(
				'member_id'=>36,
				'type'=>'Billing',
				'address_1'=>"1600 Pennsylvania Ave",
				'city'=>'Washington DC',
				'fluke'=>'bad data'
			));
			$record->found = true;
			$record->set('cityz','San Diego');
		} catch (Primal\Database\ColumnNotInSchemaException $e) {
			
			$passed = true;
			
		}
		$this->assertTrue($passed, "Non column value rejected");
	}

	public function testSaveWhenFound() {
		try {
			$record = new MemberAddressRecordTosser();
			$record->import(array(
				'member_id'=>36,
				'type'=>'Billing',
				'address_1'=>"1600 Pennsylvania Ave",
				'city'=>'Washington DC',
				'fluke'=>'bad data'
			));
			$record->found = true;
			$record->save();

			$this->assertTrue(false, "Call did not trigger executeQuery.");
		} catch (MySQLQueryExecutedException $e) {
			
			$this->assertEquals('UPDATE member_addresses SET `address_1` = :Saddress_1, `city` = :Scity WHERE `member_id` = :Wmember_id AND `type` = :Wtype', $e->query);
			$this->assertEquals(array(':Wmember_id' => "36", ':Wtype' => 'Billing', ':Saddress_1' => '1600 Pennsylvania Ave', ':Scity' => 'Washington DC'), $e->data);
			
		}
	}

	public function testSaveWhenNotFound() {
		try {
			$record = new MemberAddressRecordTosser();
			$record->import(array(
				'member_id'=>36,
				'type'=>'Billing',
				'address_1'=>"1600 Pennsylvania Ave",
				'city'=>'Washington DC',
				'fluke'=>'bad data'
			));
			$record->found = false;
			$record->save();

			$this->assertTrue(false, "Call did not trigger executeQuery.");
		} catch (MySQLQueryExecutedException $e) {
			
			$this->assertEquals('INSERT INTO member_addresses SET `member_id` = :Smember_id, `type` = :Stype, `address_1` = :Saddress_1, `city` = :Scity', $e->query);
			$this->assertEquals(array(':Smember_id' => "36", ':Stype' => 'Billing', ':Saddress_1' => '1600 Pennsylvania Ave', ':Scity' => 'Washington DC'), $e->data);
			
		}
	}
	
	public function testSaveWhenUnknown() {
		try {
			$record = new MemberAddressRecordTosser();
			$record->import(array(
				'member_id'=>36,
				'type'=>'Billing',
				'address_1'=>"1600 Pennsylvania Ave",
				'city'=>'Washington DC',
				'fluke'=>'bad data'
			));
			$record->save();

			$this->assertTrue(false, "Call did not trigger executeQuery.");
		} catch (MySQLQueryExecutedException $e) {
			
			$this->assertEquals('SELECT * FROM member_addresses WHERE `member_id` = :Wmember_id AND `type` = :Wtype', $e->query);
			$this->assertEquals(array(':Wmember_id' => "36", ':Wtype' => 'Billing'), $e->data);
			
		}
	}
	

	public function testDelete() {
		try {
			$record = new MemberAddressRecordTosser();
			$record->import(array(
				'member_id'=>36,
				'type'=>'Billing',
				'address_1'=>"1600 Pennsylvania Ave",
				'city'=>'Washington DC',
				'fluke'=>'bad data'
			));
			$record->delete();

			$this->assertTrue(false, "Call did not trigger executeQuery.");
		} catch (MySQLQueryExecutedException $e) {
			
			$this->assertEquals('DELETE FROM member_addresses WHERE `member_id` = :Wmember_id AND `type` = :Wtype', $e->query);
			$this->assertEquals(array(':Wmember_id' => "36", ':Wtype' => 'Billing'), $e->data);
			
		}
	}
	

}


