<?php defined('SYSPATH') or die('No direct access allowed.');

class Model_Users extends ORM
{
	protected $_db = 'default';
 
    protected $_table_name  = 'users';
    protected $_primary_key = 'id';
    protected $_primary_val = 'name';
 
    protected $_table_columns = array(
        'id'			=> array('data_type' => 'int',		'is_nullable' => TRUE),
        'email'			=> array('data_type' => 'string',	'is_nullable' => FALSE),
        'name'			=> array('data_type' => 'string',	'is_nullable' => FALSE)
    );

}