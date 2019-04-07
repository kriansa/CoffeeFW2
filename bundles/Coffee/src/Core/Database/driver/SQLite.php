<?php

namespace Core\Database\Driver;

class SQLite extends Driver
{

	/**
	 * Establish a PDO database connection.
	 *
	 * @param  array  $config
	 * @return \PDO
	 */
	public function connect($config)
	{
		// SQLite provides supported for "in-memory" databases, which exist only for
		// lifetime of the request. Any given in-memory database may only have one
		// PDO connection open to it at a time. These are mainly for tests.
		if ($config['database'] == ':memory:')
		{
			return new \PDO('sqlite::memory:', null, null, $this->options($config));
		}

		// If none extension given, use .sqlite
		$ext = pathinfo($config['database'], PATHINFO_EXTENSION);
		$ext = $ext === null ? '.sqlite' : '.' . $ext;

		$path = APPPATH . 'Data' . DS . 'Database' . DS . $config['database'] . $ext;

		return new \PDO('sqlite:' . $path, null, null, $this->options($config));
	}
}
