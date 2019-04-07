<?php

namespace Core\Database;

class Query
{

	/**
	 * The database connection.
	 * @var \Core\Database\Connection
	 */
	public $connection;

	/**
	 * The query grammar instance.
	 * @var Query\Grammar\Grammar
	 */
	public $grammar;

	/**
	 * The SELECT clause.
	 * @var array
	 */
	public $selects;

	/**
	 * The aggregating column and function.
	 * @var array
	 */
	public $aggregate;

	/**
	 * Indicates if the query should return distinct results.
	 * @var bool
	 */
	public $distinct = false;

	/**
	 * Whether to select using SQL_CACHE (Only for MySQL)
	 * @var bool
	 */
	public $cache = false;

	/**
	 * The table name.
	 * @var string
	 */
	public $from;

	/**
	 * The table joins.
	 * @var array
	 */
	public $joins;

	/**
	 * The WHERE clauses.
	 * @var array
	 */
	public $wheres;

	/**
	 * The GROUP BY clauses.
	 * @var array
	 */
	public $groupings;

	/**
	 * The ORDER BY clauses.
	 * @var array
	 */
	public $orderings;

	/**
	 * The LIMIT value.
	 * @var int
	 */
	public $limit;

	/**
	 * The OFFSET value.
	 * @var int
	 */
	public $offset;

	/**
	 * The query value bindings.
	 * @var array
	 */
	public $bindings = array();

	/**
	 * The options for the table
	 * @var array
	 */
	public $options = array(
		'fetch_type' => null,
	);

	/**
	 * Create a new query instance.
	 *
	 * @param  Connection  $connection
	 * @param  Grammar	 $grammar
	 * @param  string	  $table
	 * @return void
	 */
	public function __construct(Connection $connection, Grammar $grammar, $table, array $options = array())
	{
		$this->from = $table;
		$this->grammar = $grammar;
		$this->options = array_merge($this->options, $options);
		$this->connection = $connection;
	}

	/**
	 * Force the query to return distinct results.
	 *
	 * @return \Core\Database\Query
	 */
	public function distinct()
	{
		$this->distinct = true;
		return $this;
	}

	/**
	 * Force the query to use SQL_CACHE. Only for MySQL.
	 *
	 * @return \Core\Database\Query
	 */
	public function sqlCache()
	{
		$this->cache = true;
		return $this;
	}

	/**
	 * Add a join clause to the query.
	 *
	 * @param  string  $table
	 * @param  string  $column1
	 * @param  string  $operator
	 * @param  string  $column2
	 * @param  string  $type
	 * @return \Core\Database\Query
	 */
	public function join($table, $column1, $operator = null, $column2 = null, $type = 'INNER')
	{
		// If the "column" is really an instance of a Closure, the developer is
		// trying to create a join with a complex "ON" clause. So, we will add
		// the join, and then call the Closure with the join/
		if ($column1 instanceof \Closure)
		{
			$this->joins[] = new Query\Join($type, $table);

			$column1(end($this->joins));
		}

		// If the column is just a string, we can assume that the join just
		// has a simple on clause, and we'll create the join instance and
		// add the clause automatically for the develoepr.
		else
		{
			$join = new Query\Join($type, $table);

			$join->on($column1, $operator, $column2);

			$this->joins[] = $join;
		}

		return $this;
	}

	/**
	 * Add a left join to the query.
	 *
	 * @param  string  $table
	 * @param  string  $column1
	 * @param  string  $operator
	 * @param  string  $column2
	 * @return \Core\Database\Query
	 */
	public function leftJoin($table, $column1, $operator = null, $column2 = null)
	{
		return $this->join($table, $column1, $operator, $column2, 'LEFT');
	}

	/**
	 * Add a join USING clause (when 2 columns have the same name)
	 *
	 * @param string $table
	 * @param string $column
	 * @param string $type The type of join (inner, left, right)
	 * @return \Core\Database\Query
	 */
	public function joinUsing($table, $column, $type = 'INNER')
	{
		return $this->join($table, $this->from . '.' . $column, '=', $table  . '.' . $column, $type);
	}

	/**
	 * Reset the where clause to its initial state.
	 *
	 * @return void
	 */
	public function resetWhere()
	{
		$this->wheres = array();
		$this->bindings = array();
	}

