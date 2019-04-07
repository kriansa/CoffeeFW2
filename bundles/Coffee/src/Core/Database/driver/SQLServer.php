<?php

namespace Core\Database\Driver;

class SQLServer extends Driver
{

	/**
	 * The PDO connection options.
	 *
	 * @var array
	 */
	protected $options = array(
			\PDO::ATTR_CASE => \PDO::CASE_LOWER,
			\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
			\PDO::ATTR_ORACLE_NULLS => \PDO::NULL_NATURAL,
			\PDO::ATTR_STRINGIFY_FETCHES => false,
	);

	/**
	 * Establish a PDO database connection.
	 *
	 * @param  array  $config
	 * @return \PDO
	 */
	public function connect($config)
	{
		// Format the SQL Server connection string. This connection string format can
		// also be used to connect to Azure SQL Server databases. The port is defined
		// directly after the server name, so we'll create that first.
		$port = (isset($config['port'])) ? ',' . $config['port'] : '';

		$dsn = "sqlsrv:Server={$config['host']}{$port};Database={$config['database']}";

		return new \PDO($dsn, $config['username'], $config['password'], $this->options($config));
	}
}