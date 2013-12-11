<?php

namespace UFO\Model\ORM;


/**
 *	AbstractModel should be a parent for all the application models
 *	it does not implement a method such as find() cause it looks like the responsability
 * 	of the Collection (needs to be discussed)
 *
 *	READINGS :
 *		http://stackoverflow.com/questions/173400/php-arrays-a-good-way-to-check-if-an-array-is-associative-or-sequential
 *		http://stackoverflow.com/questions/4687967/php-casting-to-a-dynamically-determined-type
 *
 *	DOCUMENTATION :
 *		http://www.php.net/manual/fr/function.settype.php
 */
abstract class AbstractModel {

	const COLUMN = 0;
	const TYPE = 1;
	const DEFAULT_VALUE = 2;	

	static $_PROTECTED_WORDS = array('_orm');

	private $id = null;
	private $cid = null;

	// store all ORM related stuff
	private $_orm = array(
		// relationships between $attributes and db columns
		'mapping' => array(),
		// table name
		'table' => '',
		// previous data
		'data' => array(),
		// flag new objects
		'isNew' => true
	);


	/**
	 *	This must be implemented in child classes and must return an array of the mapping
	 *	@return <array> 
	 *		an array containing the relations between attr and database columns
	 */
	abstract public function getMapping();

	abstract public function getTable();


	/**
	 *	@param <array> $data [default array()]
	 *		data from the database, if any
	 *	@param <bool> $persisted [default true]
	 * 		if false the object is not aimed to be persisted in the database
	 */
	public function __construct(\PDO $dbh, array $data = array(), $persisted = true) {
		// give the object a unique id
		$this->cid = uniqid();
		$this->_orm['dbh'] = $dbh;
		// get the mapping with the table
		$this->_orm['mapping'] = $this->getMapping();

		// deal with the table, if persisted and no table throws an error
		if (true === $persisted && !$this->_orm['table'] = $this->getTable()) {
			throw new \Exception(sprintf(
				'"%s"::getTable" must return a valid table name'));
		}

		// 
		$this->_setDefaultProperties();
		$this->_hydrate($data);
		

		if (!$this->id) {
			$this->_orm['isNew'] = true;
		}
	}

	/**
	 *	create an array of $prop => $value from te mapping
	 *	and call $this->_hydrate($data);
	 *	@return <void>
	 */
	private function _setDefaultProperties() {
		$data = array();

		foreach ($this->_orm['mapping'] as $key => $config) {
			if (isset($config[self::DEFAULT_VALUE])) {
				$data[$key] = $config[self::DEFAULT_VALUE];
			}
		}

		$this->_hydrate($data);
	}

	private function _hydrate($data) {
		// reformat data to be sure we have property names as key
		foreach ($data as $key => $value) {
			if (property_exists($this, $key)) {
				$prop = $key;
			} else {
				$prop = $this->_getPropertyFromColumn($key);

				// nothing found - create a public property
				if (false === $prop) {
					$prop = $key;
				}
			}

			$this->set($prop, $value);
		}
	}

	/**
	 *	setter with dynamic casting
	 *	@return <this> to allow chainning
	 */
	public function set($prop, $value = null) {
		if (is_array($prop)) {
			// array_walk(...)
		}
		// try to cast according to the mapping
		if (
			isset($this->_orm['mapping'][$prop]) && 
			isset($this->_orm['mapping'][$prop][static::TYPE])
		) {
			$type = $this->_orm['mapping'][$prop][static::TYPE];
			settype($value, $type);
		}
		// assign value
		$this->{$prop} = $value;

		return $this;
	}

	/**
	 *	returns the property name from its related db column name
	 *	@param <string> column name
	 *	@return false if not match found
	 */
	private function _getPropertyFromColumn($column) {
		foreach($this->_orm['mapping'] as $prop => $config) {
			if ($column === $config[self::COLUMN]) {
				return $prop;
			}
		}

		return false;
	}

	/** 
	 *	dynamically emulate setters and getters for attributes
	 *	example use : setAttr($value), getAttr() where the object as an attribute $attr
	 *	as whole ORM config is stored in $this->_orm, blacklist it to forbid unwanted changes
	 */
	public function __call($name, $arguments) {
		preg_match('#((get|set)+([A-Z].*))#', $name, $matches);
		
		if (empty($matches)) {
			throw new \Exception(sprintf(
				'method "%s" does not exists', $name));
		}
		// now assume it's a getter or a setter
		$method = $matches[2];
		$prop = lcfirst($matches[3]);

		if (!property_exists($this, $prop)) {
			throw new \Exception(sprintf(
				'object "%s" as no property "%s"', get_class($this), $prop));
		} else if (true === in_array($prop, $this::$_PROTECTED_WORDS)) {
			throw new \Exception(sprintf(
				'attr "%s" cannot be accessed or changed this way', $prop));
		}

		switch ($method) {
			case 'get':
				if (0 !== count($arguments)) {
					throw new \Exception(sprintf(
						'method "get%s" do not accept any arguments', ucfirst($prop)));
				}

				return $this->{$prop};
				break;
			case 'set':
				if (1 !== count($arguments)) {
					throw new \Exception(sprintf(
						'method "set%s" must have 1 argument', ucfirst($prop)));
				}

				return $this->set($prop, $arguments[0]);
				break;
		}
	}

	public function save() {
		$this->persist();
	}

	public function persist() {
		$this->_orm['isNew'] ? $this->_create() : $this->_update();
	}

	private function _create() {
		// create entry
		$columns = array_map(function($value) { return $value[static::COLUMN]; }, $this->_orm['mapping']);
		$fields = array_map(function($value) { return ':'.$value; }, $columns);

		$sql = 'INSERT INTO `'.$this->_orm['table'].'` ';
		$sql .= '('.implode(', ', $columns).')';
		$sql .= ' VALUES ';
		$sql .= '('.implode(', ', $fields).')';

		$this->_execute($sql);
		// set the newly created id
		$this->id = (int) $this->_orm['dbh']->lastInsertId();
	}

	private function _update() {
		$fields = array_map(function($value) { 
			return $value[static::COLUMN].' = :'.$value[static::COLUMN]; 
		}, $this->_orm['mapping']);

		$sql  = 'UPDATE `'.$this->_orm['table'].'` ';
		$sql .= 'SET '.implode(', ', $fields).' ';
		$sql .= 'WHERE ';

	}

	private function _execute($sql) {
		$sth = $this->_orm['dbh']->prepare($sql);
		$data = array();

		foreach($this->_orm['mapping'] as $prop => $mapping) {
			switch ($mapping[static::TYPE]) {
				case 'boolean':
					$data[$prop] = (int) $this->{$prop};
					break;
				default:
					$data[$prop] = $this->{$prop};
					break;
			}

			$sth->bindParam(':'.$mapping[self::COLUMN], $data[$prop]);
		}

		if (false === $sth->execute()) {
			throw new \Exception('an eror occured on save');
		} else {
			return $sth;
		}
		
	}

	public function delete() {

	}
}