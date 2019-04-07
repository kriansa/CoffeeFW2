<?php

use Core\Route\Route as RouteResource;

/**
 * All the routes for the application
 */
return array(
	// System routes
	'mocha' => new RouteResource('Mocha.Controller.Mocha', ''),
);