	/**
	 * Add a raw where condition to the query.
	 *
	 * @param  string  $where
	 * @param  array|string   $bindings
	 * @param  string  $connector
	 * @return \Core\Database\Query
	 */
	public function rawWhere($where, $bindings = array(), $connector = 'AND')
	{
		$this->wheres[] = array('type' => 'whereRaw', 'connector' => $connector, 'sql' => $where);

		$this->bindings = array_merge($this->bindings, (array) $bindings);

		return $this;
	}

	/**
	 * Add a raw or where condition to the query.
	 *
	 * @param  string  $where
	 * @param  array   $bindings
	 * @return \Core\Database\Query
	 */
	public function rawOrWhere($where, $bindings = array())
	{
		return $this->rawWhere($where, $bindings, 'OR');
	}

	/**
	 * Add a raw and where condition to the query.
	 *
	 * @param  string  $where
	 * @param  array   $bindings
	 * @return \Core\Database\Query
	 */
	public function rawAndWhere($where, $bindings = array())
	{
		return $this->rawWhere($where, $bindings);
	}

	/**
	 * Add a where condition to the query.
	 *
	 * @param  string  $column
	 * @param  string  $operator
	 * @param  mixed   $value
	 * @param  string  $connector
	 * @return \Core\Database\Query
	 * @throws \Core\DatabaseException
	 */
	public function where($column, $operator = null, $value = null, $connector = 'AND')
	{
		// If a Closure is passed into the method, it means a nested where
		// clause is being initiated, so we will take a different course
		// of action than when the statement is just a simple where.
		if ($column instanceof \Closure)
		{
			return $this->whereNested($column, $connector);
		}

		// If passing an expression, it means we are actually doing a Raw where
		if ($column instanceof Expression)
		{
			return $this->rawWhere($column->get(), $value, $connector);
		}

		$type = 'where';

		switch ($operator = strtoupper($operator))
		{
			case '=':
				// Check if we are searching for null values
				if ($value === null)
				{
					$type = 'whereNull';
					break;
				}

			case '!=':
			case '<>':
			case 'LIKE':
			case 'NOT LIKE':
				// Check if we are searching for null values
				if ($value === null)
				{
					$type = 'whereNotNull';
					break;
				}

				$this->bindings[] = $value;
				break;

			case 'NOT BETWEEN':
				if ( ! is_array($value) or count($value) != 2)
				{
					throw new \Core\DatabaseException('The value in WHERE NOT BETWEEN must be an array with 2 elements!');
				}

				$type = 'whereNotBetween';
				$this->bindings = array_merge($this->bindings, $value);
				break;

			case 'BETWEEN':
				if ( ! is_array($value) or count($value) != 2)
				{
					throw new \Core\DatabaseException('The value in WHERE BETWEEN must be an array with 2 elements!');
				}

				$type = 'whereBetween';
				$this->bindings = array_merge($this->bindings, $value);
				break;

			case 'IN':
				if ( ! is_array($value))
				{
					throw new \Core\DatabaseException('The value in WHERE IN must be an array!');
				}

				$type = 'whereIn';
				$this->bindings = array_merge($this->bindings, $value);
				break;

			case 'NOT IN':
				if ( ! is_array($value))
				{
					throw new \Core\DatabaseException('The value in WHERE NOT IN must be an array!');
				}

				$type = 'whereNotIn';
				$this->bindings = array_merge($this->bindings, $value);
				break;

			default:
				$this->bindings[] = $value;
		}

		$this->wheres[] = compact('type', 'column', 'operator', 'value', 'connector');

		return $this;
	}

	/**
	 * Add an or where condition to the query.
	 *
	 * @param  string  $column
	 * @param  string  $operator
	 * @param  mixed   $value
	 * @return \Core\Database\Query
	 */
	public function orWhere($column, $operator = null, $value = null)
	{
		return $this->where($column, $operator, $value, 'OR');
	}

	/**
	 * Add an or where condition to the query.
	 *
	 * @param  string  $column
	 * @param  string  $operator
	 * @param  mixed   $value
	 * @return \Core\Database\Query
	 */
	public function andWhere($column, $operator = null, $value = null)
	{
		return $this->where($column, $operator, $value);
	}

	/**
	 * Add a nested where condition to the query.
	 *
	 * @param  \Closure  $callback
	 * @param  string   $connector
	 * @return \Core\Database\Query
	 */
	public function whereNested($callback, $connector = 'AND')
	{
		$type = 'whereNested';

		// To handle a nested where statement, we will actually instantiate a new
		// Query instance and run the callback over that instance, which will
		// allow the developer to have a fresh query instance
		$query = new static($this->connection, $this->grammar, $this->from);

		$callback($query);

		// Once the callback has been run on the query, we will store the nested
		// query instance on the where clause array so that it's passed to the
		// query's query grammar instance when building.
		$this->wheres[] = compact('type', 'query', 'connector');

		$this->bindings = array_merge($this->bindings, $query->bindings);

		return $this;
	}

