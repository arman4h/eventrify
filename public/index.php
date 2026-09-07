<?php

require_once __DIR__ . '/../app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = trim($requestUri, '/');

$routes = [
    ''                               => '/pages/landing/index.php',
    'home'                           => '/pages/landing/index.php',
    'event'                          => '/pages/landing/event.php',

    'login'                          => '/pages/auth/login.php',
    'register'                       => '/pages/auth/register.php',
    'logout'                         => '/pages/auth/logout.php',

    // Dashboard A (Admin / Users Management)
    'admin'                          => '/pages/dashboard-a/index.php',
    'admin/users'                    => '/pages/dashboard-a/users/index.php',
    'admin/users/create'             => '/pages/dashboard-a/users/create.php',
    'admin/users/edit'               => '/pages/dashboard-a/users/edit.php',
    'admin/users/delete'             => '/pages/dashboard-a/users/delete.php',
    'admin/reports'                  => '/pages/dashboard-a/reports/index.php',
    'admin/settings'                 => '/pages/dashboard-a/settings/index.php',

    // Dashboard B (Club / Events Management)
    'club'                           => '/pages/dashboard-b/index.php',
    'club/events'                    => '/pages/dashboard-b/events/index.php',
    'club/events/create'             => '/pages/dashboard-b/events/create.php',
    'club/events/edit'               => '/pages/dashboard-b/events/edit.php',
    'club/events/delete'             => '/pages/dashboard-b/events/delete.php',
    'club/tasks'                     => '/pages/dashboard-b/tasks/index.php',
    'club/settings'                  => '/pages/dashboard-b/settings/index.php',
];

if (array_key_exists($uri, $routes)) {
    require BASE_PATH . $routes[$uri];
} else {
    redirect('/');
}