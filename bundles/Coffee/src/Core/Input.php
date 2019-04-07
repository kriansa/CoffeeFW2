<?php

namespace Core;

/**
 * Input class
 *
 * The input class allows you to access HTTP parameters, load server variables
 * and user agent details.
 *
 * @package   Fuel
 * @category  Core
 * @link      http://docs.fuelphp.com/classes/input.html
 */

class Input
{
	/**
	 * All of the input (GET, POST, PUT, DELETE)
	 * @var array
	 */
	protected static $_input = null;

	/**
	 * All of the put or delete vars
	 * @var array
	 */
	protected static $_putDeleteVars = null;

	/**
	 * Get the public ip address of the user.
	 *
	 * @param string Default value in case the detection fails
	 * @return string
	 */
	public static function ip($default = '0.0.0.0')
	{
		if (static::server('REMOTE_ADDR') !== null)
		{
			return static::server('REMOTE_ADDR');
		}
		else
		{
			// detection failed, return the default
			return $default;
		}
	}

	/**
	 * Return's the protocol that the request was made with
	 *
	 * @return string
	 */
	public static function protocol()
	{
		if ((static::server('HTTPS') !== null and static::server('HTTPS') != 'off')
			or (static::server('HTTPS') === null and static::server('SERVER_PORT') == 443))
		{
			return 'https';
		}

		return 'http';
	}

	/**
	 * Return's whether this is an AJAX request or not
	 *
	 * @return bool
	 */
	public static function isAjax()
	{
		return (static::server('HTTP_X_REQUESTED_WITH') !== null) and strtolower(static::server('HTTP_X_REQUESTED_WITH')) === 'xmlhttprequest';
	}

	/**
	 * Return's the referrer
	 *
	 * @param string Default value in case the detection fails
	 * @return string
	 */
	public static function referrer($default = '')
	{
		return static::server('HTTP_REFERER', $default);
	}

	/**
	 * Return's the input method used (GET, POST, DELETE, etc.)
	 *
	 * @param string Default value in case the detection fails
	 * @return string
	 */
	public static function method($default = 'GET')
	{
		return static::server('HTTP_X_HTTP_METHOD_OVERRIDE', static::server('REQUEST_METHOD', $default));
	}

	/**
	 * Return's the user agent
	 *
	 * @param string Default value in case the detection fails
	 * @return string
	 */
	public static function userAgent($default = '')
	{
		return static::server('HTTP_USER_AGENT', $default);
	}

	/**
	 * Returns all of the GET, POST, PUT and DELETE variables.
	 *
	 * @return array
	 */
	public static function all()
	{
		if (is_null(static::$_input))
		{
			static::_hydrate();
		}

		return static::$_input;
	}

	/**
	 * Gets the specified GET variable.
	 *
	 * @param string $index The index to get or null to get all
	 * @param string $default The default value
	 * @return string|array
	 */
	public static function get($index = null, $default = null)
	{
		return (is_null($index) and func_num_args() === 0) ? $_GET : Arr::get($_GET, $index, $default);
	}

	/**
	 * Fetch an item from the POST array
	 *
	 * @param string  The index key or null to get all
	 * @param mixed   The default value
	 * @return string|array
	 */
	public static function post($index = null, $default = null)
	{
		return (is_null($index) and func_num_args() === 0) ? $_POST : Arr::get($_POST, $index, $default);
	}

	/**
	 * Fetch an item from the php://input for put arguments
	 *
	 * @param string The index key or null to get all
	 * @param mixed The default value
	 * @return string|array
	 */
	public static function put($index = null, $default = null)
	{
		if (is_null(static::$_putDeleteVars))
		{
			static::_hydrate();
		}

		return (is_null($index) and func_num_args() === 0) ? static::$_putDeleteVars : Arr::get(static::$_putDeleteVars, $index, $default);
	}

	/**
	 * Fetch an item from the php://input for delete arguments
	 *
	 * @param string The index key or null to get all
	 * @param mixed The default value
	 * @return string|array
	 */
	public static function delete($index = null, $default = null)
	{
		if (is_null(static::$_putDeleteVars))
		{
			static::_hydrate();
		}

		return (is_null($index) and func_num_args() === 0) ? static::$_putDeleteVars : Arr::get(static::$_putDeleteVars, $index, $default);
	}

	/**
	 * Fetch an item from the FILE array
	 *
	 * @param string The index key or null to get all
	 * @param mixed The default value
	 * @return string|array
	 */
	public static function file($index = null, $default = null)
	{
		return (is_null($index) and func_num_args() === 0) ? $_FILES : Arr::get($_FILES, $index, $default);
	}

	/**
	 * Fetch an item from either the GET, POST, PUT or DELETE array
	 *
	 * @param string The index key or null to get all
	 * @param mixed The default value
	 * @return string|array
	 */
	public static function param($index = null, $default = null)
	{
		if (is_null(static::$_input))
		{
			static::_hydrate();
		}

		return Arr::get(static::$_input, $index, $default);
	}

	/**
	 * Fetch an item from the COOKIE array
	 *
	 * @param string The index key or null to get all
	 * @param mixed The default value
	 * @return string|array
	 */
	public static function cookie($index = null, $default = null)
	{
		return (is_null($index) and func_num_args() === 0) ? $_COOKIE : Arr::get($_COOKIE, $index, $default);
	}

	/**
	 * Fetch an item from the SERVER array
	 *
	 * @param string The index key or null to get all
	 * @param mixed The default value
	 * @return string|array
	 */
	public static function server($index = null, $default = null)
	{
		return (is_null($index) and func_num_args() === 0) ? $_SERVER : Arr::get($_SERVER, strtoupper($index), $default);
	}

	/**
	 * Hydrates the input array
	 */
	protected static function _hydrate()
	{
		static::$_input = array_merge($_GET, $_POST);

		if (static::method() == 'PUT' or static::method() == 'DELETE')
		{
			parse_str(file_get_contents('php://input'), static::$_putDeleteVars);
			static::$_input = array_merge(static::$_input, static::$_putDeleteVars);
		}
	}
}