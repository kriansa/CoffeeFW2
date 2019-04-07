<?php

return array(
	/**
	 * Redis Databases
	 */
	'redis' => array(
		'default' => array(
			'host' => 'localhost',
			'port' => '6379',
		),
	),

	/**
	 * SQL Databases
	 */
	'sql' => array(
		'default' => 'mysql',

		'connection' => array(

			'sqlite' => array(
				// Full namespaced object name or 'array'
				'fetch_type' => '\\stdClass',
				'driver' => 'sqlite',
				'database' => 'application',
				'profile' => true,
				'prefix' => '',
			),

			'dev' => array(
				// Full namespaced object name or 'array'
				'fetch_type' => '\\stdClass',
				'driver' => 'mysql',
				'host' => 'localhost',
				'database' => 'fuel_dev',
				'username' => 'root',
				'password' => '123',
				'charset' => 'utf8',
				'profile' => true,
				'prefix' => '',
			),

			'mysql' => array(
				// Full namespaced object name or 'array'
				'fetch_type' => '\\stdClass',
				'driver' => 'mysql',
				'host' => 'localhost',
				'database' => 'trunk',
				'username' => 'root',
				'password' => '123',
				'charset' => 'utf8',
				'profile' => true,
				'prefix' => '',
			),

			'pgsql' => array(
				// Full namespaced object name or 'array'
				'fetch_type' => '\\stdClass',
				'driver' => 'pgsql',
				'host' => 'localhost',
				'database' => 'database',
				'username' => 'root',
				'password' => '',
				'charset' => 'utf8',
				'profile' => true,
				'prefix' => '',
			),

			'sqlsrv' => array(
				// Full namespaced object name or 'array'
				'fetch_type' => '\\stdClass',
				'driver' => 'sqlsrv',
				'host' => 'localhost',
				'database' => 'database',
				'username' => 'root',
				'password' => '',
				'profile' => true,
				'prefix' => '',
			),
		),
	),
);