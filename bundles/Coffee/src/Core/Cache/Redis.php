<?php

namespace Core\Cache;

class Redis
{
	/**
	 * Namespace of this instance
	 * @var string
	 */
	protected $_namespace = null;

	/**
	 * Redis instance
	 * @var \Core\Redis
	 */
	protected $_redis = null;

	/**
	 * Constructor
	 *
	 * @param array $config
	 */
	public function __construct(array $config)
	{
		$this->_redis = \Core\Redis::getInstance($config['connection']);
		$this->_namespace = $config['namespace'];
	}

	/**
	 * Check whether a value exists
	 *
	 * @param string $key
	 * @return bool
	 */
	public function has($key)
	{
		return ( ! is_null($this->_redis->get('cache.' . $this->_namespace . '.' . $key)));
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
		if( ! $cache = $this->_redis->get('cache.' . $this->_namespace . '.' . $key))
		{
			throw new \Core\CacheNotFoundException('Cache identifier "' . $key . '" not found or expired!');
		}

		return unserialize($cache);
	}

	/**
	 * Method to set the key in cache
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param int $minutes
	 * @return \Core\Cache\Redis $this
	 */
	public function set($key, $value, $minutes = null)
	{
		$this->_redis->set('cache.' . $this->_namespace . '.' . $key, serialize(value($value)));

		// Set the expire time
		if($minutes)
		{
			$this->_redis->expire('cache.' . $this->_namespace . '.' . $key, $minutes * 60);
		}

		return $this;
	}

	/**
	 * Remove a key from cache
	 *
	 * @param type $key
	 * @return \Core\Cache\Redis $this
	 */
	public function drop($key)
	{
		$this->_redis->del('cache.' . $this->_namespace . '.' . $key);

		return $this;
	}

	/**
	 * Delete all the keys created by this instance
	 *
	 * @return \Core\Cache\Redis $this
	 */
	public function dropAll()
	{
		$this->_redis->del('cache.' . $this->_namespace . '.*');

		return $this;
	}

	/**
	 * Do the garbage collection in the driver
	 *
	 * Not necessary in Redis
	 *
	 * @return \Core\Cache\Redis $this
	 */
	public function garbageCollector() {}
}