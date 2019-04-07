<?php

// Get the start timers for profiler
define('APP_START_TIME', microtime(true));
define('APP_START_MEM', memory_get_usage());

/**
 * Configure the local of the base files
 */
define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('PUBLIC_PATH', __DIR__ . DIRECTORY_SEPARATOR);

/**
 * Sets the current environment [development, staging, production, test, cli]
 */
define('ENVIRONMENT', (isset($_SERVER['ENVIRONMENT']) ? $_SERVER['ENVIRONMENT'] : 'production'));

/**
 * Use the global pre-cache to load all required classes
 * and config files from a single file. Remember to generate
 * the cache using the Coffee GUI Tool
 */
ENVIRONMENT === 'production' and define('USE_PRE_CACHE', true);

// Bootstrap the system
include COREPATH . 'Bootstrap.php';

(new Coffee\Application(require 'configs/application.config.php'))->run();