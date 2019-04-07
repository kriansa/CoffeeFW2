<?php

namespace Core\Database;

class Exception extends \Exception
{

	/**
	 * The inner exception.
	 *
	 * @var Exception
	 */
	protected $inner;

	/**
	 * Create a new database exception instance.
	 *
	 * @param  string     $sql
	 * @param  array      $bindings
	 * @param  Exception  $inner
	 * @return void
	 */
	public function __construct($sql, $bindings, \Exception $inner)
	{
		$this->inner = $inner;

		$this->setMessage($sql, $bindings);
	}

	/**
	 * Set the exception message to include the SQL and bindings.
	 *
	 * @param  string  $sql
	 * @param  array   $bindings
	 * @return void
	 */
	protected function setMessage($sql, $bindings)
	{
		$this->message = $this->inner->getMessage();

		$this->message .= "\n\nSQL: ".$sql."\n\nBindings: ".var_export($bindings, true);
	}

}

class Connection
{
	/**
	 * The raw PDO connection instance.
	 * @var \PDO
	 */
	public $pdo = null;

	/**
	 * The driver used for the PDO connection
	 * @var string
	 */
	public $driver = null;

	/**
	 * The connection configuration array.
	 * @var array
	 */
	public $config = array();

	/**
	 * The query grammar instance for the connection.
	 * @var Grammars\Grammar
	 */
	protected $_grammar = null;

	/**
	 * Create a new database connection instance.
	 *
	 * @param \PDO $pdo
	 * @param array $config
	 * @return void
	 */
	public function __construct(\PDO $pdo, array $config)
	{
		$this->pdo = $pdo;
		$this->config = $config;
		$this->driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
	}

	/**
	 * Begin a fluent query against a table.
	 *
	 * <code>
	 *		// Start a fluent query against the "users" table
	 *		$query = DB::connection()->table('users');
	 * </code>
	 *
	 * @param string $table
	 * @param array $options
	 * @return \Core\Database\Query
	 */
	public function table($table, $options = array())
	{
		return new Query($this, $this->grammar(), $table, $options);
	}

	/**
	 * Create a new query grammar for the connection.
	 *
	 * @return \Core\Database\Grammar
	 */
	protected function grammar()
	{
		if (isset($this->_grammar)) return $this->_grammar;

		switch (isset($this->config['grammar']) ? $this->config['grammar'] : $this->driver)
		{
			case 'mysql':
				return $this->_grammar = new Query\Grammar\MySQL($this);

			case 'sqlsrv':
				return $this->_grammar = new Query\Grammar\SQLServer($this);

			default:
				return $this->_grammar = new Grammar($this);
		}
	}

	/**
	 * Execute a callback wrapped in a database transaction.
	 *
	 * @param callable $callback
	 * @return void
	 */
	public function transaction($callback)
	{
		$this->pdo->beginTransaction();

		// After beginning the database transaction, we will call the Closure
		// so that it can do its database work. If an exception occurs we'll
		// rollback the transaction and re-throw back to the developer.
		try
		{
			$callback($this);
		}
		catch (\Exception $e)
		{
			$this->pdo->rollBack();

			throw $e;
		}

		$this->pdo->commit();
	}

	/**
	 * Execute a SQL query against the connection and return the PDO Statement.
	 *
	 * @param string $sql
	 * @param array|string $bindings
	 * @param array $options ['fetch_type'] => 'array' or the name of the object to fetch the lines into
	 * @return \Core\Database\Result If the query is a select
	 * @return int Rows affected, if the query is a delete, update or insert
	 * @return bool If none of above, return the status of the statement
	 */
	public function query($sql, array $bindings = array(), array $options = array())
	{
		foreach ($bindings as $key => $binding)
		{
			// Since expressions are injected into the query as strings, we need to
			// remove them from the array of bindings. After we have removed them,
			// we'll reset the array so there are not gaps within the keys.
			if ($binding instanceof Expression)
			{
				unset($bindings[$key]);
				continue;
			}


			// Any object having the __toSql('driver') method should be converted
			// to be used in database bindings
			if (is_object($binding) and method_exists($binding, '__toSql'))
			{
				$bindings[$key] = $binding = $binding->__toSql($this->driver);
			}

			// If the binding is an array, we can just assume it's used to
			// fill a "where in" condition, so we will just replace the
			// next place-holder in the query with the constraint.
			if (is_array($binding) and $start = strpos($sql, '(...)'))
			{
				foreach($binding as &$value)
				{
					// Any object having the __toSql('driver') method should be converted
					// to be used in database bindings
					if (is_object($value) and method_exists($value, '__toSql'))
					{
						$value = $value->__toSql($this->driver);
					}
				}

				$sql = substr_replace($sql, '(' . $this->grammar()->parameterize($binding) . ')', $start, 5);

				// Replace the array values and put them into the binding main array
				// so the bindings will all have the same level in array for the PDOStatement
				array_splice($bindings, $key, 1, $binding);
			}
		}

		$bindings = array_values($bindings);

		$sql = trim($sql);

		// Each database operation is wrapped in a try / catch so we can wrap
		// any database exceptions in our custom exception class, which will
		// set the message to include the SQL and query bindings.
		try
		{
			$statement = $this->pdo->prepare($sql);

			isset($options['fetch_type']) or $options['fetch_type'] = $this->config['fetch_type'];

			if ($options['fetch_type'] == 'array')
			{
				$statement->setFetchMode(\PDO::FETCH_ASSOC);
			}
			else
			{
				$statement->setFetchMode(\PDO::FETCH_CLASS, $options['fetch_type']);
			}

			if($this->config['profile'])
			{
				\Core\Profiler::queryStart($this->config['database'], $sql, $bindings);
			}

			$result = $statement->execute($bindings);
		}
		// If an exception occurs, we'll pass it into our custom exception
		// and set the message to include the SQL and query bindings so
		// debugging is much easier on the developer.
		catch (\PDOException $exception)
		{
			throw new Exception($sql, $bindings, $exception);
		}

		// Once we have execute the query, we log the SQL, bindings, and
		// execution time in a static array that is accessed by all of
		// the connections actively being used by the application.
		if($this->config['profile'])
		{
			\Core\Profiler::queryStop();
		}

		// If the query type was a select, return the Result set
		if (stripos($sql, 'select') === 0)
		{
			return new Result($statement, $options['fetch_type']);
		}
		// Or return the number of affected rows
		elseif (stripos($sql, 'update') === 0 or stripos($sql, 'delete') === 0 or stripos($sql, 'insert') === 0 or stripos($sql, 'replace') === 0)
		{
			return $statement->rowCount();
		}
		// Or the result of the statement
		else
		{
			return $result;
		}
	}
}