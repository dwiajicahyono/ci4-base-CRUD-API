<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// otomatis
$routes->resource('api', ['controller' => 'PegawaiController']);
// bisa manual
// $routes->get('api', 'PegawaiController::index');
// $routes->get('api/(:num)', 'PegawaiController::show/$1');
// $routes->post('api', 'PegawaiController::create');
$routes->post('api/update/(:num)', 'PegawaiController::update/$1');
// $routes->delete('api/(:num)', 'PegawaiController::delete/$1');
