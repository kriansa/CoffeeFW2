<?php

namespace Core\Database;

class Grammar
{

	/**
	 * All of the query componenets in the order they should be built.
	 * @var array
	 */
	protected $_components = array(
		'selects', 'from', 'joins', 'wheres', 'groupings', 'orderings', 'limit', 'offset'
	);

	/**
	 * The keyword identifier for the database system.
	 * @var string
	 */
	protected $_wrapper = '"%s"';

	/**
	 * The database connection instance for the grammar.
	 * @var Connection
	 */
	protected $_connection;

	/**
	 * Create a new database grammar instance.
	 *
	 * @param  Connection  $connection
	 * @return void
	 */
	public function __construct(Connection $connection)
	{
		$this->_connection = $connection;
	}

	/**
	 * Compile a SQL SELECT statement from a \Core\Database\Query instance.
	 *
	 * @param  \Core\Database\Query   $query
	 * @return string
	 */
	public function select(\Core\Database\Query $query)
	{
		return $this->concatenate($this->components($query));
	}

	/**
	 * Generate the SQL for every component of the query.
	 *
	 * @param  \Core\Database\Query  $query
	 * @return array
	 */
	final protected function components($query)
	{
		// Each portion of the statement is compiled by a function corresponding
		// to an item in the components array. This lets us to keep the creation
		// of the query very granular and very flexible.
		foreach ($this->_components as $component)
		{
			if ( ! is_null($query->$component))
			{
				$sql[$component] = $this->$component($query);
			}
		}

		return (array) $sql;
	}

	/**
	 * Concatenate an array of SQL segments, removing those that are empty.
	 *
	 * @param  array   $components
	 * @return string
	 */
	final protected function concatenate($components)
	{
		return implode(' ', array_filter($components, function($value)
		{
			return (string) $value !== '';
		}));
	}

	/**
	 * Compile the SELECT clause for a query.
	 *
	 * @param  \Core\Database\Query   $query
	 * @return string
	 */
	protected function selects(\Core\Database\Query $query)
	{
		$select = ($query->distinct) ? 'SELECT DISTINCT ' : 'SELECT ';

		return $select . $this->columnize($query->selects);
	}

	/**
	 * Compile the FROM clause for a query.
	 *
	 * @param  \Core\Database\Query   $query
	 * @return string
	 */
	protected function from(\Core\Database\Query $query)
	{
		return 'FROM '.$this->wrapTable($query->from);
	}

	/**
	 * Compile the JOIN clauses for a query.
	 *
	 * @param  \Core\Database\Query   $query
	 * @return string
	 */
	protected function joins(\Core\Database\Query $query)
	{
		// We need to iterate through each JOIN clause that is attached to the
		// query an translate it into SQL. The table and the columns will be
		// wrapped in identifiers to avoid naming collisions.
		foreach ($query->joins as $join)
		{
			$table = $this->wrapTable($join->table);

			$clauses = array();

			// Each JOIN statement may have multiple clauses, so we will iterate
			// through each clause creating the conditions then we'll join all
			// of the together at the end to build the clause.
			foreach ($join->clauses as $clause)
			{
				extract($clause);

				$column1 = $this->wrap($column1);

				$column2 = $this->wrap($column2);

				$clauses[] = "{$connector} {$column1} {$operator} {$column2}";
			}

			// The first clause will have a connector on the front, but it is
			// not needed on the first condition, so we will strip it off of
			// the condition before adding it to the arrya of joins.
			$search = array('AND ', 'OR ');

			$clauses[0] = str_replace($search, '', $clauses[0]);

			$clauses = implode(' ', $clauses);

			$sql[] = "{$join->type} JOIN {$table} ON {$clauses}";
		}

		// Finally, we should have an array of JOIN clauses that we can
		// implode together and return as the complete SQL for the
		// join clause of the query under construction.
		return implode(' ', $sql);
	}

	/**
	 * Compile the WHERE clause for a query.
	 *
	 * @param  \Core\Database\Query   $query
	 * @return string
	 */
	final protected function wheres(\Core\Database\Query $query)
	{
		if (is_null($query->wheres)) return '';

		// Each WHERE clause array has a "type" that is assigned by the query
		// builder, and each type has its own compiler function. We will call
		// the appropriate compiler for each where clause.
		foreach ($query->wheres as $where)
		{
			$sql[] = $where['connector'].' '.$this->{$where['type']}($where);
		}

		if  (isset($sql))
		{
			// We attach the boolean connector to every where segment just
			// for convenience. Once we have built the entire clause we'll
			// remove the first instance of a connector.
			return 'WHERE '.preg_replace('/AND |OR /', '', implode(' ', $sql), 1);
		}
	}

	/**
	 * Compile a nested WHERE clause.
	 *
	 * @param  array   $where
	 * @return string
	 */
	protected function whereNested($where)
	{
		return '('.substr($this->wheres($where['query']), 6).')';
	}

