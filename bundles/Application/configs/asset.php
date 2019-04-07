<?php

return array(

	/**
	 * URL to your Coffee root dir. Typically this will be your base URL:
	 *
	 * Core\URL::getBase()
	 */
	'url' => Core\URL::getBase(),

	/**
	 * An array of paths that will be searched for assets. Each path is a
	 * RELATIVE path from the speficied url:
	 *
	 * 'assets/'
	 *
	 * Paths specified here are suffixed with the sub-folder paths defined below.
	 */
	'path' => 'assets',

	/**
	 * Asset Sub-folders
	 *
	 * Names for the img, js and css folders (inside the asset search path).
	 *
	 * Examples:
	 *
	 * img/
	 * js/
	 * css/
	 */
	'img_dir' => 'img',
	'js_dir' => 'js',
	'css_dir' => 'css',

	/**
	* What to use for indenting.
	*/
	'indent_with' => "\t\t",

	/**
	 * What to do when an asset method is called without a group name. If true, it will
	 * return the generated asset tag. If false, it will add it to the default group.
	 */
	'auto_render' => true,

	/**
	 * The path of your LessCSS source files.
	 * Remember: All the Less files are compiled in CSS output dir
	 * If you use a external server to store your assets, your
	 * compiled assets will not work.
	 *
	 */
	'less_source_dir' => APPPATH . 'Data/LessCSS',
);
