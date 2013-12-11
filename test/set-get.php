<?php

include '../src/ORM/AbstractModel.php';
include '../src/DbFactory.php';

use UFO\Model\ORM\AbstractModel;
use UFO\Model\DbFactory;


class Test extends AbstractModel {

	protected 
		$name,
		$value,
		$isBoolean;

	public function getMapping() {
		return array(
			'name' => array('my_name', 'string', 'default name'),
			'value' => array('my_value', 'integer', 0),
			'isBoolean' => array('my_boolean', 'boolean', true),
			// 'test' => array('my_test', 'string', 'default test'), // add a public property
		);
	}

	public function getTable() {
		return 'my_model';
	}

}

$dbConfig = require './db_config.php';
DbFactory::setConfig($dbConfig);

// -------------------------------------------------

$data = array(array(
		'my_name' => 'Yehaaa',
		'value' => 45,
		'isBoolean' => true,
	), 
	array()
);

$test = new Test(DbFactory::getInstance(), $data[0]);

// $test->setName('myName')->setValue(5);

// var_dump($test);

// var_dump($test->getName());
// var_dump($test->getValue());
// var_dump($test->getNaip());

$test->persist();




