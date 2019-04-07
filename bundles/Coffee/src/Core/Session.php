<?php

namespace Core;

class SessionException extends \Exception {}

class Session
{
	/**
	 * The session singleton instance for the request.
	 * @var \Core\Session\Driver
	 */
	protected static $_driver = null;

	/**
	 * The session array that is stored by the driver.
	 * @var array
	 */
	protected static $_session = null;

	/**
	 * Store the loaded configs from the system config
	 * @var array
	 */
	protected static $_config = array();

	/**
	 * Create the session driver and load the session.
	 *
	 * return @void
	 */
	public static function _init()
	{
		// Store the configs inside the static array
		static::$_config = Config::get('system.session');

		$driver_name = 'Core\\Session\\' . ucfirst(static::$_config['driver']['name']);

		if( ! class_exists($driver_name))
		{
			throw new SessionException('Session driver "' . $driver_name . '" is not supported.');
		}

		// Load the storage driver
		static::$_driver = new $driver_name(static::$_config['driver']);

		// Load the data for current user
		$id = Cookie::get(static::$_config['cookie']['name']);
		$storage = static::$_driver->load($id);

		// Check for IP or Browser changes
		if($storage !== null and (static::$_config['match_user_agent'] and $storage['browser'] != \Core\Input::userAgent()) or (static::$_config['match_ip'] and $storage['ip'] != Input::ip()))
		{
			static::$_driver->delete($id);
			$storage = null;
		}

		// If all ok, assign the storage to internal session variable
		static::$_session = $storage;

		// If the session doesn't exist or is invalid we will create a new session
		if (is_null(static::$_session) or (time() - static::$_session['last_activity']) > (static::$_config['lifetime'] * 60))
		{
			static::$_session = static::$_driver->create();
		}

		// Save session before sending headers
		Event::register('before_send_headers', 'Core\\Session::save');

		// Load the CSRF token
		Security::loadToken();
	}

	/**
	 * Determine if the session or flash data contains an item.
	 *
	 * @param string $key
	 * @return bool
	 */
	public static function has($key)
	{
		return ( ! is_null(static::get($key)));
	}

	/**
	 * Get an item from the session.
	 *
	 * The session flash data will also be checked for the requested item.
	 *
	 * <code>
	 *		// Get an item from the session
	 *		$name = Session::get('name');
	 *
	 *		// Return a default value if the item doesn't exist
	 *		$name = Session::get('name', 'Taylor');
	 * </code>
	 *
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public static function get($key, $default = null)
	{
		$session = static::$_session['data'];

		// We check for the item in the general session data first, and if it
		// does not exist in that data, we will attempt to find it in the new
		// and old flash data, or finally return the default value.
		if ( ! is_null($value = Arr::get($session, $key)))
		{
			return $value;
		}
		elseif ( ! is_null($value = Arr::get($session[':new:'], $key)))
		{
			return $value;
		}
		elseif ( ! is_null($value = Arr::get($session[':old:'], $key)))
		{
			return $value;
		}

		return value($default);
	}

	/**
	 * Write an item to the session.
	 *
	 * <code>
	 *		// Write an item to the session payload
	 *		Session::set('name', 'Taylor');
	 * </code>
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public static function set($key, $value)
	{
		Arr::set(static::$_session['data'], $key, $value);
	}

	/**
	 * Write an item to the session flash data.
	 *
	 * Flash data only exists for the current and next request to the application.
	 *
	 * <code>
	 *		// Write an item to the session payload's flash data
	 *		Session::setFlash('name', 'Taylor');
	 * </code>
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public static function setFlash($key, $value)
	{
		Arr::set(static::$_session['data'][':new:'], $key, $value);
	}

	/**
	 * Keep a session flash item from expiring at the end of the request.
	 * If none given, then keep all the flash data
	 *
	 * <code>
	 *		// Keep the "name" item from expiring from the flash data
	 *		Session::keep('name');
	 *
	 *		// Keep the "name" and "email" items from expiring from the flash data
	 *		Session::keep(array('name', 'email'));
	 * </code>
	 *
	 * @param string|array|ArrayAccess $keys
	 * @return void
	 */
	public static function keepFlash($keys = null)
	{
		if($keys === null)
		{
			$old = static::$_session['data'][':old:'];

			static::$_session['data'][':new:'] = array_merge(static::$_session['data'][':new:'], $old);
		}
		else
		{
			foreach ((array) $keys as $key)
			{
				static::flash($key, static::get($key));
			}
		}
	}

	/**
	 * Remove an item from the session data.
	 *
	 * @param string $key
	 * @return void
	 */
	public static function delete($key)
	{
		Arr::delete(static::$_session['data'], $key);
	}

	/**
	 * Remove all of the items from the session.
	 *
	 * The CSRF token will not be removed from the session.
	 *
	 * @return void
	 */
	public static function destroy()
	{
		static::$_session['data'] = array(
			Security::CSRF_TOKEN_KEY => static::$_session['data'][Security::CSRF_TOKEN_KEY],
			':new:' => array(),
			':old:' => array(),
		);
	}

	/**
	 * Assign a new, random ID to the session.
	 *
	 * @return void
	 */
	public static function regenerate()
	{
		static::$_driver->delete(static::$_session['id']);
		static::$_session['id'] = static::$_driver->id();
	}

	/**
	 * Get the last activity for the session.
	 *
	 * @return int
	 */
	public static function getLastActivity()
	{
		return static::$_session['last_activity'];
	}

	/**
	 * Store the session payload in storage.
	 *
	 * This method will be called automatically at the end of the request.
	 *
	 * @return void
	 * @throws \Core\SessionException
	 */
	public static function save()
	{
		if ( ! static::$_driver)
		{
			return;
		}

		static::$_session['last_activity'] = time();

		// Session flash data is only available during the request in which it
		// was flashed and the following request. We will age the data so that
		// it expires at the end of the user's next request.
		static::$_session['data'][':old:'] = static::$_session['data'][':new:'];
		static::$_session['data'][':new:'] = array();

		// The responsibility of actually storing the session information in
		// persistent storage is delegated to the driver instance being used
		// by the session payload.
		//
		// This allows us to keep the payload very generic, while moving the
		// platform or storage mechanism code into the specialized drivers,
		// keeping our code very dry and organized.
		static::$_driver->save(static::$_session, static::$_config);

		// Next we'll write out the session cookie. This cookie contains the
		// ID of the session, and will be used to determine the owner of the
		// session on the user's subsequent requests to the application.
		$minutes = static::$_config['expire_on_close'] ? 0 : time() + static::$_config['lifetime'] * 60;

		Request::getInstance()->setCookie(array(
			'name' => static::$_config['cookie']['name'],
			'value' => static::$_session['id'],
			'expire' => $minutes,
			'path' => static::$_config['cookie']['path'],
			'domain' => static::$_config['cookie']['domain'],
			'secure' => static::$_config['cookie']['secure'],
			'http_only' => static::$_config['cookie']['http_only'],
		));
	}
}