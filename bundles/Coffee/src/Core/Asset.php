<?php

namespace Core;

class AssetException extends \Exception {}

class Asset
{

	/**
	 * Default instance
	 * @var \Core\Asset\Instance
	 */
	protected static $_instance = null;

	/**
	 * All the Asset instances
	 * @var array
	 */
	protected static $_instances = array();

	/**
	 * Default configuration values
	 * @var array
	 */
	protected static $_defaultConfig = array(
		'path' => 'assets',
		'img_dir' => 'img',
		'js_dir' => 'js',
		'css_dir' => 'css',
		'url' => '/',
		'indent_with' => "\t",
		'auto_render' => true,
		'less_source_dir' => null,
	);

	/**
	 * Gets a new instance of the Asset class.
	 *
	 * @param string Instance name
	 * @param array $config Default config overrides
	 * @return \Core\Asset\Instance
	 */
	public static function make($name = 'default', array $config = array())
	{
		if ($exists = static::getInstance($name))
		{
			trigger_error('Asset with this name exists already, cannot be overwritten.');
			return $exists;
		}

		static::$_instances[$name] = new Asset\Instance(array_merge(static::$_defaultConfig, Config::get('asset'), $config));

		if ($name == 'default')
		{
			static::$_instance = static::$_instances[$name];
		}

		return static::$_instances[$name];
	}

	/**
	 * Return a specific instance, or the default instance (is created if necessary)
	 *
	 * @param string Instance name
	 * @return \Core\Asset\Instance
	 */
	public static function getInstance($instance = null)
	{
		if ($instance !== null)
		{
			if ( ! array_key_exists($instance, static::$_instances))
			{
				return false;
			}

			return static::$_instances[$instance];
		}

		if (static::$_instance === null)
		{
			static::$_instance = static::make();
		}

		return static::$_instance;
	}

	/**
	 * Parse the config data
	 *
	 * @return void
	 */
	public static function setConfig($config)
	{
		static::getInstance()->setConfig($config);
	}

	/**
	 * Renders the given group.  Each tag will be separated by a line break.
	 * You can optionally tell it to render the files raw.  This means that
	 * all CSS and JS files in the group will be read and the contents included
	 * in the returning value.
	 *
	 * @param mixed The group to render
	 * @param bool Whether to return the raw file or not
	 * @param string Type of assets to render. Null to render all of them
	 * @param array If this param is set, its assets will be loaded instead the instance ones
	 * @return string The group's output
	 */
	public static function render($raw = false, $only_type = null, array $assets = array())
	{
		return static::getInstance()->render($raw, $only_type, $assets);
	}

	/**
	 * Either adds the stylesheet to the group, or returns the CSS tag.
	 *
	 * @param mixed The file name, or an array files.
	 * @param array An array of extra attributes
	 * @param bool Whether to render automatically or add to the list
	 * @param bool Whether to be rendered inside the page instead linked
	 * @return string|object Rendered asset or current instance when adding to group
	 */
	public static function css($stylesheets = array(), $attr = array(), $render = null, $raw = false)
	{
		return static::getInstance()->css($stylesheets, $attr, $render, $raw);
	}

	/**
	 * Compile a Less file and load it as a CSS asset.
	 *
	 * @param mixed The file name, or an array files.
	 * @param array An array of extra attributes
	 * @param bool Whether to render automatically or add to the list
	 * @param bool Whether to be rendered inside the page instead linked
	 * @return string|object Rendered asset or current instance when adding to group
	 */
	public static function less($stylesheets = array(), $attr = array(), $render = null, $raw = false)
	{
		return static::getInstance()->less($stylesheets, $attr, $render, $raw);
	}

	/**
	 * Either adds the javascript to the group, or returns the script tag.
	 *
	 * @param mixed The file name, or an array files.
	 * @param array An array of extra attributes
	 * @param bool If true, the asset will be rendered automatically
	 * @param bool If true, the JS will be rendered on the page instead linked
	 * @return string|object Rendered asset or current instance when adding to group
	 */
	public static function js($scripts = array(), $attr = array(), $render = null, $raw = false)
	{
		return static::getInstance()->js($scripts, $attr, $render, $raw);
	}

	/**
	 * Either adds the image to the group, or returns the image tag.
	 *
	 * @param string The file name, or an array files.
	 * @param array An array of extra attributes
	 * @return string|object Rendered asset or current instance when adding to group
	 */
	public static function img($image, array $attr = array())
	{
		return static::getInstance()->img($image, $attr);
	}

	/**
	 * Get the absolute filename from its folder, no html tags
	 *
	 * <code>
	 *		Asset::getFile('test.js', 'js'); // <baseurl>/assets/js/test.js
	 * </code>
	 *
	 * @param string $file
	 * @param string $folder
	 * @return string
	 */
	public static function getFile($file, $folder)
	{
		return static::getInstance()->getFile($file, $folder);
	}

	/**
	 * Start render capturing for JS or CSS code
	 *
	 * @return void
	 */
	public static function captureStart()
	{
		return static::getInstance()->captureStart();
	}

	/**
	 * Start render capturing for JS or CSS code
	 *
	 * @return void
	 */
	public static function captureStop()
	{
		return static::getInstance()->captureStop();
	}
}