	/**
	 * Add dynamic where conditions to the query.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return \Core\Database\Query
	 */
	protected function dynamicWhere($method, array $parameters)
	{
		$segments = preg_split('/(_and_|_or_)/i', substr($method, 6), -1, PREG_SPLIT_DELIM_CAPTURE);

		// The connector variable will determine which connector will be used
		// for the condition. We'll change it as we come across new boolean
		// connectors in the dynamic method string.
		//
		// The index variable helps us get the correct parameter value for
		// the where condition. We increment it each time we add another
		// condition to the query's where clause.
		$connector = 'AND';

		$index = 0;

		foreach ($segments as $segment)
		{
			// If the segment is not a boolean connector, we can assume it it is
			// a column name, and we'll add it to the query as a new constraint
			// of the query's where clause and keep iterating the segments.
			if ($segment != '_and_' and $segment != '_or_')
			{
				$this->where($segment, '=', $parameters[$index], $connector);

				$index++;
			}
			// Otherwise, we will store the connector so we know how the next
			// where clause we find in the query should be connected to the
			// previous one and will add it when we find the next one.
			else
			{
				$connector = trim(strtoupper($segment), '_');
			}
		}

		return $this;
	}

	/**
	 * Add a grouping to the query.
	 *
	 * @param  string  $column
	 * @return \Core\Database\Query
	 */
	public function groupBy($column)
	{
		$this->groupings[] = $column;
		return $this;
	}

	/**
	 * Add an ordering to the query.
	 *
	 * @param  string  $column
	 * @param  string  $direction
	 * @return \Core\Database\Query
	 */
	public function orderBy($column, $direction = 'asc')
	{
		$this->orderings[] = compact('column', 'direction');
		return $this;
	}

	/**
	 * Set the query offset.
	 *
	 * @param  int  $value
	 * @return \Core\Database\Query
	 */
	public function offset($value)
	{
		$this->offset = $value;
		return $this;
	}

	/**
	 * Set the query limit.
	 *
	 * @param int $value
	 * @return \Core\Database\Query
	 */
	public function limit($value)
	{
		$this->limit = $value;
		return $this;
	}

	/**
	 * Set the query limit and offset for a given page.
	 *
	 * @param int $page
	 * @param int $per_page
	 * @return \Core\Database\Query
	 */
	public function forPage($page, $per_page)
	{
		return $this->offset(($page - 1) * $per_page)->limit($per_page);
	}

	/**
	 * Execute the query as a SELECT statement.
	 *
	 * <code>
	 *	 DB::table('users')->select('name', 'email');
	 *	 DB::table('users')->select(array('name', 'email'));
	 * </code>
	 *
	 * @param array|mixed $columns
	 * @param ...
	 * @return \Core\Database\Result
	 */
	public function select($columns = array('*'))
	{
		$columns = is_array($columns) ? $columns : func_get_args();

		$this->selects = (array) $columns;

		$sql = $this->grammar->select($this);

		$results = $this->connection->query($sql, $this->bindings, $this->options);

		// Reset the SELECT clause so more queries can be performed using
		// the same instance. This is helpful for getting aggregates and
		// then getting actual results from the query.
		$this->selects = null;

		return $results;
	}

	/**
	 * Get an aggregate value.
	 *
	 * @param  string  $aggregator
	 * @param  array   $columns
	 * @return mixed
	 */
	public function aggregate($aggregator, $columns)
	{
		// We'll set the aggregate value so the grammar does not try to compile
		// a SELECT clause on the query. If an aggregator is present, it's own
		// grammar function will be used to build the SQL syntax.
		$this->aggregate = compact('aggregator', 'columns');

		$sql = $this->grammar->select($this);

		$result = $this->connection->singleColumn($sql, $this->bindings);

		// Reset the aggregate so more queries can be performed using the same
		// instance. This is helpful for getting aggregates and then getting
		// actual results from the query such as during paging.
		$this->aggregate = null;

		return $result;
	}

