<?php

namespace UFO\Model;

use \PDO;
use \PDOException;
use \Exception;

/**
 *	Factory to create Db connection instances
 *	instances are shared (acts as a singleton)
 */
class DbFactory {

	const DEFAULT_CONNECTION = 0;

	static private $_INSTANCES = array();

	static private $_DEFAULT_CONNECTION_CONFIG = array(
		PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "utf-8"',
	);

	private function _construct() {}

	/**
	 *	return an instance for a optionnally named connection
	 *	the connection act as a singleton
	 *	@param $name <string> optionnal name of the instance
	 */
	static function getInstance($name = null) {

		if (is_null($name)) {
			$name = static::DEFAULT_CONNECTION;
		}

		if (is_null(static::$_INSTANCES[$name]['instance'])) {
			try {
				$dsn = sprintf('mysql:dbname=%s;host=%s', 
					static::$_INSTANCES[$name]['config']['db.name'],
					static::$_INSTANCES[$name]['config']['db.host']
				);

				$dbh = new PDO(
					$dsn, 
					static::$_INSTANCES[$name]['config']['db.user'], 
					static::$_INSTANCES[$name]['config']['db.pass'],
					static::$_DEFAULT_CONNECTION_CONFIG
				);

				static::$_INSTANCES[$name]['instance'] = $dbh;
			} catch (PDOException $e) {
				throw new \Exception($e->getMessage());
			}
		}

		return static::$_INSTANCES[$name]['instance'];
	}

	static function setConnectionConfig(array $config) {
		self::$_DEFAULT_CONNECTION_CONFIG = array_merge(self::$_DEFAULT_CONNECTION_CONFIG, $config);
	}

	/**
	 *	set confg for an optionally named connexion
	 *	@param $config <array> 
	 *	@param $name <string> optionnal name of the config
	 */
	static function setConfig(array $config, $name = null) {

		if (is_null($name)) {
			$name = static::DEFAULT_CONNECTION;
		}

		if (!isset($config['db.name']) || 
			!isset($config['db.host']) || 
			!isset($config['db.user']) || 
			!isset($config['db.pass'])
		) {
			throw new Exception(sprintf('DB config wrong parameters'));
		}

		if (isset(static::$_INSTANCES[$name])) {
			throw new Exception(sprintf('config for db "%s" already exists', $name));
		}

		static::$_INSTANCES[$name] = array(
			'config' => $config,
			'instance' => null,
		);
	}

}