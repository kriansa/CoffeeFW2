<?php

namespace Core;

class Router
{
	/**
	 * Translate a URL into a route, or use the current inputted if none given
	 *
	 * @param type $url
	 * @param string $method [GET, POST, PUT, DELETE, ALL]
	 * @param string $domain
	 * @return \Core\Route\RouteInterface
	 * @throws \Core\HttpNotFoundException
	 */
	public static function resolve($url = null, $method = null, $domain = null)
	{
		Profiler::markStart(__METHOD__);

		// If none given, detect manually
		if ($url === null)
		{
			$url = URL::getRequestString();
		}

		// Trim the URL slashes
		$url = trim($url, '/');

		// If none given, detect manually
		if ($domain === null)
		{
			$domain = Input::server('HTTP_HOST');
		}

		// If none given, detect manually
		if($method === null)
		{
			$method = Input::method();
		}

		// First we try to search all routes specified
		foreach(Config::get('routes') as $route)
		{
			if($route->match($url, $method, $domain))
			{
				Profiler::markEnd(__METHOD__);
				return $route;
			}
		}

		// Lets use the normal router system then
		if (Config::get('system.router.dynamic_mode'))
		{
			$url = URL::translate($url);

			$controller = APPNAME . '\\' . ( ! empty($url['module']) ? $url['module'] . '\\' : '') . 'Controller\\' . $url['controller'];
			$filename = Loader::getClassFile($controller);
			$action = $url['action'];

			if($filename !== false and method_exists($controller, $action . 'Action'))
			{
				Profiler::markEnd(__METHOD__);
				return Route\Route::make($controller, $action)->setParams($url['arguments']);
			}
		}

		Profiler::markEnd(__METHOD__);
		throw new HttpNotFoundException('No matching route found!');
	}
}