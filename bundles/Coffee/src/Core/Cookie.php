<?php

namespace Core;

class Cookie
{

	/**
	 * Cookie class configuration defaults
	 * @var array
	 */
	private static $_config = array(
		'expire' => 0,
		'path' => '/',
		'domain' => null,
		'secure' => false,
		'http_only' => false,
	);

	/*
	 * Initialisation and auto configuration
	 */
	public static function _init()
	{
		static::$_config = static::$_config + Config::get('system.cookie', array());
	}

	/**
	 * Check whether a cookie exists
	 *
	 * @param string Cookie name
	 * @return bool
	 */
	public static function has($name = null)
	{
		return ( ! is_null(Input::cookie($name)));
	}

	/**
	 * Gets the value of a signed cookie. Cookies without signatures will not
	 * be returned. If the cookie signature is present, but invalid, the cookie
	 * will be deleted.
	 *
	 *     // Get the "theme" cookie, or use "blue" if the cookie does not exist
	 *     $theme = Cookie::get('theme', 'blue');
	 *
	 * @param string Cookie name
	 * @param mixed Default value to return
	 * @return string
	 */
	public static function get($name = null, $default = null)
	{
		return Input::cookie($name, $default);
	}

	/**
	 * Sets a signed cookie. Note that all cookie values must be strings and no
	 * automatic serialization will be performed!
	 *
	 *     // Set the "theme" cookie
	 *     Cookie::set('theme', 'red');
	 *
	 * @param string Name of cookie
	 * @param string Value of cookie
	 * @param int Lifetime in minutes
	 * @param string Path of the cookie
	 * @param string Domain of the cookie
	 * @param bool If true, the cookie should only be transmitted over a secure HTTPS connection
	 * @param bool If true, the cookie will be made accessible only through the HTTP protocol
	 * @return bool
	 */
	public static function set($name, $value, $minutes = null, $path = null, $domain = null, $secure = null, $http_only = null)
	{
		$value = value($value);

		// use the class defaults for the other parameters if not provided
		is_null($minutes) and $minutes = static::$_config['expiration'];
		is_null($path) and $path = static::$_config['path'];
		is_null($domain) and $domain = static::$_config['domain'];
		is_null($secure) and $secure = static::$_config['secure'];
		is_null($http_only) and $http_only = static::$_config['http_only'];

		// add the current time so we have an offset
		$minutes = $minutes > 0 ? ($minutes * 60) + time() : 0;

		return Request::getInstance()->setCookie(array(
			'name' => $name,
			'value' => $value,
			'expire' => $minutes,
			'path' => $path,
			'domain' => $domain,
			'secure' => $secure,
			'http_only' => $http_only,
		));
	}

	/**
	 * Deletes a cookie by making the value null and expiring it.
	 *
	 *     Cookie::delete('theme');
	 *
	 * @param string Cookie name
 	 * @param string Path of the cookie
	 * @param string Domain of the cookie
	 * @param bool If true, the cookie should only be transmitted over a secure HTTPS connection
	 * @param bool If true, the cookie will be made accessible only through the HTTP protocol
	 * @return bool
	 */
	public static function delete($name, $path = null, $domain = null, $secure = null, $http_only = null)
	{
		// Remove the cookie
		unset($_COOKIE[$name]);

		// Nullify the cookie and make it expire
		return static::set($name, null, -86400, $path, $domain, $secure, $http_only);
	}
}