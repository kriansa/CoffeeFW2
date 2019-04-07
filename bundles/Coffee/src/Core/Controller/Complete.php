<?php

namespace Core\Controller;

abstract class Complete
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
	 * Define if the controller will automagically escape its data to the view
	 * @var bool
	 */
	public $autoEscape = null;

	/**
	 * Enable the auto view rendering
	 * @var bool
	 */
	public $autoViewRendering = true;

	/**
	 * The current View object
	 * @var \Core\View
	 */
	public $view = null;

	/**
	 * The current View file name
	 * @var string
	 */
	public $viewFileName = null;

	/**
	 * The path to the View (Default: APPPATH/View)
	 * @var string
	 */
	public $viewBasePath = null;

	/**
	 * Current theme name
	 * @var string
	 */
	public $theme = 'Default';

	/**
	 * Current asset instance
	 * @var \Core\Asset
	 */
	public $asset = null;

	/**
	 * Current layout View instance
	 * @var \Core\View
	 */
	public $layout = null;

	/**
	 * Current layout filename
	 * @var string
	 */
	public $layoutName = 'layout';

	/**
	 * The variable inside the layout which will hold the data of the view
	 * @var string
	 */
	public $layoutInnerContentVar = 'content';

	/**
	 * Define if the controller cache system is enabled
	 * @var bool
	 */
	public $cacheEnabled = false;

	/**
	 * Define if the current action will be cached or not
	 * @var bool
	 */
	public $toCache = false;

	/**
	 * How many minutes this action will be cached
	 * @var int
	 */
	public $cacheMinutes = 60;

	/**
	 * Which cache adapter the system will use
	 * @var string
	 */
	public $cacheAdapter = 'controller';

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
		$this->view = \Core\View::make(null, null, $this->autoEscape);
		$this->layout = \Core\View::make(null, null, $this->autoEscape);
		$this->asset = \Core\Asset::make('theme');
	}

	/**
	 * Load the View name
	 *
	 * @param string $actionName
	 * @return mixed
	 */
	public function before($actionName = null)
	{
		// If cache is enabled, lets check if there's something cached for this request
		if ($this->cacheEnabled)
		{
			$identifier = sha1(__CLASS__ . '::' . $actionName . '(' . serialize($this->params) . ')');

			// Looks like we do
			if (\Core\Cache::getInstance($this->cacheAdapter)->has($identifier))
			{
				$response = \Core\Cache::getInstance($this->cacheAdapter)->get($identifier);
				return $response;
			}
		}

		// Get the path of the current controller
		// E.g: App\Module\Controller\Financial would be resolved to
		// APPPATH/View/Module/Financial/View/index
		list($path, $controller_name) = explode('::' . DS,
			strtr(
				get_called_class(),
				array(
					APPNAME . '\\' => '',
					'Controller\\' => '::' . DS,
					'\\' => DS,
				)
			)
		);

		// Set the view folder and filenames based on which module/controller/action we are
		$this->viewBasePath = APPPATH . 'Views' . DS . $path;
		$this->viewFileName = $controller_name . DS . \Core\Inflector::camelCaseToDashes(substr($actionName, 0, -6));
	}

	/**
	 * Called after every request in controller. It must return the
	 * body string OR a \Core\Request object
	 *
	 * @param array|string $response
	 * @param string $actionName
	 * @return string
	 */
	public function after($response = null, $actionName = null)
	{
		// Enable the auto view rendering
		if ($this->autoViewRendering)
		{
			// If somehow the response return is an array, it will merge overriding the view data
			if(is_array($response))
			{
				foreach($response as $key => $value)
				{
					$this->view->set($key, $value);
				}
			}

			// If none theme is set, disable it and load the view files from View root folder
			$theme = $this->theme ? 'Theme' . DS . $this->theme . DS : '';

			// Always re-set the View filename to the theme subfolder
			$this->view->setBaseDir($this->viewBasePath . $theme);
			$this->view->setFileName($this->viewFileName);
			// Only re-set the asset path if there's a theme defined
			if ($theme)
			{
				$this->asset->setConfig(array(
					'path' => trim(\Core\Config::get('asset.path'), '/') . '/theme/' . strtolower($this->theme) . '/'
				));
			}

			// Get the view rendered in a string
			$response = $this->view->render();

			// If we're dealing with layout, wrap this content inside the layout
			if($this->layoutName !== null)
			{
				$this->layout->setBaseDir($this->viewBasePath . $theme);

				// First we set the layout name only if it wasn't set before
				if ( ! $this->layout->getFileName())
				{
					$this->layout->setFileName($this->layoutName);
				}

				// As we expect the content view to be HTML, so we turn off the autoEscape here
				$response = $this->layout->set($this->layoutInnerContentVar, $response, false)->render();
			}
		}

		if($this->cacheEnabled and $this->toCache)
		{
			// Lets put the body inside the response
			$this->response->body = $response;
			$identifier = sha1(__CLASS__ . '::' . $actionName . '(' . serialize($this->params) . ')');
			\Core\Cache::getInstance($this->cacheAdapter)->set($identifier, $this->response, $this->cacheMinutes);
		}

		return $response;
	}

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

	/**
	 * Set the flag to cache this response
	 *
	 * @param int $minutes
	 * @param string $adapter
	 */
	public function cache($minutes = null, $adapter = null)
	{
		$this->toCache = true;
		$minutes and $this->cacheMinutes = $minutes;
		$adapter and $this->cacheAdapter = $adapter;
	}

	/**
	 * Clear all the cache from this controller adapter
	 */
	public function clearCache()
	{
		\Core\Cache::getInstance($this->cacheAdapter)->dropAll();
	}

	/**
	 * Set the current theme name
	 *
	 * @param string $name
	 * @return \Core\Controller\Complete $this
	 */
	public function setTheme($name)
	{
		$this->theme = $name;
		return $this;
	}

	/**
	 * Disable the theme loading and load the views from root path
	 *
	 * @return \Core\Controller\Complete $this
	 */
	public function disableTheme()
	{
		$this->theme = null;
		return $this;
	}

	/**
	 * Set the current layout filename, relative to viewBasePath
	 *
	 * @param string $name
	 * @return \Core\Controller\Complete $this
	 */
	public function setLayout($name)
	{
		$this->layoutName = $name;
		return $this;
	}

	/**
	 * Disable the layout rendering
	 *
	 * @return \Core\Controller\Complete $this
	 */
	public function disableLayout()
	{
		$this->layoutName = null;
		return $this;
	}

	/**
	 * Set the basepath for the views
	 *
	 * @param string $name
	 * @return \Core\Controller\Complete $this
	 */
	public function setViewBasePath($name)
	{
		$this->viewBasePath = $name;
		return $this;
	}

	/**
	 * Set the view FileName inside the View basepath
	 *
	 * <code>
	 *		$this->setViewFileName('Controller/action');
	 * </code>
	 *
	 * @param string $name
	 * @return \Core\Controller\Complete $this
	 */
	public function setViewFileName($name)
	{
		$this->viewFileName = $name;
		return $this;
	}

	/**
	 * Enable or disable the auto view rendering
	 *
	 * @return \Core\Controller\Complete $this
	 */
	public function setAutoRender($state)
	{
		$this->autoViewRendering = $state;
		return $this;
	}
}