	/**
	 * Get the paginated query results as a Paginator instance.
	 *
	 * @param  int		$per_page
	 * @param  array	  $columns
	 * @return \Core\Paginator
	 */
	public function paginate($per_page = 20, $columns = array('*'))
	{
		// Because some database engines may throw errors if we leave orderings
		// on the query when retrieving the total number of records, we'll drop
		// all of the ordreings and put them back on the query.
		list($orderings, $this->orderings) = array($this->orderings, null);

		$total = $this->count(reset($columns));

		$page = \Core\Paginator::page($total, $per_page);

		$this->orderings = $orderings;

		// Now we're ready to get the actual pagination results from the table
		// using the for_page and get methods. The "for_page" method provides
		// a convenient way to set the paging limit and offset.
		$results = $this->forPage($page, $per_page)->select($columns)->getAll();

		return \Core\Paginator::make($results, $total, $per_page);
	}

	/**
	 * Insert an array of values into the database table.
	 *
	 * @param  array  $values
	 * @return bool
	 */
	public function insert($values)
	{
		// Force every insert to be treated like a batch insert to make creating
		// the binding array simpler since we can just spin through the inserted
		// rows as if there/ was more than one every time.
		if ( ! is_array(reset($values))) $values = array($values);

		$bindings = array();

		// We need to merge the the insert values into the array of the query
		// bindings so that they will be bound to the PDO statement when it
		// is executed by the database connection.
		foreach ($values as $value)
		{
			$bindings = array_merge($bindings, array_values($value));
		}

		$sql = $this->grammar->insert($this, $values);

		return $this->connection->query($sql, $bindings, $this->options);
	}

	/**
	 * Insert an array of values into the database table and return the ID.
	 *
	 * @param  array   $values
	 * @param  string  $sequence
	 * @return int
	 */
	public function insertGetId($values, $sequence = null)
	{
		$sql = $this->grammar->insert($this, $values);

		$this->connection->query($sql, array_values($values), $this->options);

		// Some database systems (Postgres) require a sequence name to be
		// given when retrieving the auto-incrementing ID, so we'll pass
		// the given sequence into the method just in case.
		return (int) $this->connection->pdo->lastInsertId($sequence);
	}

	/**
	 * Increment the value of a column by a given amount.
	 *
	 * @param  string  $column
	 * @param  int	 $amount
	 * @return int
	 */
	public function increment($column, $amount = 1)
	{
		return $this->_adjust($column, $amount, ' + ');
	}

	/**
	 * Decrement the value of a column by a given amount.
	 *
	 * @param  string  $column
	 * @param  int	 $amount
	 * @return int
	 */
	public function decrement($column, $amount = 1)
	{
		return $this->_adjust($column, $amount, ' - ');
	}

	/**
	 * Adjust the value of a column up or down by a given amount.
	 *
	 * @param  string  $column
	 * @param  int	 $amount
	 * @param  string  $operator
	 * @return int
	 */
	protected function _adjust($column, $amount, $operator)
	{
		$wrapped = $this->grammar->wrap($column);

		// To make the adjustment to the column, we'll wrap the expression in an
		// Expression instance, which forces the adjustment to be injected into
		// the query as a string instead of bound.
		$value = \Core\DB::expr($wrapped . $operator . $amount);

		return $this->update(array($column => $value));
	}

	/**
	 * Update an array of values in the database table.
	 *
	 * @param  array  $values
	 * @return int
	 */
	public function update($values)
	{
		// For update statements, we need to merge the bindings such that the update
		// values occur before the where bindings in the array since the sets will
		// precede any of the where clauses in the SQL syntax that is generated.
		$bindings =  array_merge(array_values($values), $this->bindings);

		$sql = $this->grammar->update($this, $values);

		return $this->connection->query($sql, $bindings, $this->options);
	}

	/**
	 * Execute the query as a DELETE statement.
	 *
	 * @return int
	 */
	public function delete()
	{
		$sql = $this->grammar->delete($this);

		return $this->connection->query($sql, $this->bindings, $this->options);
	}

	/**
	 * Magic Method for handling dynamic functions.
	 *
	 * This method handles calls to aggregates as well as dynamic where clauses.
	 */
	public function __call($method, $parameters)
	{
		if (strpos($method, 'where_') === 0)
		{
			return $this->dynamicWhere($method, $parameters, $this);
		}

		// All of the aggregate methods are handled by a single method, so we'll
		// catch them all here and then pass them off to the agregate method
		// instead of creating methods for each one of them.
		if (in_array($method, array('count', 'min', 'max', 'avg', 'sum')))
		{
			count($parameters) or $parameters[0] = '*';

			return $this->aggregate(strtoupper($method), (array) $parameters[0]);
		}

		throw new \Exception('Method ' . $method . ' is not defined on the Query class.');
	}
}