	/**
	 * Compile a simple WHERE clause.
	 *
	 * @param  array   $where
	 * @return string
	 */
	protected function where($where)
	{
		$parameter = $this->parameter($where['value']);

		return $this->wrap($where['column']).' '.$where['operator'].' '.$parameter;
	}

	/**
	 * Compile a simple WHERE BETWEEN clause.
	 *
	 * @param array $where
	 * @return string
	 */
	protected function whereBetween($where)
	{
		$parameter1 = $this->parameter($where['value'][0]);
		$parameter2 = $this->parameter($where['value'][1]);

		return $this->wrap($where['column']) . ' BETWEEN ' . $parameter1 . ' AND ' . $parameter2;
	}

	/**
	 * Compile a simple WHERE NOT BETWEEN clause.
	 *
	 * @param array $where
	 * @return string
	 */
	protected function whereNotBetween($where)
	{
		$parameter1 = $this->parameter($where['value'][0]);
		$parameter2 = $this->parameter($where['value'][1]);

		return $this->wrap($where['column']) . ' NOT BETWEEN ' . $parameter1 . ' AND ' . $parameter2;
	}

	/**
	 * Compile a WHERE IN clause.
	 *
	 * @param  array   $where
	 * @return string
	 */
	protected function whereIn($where)
	{
		$parameters = $this->parameterize($where['value']);

		return $this->wrap($where['column']).' IN ('.$parameters.')';
	}

	/**
	 * Compile a WHERE NOT IN clause.
	 *
	 * @param  array   $where
	 * @return string
	 */
	protected function whereNotIn($where)
	{
		$parameters = $this->parameterize($where['value']);

		return $this->wrap($where['column']).' NOT IN ('.$parameters.')';
	}

	/**
	 * Compile a WHERE NULL clause.
	 *
	 * @param  array   $where
	 * @return string
	 */
	protected function whereNull($where)
	{
		return $this->wrap($where['column']).' IS NULL';
	}

	/**
	 * Compile a WHERE NULL clause.
	 *
	 * @param  array   $where
	 * @return string
	 */
	protected function whereNotNull($where)
	{
		return $this->wrap($where['column']).' IS NOT NULL';
	}

	/**
	 * Compile a raw WHERE clause.
	 *
	 * @param  array   $where
	 * @return string
	 */
	final protected function whereRaw($where)
	{
		return $where['sql'];
	}

	/**
	 * Compile the GROUP BY clause for a query.
	 *
	 * @param  \Core\Database\Query   $query
	 * @return string
	 */
	protected function groupings(\Core\Database\Query $query)
	{
		return 'GROUP BY '.$this->columnize($query->groupings);
	}

	/**
	 * Compile the ORDER BY clause for a query.
	 *
	 * @param  \Core\Database\Query   $query
	 * @return string
	 */
	protected function orderings(\Core\Database\Query $query)
	{
		foreach ($query->orderings as $ordering)
		{
			$sql[] = $this->wrap($ordering['column']).' '.strtoupper($ordering['direction']);
		}

		return 'ORDER BY '.implode(', ', $sql);
	}

	/**
	 * Compile the LIMIT clause for a query.
	 *
	 * @param  \Core\Database\Query   $query
	 * @return string
	 */
	protected function limit(\Core\Database\Query $query)
	{
		return 'LIMIT '.$query->limit;
	}

	/**
	 * Compile the OFFSET clause for a query.
	 *
	 * @param  \Core\Database\Query   $query
	 * @return string
	 */
	protected function offset(\Core\Database\Query $query)
	{
		return 'OFFSET '.$query->offset;
	}

	/**
	 * Compile a SQL INSERT statment from a \Core\Database\Query instance.
	 *
	 * This method handles the compilation of single row inserts and batch inserts.
	 *
	 * @param  \Core\Database\Query   $query
	 * @param  array   $values
	 * @return string
	 */
	public function insert(\Core\Database\Query $query, $values)
	{
		$table = $this->wrapTable($query->from);

		// Force every insert to be treated like a batch insert. This simply makes
		// creating the SQL syntax a little easier on us since we can always treat
		// the values as if it contains multiple inserts.
		if ( ! is_array(reset($values))) $values = array($values);

		// Since we only care about the column names, we can pass any of the insert
		// arrays into the "columnize" method. The columns should be the same for
		// every record inserted into the table.
		$columns = $this->columnize(array_keys(reset($values)));

		// Build the list of parameter place-holders of values bound to the query.
		// Each insert should have the same number of bound paramters, so we can
		// just use the first array of values.
		$parameters = $this->parameterize(reset($values));

		$parameters = implode(', ', array_fill(0, count($values), "($parameters)"));

		return "INSERT INTO {$table} ({$columns}) VALUES {$parameters}";
	}

