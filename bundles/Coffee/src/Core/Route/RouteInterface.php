<?php

namespace Core\Route;

interface RouteInterface
{
	/**
	 * Check if the route matches with the URL and Method given
	 *
	 * @param string $url
	 * @param string $method [GET, POST, PUT, DELETE, ALL]
	 * @param string $domain
	 * @return \Core\Route\RouteInterface|bool False if don't match
	 */
	public function match($url, $method = 'ALL', $domain = null);

	/**
	 * Set the route params
	 *
	 * @param array $params
	 * @return \Core\Route\Route
	 */
	public function setParams(array $params);

	/**
	 * Get the route params
	 *
	 * @return array
	 */
	public function getParams();

	/**
	 * Get the Route controller class name
	 *
	 * @return string
	 */
	public function getController();

	/**
	 * Get the Route action method name
	 *
	 * @return string
	 */
	public function getAction();

	/**
	 * Return the param from resolved route, or get all if none given
	 *
	 * @param int $param
	 * @return string|array
	 */
	public function getParam($param = null);
}