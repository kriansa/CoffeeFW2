<?php

namespace Core\View;

class Helper
{
	/**
	 * The View instance which called the helper
	 * @var \Core\View
	 */
	protected $_view = null;

	/**
	 * Constructor
	 *
	 * @param \Core\View $view
	 * @return void
	 */
	public function __construct(\Core\View $view)
	{
		$this->_view = $view;
	}

	/**
	 * Create a URL for assets or anything based on the app Base URL
	 *
	 * @param string $name
	 * @return string
	 */
	public static function baseUrl($name = null)
	{
		return \Core\URL::getBase() . '/' . (empty($name) ? '' : $name);
	}

	/**
	 * Load a partial View, which default is loaded from the directory above
	 * from current view.
	 *
	 * E.g: If the current view is APPPATH/Views/User/list.phtml
	 * When you call a partial, it will be loaded from APPPATH/Views/<partial>.phtml
	 *
	 * @param string $view_name
	 * @param array|object $data
	 * @param bool $autoEscape
	 * @param string $base_path
	 * @return string
	 */
	public function partial($view_name, $data = null, $auto_escape = null, $base_path = null)
	{
		// The basepath of the current view is the folder within the View files
		// Eg: APPPATH/Views/  OR  APPPATH/Module/Financial/Views/
		// And should NOT include the Controller dir!

		$base_path === null and $base_path = $this->_view->getBaseDir();
		return \Core\View::make($view_name, $data, $auto_escape, $base_path)->render();
	}

	/**
	 * Create a link for anywhere
	 *
	 * E.g: $this->link('controller/action')
	 * $this->link('http://anotherwebsite.com/controller/action')
	 *
	 * @param string $link
	 * @return string
	 */
	public static function link($link = null)
	{
		return \Core\URL::create($link);
	}

	// int, string, text, float, preset, date, time, datetime, csrf
	// http://solarphp.com/apidoc/package.Solar_View_Helper_Form
	public static function createField($name, $value, $type = 'string', $size = 255)
	{

	}
}