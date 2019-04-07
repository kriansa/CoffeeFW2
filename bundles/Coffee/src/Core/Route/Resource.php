<?php

namespace Core\Route;

class Resource implements RouteInterface
{
	/**
	 * All the domains allowed to reach the route
	 * @var string
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
	 * Namespace name
	 * @var string
	 */
	public $namespace = null;

	/**
	 * Base URL of the resource
	 * @var string
	 */
	public $base_name = null;

	/**
	 * Params of the resolved route
	 * @var array
	 */
	public $params = array();

	/**
	 * Constructor
	 *
	 * @param string $controller
	 * @param string $namespace
	 * @param array $domains The domains allowed the this route
	 */
	public function __construct($controller, $namespace = null, array $domains = null)
	{
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
		$this->namespace = $namespace;
		$this->controller = str_replace('.', '\\', $controller);
		$this->base_name = substr($this->controller, strrpos($this->controller, '\\') + 1);
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
		if (!empty($this->domains) and !empty($domain) and !$this->_matchDomain($domain))
		{
			return false;
		}

		// Clean the current action
		$this->action = null;

		// The URL beginning as this route should be
		$start_url = ($this->namespace ? $this->namespace . '/' : '') . $this->base_name;

		switch (strtoupper($method)) {
			case 'POST':
				if (!strcasecmp($url, $start_url)) {
					$this->action = 'create';
				}
				break;
			case 'GET':
				if (!strcasecmp($url, $start_url))
				{
					$this->action = 'index';
				}
				elseif (!strcasecmp($url, $start_url . '/new'))
				{
					$this->action = 'new';
				}
				elseif (preg_match('#^' . preg_quote($start_url) . '/(?P<param>[[:alnum:]-_]+)(?:/)?(?P<edit>edit)?$#i', $url, $matches))
				{
					if (isset($matches['edit']))
					{
						$this->action = 'edit';
					}
					else
					{
						$this->action = 'show';
					}
					$this->params = [$matches['param']];
				}
				break;
			case 'PUT':
				if (preg_match('#^' . preg_quote($start_url) . '/(?P<param>[[:alnum:]-_]+)$#i', $url, $matches))
				{
					$this->action = 'update';
					$this->params = [$matches['param']];
				}
				break;
			case 'DELETE':
				if (preg_match('#^' . preg_quote($start_url) . '/(?P<param>[[:alnum:]-_]+)$#i', $url, $matches))
				{
					$this->action = 'destroy';
					$this->params = [$matches['param']];
				}
		}

		if (!$this->action)
		{
			return false;
		}

		return $this;
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