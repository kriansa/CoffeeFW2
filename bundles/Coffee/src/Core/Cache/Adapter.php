<?php

namespace Core\Cache;

interface Adapter
{
	/**
	 * Constructor
	 *
	 * @param array $config
	 */
	public function __construct(array $config);

	/**
	 * Check whether a value exists
	 *
	 * @param string $key
	 * @return bool
	 */
	public function has($key);

	/**
	 * Retrieve a value from cache
	 *
	 * @param string $key
	 * @return mixed
	 * @throws \Core\CacheNotFoundException
	 */
	public function get($key);

	/**
	 * Method to set the key in cache
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param int $minutes
	 * @return \Core\Cache\Adapter $this
	 */
	public function set($key, $value, $minutes = null);

	/**
	 * Remove a key from cache
	 *
	 * @param type $key
	 * @return \Core\Cache\Adapter $this
	 */
	public function drop($key);

	/**
	 * Delete all the keys created by this instance
	 *
	 * @return \Core\Cache\Adapter $this
	 */
	public function dropAll();

	/**
	 * Do the garbage collection in the driver
	 *
	 * @return \Core\Cache\Adapter $this
	 */
	public function garbageCollector();
}