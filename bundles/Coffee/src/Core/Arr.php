<?php

namespace Core;

/**
 * The Arr class provides a few nice functions for making
 * dealing with arrays easier
 *
 * @package     Fuel
 * @subpackage  Core
 */
class Arr
{

	/**
	 * Gets a dot-notated key from an array, with a default value if it does
	 * not exist.
	 *
	 * @param array $array The search array
	 * @param mixed $key The dot-notated key or array of keys
	 * @param string $default The default value
	 * @return mixed
	 */
	public static function get($array, $key, $default = null)
	{
		if ( ! is_array($array) and ! $array instanceof \ArrayAccess)
		{
			throw new \InvalidArgumentException('First parameter must be an array or ArrayAccess object.');
		}

		if (is_null($key))
		{
			return $array;
		}

		if (is_array($key))
		{
			$return = array();
			foreach ($key as $k)
			{
				$return[$k] = static::get($array, $k, $default);
			}
			return $return;
		}

		foreach (explode('.', $key) as $key_part)
		{
			if (($array instanceof \ArrayAccess and isset($array[$key_part])) === false)
			{
				if ( ! is_array($array) or ! array_key_exists($key_part, $array))
				{
					return value($default);
				}
			}

			$array = $array[$key_part];
		}

		return $array;
	}

	/**
	 * Set an array item (dot-notated) to the value.
	 *
	 * @param array $array The array to insert it into
	 * @param mixed $key The dot-notated key to set or array of keys
	 * @param mixed $value The value
	 */
	public static function set(&$array, $key, $value = null)
	{
		if (is_null($key))
		{
			$array = $value;
			return;
		}

		if (is_array($key))
		{
			foreach ($key as $k => $v)
			{
				static::set($array, $k, $v);
			}
		}

		$keys = explode('.', $key);

		while (count($keys) > 1)
		{
			$key = array_shift($keys);

			if ( ! isset($array[$key]) or ! is_array($array[$key]))
			{
				$array[$key] = array();
			}

			$array =& $array[$key];
		}

		$array[array_shift($keys)] = $value;
	}

	/**
	 * Array_key_exists with a dot-notated key from an array.
	 *
	 * @param array $array The search array
	 * @param mixed $key The dot-notated key or array of keys
	 * @return bool
	 */
	public static function keyExists($array, $key)
	{
		foreach (explode('.', $key) as $key_part)
		{
			if ( ! is_array($array) or ! array_key_exists($key_part, $array))
			{
				return false;
			}

			$array = $array[$key_part];
		}

		return true;
	}

	/**
	 * Unsets dot-notated key from an array
	 *
	 * @param array $array The search array
	 * @param mixed $key The dot-notated key or array of keys
	 * @return bool
	 */
	public static function delete(&$array, $key)
	{
		if (is_null($key))
		{
			return false;
		}

		if (is_array($key))
		{
			$return = array();
			foreach ($key as $k)
			{
				$return[$k] = static::delete($array, $k);
			}
			return $return;
		}

		$key_parts = explode('.', $key);

		if ( ! is_array($array) or ! array_key_exists($key_parts[0], $array))
		{
			return false;
		}

		$this_key = array_shift($key_parts);

		if ( ! empty($key_parts))
		{
			$key = implode('.', $key_parts);
			return static::delete($array[$this_key], $key);
		}
		else
		{
			unset($array[$this_key]);
		}

		return true;
	}

	/**
	 * Converts a multi-dimensional associative array into an array of key => values with the provided field names
	 *
	 * @param array The array to convert
	 * @param string The field name of the key field
	 * @param string The field name of the value field
	 * @return array
	 */
	public static function assocToKeyval($assoc = null, $key_field = null, $val_field = null)
	{
		if(empty($assoc) or empty($key_field) or empty($val_field))
		{
			return null;
		}

		$output = array();
		foreach($assoc as $row)
		{
			if(isset($row[$key_field]) AND isset($row[$val_field]))
			{
				$output[$row[$key_field]] = $row[$val_field];
			}
		}

		return $output;
	}

	/**
	 * Converts the given 1 dimensional non-associative array to an associative
	 * array.
	 *
	 * The array given must have an even number of elements or null will be returned.
	 *
	 *     Arr::to_assoc(array('foo','bar'));
	 *
	 * @param string $arr The array to change
	 * @return array|null The new array or null
	 */
	public static function toAssoc($arr)
	{
		if (($count = count($arr)) % 2 > 0)
		{
			return null;
		}
		$keys = $vals = array();

		for ($i = 0; $i < $count - 1; $i += 2)
		{
			$keys[] = array_shift($arr);
			$vals[] = array_shift($arr);
		}
		return array_combine($keys, $vals);
	}

