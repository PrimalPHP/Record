<?php
return array (
  0 => 
  array (
    'Field' => 'member_id',
    'Type' => 'int(11) unsigned',
    'Null' => 'NO',
    'Key' => 'PRI',
    'Default' => NULL,
    'Extra' => 'auto_increment',
  ),
  1 => 
  array (
    'Field' => 'email',
    'Type' => 'varchar(255)',
    'Null' => 'NO',
    'Key' => 'MUL',
    'Default' => NULL,
    'Extra' => '',
  ),
  2 => 
  array (
    'Field' => 'username',
    'Type' => 'varchar(200)',
    'Null' => 'YES',
    'Key' => '',
    'Default' => '',
    'Extra' => '',
  ),
  3 => 
  array (
    'Field' => 'firstname',
    'Type' => 'tinytext',
    'Null' => 'YES',
    'Key' => '',
    'Default' => NULL,
    'Extra' => '',
  ),
  4 => 
  array (
    'Field' => 'middlename',
    'Type' => 'tinytext',
    'Null' => 'YES',
    'Key' => '',
    'Default' => NULL,
    'Extra' => '',
  ),
  5 => 
  array (
    'Field' => 'lastname',
    'Type' => 'tinytext',
    'Null' => 'YES',
    'Key' => '',
    'Default' => NULL,
    'Extra' => '',
  ),
  6 => 
  array (
    'Field' => 'website',
    'Type' => 'text',
    'Null' => 'YES',
    'Key' => '',
    'Default' => NULL,
    'Extra' => '',
  ),
  7 => 
  array (
    'Field' => 'industry',
    'Type' => 'int(11) unsigned',
    'Null' => 'NO',
    'Key' => '',
    'Default' => '0',
    'Extra' => '',
  ),
  8 => 
  array (
    'Field' => 'last_updated',
    'Type' => 'timestamp',
    'Null' => 'NO',
    'Key' => '',
    'Default' => 'CURRENT_TIMESTAMP',
    'Extra' => 'on update CURRENT_TIMESTAMP',
  ),
  9 => 
  array (
    'Field' => 'last_login',
    'Type' => 'datetime',
    'Null' => 'YES',
    'Key' => '',
    'Default' => NULL,
    'Extra' => '',
  ),
  10 => 
  array (
    'Field' => 'validated',
    'Type' => 'tinyint(1)',
    'Null' => 'NO',
    'Key' => '',
    'Default' => '0',
    'Extra' => '',
  ),
  11 => 
  array (
    'Field' => 'review_rating',
    'Type' => 'int(11) unsigned',
    'Null' => 'YES',
    'Key' => '',
    'Default' => NULL,
    'Extra' => '',
  ),
  12 => 
  array (
    'Field' => 'membership_type',
    'Type' => 'enum(\'Free\',\'None\',\'Credited\',\'Monthly\',\'Yearly\')',
    'Null' => 'NO',
    'Key' => '',
    'Default' => 'Free',
    'Extra' => '',
  ),
  13 => 
  array (
    'Field' => 'membership_expires',
    'Type' => 'date',
    'Null' => 'YES',
    'Key' => '',
    'Default' => NULL,
    'Extra' => '',
  ),
  14 => 
  array (
    'Field' => 'balance',
    'Type' => 'decimal(10,2)',
    'Null' => 'NO',
    'Key' => '',
    'Default' => '0.00',
    'Extra' => '',
  )
);