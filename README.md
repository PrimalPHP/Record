#Primal Record

Created and Copyright 2013 by Jarvis Badgley, chiper at chipersoft dot com.

Primal Record is an Active Record ORM library for manipulating individual tables rows as arrays, designed to be extremely lightweight and extensible for maximum flexibility.  It is recommended that Primal Record be used in a Data Mapper capacity, but can be used as a direct data model if so desired.

[Primal](http://www.primalphp.com) is a collection of independent micro-libraries for simplifying common PHP tasks.

**PLEASE NOTE THAT RECORD 2.0 IS IN ALPHA STATUS AND IS SUBJECT TO IMPLEMENTATION CHANGES**

##Requirements

Primal Record uses the PHP 5.4 Traits feature for the abstraction of database engine drivers.  You must have PHP 5.4 to use this library.

Primal Record has no other dependencies.

##Usage

Primal Record is an abstract class and *must* be extended to function.  For rapid development, the only implementation requirement is the definition of a table name.  Primal Record will automatically request the table schema from your database, caching the structure for later usage.

Lets say you have the following table to interact with:

```sql
CREATE TABLE `example_table` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `column_1` varchar(200) DEFAULT NULL,
  `column_2` int(11) DEFAULT NULL,
  `date_column` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
)
```

First create your record implementation for that table.

```php
class ExampleRecord extends \Primal\Database\MySQL\Record {
	protected $tablename = 'example_table';
}
```

Once this implementation is created, you are ready to interface with table rows.

For the following examples, it is assumed that the following code has already executed:

```php
$pdo = new PDO("mysql:host=localhost;dbname=test", 'username', 'password');
```

**Getting & Setting Row Data**

Primal Record extends the built in PHP [ArrayObject](http://php.net/manual/en/class.arrayobject.php) class.  All row data is accessed on the object as if the Record object were an array:

```php
$row = new ExampleRecord($pdo);
$row['column_1'] = "Foo";
```

Record also implements the `__get` and `__set` methods to allow for getting and setting the contents as properties on the object.

```php
$row = new ExampleRecord($pdo);
$row->column_1 = "Foo";
```

This is not a recommended method, however, as it removes the separation of implementation data (member properties) and external data (database rows).


**Saving Rows**

The `save()` function will perform a smart save of the row contents.  If the primary keys match an existing row in the database, `save` will perform an update.  If they do not match, or the auto-incrementing primary key was left undefined, `save` will perform an insert and update the record object with the new auto-incremented column value.

```php
$row = new ExampleRecord($pdo);
$row['column_1'] = "Foo";
$row['column_2'] = 16;
$row['date_column'] = new DateTime('yesterday');
$row->save();

//$row['id'] now contains the auto-incremented primary key value.
```

If it is known that the record does or does not exist, you can also call the `insert()` and `update()` functions explicitly.  Calling `insert(true)` will perform a row replacement (`REPLACE` vs `INSERT`).  A further shortcut is provided for saving as an entirely new row; calling `save(true)` will remove the auto-incrementing primary key and trigger an insert of a brand new row.

**Saving Partial Row Contents**

Only the values that have been defined on the Record object will be included in the INSERT and UPDATE calls.

```php
$row = new ExampleRecord($pdo);
$row['id'] = 2;
$row['column_2'] = 16;
$row['date_column'] = 'now'; //Record will automatically parse strings into DateTime values for date column types (`DATE`, `DATETIME`, `TIMESTAMP`).
$row->save();

// only `column_2` and `date_column` on the row keyed with an id of 2 will be updated, all other columns will remain unchanged.
```

Calling `save` or `update` will update the database with all the values defined within the Record.  If you only wish to update a single then you may use the `set()` function.

```php
$row = new ExampleRecord($pdo);
if ($row->load(5)) {
    $row->set('column_2', $new_value);
}
```

The second argument on `set()` may be omitted to trigger an updating using whatever value is defined in the record (or NULL if nothing is defined).

**Loading Rows**

```php
$row = new ExampleRecord($pdo);
if ($row->load(2)) {
    //$row found a table row where the `id` column (the primary key) contained 2.
} else {
    //No row was found that matched that primary key value.
}
```

The `load()` function supports three methods of interaction:

1. Single primary key loading: `$row->load($value);`
2. Single non-primary key loading:  `$row->load($value, 'column_name');`
3. Multi-key loading: `$row->load(array('column_1'=>'foo', 'column_2'=>8));`

In all three cases, `load()` will return a boolean condition indicating if a matching row was found.  This result can also be retrieved from the public `found` property.

For convenience, the load arguments can be passed directly on the class constructor:

```php
$user = new User($pdo, array('email'=>'john.doe@example.com'));
if ($user->found) {
    // user code
}
```

##Pre-caching Table Structure

If your table schema has been locked down and will not be changing, performance can be gained by pre-creating the schema structure array in your class definition.

```php
class Member extends \Primal\Database\MySQL\Record {
	protected $tablename = 'members';
	protected $schema = array(
    	'primaries' => array('member_id'),
    	'auto_increment' => 'member_id',
    	'columns' => array(
    		'member_id' => array(
    			'null' => false,
    			'unsigned' => 1,
    			'format' => 'number',
    			'precision' => 0,
    			'type' => 'int',
    		),
    		'email' => array(
    			'null' => false,
    			'unsigned' => 0,
    			'format' => 'string',
    			'precision' => 0,
    			'type' => 'varchar',
    			'length' => '255',
    		),
    		'username' => array(
    			'null' => true,
    			'unsigned' => 0,
    			'format' => 'string',
    			'precision' => 0,
    			'type' => 'varchar',
    			'length' => '200',
    		),
    		'firstname' => array(
    			'null' => true,
    			'unsigned' => 0,
    			'format' => 'string',
    			'precision' => 0,
    			'type' => 'tinytext',
    		),
    		'middlename' => array(
    			'null' => true,
    			'unsigned' => 0,
    			'format' => 'string',
    			'precision' => 0,
    			'type' => 'tinytext',
    		),
    		'lastname' => array(
    			'null' => true,
    			'unsigned' => 0,
    			'format' => 'string',
    			'precision' => 0,
    			'type' => 'tinytext',
    		),
    		'website' => array(
    			'null' => true,
    			'unsigned' => 0,
    			'format' => 'string',
    			'precision' => 0,
    			'type' => 'text',
    		),
    		'industry' => array(
    			'null' => false,
    			'unsigned' => 1,
    			'format' => 'number',
    			'precision' => 0,
    			'type' => 'int',
    		),
    		'last_updated' => array(
    			'null' => false,
    			'unsigned' => 0,
    			'format' => 'datetime',
    			'precision' => 0,
    			'type' => 'timestamp',
    		),
    		'last_login' => array(
    			'null' => true,
    			'unsigned' => 0,
    			'format' => 'datetime',
    			'precision' => 0,
    			'type' => 'datetime',
    		),
    		'validated' => array(
    			'null' => false,
    			'unsigned' => 0,
    			'format' => 'number',
    			'precision' => 0,
    			'type' => 'tinyint',
    		),
    		'review_rating' => array(
    			'null' => true,
    			'unsigned' => 1,
    			'format' => 'number',
    			'precision' => 0,
    			'type' => 'int',
    		),
    		'membership_type' => array(
    			'null' => false,
    			'unsigned' => 0,
    			'format' => 'enum',
    			'precision' => 0,
    			'type' => 'enum',
    			'options' => array (
    				0 => 'Free',
    				1 => 'None',
    				2 => 'Credited',
    				3 => 'Monthly',
    				4 => 'Yearly',
    			),
    		),
    		'membership_expires' => array(
    			'null' => true,
    			'unsigned' => 0,
    			'format' => 'date',
    			'precision' => 0,
    			'type' => 'date',
    		),
    		'balance' => array(
    			'null' => false,
    			'unsigned' => 0,
    			'format' => 'number',
    			'precision' => '2',
    			'type' => 'decimal',
    		),
    	)
    );
}
```

This structure can be made by hand, but is much easier to obtain by code using the `var_export` function in conjunction with `->buildTableSchema()`:

```php
$record = new Member($this->mypdo);
var_export($record->buildTableSchema());

//output is the above array structure
```

