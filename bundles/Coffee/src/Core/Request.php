<?php

namespace Core;

class RequestException extends \Exception {}
class HttpNotFoundException extends \Exception {}

class Request
{
	/**
	 * Current request status
	 * @var int
	 */
	public $status = 200;

	/**
	 * Current content-type of the request
	 * [A key from Config::get('mimes')]
	 * @var string
	 */
	public $contentType = 'html';

	/**
	 * String with the full content to be sent
	 * @var string
	 */
	public $body = null;

	/**
	 * Current charset
	 * @var string
	 */
	public $charset = null;

	/**
	 * Array with all headers contents
	 * @var array
	 */
	protected $_headers = [];

	/**
	 * Array with all cookies to be sent
	 * @var array
	 */
	protected $_cookies = [];

	/**
	 * Cache directives
	 * @var array
	 */
	protected $_cacheDirectives = [];

	/**
	 * Singleton instance
	 * @var \Core\Request
	 */
	protected static $_instance = null;

	/**
	 * Gets the default charset
	 */
	public function __construct()
	{
		$this->charset = Config::get('system.encoding');
	}

	/**
	 * Gets the singleton instance
	 *
	 * @return \Core\Request  Retorna a instância ou cria caso ela não tenha sido iniciada
	 */
	public static function getInstance()
	{
		if (static::$_instance === null)
		{
			static::$_instance = new static;
		}

		return static::$_instance;
	}

	/**
	 * Create a new instance using factory pattern
	 *
	 * @return \Core\Request
	 */
	public static function make()
	{
		return new static;
	}

	/**
	 * Dispatch a route
	 *
	 * @param \Core\Route\RouteInterface
	 * @return \Core\Request
	 * @throws \Core\HttpNotFoundException
	 */
	public function dispatch(Route\RouteInterface $route = null)
	{
		Profiler::markStart(__METHOD__);

		if($route === null)
		{
			throw new HttpNotFoundException('Invalid route');
		}

		$params = $route->getParams();
		$controller = $route->getController();
		$action = $route->getAction();

		// Try to autoload the class, otherwise, error 404!
		if ( ! class_exists($controller))
		{
			throw new HttpNotFoundException('Controller "' . $controller . '" not found');
		}

		if ( ! method_exists($controller, $action))
		{
			throw new HttpNotFoundException('Action "' . $action . '" not found');
		}

		// Autoload the session
		Config::get('system.session.autoload') and class_exists('Core\\Session');

		// Autoload the profiler
		Config::get('system.profiler.enabled') and class_exists('Core\\Profiler');

		// Create a new controller instance, and send two parameters
		// This Request instance and the params from Route
		/** @var $instance \Core\Controller\Complete */
		$instance = new $controller($this, $params);

		// Get the response from Controller::before first
		if (method_exists($controller, 'before'))
		{
			Profiler::markStart(' - ' . $controller . '::before');
			$response = $instance->before($action);
			Profiler::markEnd(' - ' . $controller . '::before');
		}
		else
		{
			$response = null;
		}

		// If the before method is the response OR false, then we don't execute the action and after methods
		if ($response !== false and ! is_string($response) and ! ($response instanceof Request))
		{
			// Execute the action
			Profiler::markStart(' - ' . $controller . '::' . $action);
			$response = $instance->$action($params);
			Profiler::markEnd(' - ' . $controller . '::' . $action);

			// The controller response will be re-sent to the Controller::after
			// Which must return the formatted body for layout and such things
			if (method_exists($controller, 'after'))
			{
				Profiler::markStart(' - ' . $controller . '::after');
				$response = $instance->after($response, $action);
				Profiler::markEnd(' - ' . $controller . '::after');
			}
		}

		// If the response is a Request object, lets replace our by its
		if ($response instanceof Request)
		{
			foreach (get_object_vars($response) as $key => $value)
			{
				$this->$key = $value;
			}
		}
		else
		{
			$this->body = (string) $response;
		}

		Profiler::markEnd(__METHOD__);
		return $this;
	}

