<?php

use Core\Route\Route;
use Core\Route\RouteNamespace;

/**
 * All the routes for the application
 */
return array(
	// Example routes

    new RouteNamespace('module', [
        new Route('App.Controller.Index', 'index', 'eu-amo-voce[-_/](:any)?'),
        new Route('App.Controller.Index', 'quemVoceAma', 'eu-amo{/}(:any)?'),
    ]),

    new Route('App.Controller.Index', 'index', ''),
	new Route('App.Controller.Index', 'index', 'eu-amo-voce[-_/](:any)?'),
	new Route('App.Controller.Index', 'quemVoceAma', 'eu-amo{/}(:any)?'),

	// System routes
	'error_404' => new Route('App.Controller.Error', 'error404'),
);