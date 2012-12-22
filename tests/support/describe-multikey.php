<?php
return array (
  0 => 
  array (
    'Field' => 'member_id',
    'Type' => 'int(11) unsigned',
    'Null' => 'NO',
    'Key' => 'PRI',
    'Default' => NULL,
    'Extra' => '',
  ),
  1 => 
  array (
    'Field' => 'type',
    'Type' => 'enum(\'Profile\',\'Billing\',\'Shipping\',\'Other\')',
    'Null' => 'NO',
    'Key' => 'PRI',
    'Default' => 'Profile',
    'Extra' => '',
  ),
  2 => 
  array (
    'Field' => 'address_1',
    'Type' => 'tinytext',
    'Null' => 'YES',
    'Key' => '',
    'Default' => NULL,
    'Extra' => '',
  ),
  3 => 
  array (
    'Field' => 'address_2',
    'Type' => 'tinytext',
    'Null' => 'YES',
    'Key' => '',
    'Default' => NULL,
    'Extra' => '',
  ),
  4 => 
  array (
    'Field' => 'city',
    'Type' => 'varchar(100)',
    'Null' => 'YES',
    'Key' => '',
    'Default' => NULL,
    'Extra' => '',
  ),
  5 => 
  array (
    'Field' => 'state',
    'Type' => 'varchar(10)',
    'Null' => 'YES',
    'Key' => '',
    'Default' => NULL,
    'Extra' => '',
  ),
  6 => 
  array (
    'Field' => 'zip',
    'Type' => 'varchar(5)',
    'Null' => 'YES',
    'Key' => '',
    'Default' => NULL,
    'Extra' => '',
  ),
  7 => 
  array (
    'Field' => 'zip4',
    'Type' => 'varchar(4)',
    'Null' => 'YES',
    'Key' => '',
    'Default' => NULL,
    'Extra' => '',
  ),
  8 => 
  array (
    'Field' => 'country',
    'Type' => 'tinytext',
    'Null' => 'YES',
    'Key' => '',
    'Default' => NULL,
    'Extra' => '',
  ),
);