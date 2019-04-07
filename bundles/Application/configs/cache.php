<?php

return array(
	'default' => array(
		'adapter' => 'xcache',
		'namespace' => '',
	),

	// Session
	'session' => array(
		'adapter' => 'file',
		'namespace' => 'sess',
		'path' => APPPATH . 'data' . DS . 'cache' . DS . 'session',
	),

	// specific configuration settings for Controller cache system
	'controller'  => array(
		'adapter' => 'file',
		'namespace' => 'controller',
	),

	// Database
	'database' => array(
		'adapter' => 'database',
		'namespace' => '',
		'connection' => 'dev',
		'table' => 'cache',
		'column_key' => 'key',
		'column_data' => 'data',
		'column_expire_time' => 'expire_time',
	),


	// specific configuration settings for the file driver
	'file' => array(
		'adapter' => 'file',
		'namespace' => '',
		'path' => '',  // if empty the default will be App/Cache/
	),

	// specific configuration settings for the memcached driver
	'memcache' => array(
		'adapter' => 'memcached',
		'namespace' => '',
		'servers'  => array(   // array of servers and portnumbers that run the memcached service
			array('host' => '127.0.0.1', 'port' => 11211, 'weight' => 100)
		),
	),

	// specific configuration settings for the apc driver
	'apc' => array(
		'adapter' => 'apc',
		'namespace' => '',
	),

	// specific configuration settings for the xcache driver
	'xcache' => array(
		'adapter' => 'xcache',
		'namespace' => '',
	),

	// specific configuration settings for the redis driver
	'redis' => array(
		'adapter' => 'redis',
		'namespace' => '',
		'connection' => 'default',
	),
);