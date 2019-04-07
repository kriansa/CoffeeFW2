<?php

namespace Core;

class URL
{
	/**
	 * Gets the base URL of the system without the trailing slash.
	 *
	 * E.g: '/baseurl' or '' if the base is in root
	 *
	 * @return string
	 */
	public static function getBase()
	{
		return rtrim('/' . trim(Config::get('system.base_url'), '/'), '/');
	}

	/**
	 * Creates a url with the given uri, including the base url
	 *
	 * @param string $uri The uri to create the URL for
	 * @param array|string $get_variables Any GET urls to append via a query string
	 * @param bool $secure If false, force http. If true, force https
	 * @return string
	 */
	public static function create($uri = null, $get_variables = array(), $secure = null)
	{
		$url = '';

		// If the given uri is not a full URL
		if( ! preg_match("#^(http|https|ftp)://#i", $uri))
		{
			$url .= static::base();
		}

		$uri and $url .= '/' . trim($uri, '/');

		if ( ! empty($get_variables))
		{
			$char = strpos($url, '?') === false ? '?' : '&';

			if (is_string($get_variables))
			{
				$url .= $char . str_replace('%3A', ':', $get_variables);
			}
			else
			{
				$url .= $char . str_replace('%3A', ':', http_build_query($get_variables, null, '&'));
			}
		}

		is_bool($secure) and $url = http_build_url($url, array('scheme' => $secure ? 'https' : 'http'));

		return $url;
	}

	/**
	 * Detects and returns the current URL without the trailing slash
	 *
	 * @return string
	 */
	public static function getFull()
	{
		static $url = null;
		if($url === null)
		{
			$url = Input::protocol() . '://' . strtolower($_SERVER['SERVER_NAME']) . rtrim($_SERVER['REQUEST_URI'], '/');
		}

		return $url;
	}

	/**
	 * Gets the request string without the trailing slashes
	 *
	 * @return string
	 */
	public static function getRequestString()
	{
		static $request_string = null;
		if($request_string === null)
		{
			// If is there a PATH_INFO var, we use it and take off the trailing slashes
			if (isset($_SERVER['PATH_INFO']))
			{
				$url = trim(parse_url($_SERVER['PATH_INFO'], PHP_URL_PATH), '/');
			}
			// Otherwise, we try to emulate the PATH_INFO, cleaning all the double-slashes from the REQUEST_URI
			else
			{
				$url = parse_url(preg_replace('#/{2,}#i', '/', trim(Input::server('REQUEST_URI'), '/ ')), PHP_URL_PATH);
			}

			$request_string = trim((string) substr($url, strlen(trim(Config::get('system.base_url'), '/'))), '/');
		}

		// Then we slice the string from the base_url setting, removing all the trailing slashes
		return $request_string;
	}

	/**
	 * Get the segment of URL
	 *
	 * @param int $segment
	 * @return string
	 */
	public static function getSegment($segment = null)
	{
		$segments = explode('/', static::getRequestString());

		if (empty($segments))
		{
			return '';
		}

		return isset($segments[(int)$segment]) ? $segments[(int)$segment] : '';
	}

	/**
	 * Detects the extension inputted in URL
	 *
	 * @staticvar string $ext
	 * @return string
	 */
	public static function getExtension()
	{
		static $ext = null;

		if ($ext === null)
		{
			$ext = pathinfo(static::getRequestString(), PATHINFO_EXTENSION);
		}

		return $ext;
	}

	/**
	 * Translate a URL in module/controller/action/args format.
	 * If you are using the modules system, this will check if the module exist,
	 * and if it doesn't, the default module will be used instead
	 *
	 * @param string $url
	 * @return array
	 */
	public static function translate($url)
	{
		if(empty($url))
		{
			$url = 'index/index';
		}

		$parts = (array) explode('/', $url);

		if(Config::get('system.router.use_modules'))
		{
			$module = ! empty($parts[0]) ? static::nameToModule($parts[0]) : static::nameToModule(Config::get('system.router.default_module'));
			if ( ! is_dir(APPPATH . 'classes' . DS . APPNAME . DS . 'Module' . DS . ucfirst($module)))
			{
				$module = static::nameToModule(Config::get('system.router.default_module'));
				array_unshift($parts, null);
			}
		}
		else
		{
			$module = null;
			array_unshift($parts, null);
		}

		return array(
			'module' => $module,
			'controller' => ! empty($parts[1]) ? static::nameToController($parts[1]) : 'Index',
			'action' => ! empty($parts[2]) ? static::nameToAction($parts[2]) : 'index',
			'arguments' => array_slice($parts, 3),
		);
	}

	/**
	 * Use the Coffee conventions to convert a URL segment into a module name
	 *
	 * @param string $string
	 * @return string
	 */
	public static function nameToModule($string)
	{
		return Inflector::dashesToStudlyCase($string);
	}

	/**
	 * Use the Coffee conventions to convert a URL segment into a controller name
	 *
	 * @param string $string
	 * @return string
	 */
	public static function nameToController($string)
	{
		return static::nameToModule($string);
	}

	/**
	 * Use the Coffee conventions to convert a URL segment into a action name
	 *
	 * @param string $string
	 * @return string
	 */
	public static function nameToAction($string)
	{
		return Inflector::dashesToCamelCase($string);
	}
}