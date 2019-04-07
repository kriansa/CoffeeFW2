<?php

namespace Core;

class Security
{
	/**
	 * The token as submitted in the cookie from the previous request
	 * @var string
	 */
	protected static $_csrfOldToken = null;

	/**
	 * The token for the next request
	 * @var string
	 */
	protected static $_csrfToken = null;

	/**
	 * The string name of the CSRF token stored in the session.
	 * @var string
	 */
	const CSRF_TOKEN_KEY = 'csrf_token';

	/**
	 * Cleans the global $_GET, $_POST and $_COOKIE arrays
	 *
	 * @return void
	 */
	public static function cleanInput()
	{
		$_GET = static::clean($_GET);
		$_POST = static::clean($_POST);
		$_COOKIE = static::clean($_COOKIE);
	}

	/**
	 * Generic variable clean method
	 *
	 * @param mixed $var
	 * @param array|null $filters All the function filters to clean the values - Can be a callable or a Regex string
	 * @return mixed
	 */
	public static function clean($var, $filters = null)
	{
		is_null($filters) and $filters = Config::get('system.security.input_filter', array());
		is_array($filters) or $filters = array($filters);

		foreach ($filters as $filter)
		{
			// Is this filter a callable function?
			if (is_callable($filter))
			{
				if (is_array($var))
				{
					foreach($var as $key => $value)
					{
						$var[$key] = call_user_func($filter, $value);
					}
				}
				else
				{
					$var = call_user_func($filter, $var);
				}
			}

			// Assume it's a regex of characters to filter
			else
			{
				if (is_array($var))
				{
					foreach($var as $key => $value)
					{
						$var[$key] = preg_replace('#['.$filter.']#ui', '', $value);
					}
				}
				else
				{
					$var = preg_replace('#['.$filter.']#ui', '', $var);
				}
			}
		}
		return $var;
	}

	/**
	 * Clean the input value with the anti XSS injection
	 *
	 * @param array|string $value
	 * @return array|string
	 */
	public static function xssClean($value)
	{
		if ( ! is_array($value))
		{
			if ( ! function_exists('htmLawed'))
			{
				include COREPATH . 'Vendor' . DS . 'htmLawed' . DS . 'htmLawed.php';
			}

			return htmLawed($value, array('safe' => 1, 'balanced' => 0));
		}

		foreach ($value as &$element)
		{
			$element = static::xssClean($element);
		}

		return $value;
	}

	/**
	 * Strip all HTML tags from the value
	 *
	 * @param string|array $value
	 * @return string|array
	 */
	public static function stripTags($value)
	{
		if ( ! is_array($value))
		{
			$value = filter_var($value, FILTER_SANITIZE_STRING);
		}
		else
		{
			foreach ($value as &$element)
			{
				$element = static::stripTags($element);
			}
		}

		return $value;
	}

	/**
	 * Escape all the special HTML characters into its entities
	 *
	 * @param string|array $value
	 * @param int $flags Htmlentities flags to use
	 * @return string|array
	 * @throws \RuntimeException
	 */
	public static function htmlEntities($value, $flags = null)
	{
		is_null($flags) and $flags = Config::get('system.security.htmlentities_flags', ENT_QUOTES);

		// Nothing to escape for non-string scalars
		if (is_bool($value) or is_int($value) or is_float($value))
		{
			return $value;
		}

		if (is_string($value))
		{
			$value = htmlentities($value, $flags, Config::get('system.encoding'), false);
		}
		elseif (is_array($value) or ($value instanceof \Iterator and $value instanceof \ArrayAccess))
		{
			foreach ($value as $k => $v)
			{
				$value->{$k} = static::htmlEntities($v, $flags);
			}
		}
		elseif ($value instanceof \Iterator or get_class($value) == 'stdClass')
		{
			foreach ($value as $k => $v)
			{
				$value->{$k} = static::htmlEntities($v, $flags);
			}
		}
		elseif (is_object($value))
		{
			// Check if the object is whitelisted and return when that's the case
			foreach (Config::get('system.security.whitelisted_classes', array()) as $class)
			{
				if (is_a($value, $class))
				{
					return $value;
				}
			}

			// Throw exception when it wasn't whitelisted and can't be converted to String
			if ( ! method_exists($value, '__toString'))
			{
				throw new \RuntimeException('Object class "'.get_class($value).'" could not be converted to string or '.
					'sanitized as ArrayAccess. Whitelist it in security.whitelisted_classes in APPPATH/Config/System.php '.
					'to allow it to be passed unchecked.');
			}

			$value = static::htmlEntities((string) $value, $flags);
		}

		return $value;
	}