	/**
	 * Redirect user to another URL
	 *
	 * @param string $url The url
	 * @param string $method 'location' or 'refresh'
	 * @param int $code Status code
	 */
	public static function redirect($url = '', $method = 'location', $code = 302)
	{
		$request = static::getInstance();

		$request->status = $code;

		if (strpos($url, '://') === false)
		{
			$url = $url !== '' ? URL::create($url) : URL::getBase();
		}

		if ($method === 'location')
		{
			$request->setHeader('Location', $url);
		}
		elseif ($method === 'refresh')
		{
			$request->setHeader('Refresh', '0;url='.$url);
		}
		else
		{
			return;
		}

		$request->_sendHeaders();

		exit;
	}

	/**
	 * Set the Content Type of this request
	 *
	 * @param string $contentType
	 * @return \Core\Request
	 */
	public function setContentType($contentType)
	{
		$this->contentType = $contentType;
		return $this;
	}

	/**
	 * Return the current Content Type of request
	 *
	 * @return string
	 */
	public function getContentType()
	{
		return $this->contentType;
	}

	/**
	 * Sets the request charset
	 *
	 * @param string $charset
	 * @return \Core\Request
	 */
	public function setCharset($charset)
	{
		$this->charset = $charset;
		return $this;
	}

	/**
	 * Return the current charset
	 *
	 * @return string
	 */
	public function getCharset()
	{
		return $this->charset;
	}

	/**
	 * Get the configs of a current cookie to be sent
	 *
	 * @param string $cookie
	 * @return array
	 */
	public function getCookie($cookie)
	{
		if ( ! isset($this->_cookies[$cookie]))
			return null;

		return $this->_cookies[$cookie];
	}

	/**
	 * Set a cookie
	 *
	 * @param array $options
	 * @return \Core\Request $this
	 */
	public function setCookie($options)
	{
		$defaults = [
			'name' => 'CoffeeCookie',
			'value' => '',
			'expire' => 0,
			'path' => '/',
			'domain' => '',
			'secure' => false,
			'http_only' => false,
		];
		$options += $defaults;

		$this->_cookies[$options['name']] = $options;

		return $this;
	}

	/**
	 * Sets the correct headers to instruct the client to cache the request.
	 *
	 * @param string $since a valid time since the request text has not been modified
	 * @param string|int $time a valid time recognizable by strtotime or timestamp for cache expiry
	 * @return \Core\Request $this
	 */
	public function setCache($since = 'now', $time = '+1 day')
	{
		if ( ! is_int($time))
		{
			$time = strtotime($time);
		}
		$this->_headers['Date'] = gmdate("D, j M Y H:i:s \G\M\T");
		$this->setLastModified($since);
		$this->setExpires($time);
		$this->setSharable(true);
		$this->setMaxAge($time - time());

		return $this;
	}

	/**
	 * Sets whether a response is eligible to be cached by intermediate proxies
	 * This method controls the `public` or `private` directive in the Cache-Control
	 * header
	 *
	 * @param boolean $public  if set to true, the Cache-Control header will be set as public
	 * if set to false, the response will be set to private
	 * @param int $time time in seconds after which the response should no longer be considered fresh
	 * @return \Core\Request $this
	 */
	public function setSharable($public = true, $time = null)
	{
		if ($public)
		{
			$this->_cacheDirectives['public'] = true;
			unset($this->_cacheDirectives['private']);
			$time and $this->setSharedMaxAge($time);
		}
		else
		{
			$this->_cacheDirectives['private'] = true;
			unset($this->_cacheDirectives['public']);
			$time and $this->setMaxAge($time);
		}

		return $this;
	}

	/**
	 * Return whether the response is sharable or not
	 * @return bool
	 */
	public function getSharable()
	{
		$public = array_key_exists('public', $this->_cacheDirectives);
		$private = array_key_exists('private', $this->_cacheDirectives);
		$noCache = array_key_exists('no-cache', $this->_cacheDirectives);

		if (!$public and !$private and !$noCache)
			return null;

		$sharable = $public || ! ($private || $noCache);
		return $sharable;
	}

