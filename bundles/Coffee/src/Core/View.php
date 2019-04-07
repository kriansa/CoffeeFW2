<?php

namespace Core;

class ViewException extends \Exception {}

class View
{
	/**
	 * Store all the view data
	 * @var array
	 */
	protected $_data = array();

	/**
	 * Store the global data
	 * @var \stdClass
	 */
	protected static $_globalData = null;

	/**
	 * Local instanced shorcut pointer to global data
	 * @var array
	 */
	protected $global = null;

	/**
	 * The file name of the View
	 * @var string
	 */
	protected $_file = null;

	/**
	 * The basepath where is stored the View file
	 * @var type
	 */
	protected $_basePath = null;

	/**
	 * Set the auto escaping of the variables
	 * @var bool
	 */
	protected $_autoEscape = true;

	/**
	 * The default extension of view files
	 * @var string
	 */
	protected static $_extension = '.phtml';

	/**
	 * The view helper instance
	 * @var \Core\View\Helper
	 */
	protected $_helper = null;

	/**
	 * Start the global data array
	 *
	 * @return void
	 */
	public static function _init()
	{
		static::$_globalData = new \ArrayObject(array(), \ArrayObject::ARRAY_AS_PROPS);
	}

	/**
	 * Constructor method. Create the View instance and set the params
	 *
	 * @param string $file
	 * @param array|object $data
	 * @param bool $auto_escape
	 * @param string $base_path
	 * @throws ViewException
	 */
	public function __construct($file = null, $data = null, $auto_escape = null, $base_path = null)
	{
		$auto_escape === null and $auto_escape = Config::get('system.security.auto_filter_output', true);
		$this->_autoEscape = $auto_escape;
		$this->setBaseDir($base_path);
		$file !== null and $this->setFileName($file);

		// Set the instance $global var to the static $_globalData var
		$this->global = & static::$_globalData;

		if (is_array($data) or is_object($data))
		{
			foreach ($data as $name => $value)
			{
				$this->set($name, $value);
			}
		}
	}

	/**
	 * Create a instance using factory pattern
	 *
	 * @param string $file
	 * @param array|object $data
	 * @param bool $auto_escape
	 * @param string $base_path
	 * @return \Core\View
	 */
	public static function make($file = null, $data = null, $auto_escape = null, $base_path = null)
	{
		return new static($file, $data, $auto_escape, $base_path);
	}

	/**
	 * Set the basedir of View file
	 *
	 * Eg: APPPATH/Views/  OR  APPPATH/Module/Financial/Views/  OR   APPPATH/Views/Theme/Default/
	 * And should NOT include the Controller dir!
	 *
	 * @param string $base_path
	 * @return \Core\View
	 * @throws ViewException
	 */
	public function setBaseDir($base_path = null)
	{
		empty($base_path) and $base_path = APPPATH . 'Views' . DS;

		if ( ! is_dir($base_path))
		{
			throw new ViewException('View base dir "' . $base_path . '" not found!');
		}

		$this->_basePath = $base_path;

		return $this;
	}

	/**
	 * Get the current basedir of View file
	 *
	 * @return string
	 */
	public function getBaseDir()
	{
		return $this->_basePath;
	}

	/**
	 * Set the filename of the View to render (without extension)
	 *
	 * Must include the Controller directory, if exists
	 *
	 * @param string $file
	 * @return \Core\View $this
	 */
	public function setFileName($file)
	{
		$this->_file = $file . static::$_extension;

		return $this;
	}

	/**
	 * Get the current View filename
	 *
	 * @return string
	 */
	public function getFileName()
	{
		return $this->_file;
	}

	/**
	 * Render the View
	 *
	 * @return string
	 * @throws Exception
	 */
	public function render()
	{
		// Make sure the file var won't be overwritten
		$___file___ = rtrim($this->_basePath, '\\/') . DS . $this->_file;

		if ( ! is_file($___file___))
		{
			throw new ViewException('View file "' . Debug::cleanPath($___file___) . '" not found!');
		}

		ob_start();

		try
		{
			// Extract the content local view data
			extract($this->_data);

			// Extract the content of global view data into $global_ prefix
			extract((array) static::$_globalData, EXTR_PREFIX_ALL, 'global');

			// Load the view within the current scope
			include $___file___;

			// Get the included view content
			return ob_get_clean();
		}
		catch (\Exception $exception)
		{
			// Delete the output buffer
			ob_end_clean();

			// Re-throw the exception
			throw $exception;
		}
	}

