<?php

namespace App\Controller;
use Core\View, Core\Asset, Core\URL, Core\Config, Core\Inflector;

class Error extends \Core\Controller\Complete
{
	/**
	 * Disable for all the controller
	 * @var string
	 */
	public $theme = null;

	/**
	 * The default 404 not found action
	 *
	 * @return void
	 */
	public function error404Action()
	{
		$method = '_notFound' . ENVIRONMENT;

		// Try to call the internal method for the environment
		if (method_exists($this, $method))
		{
			return $this->$method();
		}

		// If the method for the current environment does not exist
		// We just output the 404 production view :)
		$this->setViewFileName('Error/404-production');
	}

	/**
	 * The method to show not found pages in production mode
	 *
	 * @return void
	 */
	protected function _notFoundProduction()
	{
		Asset::css('bootstrap.min.css');
		Asset::css('bootstrap-responsive.min.css');
		Asset::js('jquery-1.7.1.min.js');
		Asset::js('bootstrap.min.js');

		$this->layout->title = 'Page not found';
		$this->setViewFileName('Error/404-production');
		$this->cache();
	}

	/**
	 * The method to show not found pages in development mode
	 *
	 * @return void
	 */
	protected function _notFoundDevelopment()
	{
		Asset::css('bootstrap.min.css');
		Asset::css('bootstrap-responsive.min.css');
		Asset::css('google-code-prettify/prettify.css');
		Asset::js('jquery-1.7.1.min.js');
		Asset::js('bootstrap.min.js');
		Asset::js('google-code-prettify/prettify.js');


		// Decode the url string
		$url = URL::translate(URL::getRequestString());

		$controller = $url['controller'];
		$filename = APPPATH . 'Lib' . DS . APPNAME . ( ! empty($url['module']) ? DS . 'Module' . DS . $url['module'] : '') . DS . 'Controller' . DS . $url['controller'] . '.php';
		$namespace = APPNAME . ( ! empty($url['module']) ? '\\Module\\' . $url['module'] : '');
		$action = $url['action'];

		if ( ! is_file($filename))
		{
			$message = 'Controller not found!';
			$comment = 'Please create you controller!';
			$action = 'index';
		}
		else
		{
			$message = 'Action not found!';
			$comment = 'Your controller doesn\'t have this action!';
		}

		$code = "<?php

namespace {$namespace};

class Controller_{$controller} extends \\Core\\Controller\\Complete
{
	public function {$action}Action(array \$params)
	{
		// Your code
	}
}";

		$this->setViewFileName('Error/404-development');
		$this->layout->title = 'Page not found';
		$this->view->code = $code;
		$this->view->message = $message;
		$this->view->file = \Core\Debug::cleanPath($filename);
		$this->view->comment = $comment;
	}
}