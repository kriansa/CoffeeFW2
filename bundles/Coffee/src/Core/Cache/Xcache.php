<?php

namespace Core\Cache;

class Xcache
{

	/**
	 * Namespace of this instance
	 * @var string
	 */
	protected $_namespace = null;

	/**
	 * Constructor
	 *
	 * @param array $config
	 */
	public function __construct(array $config)
	{
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
		return xcache_isset('cache.' . $this->_namespace . '.' . $key);
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
		if ( ! xcache_isset('cache.' . $this->_namespace . '.' . $key))
		{
			throw new \Core\CacheNotFoundException('Cache identifier "' . $key . '" not found or expired!');
		}

		return xcache_get('cache.' . $this->_namespace . '.' . $key);
	}

	/**
	 * Method to set the key in cache
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param int $minutes
	 * @return \Core\Cache\Xcache $this
	 */
	public function set($key, $value, $minutes = null)
	{
		xcache_set('cache.' . $this->_namespace . '.' . $key, value($value), $minutes * 60);

		return $this;
	}

	/**
	 * Remove a key from cache
	 *
	 * @param type $key
	 * @return \Core\Cache\Xcache $this
	 */
	public function drop($key)
	{
		xcache_unset('cache.' . $this->_namespace . '.' . $key);

		return $this;
	}

	/**
	 * Delete all the keys created by this instance
	 *
	 * @return \Core\Cache\Xcache $this
	 */
	public function dropAll()
	{
		xcache_unset_by_prefix('cache.' . $this->_namespace);

		return $this;
	}

	/**
	 * Do the garbage collection in the driver
	 *
	 * Not necessary in Xcache
	 *
	 * @return \Core\Cache\Xcache $this
	 */
	public function garbageCollector() {}
}