	/**
	 * Handle helper calls
	 *
	 * @param string $method
	 * @param array $arguments
	 * @return mixed
	 */
	public function __call($method, array $arguments)
	{
		if ( ! $this->_helper)
		{
			$this->_helper = new View\Helper($this);
		}

		if (method_exists($this->_helper, $method))
		{
			return call_user_func_array(array($this->_helper, $method), $arguments);
		}

		throw new ViewException('Helper method "' . $method . '" not found!');
	}

	/**
	 * Get a single variable for the template.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function getGlobal($name)
	{
		if( ! isset(static::$_globalData[$name]))
		{
			$backtrace = debug_backtrace();
			Error::errorHandler(E_NOTICE, 'Undefined view global variable: ' . $name, $backtrace[0]['file'], $backtrace[0]['line']);
			return null;
		}

		return isset(static::$_globalData[$name]) ? static::$_globalData[$name] : null;
	}

	/**
	 * Set a global variable for the template.
	 *
	 * @param string $name
	 * @param mixed $value
	 * @param bool $escape
	 * @return \Core\View $this
	 */
	public function setGlobal($name, $value, $escape = null)
	{
		if(strcasecmp($name, 'this')  === 0)
		{
			throw new ViewException('Please, do not set a view variable called "this" !');
		}

		$escape === null and $escape = $this->_autoEscape;
		static::$_globalData[$name] = $escape ? Security::clean(value($value), Config::get('system.security.output_filter', array())) : value($value);
		return $this;
	}

	/**
	 * Get a single variable for the template.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function get($name)
	{
		if( ! isset($this->_data[$name]))
		{
			$backtrace = debug_backtrace();
			Error::errorHandler(E_NOTICE, 'Undefined view variable: ' . $name, $backtrace[0]['file'], $backtrace[0]['line']);
			return null;
		}

		return isset($this->_data[$name]) ? $this->_data[$name] : null;
	}

	/**
	 * Set a single variable for the template.
	 *
	 * @param string $name
	 * @param mixed $value
	 * @param bool $escape
	 * @return \Core\View $this
	 */
	public function set($name, $value, $escape = null)
	{
		if(strcasecmp($name, 'this')  === 0)
		{
			throw new ViewException('Please, do not set a view variable called "this" !');
		}

		$escape === null and $escape = $this->_autoEscape;
		$this->_data[$name] = $escape ? Security::clean(value($value), Config::get('system.security.output_filter', array())) : value($value);
		return $this;
	}

	/**
	 * Magic function to dynamicaly set variables of the View
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value)
	{
		$this->set($name, $value);
	}

	/**
	 * Magic function to get variables of the View
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name)
	{
		if( ! isset($this->_data[$name]))
		{
			$backtrace = debug_backtrace();
			Error::errorHandler(E_NOTICE, 'Undefined view variable: ' . $name, $backtrace[0]['file'], $backtrace[0]['line']);
			return null;
		}

		return $this->_data[$name];
	}

	/**
	 * Magic function to check if a View variable is set
	 *
	 * @param string $name
	 * @return bool
	 */
	public function __isset($name)
	{
		return isset($this->_data[$name]);
	}

	/**
	 * Magic function to unset the View variables
	 *
	 * @param string $name
	 */
	public function __unset($name)
	{
		unset($this->_data[$name]);
	}

	/**
	 * Render the template when casting to string
	 *
	 * @return string
	 */
	public function __toString()
	{
		// Avoid throwing exceptions in __toString() (not allowed by PHP)
		try
		{
			return $this->render();
		}
		catch(\Exception $exception)
		{
			Error::exceptionHandler($exception);
		}
	}
}