	/**
	 * Compile a SQL UPDATE statment from a \Core\Database\Query instance.
	 *
	 * @param  \Core\Database\Query   $query
	 * @param  array   $values
	 * @return string
	 */
	public function update(\Core\Database\Query $query, $values)
	{
		$table = $this->wrapTable($query->from);

		// Each column in the UPDATE statement needs to be wrapped in the keyword
		// identifiers, and a place-holder needs to be created for each value in
		// the array of bindings, so we'll build the sets first.
		foreach ($values as $column => $value)
		{
			$columns[] = $this->wrap($column).' = '.$this->parameter($value);
		}

		$columns = implode(', ', $columns);

		// UPDATE statements may be constrained by a WHERE clause, so we'll run
		// the entire where compilation process for those contraints. This is
		// easily achieved by passing it to the "wheres" method.
		return trim("UPDATE {$table} SET {$columns} ".$this->wheres($query));
	}

	/**
	 * Compile a SQL DELETE statment from a \Core\Database\Query instance.
	 *
	 * @param  \Core\Database\Query   $query
	 * @return string
	 */
	public function delete(\Core\Database\Query $query)
	{
		$table = $this->wrapTable($query->from);

		return trim("DELETE FROM {$table} ".$this->wheres($query));
	}

	/**
	 * Wrap a table in keyword identifiers.
	 *
	 * @param  string  $table
	 * @return string
	 */
	public function wrapTable($table)
	{
		// Expressions should be injected into the query as raw strings so
		// so we do not want to wrap them in any way. We will just return
		// the string value from the expression to be included.
		if ($table instanceof Expression)
		{
			return $table->get();
		}

		$prefix = '';

		// Tables may be prefixed with a string. This allows developers to
		// prefix tables by application on the same database which may be
		// required in some brown-field situations.
		if (isset($this->_connection->config['prefix']))
		{
			$prefix = $this->_connection->config['prefix'];
		}

		// If the table being wrapped contains a table alias, we need to
		// wrap it a little differently as each segment must be wrapped
		// and not the entire string.
		if (stripos($table, ' as ') !== false)
		{
			$segments = explode(' ', $table, 3);

			return sprintf(
				'%s %s',
				$this->wrap($prefix . $segments[0]),
				$this->wrap($segments[2])
			);
		}

		return $this->wrap($prefix . $table);
	}

	/**
	 * Wrap a value in keyword identifiers.
	 *
	 * @param  string  $value
	 * @return string
	 */
	public function wrap($value)
	{
		// Expressions should be injected into the query as raw strings so
		// so we do not want to wrap them in any way. We will just return
		// the string value from the expression to be included.
		if ($value instanceof Expression)
		{
			return $value->get();
		}

		// If the value being wrapped contains a column alias, we need to
		// wrap it a little differently as each segment must be wrapped
		// and not the entire string.
		if (stripos($value, ' as ') !== false)
		{
			$segments = explode(' ', $value, 3);

			return sprintf(
				'%s AS %s',
				$this->wrap($segments[0]),
				$this->wrap($segments[2])
			);
		}

		// Since columns may be prefixed with their corresponding table
		// name so as to not make them ambiguous, we will need to wrap
		// the table and the column in keyword identifiers.
		if (stripos($value, '.') !== false)
		{
			$segments = explode('.', $value);
			$wrapped = array();

			foreach ($segments as $key => $value)
			{
				if ($key == 0)
				{
					$wrapped[] = $this->wrapTable($value);
				}
				else
				{
					$wrapped[] = ($value !== '*') ? sprintf($this->_wrapper, $value) : '*';
				}
			}

			return implode('.', $wrapped);
		}

		return ($value !== '*') ? sprintf($this->_wrapper, $value) : '*';

	}

	/**
	 * Create query parameters from an array of values.
	 *
	 * <code>
	 *		Returns "?, ?, ?", which may be used as PDO place-holders
	 *		$parameters = $grammar->parameterize(array(1, 2, 3));
	 *
	 *		// Returns "?, "Taylor"" since an expression is used
	 *		$parameters = $grammar->parameterize(array(1, DB::raw('Taylor')));
	 * </code>
	 *
	 * @param  array   $values
	 * @return string
	 */
	final public function parameterize($values)
	{
		return implode(', ', array_map(array($this, 'parameter'), $values));
	}

	/**
	 * Get the appropriate query parameter string for a value.
	 *
	 * <code>
	 *		// Returns a "?" PDO place-holder
	 *		$value = $grammar->parameter('Taylor Otwell');
	 *
	 *		// Returns "Taylor Otwell" as the raw value of the expression
	 *		$value = $grammar->parameter(DB::raw('Taylor Otwell'));
	 * </code>
	 *
	 * @param  mixed   $value
	 * @return string
	 */
	final public function parameter($value)
	{
		return ($value instanceof Expression) ? $value->get() : '?';
	}

	/**
	 * Create a comma-delimited list of wrapped column names.
	 *
	 * <code>
	 *		// Returns ""Taylor", "Otwell"" when the identifier is quotes
	 *		$columns = $grammar->columnize(array('Taylor', 'Otwell'));
	 * </code>
	 *
	 * @param  array   $columns
	 * @return string
	 */
	final public function columnize($columns)
	{
		return implode(', ', array_map(array($this, 'wrap'), $columns));
	}
}