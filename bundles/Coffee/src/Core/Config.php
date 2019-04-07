<?php

namespace Core;

class ConfigException extends \Exception {}

class Config
{
	/**
	 * The master config array
	 * @var array
	 */
	public static $items = array();

	/**
	 * Loads a config file.
	 *
	 * @param string|array $file string File | Config array
	 * @param bool $overwrite
	 * @param string $environment
	 * @return bool
	 */
	public static function load($file, $overwrite = false, $environment = null)
	{
		if ( ! $overwrite and
		     ! is_array($file) and
		    array_key_exists(($file = strtolower($file)), static::$items))
		{
			return false;
		}

		if (is_array($file))
		{
			static::$items = $file;
		}
		elseif (is_string($file))
		{
			static::$items[$file] = static::_mergeCascading($file . '.php', $environment);
		}
		else
		{
			return false;
		}

		return true;
	}

	/**
	 * Export all items from config
	 *
	 * @return array
	 */
	public static function export()
	{
		return static::$items;
	}

	/**
	 * Returns a (dot notated) config setting
	 *
	 * @param string $item Name of the config item, can be dot notated
	 * @param mixed $default The return value if the item isn't found
	 * @return mixed The config setting or default if not found
	 */
	public static function get($item, $default = null)
	{
		$item = strtolower($item);
		$parts = explode('.', $item);
		$file = $parts[0];

		// Check if the file was loaded, and try to load it if not
		if( ! isset(static::$items[$file]))
		{
			static::load($file);
		}

		// Avoid some overhead in Arr::get
		switch (count($parts))
		{
			case 1:
				if (isset(static::$items[$item]))
				{
					return value(static::$items[$item]);
				}
			break;

			case 2:
				if (isset(static::$items[$parts[0]][$parts[1]]))
				{
					return value(static::$items[$parts[0]][$parts[1]]);
				}
			break;

			case 3:
				if (isset(static::$items[$parts[0]][$parts[1]][$parts[2]]))
				{
					return value(static::$items[$parts[0]][$parts[1]][$parts[2]]);
				}
			break;

			case 4:
				if (isset(static::$items[$parts[0]][$parts[1]][$parts[2]][$parts[3]]))
				{
					return value(static::$items[$parts[0]][$parts[1]][$parts[2]][$parts[3]]);
				}
			break;

			case 5:
				if (isset(static::$items[$parts[0]][$parts[1]][$parts[2]][$parts[3]][$parts[4]]))
				{
					return value(static::$items[$parts[0]][$parts[1]][$parts[2]][$parts[3]][$parts[4]]);
				}
			break;
		}

		return value(Arr::get(static::$items, $item, $default));
	}

	/**
	 * Sets a (dot notated) config item
	 *
	 * @param string A (dot notated) config key
	 * @param mixed The config value
	 */
	public static function set($item, $value)
	{
		Arr::set(static::$items, strtolower($item), value($value));
	}

	/**
	 * Deletes a (dot notated) config item
	 *
	 * @param string A (dot notated) config key
	 * @return array|bool The Arr::delete result, success boolean or array of success booleans
	 */
	public static function delete($item)
	{
		return Arr::delete(static::$items, $item);
	}

	/**
	 * Makes a recursive merge using the cascading file system
	 *
	 * @param string $fileName
	 * @param string $environment
	 * @return array Configs
	 */
	protected static function _mergeCascading($fileName, $environment)
	{
		$files = array();
		$environment or $environment = ENVIRONMENT;

		if(file_exists(APPPATH . 'Config' . DS . $fileName))
			$files[] = include(APPPATH . 'Config' . DS . $fileName);

		if(file_exists(APPPATH . 'Config' . DS . $environment . DS . $fileName))
			$files[] = include(APPPATH . 'Config' . DS . $environment . DS . $fileName);

		if(empty($files))
		{
			return array();
		}

		// Lets make as fast as we can! :)
		switch(count($files)){
			case 1:
				return $files[0];
			case 2:
				return Arr::merge($files[0], $files[1]);
		}
	}
}