<?php

// Start the output buffer
ob_start();

/**
 * Set some useful shortcuts constants
 */
define('DS', DIRECTORY_SEPARATOR);
define('MBSTRING', function_exists('mb_get_info'));
define('CRLF', "\r\n");

// Enable and disable error reporting inside environments
error_reporting(E_ALL);
ini_set('display_errors', (int) (ENVIRONMENT === 'development'));
ini_set('log_errors', 0);

// Use the super pre-cache
if (defined('USE_PRE_CACHE'))
{
	include APPPATH . 'data/cache/classes.cache';
	Core\Config::load(include APPPATH . 'data/cache/config.cache');
	Core\Loader::setCache(include APPPATH . 'data/cache/loader.cache');
}
else
{
	// Include the procedural helper
	include 'Helper.php';
	// Include the class loader
	include 'classes/Core/Loader.php';
}


// Register the autoloader
Core\Loader::register();

// Register all the error/shutdown handlers
register_shutdown_function('Core\\Event::trigger', 'shutdown');
register_shutdown_function('Core\\Error::shutdownHandler');
set_exception_handler('Core\\Error::exceptionHandler');
set_error_handler('Core\\Error::errorHandler');

// Default timezone
date_default_timezone_set(Core\Config::get('system.timezone'));

// Configure the mbstring
MBSTRING and mb_internal_encoding(Core\Config::get('system.encoding'));

// Set the system locales
// setlocale(LC_ALL, Core\Config::get('system.locale'));

// Profiler
Core\Profiler::sinceStart('System Bootstrap');