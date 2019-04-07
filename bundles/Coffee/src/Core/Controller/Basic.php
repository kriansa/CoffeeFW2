<?php

namespace Core\Controller;

abstract class Basic
{
	/**
	 * The current request-response object
	 * @var \Core\Request
	 */
	public $request = null;
	
	/**
	 * The params used in URL
	 * @var array 
	 */
	public $params = array();

	/**
	 * Constructor (used by dispatcher)
	 * 
	 * @param \Core\Request $request
	 * @param array $params
	 */
	public function __construct(\Core\Request $request, array $params = array())
	{
		$this->request = $request;
		$this->params = $params;
	}

	/**
	 * Method called before every action
	 * Don't confuse with _init()
	 * 
	 * - The _init() method is called once this class is loaded
	 * no matter if it will be instantied, so it's static
	 * 
	 * - The before() method is before the dispatcher call the
	 * action, and if it return false, then the action won't be
	 * executed, and the dispatcher will stop. Useful for ACL stuff.
	 * 
	 * @param string $actionName
	 * @return void|bool False to stop the dispatch
	 */
	// public function before($actionName = null) {}

	/**
	 * Called after every request in controller. It must return the
	 * body string OR a \Core\Request object
	 * 
	 * @param string $response
	 * @param string $actionName
	 * @return string 
	 */
	/*public function after($response = null, $actionName = null)
	{
		return $response;
	}*/
	
	/**
	 * Get the specified param, or all of them inside an array if null given
	 * 
	 * @param string $param
	 * @param mixed $default
	 * @return string|array 
	 */
	public function getParam($param = null, $default = null)
	{
		return $param === null ? $this->params : (isset($this->params[$param]) ? $this->params[$param] : $default);
	}

	/**
	 * Get the current Request object
	 * 
	 * @return \Core\Request
	 */
	public function getRequest()
	{
		return $this->request;
	}
}