	/**
	 * Sets the Cache-Control s-maxage directive.
	 * The max-age is the number of seconds after which the response should no longer be considered
	 * a good candidate to be fetched from a shared cache (like in a proxy server).
	 *
	 * @param int $seconds if null, the method will return the current s-maxage value
	 * @return \Core\Request $this
	 */
	public function setSharedMaxAge($seconds = null)
	{
		$this->_cacheDirectives['s-maxage'] = $seconds;
		return $this;
	}

	/**
	 * Return the current max-age value if any
	 *
	 * @return int|null
	 */
	public function getSharedMaxAge()
	{
		if (isset($this->_cacheDirectives['s-maxage']))
			return $this->_cacheDirectives['s-maxage'];

		return null;
	}

	/**
	 * Sets the Cache-Control max-age directive.
	 * The max-age is the number of seconds after which the response should no longer be considered
	 * a good candidate to be fetched from the local (client) cache.
	 *
	 * @param int $seconds
	 * @return \Core\Request $this
	 */
	public function setMaxAge($seconds = null)
	{
		if ($seconds !== null)
			$this->_cacheDirectives['max-age'] = $seconds;

		return $this;
	}

	/**
	 * Return the current max-age value if any
	 *
	 * @return int|null
	 */
	public function getMaxAge()
	{
		if (isset($this->_cacheDirectives['max-age']))
			return $this->_cacheDirectives['max-age'];

		return null;
	}

	/**
	 * Sets the Cache-Control must-revalidate directive.
	 * must-revalidate indicates that the response should not be served
	 * stale by a cache under any cirumstance without first revalidating
	 * with the origin.
	 *
	 * @param bool $enable must-revalidate value
	 * @return \Core\Request $this
	 */
	public function setMustRevalidate($enable = true)
	{
		if ($enable)
			$this->_cacheDirectives['must-revalidate'] = true;
		else
			unset($this->_cacheDirectives['must-revalidate']);

		return $this;
	}

	/**
	 * Return wheter must-revalidate is present
	 * @return bool
	 */
	public function getMustRevalidate()
	{
		return array_key_exists('must-revalidate', $this->_cacheDirectives);
	}


	/**
	 * Sets the Expires header for the response by taking an expiration time
	 *
	 * ## Examples:
	 *
	 * `$response->expires('now')` Will Expire the response cache now
	 * `$response->expires(new DateTime('+1 day'))` Will set the expiration in next 24 hours
	 *
	 * @param string|int $time Date format recognizable by strtotime or timestamp
	 * @return \Core\Request $this
	 */
	public function setExpires($time = null)
	{
		$this->_headers['Expires'] = gmdate('D, j M Y H:i:s', is_int($time) ? $time : strtotime($time)) . ' GMT';
		return $this;
	}

	/**
	 * Return the current Expires value
	 *
	 * @return string
	 */
	public function getExpires()
	{
		if (isset($this->_headers['Expires']))
			return $this->_headers['Expires'];

			return null;
	}

	/**
	 * Sets the Last-Modified header for the response by taking an modification time
	 *
	 * ## Examples:
	 *
	 * `$response->modified('now')` Will set the Last-Modified to the current time
	 * `$response->modified(new DateTime('+1 day'))` Will set the modification date in the past 24 hours
	 *
	 * @param string|int $time Date format recognizable by strtotime or timestamp
	 * @return \Core\Request $this
	 */
	public function setLastModified($time)
	{
		$this->_headers['Last-Modified'] = gmdate('D, j M Y H:i:s', is_int($time) ? $time : strtotime($time)) . ' GMT';
		return $this;
	}

	/**
	 * Return the current Last-Modified value
	 *
	 * @return string
	 */
	public function getLastModified()
	{
		if (isset($this->_headers['Last-Modified']))
			return $this->_headers['Last-Modified'];

		return null;
	}

