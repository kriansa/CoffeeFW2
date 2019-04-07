<?php

namespace Core\Cache;

class Memcache
{

	/**
	 * Namespace of this instance
	 * @var string
	 */
	protected $_namespace = null;

	/**
	 * Instance of current Memcache
	 * @var \Memcache
	 */
	protected $_memcache = null;

	/**
	 * Constructor
	 *
	 * @param array $config
	 * @throws \Core\CacheException
	 */
	public function __construct(array $config)
	{
		$this->_namespace = $config['namespace'];
		$this->_memcache = new \Memcache;

		foreach ($config['servers'] as $server)
		{
			$this->_memcache->addServer($server['host'], $server['port'], true, $server['weight']);
		}

		if ($this->_memcache->getVersion() === false)
		{
			throw new \Core\CacheException('Could not establish memcached connection.');
		}
	}

	/**
	 * Check whether a value exists
	 *
	 * @param string $key
	 * @return bool
	 */
	public function has($key)
	{
		return ($this->_memcache->get('cache.' . $this->_namespace . '.' . $key) !== false);
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
		if (($cache = $this->_memcache->get('cache.' . $this->_namespace . '.' . $key)) === false)
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
	 * @return \Core\Cache\Memcache $this
	 */
	public function set($key, $value, $minutes = null)
	{
		$this->_memcache->set('cache.' . $this->_namespace . '.' . $key, value($value), 0, $minutes * 60);

		// Store it into a internal var
		$current_keys = $this->_memcache->get('cache.' . $this->_namespace);
		$current_keys[$key] = true;
		$this->_memcache->set('cache.' . $this->_namespace, $current_keys);

		return $this;
	}

	/**
	 * Remove a key from cache
	 *
	 * @param type $key
	 * @return \Core\Cache\Memcache $this
	 */
	public function drop($key)
	{
		$this->_memcache->delete('cache.' . $this->_namespace . '.' . $key);

		// Store it into a internal var
		$current_keys = $this->_memcache->get('cache.' . $this->_namespace);
		unset($current_keys[$key]);
		$this->_memcache->set('cache.' . $this->_namespace, $current_keys);

		return $this;
	}

	/**
	 * Delete all the keys created by this instance
	 *
	 * @return \Core\Cache\Memcache $this
	 */
	public function dropAll()
	{
		foreach($this->_memcache->get('cache.' . $this->_namespace) as $key => $value)
		{
			$this->_memcache->delete('cache.' . $this->_namespace . '.' . $key);
		}
		$this->_memcache->delete('cache.' . $this->_namespace);

		return $this;
	}

	/**
	 * Do the garbage collection in the driver
	 *
	 * Not necessary in Memcache
	 *
	 * @return \Core\Cache\Memcache $this
	 */
	public function garbageCollector() {}
}