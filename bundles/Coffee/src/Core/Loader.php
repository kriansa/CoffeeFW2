<?php
namespace Core;

class LoaderException extends \Exception {}

class Loader
{
	/**
	 * Check whether the loader is already registered
	 * @var bool
	 */
	protected static $_registered = false;

	/**
	 * File location cache
	 * @var array
	 */
	protected static $_cache = [];

	/**
	 * Register the class autoloader
	 */
	public static function register()
	{
		// Register only once
		if (static::$_registered)
		{
			return;
		}

		static::$_registered = true;

		$include_path = array(
			realpath(APPPATH . 'classes'),
			realpath(COREPATH . 'classes'),
			get_include_path(),
		);

		set_include_path(implode(PATH_SEPARATOR, $include_path));

		spl_autoload_register('Core\\Loader::loadClass');
	}

	/**
	 * Load a class
	 *
	 * @param string $class
	 * @return bool
	 */
	public static function loadClass($class)
	{
		// Already loaded
		if (strpos($class, 'static::') !== false)
		{
			return true;
		}

		if (!$file = static::getClassFile($class))
		{
			return false;
		}

		include $file;

		// Init the class
		if (method_exists($class, '_init'))
		{
			$class::_init();
		}

		return true;
	}

	/**
	 * Get the absolute path of a given class
	 *
	 * @param string $class
	 * @return string
	 */
	public static function getClassFile($class)
	{
		// Lets get it from cache ;)
		if (!empty(static::$_cache[$class]))
		{
			return static::$_cache[$class];
		}

		$fileName = '';

		if (false !== ($lastNsPos = strripos($class, '\\')))
		{
			$namespace = substr($class, 0, $lastNsPos);
			$class = substr($class, $lastNsPos + 1);
			$fileName = str_replace('\\', DS, $namespace) . DS;
		}

		$file = stream_resolve_include_path($fileName . str_replace('_', DS, $class) . '.php');

		return $file;
	}

	/**
	 * Set a cache for classes locations
	 *
	 * @param array|ArrayAccess $files
	 */
	public static function setCache($files)
	{
		static::$_cache = $files;
	}
}