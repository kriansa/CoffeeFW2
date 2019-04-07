<?php

namespace Core\Route;

class Route implements RouteInterface
{
	/**
	 * Methods used by this route
	 * @var array
	 */
	public $methods = array();

	/**
	 * Regex pattern to reach this route
	 * @var string
	 */
	public $pattern = null;

	/**
	 * All the domains allowed to reach the route
	 * @var type
	 */
	public $domains = array();

	/**
	 * Controller name
	 * @var string
	 */
	public $controller = null;

	/**
	 * Action name
	 * @var string
	 */
	public $action = null;

	/**
	 * Params of the resolved route
	 * @var array
	 */
	public $params = array();

	/**
	 * Constructor
	 *
	 * @param string $controller
	 * @param string $action
	 * @param string $pattern Regex URL to reach the route
	 * @param array $methods [GET, POST, DELETE, PUT, ALL]
	 * @param array $domains The domains allowed the this route
	 */
	public function __construct($controller, $action = null, $pattern = null, array $methods = array('ALL'), array $domains = null)
	{
		if ( ! empty($methods))
		{
			foreach($methods as &$method)
			{
				$method = strtoupper($method);
			}
		}

		if ($pattern !== null)
		{
			// Faz um tratamento na pattern, permitindo atalhos fáceis
			$pattern = '#^' . str_replace(array(
				':any',
				':string',
				':num',
				':letters',
				':segment',
				'{/}',
			), array(
				'.+',
				'[[:alnum:]]+',
				'[[:digit:]]+',
				'[[:alpha:]]+',
				'[^/]+',
				'(?:/)',
			), $pattern) . '$#iu';
		}


		if( ! empty($domains))
		{
			// Do a glob inside the domain pattern
			foreach ($domains as &$domain)
			{
				$domain = '#^' . strtr(preg_quote($domain, '#'), array(
					'*' => '[^.]*',
					'.' => '\\.',
				)) . '#iu';
			}
		}

		// Set the internal vars
		$this->controller = str_replace('.', '\\', $controller);
		$this->methods = $methods;
		$this->pattern = $pattern;
		$this->action = $action;
		$this->domains = $domains;
	}

	/**
	 * Check if the route matches with the URL and Method given
	 *
	 * @param string $url
	 * @param string $method [GET, POST, PUT, DELETE, ALL]
	 * @param string $domain
	 * @return \Core\Route\Route|bool False if don't match
	 */
	public function match($url, $method = 'ALL', $domain = null)
	{
		if (
				(in_array('ALL', $this->methods) or in_array($method, $this->methods))
				and ($domain === null or empty($this->domains) or ($domain !== null and ! empty($this->domains) and $this->_matchDomain($domain)))
				and ! empty($this->pattern) and preg_match($this->pattern, $url, $matches)
		)
		{
			array_shift($matches);
			$this->params = $matches;
			return $this;
		}

		return false;
	}

	/**
	 * Match a domain a domain against the route domain list
	 *
	 * @param string $input
	 * @return bool
	 */
	protected function _matchDomain($input)
	{
		foreach ($this->domains as $domain)
		{
			if (preg_match($domain, $input))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Set the route params
	 *
	 * @param array $params
	 * @return \Core\Route\Route
	 */
	public function setParams(array $params)
	{
		$this->params = $params;
		return $this;
	}

	/**
	 * Get the route params
	 *
	 * @return array
	 */
	public function getParams()
	{
		return $this->params;
	}

	/**
	 * Get the Route controller class name
	 *
	 * @return string
	 */
	public function getController()
	{
		return $this->controller;
	}

	/**
	 * Get the Route action method name
	 *
	 * @return string
	 */
	public function getAction()
	{
		return $this->action . 'Action';
	}

	/**
	 * Return the param from resolved route, or get all if none given
	 *
	 * @param int $param
	 * @return string|array
	 */
	public function getParam($param = null)
	{
		if ($param === null or ! isset($this->params[$param]))
			return $this->params;

		return $this->params[$param];
	}
}