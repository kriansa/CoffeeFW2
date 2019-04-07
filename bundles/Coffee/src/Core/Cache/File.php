<?php

namespace Core\Cache;

class File
{
	/**
	 * Namespace of this instance
	 * @var string
	 */
	protected $_namespace = null;

	/**
	 * Current path to the cached files
	 * @var string
	 */
	protected $_path = null;

	/**
	 * Constructor
	 *
	 * @param array $config
	 * @throws \Core\CacheException
	 */
	public function __construct(array $config)
	{
		$this->_path = empty($config['path']) ? APPPATH . 'data' . DS . 'cache' . DS : rtrim($config['path'], '/\\') . DS;
		$this->_namespace = $config['namespace'];

		if ( ! is_dir($this->_path) or ! is_writable($this->_path))
		{
			throw new \Core\CacheException('Cache directory "' . \Core\Debug::cleanPath($this->_path) . '" does not exist or is not writable.');
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
		$file_name = $this->_path . $this->_namespace . '_' . md5($key);

		// File based caches store have the expiration timestamp stored in
		// UNIX format prepended to their contents. This timestamp is then
		// extracted and removed when the cache is read to determine if
		// the file is still valid.
		if (is_file($file_name) and time() >= substr($cache = file_get_contents($file_name), 0, 10))
		{
			unlink($file_name);
		}

		return is_file($file_name);
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
		$file_name = $this->_path . $this->_namespace . '_' . md5($key);

		// File based caches store have the expiration timestamp stored in
		// UNIX format prepended to their contents. This timestamp is then
		// extracted and removed when the cache is read to determine if
		// the file is still valid.
		if (time() >= substr($cache = file_get_contents($file_name), 0, 10))
		{
			unlink($file_name);
		}

		if( ! is_file($file_name))
		{
			throw new \Core\CacheNotFoundException('Cache identifier "' . $key . '" not found or expired!');
		}

		return unserialize(substr($cache, 10));
	}

	/**
	 * Method to set the key in cache
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param int $minutes
	 * @return \Core\Cache\File $this
	 */
	public function set($key, $value, $minutes = null)
	{
		$file_name = $this->_path . $this->_namespace . '_' . md5($key);

		file_put_contents($file_name, ($minutes ? (string) (time() + $minutes * 60) : '9999999999') . serialize(value($value)), LOCK_EX);

		return $this;
	}

	/**
	 * Remove a key from cache
	 *
	 * @param type $key
	 * @return \Core\Cache\File $this
	 */
	public function drop($key)
	{
		$file_name = $this->_path . $this->_namespace . '_' . md5($key);
		is_file($file_name) and unlink($file_name);

		return $this;
	}

	/**
	 * Delete all the keys created by this instance
	 *
	 * @return \Core\Cache\File $this
	 */
	public function dropAll()
	{
		foreach(glob($this->_path . $this->_namespace . '_*') as $file)
		{
			unlink($file);
		}

		return $this;
	}

	/**
	 * Do the garbage collection in the driver
	 *
	 * @return \Core\Cache\File $this
	 */
	public function garbageCollector()
	{
		foreach(glob($this->_path . $this->_namespace . '_*') as $file)
		{
			if (time() >= substr(file_get_contents($file), 0, 10))
			{
				unlink($file);
			}
		}

		return $this;
	}
}