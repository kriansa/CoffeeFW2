<?php

return array(
	'encoding' => 'UTF-8',
	'timezone' => 'America/Sao_Paulo',
	'locale' => strpos(PHP_OS, 'WIN') !== false ? 'ptb' : 'pt_BR.utf-8',
	'base_url' => '/',

	'security' => array(
		'uri_filter'       => array('htmlentities'),

		/**
		 * This input filter can be any normal PHP function as well as 'xss_clean'
		 *
		 * WARNING: Using xss_clean will cause a performance hit.  How much is
		 * dependant on how much input data there is.
		 */
		'input_filter'  => array(),

		/**
		 * This output filter can be any normal PHP function as well as 'xss_clean'
		 *
		 * WARNING: Using xss_clean will cause a performance hit.  How much is
		 * dependant on how much input data there is.
		 */
		'output_filter'  => array('Core\\Security::htmlentities'),

		/**
		 * Whether to automatically filter view data
		 */
		'auto_filter_output'  => true,

		/**
		 * With output encoding switched on all objects passed will be converted to strings or
		 * throw exceptions unless they are instances of the classes in this array.
		 */
		'whitelisted_classes' => array(
			'Core\\View',
			'Closure',
		)
	),

	'profiler' => array(

		/**
		 * Ativa ou desativa
		 */
		'enabled' => true,

		/**
		 * Quais recursos do profiler ativar/desativar
		 */
		'resources' => array(
			Core\Profiler::MARK => true,
			Core\Profiler::MARK_MEMORY => true,
			Core\Profiler::DB_BENCHMARK => true,
			Core\Profiler::APP_STATS => true,
		),
	),

	'cookie' => array(
		'expire' => 0,
		'path' => '/',
		'domain' => null,
		'secure' => false,
		'http_only' => false,
	),

	// How many errors should we show before we stop showing them? (prevents out-of-memory errors)
	'error_throttling' => 10,

	// Default chmod value for new created folders
	'file_folders_chmod' => 0777,

	'session' => array(
		// Define whether the system will autoload the default session driver when Session is called
		'autoload' => true,

		// Storage adapter to use with Session
		'driver' => array(
			// Available drivers: cache, cookie
			'name' => 'cache',

			// Used only for cache driver
			'adapter' => 'session',
		),

		// Lifetime of cookie from the last activity - in minutes
		'lifetime' => 120,

		// Expire session when the browser closes
		'expire_on_close' => false,

		// Match IP address - Prevent cookie stealing
		'match_ip' => true,

		// Match user agent - Prevent cookie stealing
		'match_user_agent' => true,

		// Cookie config
		'cookie' => array(
			'name' => 'sess_id',
			'path' => '/',
			'domain' => null,
			'secure' => false,
			'http_only' => false,
		),

	),

	'router' => array(

		/**
		 * Whether to use the dynamic router mode, which
		 * will translate all routes to Controller/Action/Args
		 * when no matching route in Config/Routes.php is found
		 */
		'dynamic_mode' => true,

		/**
		 * Whether to use the Module/Controller/Action/Args
		 * To reach the routes
		 *
		 * If false, the system will use the traditional route
		 * Controller/Action/Args
		 */
		'use_modules' => false,

		/**
		 * If you're using modules, define which module is the default
		 * when none set. Define as if you're putting int the URL
		 *
		 * Eg: main, web-site
		 *
		 * Warning: Be careful with the names you give to your modules or controller
		 * - If your default module has a controller called Main and you has
		 * - a module also called Main, you'll never be able to reach the
		 * - controller on the default module
		 *
		 */
		'default_module' => 'Main',
	),

	// Crypt Key
	'crypt_key' => '4YtBhA9Dn975FKcFjYtW27q2IsREfUd7',
);