	/**
	 * Sets the correct headers to instruct the client NOT to cache the response.
	 */
	public function disableCache()
	{
		$this->_headers['Expires'] = 'Mon, 26 Jul 1997 05:00:00 GMT';
		$this->_headers['Last-Modified'] = gmdate("D, d M Y H:i:s") . " GMT";
		$this->_headers['Cache-Control'] = 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0';
	}

	/**
	 * Set the current status code
	 *
	 * @param int $status
	 * @return \Core\Request $this
	 */
	public function setStatus($status = 200)
	{
		$this->status = $status;
		return $this;
	}

	/**
	 * Return the current status code
	 *
	 * @return int
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * Sets a header
	 *
	 * @param string $name Name
	 * @param string $value Value
	 * @param bool $replace Replace a existing one?
	 * @return \Core\Request $this
	 */
	public function setHeader($name, $value, $replace = true)
	{
		if ($replace)
		{
			$this->_headers[$name] = $value;
		}
		else
		{
			$this->_headers[] = [$name, $value];
		}

		return $this;
	}

	/**
	 * Get a header of current response
	 *
	 * @param string $name Name or null to get all inside an array
	 * @return string|array
	 */
	public function getHeader($name = null)
	{
		if ($name)
		{
			return isset($this->_headers[$name]) ? $this->_headers[$name] : null;
		}
		else
		{
			return $this->_headers;
		}
	}

	/**
	 * Set the content to be sent to client
	 *
	 * @param string
	 * @return \Core\Request $this
	 */
	public function setBody($value)
	{
		$this->body = $value;
		return $this;
	}

	/**
	 * Get the current content to be sent to client
	 *
	 * @return string
	 */
	public function getBody()
	{
		return $this->body;
	}

	/**
	 * If casted to string, get the current body
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->getBody();
	}

	/**
	 * Send all headers of the request
	 *
	 * @return bool
	 * @throws RequestException
	 */
	protected function _sendHeaders()
	{
		if ( ! headers_sent($file, $line))
		{
			// Trigger the events before send headers
			Event::trigger('before_send_headers');

			// Send the cookies
			foreach ($this->_cookies as $cookie)
			{
				setcookie(
					$cookie['name'], $cookie['value'], $cookie['expire'], $cookie['path'],
					$cookie['domain'], $cookie['secure'], $cookie['http_only']
				);
			}

			// Send the response code
			http_response_code($this->status);

			// Set the content-header and charset
			if ( ! in_array($this->status, [304, 204]))
			{
				$content_type = Config::get('mimes.' . $this->contentType, $this->contentType);
				if (strpos($content_type, 'text/') === 0) {
					$this->_headers['Content-Type'] = $content_type . '; charset=' . $this->charset;
				} else {
					$this->_headers['Content-Type'] = $content_type;
				}
			}

			// Send the cache-control directives
			$control = '';
			foreach ($this->_cacheDirectives as $key => $val) {
				$control .= $val === true ? $key : sprintf('%s=%s', $key, $val);
				$control .= ', ';
			}
			$control = rtrim($control, ', ');
			$this->_headers['Cache-Control'] = $control;

			// Send the headers
			foreach ($this->_headers as $name => $value)
			{
				// Parse non-replace headers
				is_int($name) and is_array($value) and list($name, $value) = $value;

				// Create the header
				is_string($name) and $value = "{$name}: {$value}";

				// Send it
				header($value, true);
			}
			return true;
		}

		throw new RequestException('Headers already sent in file ' . $file . ' @ line: ' . $line);
	}

	/**
	 * Send the output html to the client
	 */
	protected function _sendBody()
	{
		Event::trigger('before_send_body');
		echo $this->body;
	}

	/**
	 * Send all the content to the browser
	 *
	 * @param bool $body Whether to send the body output or not
	 */
	public function send($body = true)
	{
		// Disallow any kind of echo'es outside the Request body
		do {
			ob_get_clean();
		} while (ob_get_level());

		$this->_sendHeaders();
		$body === true and $this->_sendBody();
	}
}