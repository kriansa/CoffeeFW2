<?php

namespace Core\Asset;

class Instance
{
	/**
	 * The asset paths to be searched
	 * @var string
	 */
	protected $_assetPath = 'assets/';

	/**
	 * The sub-folders to be searched
	 * @var array
	 */
	protected $_pathFolders = array(
		'css' => 'css/',
		'js' => 'js/',
		'img' => 'img/',
	);

	/**
	 * The URL to be prepended to all assets
	 * @var string
	 */
	protected $_assetUrl = '/';

	/**
	 * Holds the groups of assets
	 * @var array
	 */
	protected $_assets = array();

	/**
	 * Prefix for generated output to provide proper indentation
	 * @var string
	 */
	protected $_indent = '';

	/**
	 * If true, directly renders the output of no group name is given
	 * @var bool
	 */
	protected $_autoRender = true;

	/**
	 * Where the LessCSS resources are stored
	 * @var string
	 */
	protected $_lessSourcePath = null;

	/**
	 * Array with all the instanced captured raw strings
	 * @var array
	 */
	protected $_raw = array();

	/**
	 * Whether there is a started raw handler
	 * @var bool
	 */
	protected $_rawStarted = false;

	/**
	 * Parse the config and initialize the object instance
	 *
	 * @return void
	 */
	public function __construct($config)
	{
		$this->setConfig($config);
	}

