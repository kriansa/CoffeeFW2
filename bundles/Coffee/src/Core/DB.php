<?php

namespace Core;

class DatabaseException extends \Exception {}

class DB
{
	/**
	 * The established database connections.
	 * @var array
	 */
	public static $connections = array();

	/**
	 * Get a database connection.
	 *
	 * If no database name is specified, the default connection will be returned.
	 *
	 * <code>
	 *		// Get the default database connection for the application
	 *		$connection = DB::getInstance();
	 *
	 *		// Get a specific connection by passing the connection name
	 *		$connection = DB::getInstance('mysql');
	 * </code>
	 *
	 * @param string $connection
	 * @return \Core\Database\Connection
	 * @throws \Core\DatabaseException
	 */
	public static function getInstance($connection = null)
	{
		if (is_null($connection)) $connection = Config::get('database.sql.default');

		if ( ! isset(static::$connections[$connection]))
		{
			$config = Config::get("database.sql.connection.{$connection}");

			if (is_null($config))
			{
				throw new DatabaseException('Database connection is not defined for ' . $connection . '.');
			}

			switch ($config['driver'])
			{
				case 'sqlite':
					$pdo = new Database\Driver\SQLite;
					break;

				case 'mysql':
					$pdo = new Database\Driver\MySQL;
					break;

				case 'pgsql':
					$pdo = new Database\Driver\Postgres;
					break;

				case 'sqlsrv':
					$pdo = new Database\Driver\SQLServer;
					break;

				default:
					throw new DatabaseException('Database driver ' . $config['driver'] . ' is not supported.');
			}

			static::$connections[$connection] = new Database\Connection($pdo->connect($config), $config);
		}

		return static::$connections[$connection];
	}

	/**
	 * Begin a fluent query against a table.
	 *
	 * @param string $table
	 * @param array $options
	 * @return \Core\Database\Query
	 */
	public static function table($table, $options = array())
	{
		return static::getInstance()->table($table, $options);
	}

	/**
	 * Execute a SQL query against the connection and return the PDO Statement.
	 *
	 * @param string $sql
	 * @param array $bindings
	 * @param array $options ['fetch_type] => 'array' or the name of the object to fetch the lines into
	 * @return \Core\Database\Result If the query is a select
	 * @return int Rows affected, if the query is a update or insert
	 * @return bool If none of above, return the status of the statement
	 */
	public static function query($sql, array $bindings = array(), array $options = array())
	{
		return static::getInstance()->query($sql, $bindings, $options);
	}

	/**
	 * Create a new database expression instance.
	 *
	 * Database expressions are used to inject raw SQL into a fluent query.
	 *
	 * @param  string      $value
	 * @return Expression
	 */
	public static function expr($value)
	{
		return new Database\Expression($value);
	}
}