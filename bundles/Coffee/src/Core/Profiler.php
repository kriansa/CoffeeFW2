<?php

namespace Core;

class Profiler
{
	/**
	 * Resource types of the profiler
	 */
	const MARK = 1;
	const MARK_MEMORY = 2;
	const DB_BENCHMARK = 3;
	const APP_STATS = 4;

	/**
	 * Whether the profiler is enabled or not
	 * @var bool
	 */
	protected static $_enabled = false;

	/**
	 * Store the last query
	 * @var string
	 */
	protected static $_query = null;

	/**
	 * Array with all the queries of the profiler
	 * @var array
	 */
	protected static $_queries = array();

	/**
	 * Array with all the time markpoints
	 * @var array
	 */
	public static $_marks = array();

	/**
	 * Array with all the memory markpoints
	 * @var array
	 */
	protected static $_memory_marks = array();

	/**
	 * Resources enabled or disabled
	 * @var array
	 */
	protected static $_resources = array(
		self::MARK => true,
		self::MARK_MEMORY => true,
		self::DB_BENCHMARK => true,
		self::APP_STATS => true,
	);

	/**
	 * Start the system and enable its resources
	 *
	 * @return void
	 */
	public static function _init()
	{
		if (static::$_enabled = (bool) Config::get('system.profiler.enabled'))
		{
			static::enableResources(Config::get('system.profiler.resources'));
			Event::register('before_send_headers', 'Core\\Profiler::send');
		}
	}

	/**
	 * Enable or disable profiler resources
	 *
	 * Ex: array(
	 *		Profiler::MARK => false,
	 *		Profiler::MARK_MEMORY => true,
	 * )
	 *
	 * @param array $resources
	 * @return void
	 */
	public static function enableResources(array $resources)
	{
		static::$_resources = $resources + static::$_resources;
	}

	/**
	 * Check if some resource is enabled
	 *
	 * @param int $resource
	 * @return bool
	 */
	public static function isEnabled($resource)
	{
		return static::$_enabled and static::$_resources[$resource] === true;
	}

	/**
	 * Start a timer mark
	 *
	 * @param string Name
	 * @return void
	 */
	public static function markStart($label)
	{
		static::isEnabled(static::MARK) and static::$_marks[$label] = array(
			'time' => microtime(true),
			'name' => $label,
			'stopped' => false,
		);
	}

	/**
	 * Stop a timer mark
	 *
	 * @param string Name
	 * @return void
	 */
	public static function markEnd($label)
	{
		static::isEnabled(static::MARK) and static::$_marks[$label] = array(
			'time' => (microtime(true) - static::$_marks[$label]['time']),
			'name' => $label,
			'stopped' => true,
		);
	}

	/**
	 * Set a mark status since the script start time
	 *
	 * @param string Name
	 * @return void
	 */
	public static function sinceStart($label)
	{
		static::isEnabled(static::MARK) and static::$_marks[$label] = array(
			'time' => (microtime(true) - APP_START_TIME),
			'name' => $label,
			'stopped' => true,
		);
	}

	/**
	 * Marks the memory usage of some variable
	 * If null, marks the memory usage of PHP at the moment of calling
	 *
	 * @param mixed $var Variable
	 * @param string $label Label
	 */
	public static function markMemory($var = null, $label = 'PHP')
	{
		if (static::isEnabled(static::MARK_MEMORY)) {
			$memory = $var ? strlen(serialize($var)) : memory_get_usage();
			static::$_memory_marks[] = array(
				'time' => microtime(true),
				'name' => $label,
				'memory' => $memory,
				'type' => Debug::getType($var),
			);
		}
	}

	/**
	 * Start the benchmark of some query
	 *
	 * @param string $dbname Database connection name
	 * @param string $sql SQL Query
	 * @param string $bindings SQL Bindings
	 * @return void
	 */
	public static function queryStart($dbname, $sql, $bindings)
	{
		if (static::isEnabled(static::DB_BENCHMARK))
		{
			static::$_query = array(
				'dbname' => $dbname,
				'sql' => $sql,
				'bindings' => $bindings,
				'time' => microtime(true),
			);
		}
	}

	/**
	 * Stop the benchmark of the last query and put into the queries array
	 *
	 * @return void
	 */
	public static function queryStop()
	{
		if (static::isEnabled(static::DB_BENCHMARK))
		{
			static::$_query['time'] = microtime(true) - static::$_query['time'];
			static::$_queries[] = static::$_query;
		}
	}

	/**
	 * Send the data to browser
	 * And displays the final stats
	 *
	 * @return void
	 */
	public static function send()
	{
		if (static::isEnabled(static::DB_BENCHMARK) and count(static::$_queries))
		{
			$table = array(array('DB', 'Time', 'SQL', 'Bindings'));
			foreach(static::$_queries as $query)
			{
				$table[] = array($query['dbname'], (string)round($query['time'], 4), $query['sql'], $query['bindings']);
			}
			Debug\FirePHP::getInstance()->table('Profiler - DB Benchmark', $table);
		}

		if (static::isEnabled(static::MARK_MEMORY) and count(static::$_memory_marks))
		{
			$table = array(array('Name', 'Type', 'Time', 'Memory'));
			foreach(static::$_memory_marks as $mark)
			{
				$table[] = array($mark['name'], $mark['type'], (string)round(($mark['time'] - APP_START_TIME), 4), (string)round($mark['memory'] / pow(1024, 2), 5) . 'MB');
			}
			Debug\FirePHP::getInstance()->table('Profiler - Memory', $table);
		}

		if (static::isEnabled(static::MARK) and count(static::$_marks))
		{
			$table = array(array('Name', 'Time'));
			foreach(static::$_marks as $mark)
			{
				if($mark['stopped'])
				{
					$table[] = array($mark['name'], (string)round($mark['time'], 4));
				}
			}
			// Only displays the time marks if they're finished
			count($table) > 2 and Debug\FirePHP::getInstance()->table('Profiler - Time marks', $table);
		}

		if (static::isEnabled(static::APP_STATS))
		{
			Debug\FirePHP::getInstance()->info('Execution time: ~' . round((microtime(true) - APP_START_TIME), 4) . ' - Memory usage: ' . round((memory_get_peak_usage() - APP_START_MEM) / pow(1024, 2), 3) . 'MB');
		}
	}
}