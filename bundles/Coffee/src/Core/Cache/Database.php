<?php

namespace Core\Cache;

class Database
{
	/**
	 * Namespace of this instance
	 * @var string
	 */
	protected $_namespace = null;

	/**
	 * Current DB connection
	 * @var \Core\Database\Connection
	 */
	protected $_db = null;

	/**
	 * The table
	 * @var string
	 */
	protected $_table = null;

	/**
	 * The key column
	 * @var string
	 */
	protected $_columnKey = null;

	/**
	 * The data column
	 * @var string
	 */
	protected $_columnData = null;

	/**
	 * The expire time column
	 * @var string
	 */
	protected $_columnExpireTime = null;

	/**
	 * Save the last query handler to prevent two queries on Session::get
	 * @var \Core\Database\Result
	 */
	protected $_lastQuery = null;

	/**
	 * Constructor
	 *
	 * @param array $config
	 */
	public function __construct(array $config)
	{
		$this->_namespace = $config['namespace'];
		$this->_table = $config['table'];
		$this->_columnKey = $config['column_key'];
		$this->_columnData = $config['column_data'];
		$this->_columnExpireTime = $config['column_expire_time'];
		$this->_db = \Core\DB::getInstance($config['connection']);
	}

	/**
	 * Check whether a value exists
	 *
	 * @param string $key
	 * @return bool
	 */
	public function has($key)
	{
		// Keep the query result in the handler to prevent that the
		// ::get method do the query again
		$this->_lastQuery = $this->_db->table($this->_table)
				->where($this->_columnKey, '=', $this->_namespace . '.' . $key)
				->where($this->_columnExpireTime, '=', '0000-00-00 00:00:00')
				->orWhere($this->_columnExpireTime, '>=', date('Y-m-d H:i:s'))
				->select($this->_columnData);

		return (bool) ($this->_lastQuery->count() == 1);
	}


	/**
	 * Retrieve a value from cache
	 *
	 * @param string $key
	 * @return mixed
	 * @throws \Core\CacheNotFoundException
	 */
	public function get($key)
	{
		if ( ! $this->has($key))
		{
			throw new \Core\CacheNotFoundException('Cache identifier "' . $key . '" not found or expired!');
		}

		return unserialize($this->_lastQuery->getColumn($this->_columnData));
	}

	/**
	 * Method to set the key in cache
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param int $minutes
	 * @return \Core\Cache\Database $this
	 */
	public function set($key, $value, $minutes = null)
	{
		$minutes = $minutes ? date('Y-m-d H:i:s', strtotime('+' . $minutes . ' minutes')) : '0000-00-00 00:00:00';

		if ($this->has($key))
		{
			$this->_db->table($this->_table)
				->where($this->_columnKey, '=', $this->_namespace . '.' . $key)
				->update(array(
					$this->_columnData => serialize($value),
					$this->_columnExpireTime => $minutes,
				));
		}
		else
		{
			$this->_db->table($this->_table)
				->insert(array(
					$this->_columnKey => $this->_namespace . '.' . $key,
					$this->_columnData => serialize($value),
					$this->_columnExpireTime => $minutes,
				));
		}

		return $this;
	}

	/**
	 * Remove a key from cache
	 *
	 * @param type $key
	 * @return \Core\Cache\Database $this
	 */
	public function drop($key)
	{
		$this->_db->table($this->_table)
				->where($this->_columnKey, '=', $this->_namespace . '.' . $key)
				->delete();

		return $this;
	}

	/**
	 * Delete all the keys created by this instance
	 *
	 * @return \Core\Cache\Database $this
	 */
	public function dropAll()
	{
		$this->_db->table($this->_table)
				->where($this->_columnKey, 'like', $this->_namespace . '.%')
				->delete();

		return $this;
	}

	/**
	 * Do the garbage collection in the driver
	 *
	 * @return \Core\Cache\Database $this
	 */
	public function garbageCollector()
	{
		$this->_db->table($this->_table)
				->where($this->_columnKey, 'like', $this->_namespace . '.%')
				->where($this->_columnExpireTime, '!=', '0000-00-00 00:00:00')
				->where($this->_columnExpireTime, '<', date('Y-m-d H:i:s'))
				->delete();

		return $this;
	}
}