<?php

namespace Core;

class CacheException extends \Exception {}
class CacheNotFoundException extends \Exception {}

class Cache
{
	/**
	 * Already instanced adapters
	 * @var array
	 */
	protected static $_instances = array();

	/**
	 * Get a cache instance from a Cache config file
	 *
	 * @param string $adapter
	 * @return \Core\Cache\Adapter
	 * @throws CacheException
	 */
	public static function getInstance($adapter = 'default')
	{
		if (isset(static::$_instances[$adapter]))
		{
			return static::$_instances[$adapter];
		}

		if ( ! $config = Config::get('cache.' . $adapter))
		{
			throw new CacheException('Cache identifier "' . $adapter . '" not found in Config/Cache.php!');
		}

		// Allow creating custom cache adapters inside App
		if ( ! class_exists($class = 'Core\\Cache\\' . ucfirst($config['adapter'])))
		{
			throw new CacheException('Cache adapter "' . ucfirst($config['adapter']) . '" not found!');
		}

		static::$_instances[$adapter] = new $class($config);
		return static::$_instances[$adapter];
	}

	/**
	 * Check if a adapter instance exists
	 *
	 * @param string $adapter
	 * @return bool
	 * @throws CacheException
	 */
	public static function hasInstance($adapter)
	{
		return (bool) Config::get('cache.' . $adapter);
	}

	/**
	 * Check whether some key from cache exists
	 *
	 * @param string $key
	 * @return bool
	 */
	public static function has($key)
	{
		return static::getInstance()->has($key);
	}

	/**
	 * Save a value in the cache and set its TTL (Time To Live) in minutes
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param int $minutes
	 * @return \Core\Cache\Adapter $this
	 */
	public static function set($key, $value, $minutes = null)
	{
		return static::getInstance()->set($key, $value, $minutes);
	}

	/**
	 * Read a single value from cache
	 *
	 * @param string $key
	 * @return mixed
	 */
	public static function get($key)
	{
		return static::getInstance()->get($key);
	}

	/**
	 * Clear a specified key from cache
	 *
	 * @param string $key
	 * @return \Core\Cache\Adapter
	 */
	public static function drop($key)
	{
		return static::getInstance()->drop($key);
	}

	/**
	 * Clear all the current cache keys
	 *
	 * @return \Core\Cache\Adapter
	 */
	public static function dropAll()
	{
		return static::getInstance()->dropAll();
	}

	/**
	 * Do the garbage collection in the driver
	 *
	 * Only for Database and File drivers
	 *
	 * @return \Core\Cache\Adapter
	 */
	public static function garbageCollector()
	{
		return static::getInstance()->garbageCollector();
	}
}