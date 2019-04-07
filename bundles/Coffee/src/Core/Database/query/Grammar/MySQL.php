<?php

namespace Core\Database\Query\Grammar;

class MySQL extends \Core\Database\Grammar
{

	/**
	 * The keyword identifier for the database system.
	 * @var string
	 */
	protected $_wrapper = '`%s`';

	/**
	 * Compile the SELECT clause for a query.
	 *
	 * @param  \Core\Database\Query   $query
	 * @return string
	 */
	protected function selects(\Core\Database\Query $query)
	{
		if ( ! is_null($query->aggregate)) return;

		$select = ($query->distinct) ? 'SELECT DISTINCT ' : 'SELECT ';
		$query->cache === true and $select = $select . ' SQL_CACHE ';

		return $select . $this->columnize($query->selects);
	}

	/**
	 * Compile an aggregating SELECT clause for a query.
	 *
	 * @param  \Core\Database\Query   $query
	 * @return string
	 */
	protected function aggregate(\Core\Database\Query $query)
	{
		$column = $this->columnize($query->aggregate['columns']);

		// If the "distinct" flag is set and we're not aggregating everything
		// we'll set the distinct clause on the query, since this is used
		// to count all of the distinct values in a column, etc.
		if ($query->distinct and $column !== '*')
		{
			$column = 'DISTINCT '.$column;
		}

		return ($query->cache ? 'SELECT SQL_CACHE ' : 'SELECT ') . $query->aggregate['aggregator'] . '(' . $column . ') AS ' . $this->wrap('aggregate');
	}
}