<?php

require_once __DIR__ . '/../app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = trim($requestUri, '/');

$routes = [
    ''                               => '/pages/landing/home.php',
    'home'                           => '/pages/landing/home.php',
    'events'                         => '/pages/landing/index.php',
    'event'                          => '/pages/landing/event.php',

    'login'                          => '/pages/auth/login.php',
    'register-student'               => '/pages/auth/register-student.php',
    'register-club'                  => '/pages/auth/register-club.php',
    'logout'                         => '/pages/auth/logout.php',

    // Admin auth
    'admin/login'                    => '/pages/auth/admin-login.php',

    'admin'                          => '/pages/dashboard-a/index.php',

   
    'admin/applications'             => '/pages/dashboard-a/applications/index.php',

    'admin/reports'                  => '/pages/dashboard-a/reports/index.php',

    'admin/settings'                 => '/pages/dashboard-a/settings/index.php',


    'club'                           => '/pages/dashboard-b/index.php',
    'club/events'                    => '/pages/dashboard-b/events/index.php',
    'club/events/create'             => '/pages/dashboard-b/events/create.php',
    'club/events/edit'               => '/pages/dashboard-b/events/edit.php',
    'club/events/delete'             => '/pages/dashboard-b/events/delete.php',
    'club/tasks'                     => '/pages/dashboard-b/tasks/index.php',
    'club/settings'                  => '/pages/dashboard-b/settings/index.php',
];

if (array_key_exists($uri, $routes)) {
    // Guard admin dashboard routes: must be a logged-in system admin
    $isAdminRoute = str_starts_with($uri, 'admin') && $uri !== 'admin/login';
    if ($isAdminRoute && !isSystemAdmin()) {
        redirect('/admin/login');
    }

    require BASE_PATH . $routes[$uri];
} else {
    redirect('/');
}