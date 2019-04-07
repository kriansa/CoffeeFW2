<?php

namespace Core\Database\Driver;

class Postgres extends Driver
{

	/**
	 * Establish a PDO database connection.
	 *
	 * @param  array  $config
	 * @return \PDO
	 */
	public function connect($config)
	{
		$dsn = "pgsql:host={$config['host']};dbname={$config['database']}";

		// The developer has the freedom of specifying a port for the PostgresSQL
		// database or the default port (5432) will be used by PDO to create the
		// connection to the database for the developer.
		if (isset($config['port']))
		{
			$dsn .= ";port={$config['port']}";
		}

		$connection = new \PDO($dsn, $config['username'], $config['password'], $this->options($config));

		// If a character set has been specified, we'll execute a query against
		// the database to set the correct character set. By default, this is
		// set to UTF-8 which should be fine for most scenarios.
		if (isset($config['charset']))
		{
			$connection->prepare("SET NAMES '{$config['charset']}'")->execute();
		}

		return $connection;
	}
}