	/**
	 * Parse the config data
	 *
	 * @return void
	 */
	public function setConfig($config)
	{
		//global search path folders
		isset($config['css_dir']) and $this->_pathFolders['css'] = trim($config['css_dir'], '/') . '/';
		isset($config['js_dir']) and $this->_pathFolders['js'] = trim($config['js_dir'], '/') . '/';
		isset($config['img_dir']) and $this->_pathFolders['img'] = trim($config['img_dir'], '/') . '/';
		isset($config['path']) and $this->_assetPath = trim($config['path'], '/') . '/';
		isset($config['url']) and $this->_assetUrl = rtrim($config['url'], '/') . '/';
		isset($config['indent_with']) and $this->_indent = $config['indent_with'];
		isset($config['auto_render']) and $this->_autoRender = (bool) $config['auto_render'];
		isset($config['less_source_dir']) and $this->_lessSourcePath = rtrim($config['less_source_dir'], '/\\') . DS;
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
	public function render($raw = false, $only_type = null, array $assets = array())
	{
		$html = array();

		// Define what assets will be loaded: the ones in the list or a custom list of assets
		$assets = empty($assets) ? $this->_assets : $assets;

		foreach ($assets as $item)
		{
			$type = $item['type'];
			$filename = $item['file'];
			$attr = $item['attr'];

			if($only_type !== null and $only_type != $type)
			{
				continue;
			}

			// only do a file search if the asset is not a URI
			if ( ! preg_match('|^(\w+:)?//|', $filename))
			{
				// Get the full path
				$file = $this->_assetUrl . $this->_assetPath . $this->_pathFolders[$type] . $filename;
			}
			else
			{
				$file = $filename;
			}

			switch($type)
			{
				case 'css':
					$attr['type'] = 'text/css';

					if ($raw)
					{
						$html[] = \Core\Html::tag('style', $attr, '<!--' . PHP_EOL . file_get_contents($file) . PHP_EOL . '-->');
					}
					else
					{
						empty($attr['rel']) and $attr['rel'] = 'stylesheet';

						$attr['href'] = $file;
						$html[] = \Core\Html::tag('link', $attr);
					}
				break;

				case 'js':
					$attr['type'] = 'text/javascript';

					if ($raw)
					{
						$html[] = \Core\Html::tag('script', $attr, '/* <![CDATA[ */' . PHP_EOL . file_get_contents($file) . PHP_EOL . '/* ]]> */');
					}
					else
					{
						$attr['src'] = $file;
						$html[] = \Core\Html::tag('script', $attr, '');
					}
				break;
			}

		}

		if ( ! empty($this->_raw['style']))
		{
			$html[] = \Core\Html::tag('style', array('type' => 'text/css'), PHP_EOL . $this->_indent . '<!--' . PHP_EOL . $this->_indent . implode(PHP_EOL . $this->_indent, $this->_raw['style']) . PHP_EOL . $this->_indent . '-->' . PHP_EOL . $this->_indent);
		}

		if ( ! empty($this->_raw['script']))
		{
			$html[] = \Core\Html::tag('script', array('type' => 'text/javascript'), PHP_EOL . $this->_indent . '/* <![CDATA[ */' . PHP_EOL . $this->_indent . implode(PHP_EOL . $this->_indent, $this->_raw['script']) . PHP_EOL . $this->_indent . '/* ]]> */' . PHP_EOL . $this->_indent);
		}

		return trim(implode(PHP_EOL . $this->_indent, $html));
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
	public function css($stylesheets = array(), $attr = array(), $render = null, $raw = false)
	{
		$assets = $this->_parseAssets('css', $stylesheets, $attr, $render);

		if ($this->_autoRender and $render !== false)
		{
			return $this->render($raw, 'css', (array) $assets);
		}

		return $this;
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
	public function less($stylesheets = array(), $attr = array(), $render = null, $raw = false)
	{
		if ( ! is_array($stylesheets))
		{
			$stylesheets = array($stylesheets);
		}

		foreach($stylesheets as &$lessfile)
		{
			$source_less = $this->_lessSourcePath . $lessfile;

			if( ! is_file($source_less))
			{
				throw new \Core\AssetException('Could not find less source file: ' . $source_less);
			}

			// Change the name for loading with $this->css
			$lessfile = str_replace('.' . pathinfo($lessfile, PATHINFO_EXTENSION), '', $lessfile) . '.css';

			// Full path to css compiled file
			$compiled_css = APPPATH . 'Public' . DS . $this->_assetPath . $this->_pathFolders['css'] . $lessfile;

			// Compile only if source is newer than compiled file
			if ( ! is_file($compiled_css) or filemtime($source_less) > filemtime($compiled_css))
			{
				require_once COREPATH . 'Vendor' . DS . 'lessphp' . DS . 'lessc.inc.php';

				$compile_path = dirname($compiled_css);
				$handle = new \lessc($source_less);
				$handle->indentChar = \Core\Config::get('asset.indent_with');

				if ( ! is_dir($compile_path) and ! mkdir($compile_path, 0775, true))
				{
					throw new \Core\AssetException('Base dir could not be created!');
				}

				\Core\File::create($compile_path, pathinfo($compiled_css, PATHINFO_BASENAME), $handle->parse(), true);
			}
		}

		return $this->css($stylesheets, $attr, $render, $raw);
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
	public function js($scripts = array(), $attr = array(), $render = null, $raw = false)
	{
		$assets = $this->_parseAssets('js', $scripts, $attr, $render);

		if ($this->_autoRender and $render !== false)
		{
			return $this->render($raw, 'js', (array) $assets);
		}

		return $this;
	}

	/**
	 * Either adds the image to the group, or returns the image tag.
	 *
	 * @param string The file name, or an array files.
	 * @param array An array of extra attributes
	 * @return string|object Rendered asset or current instance when adding to group
	 */
	public function img($image, array $attr = array())
	{
		// only do a file search if the asset is not a URI
		if ( ! preg_match('|^(\w+:)?//|', $image))
		{
			// Get the full path
			$file = $this->_assetUrl . $this->_assetPath . $this->_pathFolders['img'] . $image;
		}
		else
		{
			$file = $image;
		}

		return \Core\Html::img($file, $attr);
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
	public function getFile($file, $folder)
	{
		return $this->_assetUrl . $this->_assetPath . $this->_pathFolders[$folder] . $file;
	}

	/**
	 * Start render capturing for JS or CSS code
	 *
	 * @return void
	 */
	public function captureStart()
	{
		if ($this->_rawStarted === false)
		{
			$this->_rawStarted = true;
			ob_start();
		}
	}

	/**
	 * Start render capturing for JS or CSS code
	 *
	 * @return void
	 */
	public function captureStop()
	{
		if ($this->_rawStarted === true)
		{
			$output = ob_get_clean();
			if (preg_match_all('#<(?P<type>script|style)[^>]*>(?P<script>.*)</(?:script|style)>#Uis', trim($output), $matches, PREG_SET_ORDER))
			{
				foreach ($matches as $match)
				{
					$type = strtolower($match['type']);
					$script = trim($match['script']);
					$this->_raw[$type][] = $script;
				}
			}
			$this->_rawStarted = false;
		}
	}

	/**
	 * Parses the assets and adds them to the group
	 *
	 * @param string The asset type
	 * @param mixed The file name, or an array files.
	 * @param array An array of extra attributes
	 * @param bool Whether to return or add to the assets list
	 * @return string
	 */
	protected function _parseAssets($type, $assets, array $attr, $return = false)
	{
		if ( ! is_array($assets))
		{
			$assets = array($assets);
		}

		if ($return === true)
		{
			$parsed = array();
		}

		foreach ($assets as $asset)
		{
			$formatted_assets = array(
				'type'	=>	$type,
				'file'	=>	$asset,
				'attr'	=>	(array) $attr
			);

			if ($return === true)
			{
				$parsed[] = $formatted_assets;
			}
			else
			{
				$this->_assets[] = $formatted_assets;
			}
		}

		if ($return === true)
		{
			return $parsed;
		}
	}
}