	/**
	 * Hash a password using the Bcrypt hashing scheme.
	 *
	 * <code>
	 *		// Create a Bcrypt hash of a value
	 *		$hash = Hash::make('secret');
	 *
	 *		// Use a specified number of iterations when creating the hash
	 *		$hash = Hash::make('secret', 12);
	 * </code>
	 *
	 * @param string $value
	 * @param int $rounds
	 * @return string
	 */
	public static function hash($value, $rounds = 8)
	{
		$work = str_pad($rounds, 2, '0', STR_PAD_LEFT);

		// Bcrypt expects the salt to be 22 base64 encoded characters including
		// dots and slashes. We will get rid of the plus signs included in the
		// base64 data and replace them with dots.
		if (function_exists('openssl_random_pseudo_bytes'))
		{
			$salt = openssl_random_pseudo_bytes(16);
		}
		else
		{
			$salt = Str::random(40);
		}

		$salt = substr(strtr(base64_encode($salt), '+', '.'), 0 , 22);

		return crypt($value, '$2a$'.$work.'$'.$salt);
	}

	/**
	 * Determine if an unhashed value matches a Bcrypt hash.
	 *
	 * @param string $value
	 * @param string $hash
	 * @return bool
	 */
	public static function checkHash($value, $hash)
	{
		return (bool) (strcmp(crypt($value, $hash), $hash) == 0);
	}

	/**
	 * Fetches CSRF settings and current token
	 *
	 * @return void
	 */
	public static function loadToken()
	{
		static::$_csrfOldToken = Session::get(static::CSRF_TOKEN_KEY);
		static::checkToken();
	}

	/**
	 * Check CSRF Token
	 * Check token also ensures a token is present and will reset the
	 * token for the next session when it receives a value to check
	 * (no matter the result of the check).
	 *
	 * @param string CSRF token to be checked, checks post when empty
	 * @return bool
	 */
	public static function checkToken($value = null)
	{
		$value or $value = Input::post(static::CSRF_TOKEN_KEY, 'fail');

		// Always reset token once it's been checked and still the same
		if (strcmp(static::fetchToken(), static::$_csrfOldToken) == 0 and ! empty($value))
		{
			static::_setToken(true);
		}

		return (bool) (strcmp($value, static::$_csrfOldToken) == 0);
	}

	/**
	 * Fetch CSRF Token for the next request
	 *
	 * @return string
	 */
	public static function fetchToken()
	{
		if (static::$_csrfToken !== null)
		{
			return static::$_csrfToken;
		}


		static::_setToken();

		return static::$_csrfToken;
	}

	/**
	 * Set the current token and send cookie
	 *
	 * @param bool $reset If true, resend the cookie always
	 * @return void
	 */
	protected static function _setToken($reset = false)
	{
		// re-use old token when found (= not expired) and expiration is used (otherwise always reset)
		if ( ! $reset and static::$_csrfOldToken)
		{
			static::$_csrfToken = static::$_csrfOldToken;
		}
		// set new token for next session when necessary
		else
		{
			static::$_csrfToken = sha1(uniqid() . microtime(true));
			Session::set(static::CSRF_TOKEN_KEY, static::$_csrfToken);
		}
	}
}