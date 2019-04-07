<?php

namespace Core\Database;

class Result implements \Countable, \SeekableIterator, \ArrayAccess
{
	/**
	 * Raw result statement
	 * @var \PDOStatement
	 */
	protected $_statement;

	/**
	 * Total number of rows
	 * @var int
	 */
	protected $_totalRows  = 0;

	/**
	 * Current row number
	 * @var int
	 */
	protected $_currentRow = 0;

	/**
	 * Internal PDO row
	 * @var int
	 */
	protected $_internalRow = 0;

	/**
	 * Array with all iterated rows
	 * @var array
	 */
	protected $_rows = array();

	/**
	 * Sets the total number of rows and stores the result locally.
	 *
	 * @param \PDOStatement $result Query result
	 */
	public function __construct(\PDOStatement $result)
	{
		// Store the result locally
		$this->_statement = $result;

		$this->_totalRows = $result->rowCount();
	}

	/**
	 * Execute a SQL query and return an array of objects.
	 *
	 * @return array If query is SELECT and the fetch type configured is PDO::FETCH_ASSOC, PDO::FETCH_BOTH, PDO::FETCH_NUM
	 * @return object If query is SELECT and the fetch type configured is PDO::FETCH_CLASS, PDO::FETCH_INTO, PDO::FETCH_LAZY, PDO::FETCH_OBJ
	 */
	public function getAll()
	{
		return array_merge($this->_rows, $this->_statement->fetchAll());
	}

	/**
	 * Fetch a single row from the resultset
	 *
	 * @param int $row Row number
	 * @return array|object
	 * @return null If not found
	 */
	public function getRow($row = null)
	{
		if ($row !== null and ! $this->seek($row))
		{
			return null;
		}

		return $this->current();
	}

	/**
	 * Get the first column from the first line in the rowset
	 * and set the internal pointer to the next row
	 *
	 * @return mixed
	 */
	public function getSingle()
	{
		return $this->current();
	}

	/**
	 * Return the named column from the current row.
	 *
	 *     // Get the "id" value
	 *     $id = $result->getColumn('id');
	 *
	 * @param string  column to get
	 * @return mixed
	 */
	public function getColumn($name)
	{
		$row = $this->current();

		if (isset($row->$name))
		{
			return $row->$name;
		}

		return null;
	}

	/**
	 * Get an array with the values of a given column.
	 *
	 * <code>
	 *		$result = DB::table('users')->select('id', 'name')->getPairs('name', 'id');
	 *		Will result in a array set with id => name pairs
	 * </code>
	 *
	 * @param string $column
	 * @param string $key
	 * @return array
	 */
	public function getPairs($column, $key = null)
	{
		$results = $this->getAll();

		// First we will get the array of values for the requested column.
		// Of course, this array will simply have numeric keys. After we
		// have this array we will determine if we need to key the array
		// by another column from the result set.
		$values = array_map(function($row) use ($column)
		{
			return $row->$column;

		}, $results);

		// If a key was provided, we will extract an array of keys and
		// set the keys on the array of values using the array_combine
		// function provided by PHP, which should give us the proper
		// array form to return from the method.
		if ( ! is_null($key))
		{
			return array_combine(array_map(function($row) use ($key)
			{
				return $row->$key;

			}, $results), $values);
		}

		return $values;
	}

	/**
	 * Implements the [SeekableIterator::seek]
	 *
	 * @param int $offset
	 * @return boolean
	 */
	public function seek($offset)
	{
		if ($this->offsetExists($offset))
		{
			// Set the current row to the offset
			$this->_currentRow = $offset;

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Implements the [SeekableIterator::seek]
	 *
	 * @return array|object Current row from the resultset
	 * @return bool False if not found
	 */
	public function current()
	{
		// We are trying to get a row higher than the actual fetched lines
		// So we fetch them all, until we reach this row
		// Once we reached it, the internalRow will be currentRow-1
		// And then we do the last fetch
		if ($this->_currentRow > $this->_internalRow)
		{
			while($this->_currentRow > $this->_internalRow)
			{
				$this->_rows[$this->_internalRow] = $this->_statement->fetch();
				$this->_internalRow++;
			}
		}
		// If we are trying to get a row already fetched (less than the internal row)
		// Get it directly from the "cache" variable
		elseif($this->_currentRow < $this->_internalRow)
		{
			return $this->_rows[$this->_currentRow];
		}

		$this->_internalRow++;
		return $this->_rows[$this->_currentRow] = $this->_statement->fetch();
	}

	/**
	 * Implements [Countable::count], returns the total number of rows.
	 *
	 *     echo count($result);
	 *
	 * @return  integer
	 */
	public function count()
	{
		return $this->_totalRows;
	}

	/**
	 * Implements [ArrayAccess::offsetExists], determines if row exists.
	 *
	 *     if (isset($result[10]))
	 *     {
	 *         // Row 10 exists
	 *     }
	 *
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		return ($offset >= 0 and $offset < $this->_totalRows);
	}

	/**
	 * Implements [ArrayAccess::offsetGet], gets a given row.
	 *
	 *     $row = $result[10];
	 *
	 * @param mixed $offset
	 * @return array|mixed|null|object
	 */
	public function offsetGet($offset)
	{
		if ( ! $this->seek($offset))
		{
			return null;
		}

		return $this->current();
	}

	/**
	 * Implements [ArrayAccess::offsetSet], throws an error.
	 *
	 * [!!] You cannot modify a database result.
	 *
	 * @param mixed $offset
	 * @param mixed $value
	 * @throws \Core\DatabaseException
	 */
	final public function offsetSet($offset, $value)
	{
		throw new \Core\DatabaseException('Database results are read-only');
	}

	/**
	 * Implements [ArrayAccess::offsetUnset], throws an error.
	 *
	 * [!!] You cannot modify a database result.
	 *
	 * @param mixed $offset
	 * @throws \Core\DatabaseException
	 */
	final public function offsetUnset($offset)
	{
		throw new \Core\DatabaseException('Database results are read-only');
	}

	/**
	 * Implements [Iterator::key], returns the current row number.
	 *
	 *     echo key($result);
	 *
	 * @return  integer
	 */
	public function key()
	{
		return $this->_currentRow;
	}

	/**
	 * Implements [Iterator::next], moves to the next row.
	 *
	 *     next($result);
	 *
	 * @return  $this
	 */
	public function next()
	{
		++$this->_currentRow;
		return $this;
	}

	/**
	 * Implements [Iterator::prev], moves to the previous row.
	 *
	 *     prev($result);
	 *
	 * @return  $this
	 */
	public function prev()
	{
		--$this->_currentRow;
		return $this;
	}

	/**
	 * Implements [Iterator::rewind], sets the current row to zero.
	 *
	 *     rewind($result);
	 *
	 * @return  $this
	 */
	public function rewind()
	{
		$this->_currentRow = 0;
		return $this;
	}

	/**
	 * Implements [Iterator::valid], checks if the current row exists.
	 *
	 * [!!] This method is only used internally.
	 *
	 * @return  boolean
	 */
	public function valid()
	{
		return $this->offsetExists($this->_currentRow);
	}
}