	/**
	 * Checks if the given array is an assoc array.
	 *
	 * @param array $arr The array to check
	 * @return bool
	 */
	public static function isAssoc($arr)
	{
		foreach ($arr as $key => $unused)
		{
			if ( ! is_int($key))
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * Flattens a multi-dimensional associative array down into a 1 dimensional
	 * associative array.
	 *
	 * @param array The array to flatten
	 * @param string What to glue the keys together with
	 * @param bool Whether to reset and start over on a new array
	 * @param bool Whether to flatten only associative array's, or also indexed ones
	 * @return array
	 */
	public static function flatten($array, $glue = ':', $reset = true, $indexed = true)
	{
		static $return = array();
		static $curr_key = array();

		if ($reset)
		{
			$return = array();
			$curr_key = array();
		}

		foreach ($array as $key => $val)
		{
			$curr_key[] = $key;
			if (is_array($val) and ($indexed or array_values($val) !== $val))
			{
				static::flatten($val, $glue, false, $indexed);
			}
			else
			{
				$return[implode($glue, $curr_key)] = $val;
			}
			array_pop($curr_key);
		}
		return $return;
	}

	/**
	 * Flattens a multi-dimensional associative array down into a 1 dimensional
	 * associative array.
	 *
	 * @param array The array to flatten
	 * @param string What to glue the keys together with
	 * @param bool Whether to reset and start over on a new array
	 * @return array
	 */
	public static function flattenAssoc($array, $glue = ':', $reset = true)
	{
		return static::flatten($array, $glue, $reset, false);
	}

	/**
	 * Filters an array on prefixed associative keys.
	 *
	 * @param array The array to filter.
	 * @param string Prefix to filter on.
	 * @param bool Whether to remove the prefix.
	 * @return array
	 */
	public static function filterPrefixed($array, $prefix = 'prefix_', $remove_prefix = true)
	{
		$return = array();
		foreach ($array as $key => $val)
		{
			if(preg_match('/^'.$prefix.'/', $key))
			{
				if($remove_prefix === true)
				{
					$key = preg_replace('/^'.$prefix.'/','',$key);
				}
				$return[$key] = $val;
			}
		}
		return $return;
	}

	/**
	 * Filters an array by an array of keys
	 *
	 * @param array The array to filter.
	 * @param array The keys to filter
	 * @param bool If true, removes the matched elements.
	 * @return array
	 */
	public static function filterKeys(array $array, $keys, $remove = false)
	{
		$return = array();
		foreach ($keys as $key)
		{
			if (isset($array[$key]))
			{
				$remove or $return[$key] = $array[$key];
				if($remove)
				{
					unset($array[$key]);
				}
			}
		}
		return $remove ? $array : $return;
	}

	/**
	 * Insert value(s) into an array, mostly an array_splice alias
	 * WARNING: original array is edited by reference, only boolean success is returned
	 *
	 * @param array The original array (by reference)
	 * @param array|mixed The value(s) to insert, if you want to insert an array it needs to be in an array itself
	 * @param int The numeric position at which to insert, negative to count from the end backwards
	 * @return bool False when array shorter then $pos, otherwise true
	 */
	public static function insert(array &$original, $value, $pos)
	{
		if (count($original) < abs($pos))
		{
			trigger_error('Position larger than number of elements in array in which to insert.');
			return false;
		}

		array_splice($original, $pos, 0, $value);
		return true;
	}

	/**
	 * Insert value(s) into an array before a specific key
	 * WARNING: original array is edited by reference, only boolean success is returned
	 *
	 * @param array The original array (by reference)
	 * @param array|mixed The value(s) to insert, if you want to insert an array it needs to be in an array itself
	 * @param string|int The key before which to insert
	 * @return bool False when key isn't found in the array, otherwise true
	 */
	public static function insertBeforeKey(array &$original, $value, $key)
	{
		$pos = array_search($key, array_keys($original));
		if ($pos === false)
		{
			trigger_error('Unknown key before which to insert the new value into the array.');
			return false;
		}

		return static::insert($original, $value, $pos);
	}

	/**
	 * Insert value(s) into an array after a specific key
	 * WARNING: original array is edited by reference, only boolean success is returned
	 *
	 * @param array The original array (by reference)
	 * @param array|mixed The value(s) to insert, if you want to insert an array it needs to be in an array itself
	 * @param string|int The key after which to insert
	 * @return bool False when key isn't found in the array, otherwise true
	 */
	public static function insertAfterKey(array &$original, $value, $key)
	{
		$pos = array_search($key, array_keys($original));
		if ($pos === false)
		{
			trigger_error('Unknown key after which to insert the new value into the array.');
			return false;
		}

		return static::insert($original, $value, $pos + 1);
	}

	/**
	 * Insert value(s) into an array after a specific value (first found in array)
	 *
	 * @param array The original array (by reference)
	 * @param array|mixed The value(s) to insert, if you want to insert an array it needs to be in an array itself
	 * @param string|int The value after which to insert
	 * @return bool False when value isn't found in the array, otherwise true
	 */
	public static function insertAfterValue(array &$original, $value, $search)
	{
		$key = array_search($search, $original);
		if ($key === false)
		{
			trigger_error('Unknown value after which to insert the new value into the array.');
			return false;
		}

		return static::insert_after_key($original, $value, $key);
	}

	/**
	 * Sorts a multi-dimensional array by it's values.
	 *
	 * @param array The array to fetch from
	 * @param string The key to sort by
	 * @param string The order (asc or desc)
	 * @param int The php sort type flag
	 * @return array
	 * @throws \InvalidArgumentException
	 */
	public static function sort(array $array, $key, $order = 'asc', $sort_flags = SORT_REGULAR)
	{
		foreach ($array as $k => $v)
		{
			$b[$k] = static::get($v, $key);
		}

		switch ($order)
		{
			case 'asc':
				asort($b, $sort_flags);
			break;

			case 'desc':
				arsort($b, $sort_flags);
			break;

			default:
				throw new \InvalidArgumentException('Arr::sort() - $order must be asc or desc.');
			break;
		}

		foreach ($b as $key => $val)
		{
			$c[] = $array[$key];
		}

		return $c;
	}

	/**
	 * Find the average of an array
	 *
	 * @param array The array containing the values
	 * @return int The average value
	 */
	public static function average(array $array)
	{
		// No arguments passed, lets not divide by 0
		if ( ! ($count = count($array)) > 0)
			return 0;

		return (array_sum($array) / $count);
	}

	/**
	 * Replaces key names in an array by names in $replace
	 *
	 * @param array The array containing the key/value combinations
	 * @param array|string Key to replace or array containing the replacement keys
	 * @param string The replacement key
	 * @return array The array with the new keys
	 * @throws \InvalidArgumentException
	 */
	public static function replaceKey(array $source, $replace, $new_key = null)
	{
		if(is_string($replace))
			$replace = array($replace => $new_key);

		if ( ! is_array($source) or ! is_array($replace))
			throw new \InvalidArgumentException('Arr::replace_keys() - $source must an array. $replace must be an array or string.');

		$result = array();

		foreach ($source as $key => $value)
		{
			if (array_key_exists($key, $replace))
				$result[$replace[$key]] = $value;
			else
				$result[$key] = $value;
		}

		return $result;
	}

	/**
	 * Merge 2 arrays recursively, differs in 2 important ways from array_merge_recursive()
	 * - When there's 2 different values and not both arrays, the latter value overwrites the earlier
	 *   instead of merging both into an array
	 * - Numeric keys that don't conflict aren't changed, only when a numeric key already exists is the
	 *   value added using array_push()
	 *
	 * @param array Multiple variables all of which must be arrays
	 * @return array
	 * @throws \InvalidArgumentException
	 */
	public static function merge()
	{
		$array  = func_get_arg(0);
		$arrays = array_slice(func_get_args(), 1);

		if ( ! is_array($array))
		{
			throw new \InvalidArgumentException('Arr::merge() - all arguments must be arrays.');
		}

		foreach ($arrays as $arr)
		{
			if(empty($arr))
				continue;

			if ( ! is_array($arr))
				throw new \InvalidArgumentException('Arr::merge() - all arguments must be arrays.');

			foreach ($arr as $k => $v)
			{
				// numeric keys are appended
				if (is_int($k))
					array_key_exists($k, $array) ? array_push($array, $v) : $array[$k] = $v;
				elseif (is_array($v) and array_key_exists($k, $array) and is_array($array[$k]))
					$array[$k] = static::merge($array[$k], $v);
				else
					$array[$k] = $v;
			}
		}

		return $array;
	}

	/**
	 * Prepends a value with an asociative key to an array.
	 * Will overwrite if the value exists.
	 *
	 * @param array $arr The array to prepend to
	 * @param string|array $key The key or array of keys and values
	 * @param mixed $value the Value to prepend
	 */
	public static function prepend(array &$arr, $key, $value = null)
	{
		$arr = (is_array($key) ? $key : array($key => $value)) + $arr;
	}
}