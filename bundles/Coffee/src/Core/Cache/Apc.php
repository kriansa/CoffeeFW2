<?php

namespace Core\Cache;

class Apc
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
		return apc_exists('cache.' . $this->_namespace . '.' . $key);
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
		if(($cache = apc_fetch('cache.' . $this->_namespace . '.' . $key)) === false)
		{
			throw new \Core\CacheNotFoundException('Cache identifier "' . $key . '" not found or expired!');
		}

		return $cache;
	}

	/**
	 * Method to set the key in cache
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param int $minutes
	 * @return \Core\Cache\Apc $this
	 */
	public function set($key, $value, $minutes = null)
	{
		apc_store('cache.' . $this->_namespace . '.' . $key, value($value), $minutes * 60);

		// Store it into a internal var
		$current_keys = apc_fetch('cache.' . $this->_namespace);
		$current_keys[$key] = true;
		apc_store('cache.' . $this->_namespace, $current_keys);

		return $this;
	}

	/**
	 * Remove a key from cache
	 *
	 * @param type $key
	 * @return \Core\Cache\Apc $this
	 */
	public function drop($key)
	{
		apc_delete('cache.' . $this->_namespace . '.' . $key);

		// Store it into a internal var
		$current_keys = apc_fetch('cache.' . $this->_namespace);
		unset($current_keys[$key]);
		apc_store('cache.' . $this->_namespace, $current_keys);

		return $this;
	}

	/**
	 * Delete all the keys created by this instance
	 *
	 * @return \Core\Cache\Apc $this
	 */
	public function dropAll()
	{
		foreach(apc_fetch('cache.' . $this->_namespace) as $key => $value)
		{
			apc_delete('cache.' . $this->_namespace . '.' . $key);
		}
		apc_delete('cache.' . $this->_namespace);

		return $this;
	}

	/**
	 * Do the garbage collection in the driver
	 *
	 * Not necessary in APC
	 *
	 * @return \Core\Cache\Apc $this
	 */
	public function garbageCollector() {}
}