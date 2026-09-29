<?php

// ── Static file passthrough ───────────────────────────────────────────
// Started as `php -S localhost:8000 -t public public/index.php`, PHP's
// built-in server routes *every* request through this file, including
// requests for files that really do exist under public/ (the stylesheet, the
// script, club logos). Returning false tells the server to serve the file
// itself, which is both correct and much faster.
//
// Must run before app.php: booting the app here would open a database
// connection and start a session for every asset request.
//
// Only files inside public/ are eligible, and .php is always excluded so an
// unrouted PHP file in the document root can never be executed directly.
if (PHP_SAPI === 'cli-server') {
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $requestPath = is_string($requestPath) ? rawurldecode($requestPath) : '/';

    if ($requestPath !== '/' && !str_contains($requestPath, "\0")) {
        $candidate = realpath(__DIR__ . '/' . ltrim($requestPath, '/'));
        $insideDocRoot = $candidate !== false
            && str_starts_with($candidate, __DIR__ . DIRECTORY_SEPARATOR);

        if ($insideDocRoot && is_file($candidate) && !str_ends_with(strtolower($candidate), '.php')) {
            return false;
        }
    }
}

require_once __DIR__ . '/../app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = trim($requestUri, '/');

$routes = [
    // ── Public / Landing ──────────────────────
    ''                               => '/pages/landing/home.php',
    'home'                           => '/pages/landing/home.php',
    'events'                         => '/pages/landing/explore.php',
    'event'                          => '/pages/landing/event.php',
    'about'                          => '/pages/landing/about.php',
    'help'                           => '/pages/landing/help.php',

    // ── Auth (students) ───────────────────────
    'login'                          => '/pages/auth/login.php',
    'register-student'               => '/pages/auth/register-student.php',
    'logout'                         => '/pages/auth/logout.php',

    // ── Auth (clubs) ──────────────────────────
    'club/login'                     => '/pages/auth/club-login.php',
    'club/register'                  => '/pages/auth/register-club.php',
    'club/access-request'            => '/pages/auth/register-club.php',

    // ── Auth (admin) ──────────────────────────
    'admin/login'                    => '/pages/auth/admin-login.php',

    // ── Student dashboard ─────────────────────
    'student'                        => '/pages/dashboard-s/index.php',
    'student/profile'                => '/pages/dashboard-s/profile.php',
    'student/profile/edit'           => '/pages/dashboard-s/edit-profile.php',
    'student/registrations'          => '/pages/dashboard-s/registrations.php',
    'student/reports'               => '/pages/dashboard-s/reports.php',
    'student/report'                => '/pages/dashboard-s/report.php',

    // ── Admin dashboard ───────────────────────
    'admin'                          => '/pages/dashboard-a/index.php',
    'admin/club-requests'            => '/pages/dashboard-a/club-requests/index.php',
    'admin/club-requests/review'     => '/pages/dashboard-a/club-requests/review.php',
    'admin/clubs'                    => '/pages/dashboard-a/clubs/index.php',
    'admin/events'                   => '/pages/dashboard-a/events/index.php',
    'admin/events/delete'            => '/pages/dashboard-a/events/delete.php',
    'admin/events/history'           => '/pages/dashboard-a/events/history.php',
    'admin/users'                    => '/pages/dashboard-a/users/index.php',
    'admin/room-requests'            => '/pages/dashboard-a/room-requests/index.php',
    'admin/reports'                  => '/pages/dashboard-a/reports/index.php',
    'admin/settings'                 => '/pages/dashboard-a/settings/index.php',

    // ── Club dashboard ────────────────────────
    'club'                           => '/pages/dashboard-b/index.php',
    'club/events'                    => '/pages/dashboard-b/events/index.php',
    'club/events/create'             => '/pages/dashboard-b/events/create.php',
    'club/events/edit'               => '/pages/dashboard-b/events/edit.php',
    'club/events/manage'             => '/pages/dashboard-b/events/manage.php',
    'club/events/delete'             => '/pages/dashboard-b/events/delete.php',
    'club/registrations'             => '/pages/dashboard-b/registrations/index.php',
    'club/attendance'                => '/pages/dashboard-b/attendance/index.php',
    'club/attendance/walkin'         => '/pages/dashboard-b/attendance/walkin.php',
    'club/members'                   => '/pages/dashboard-b/members/index.php',
    'club/room-requests'             => '/pages/dashboard-b/room-requests/index.php',
    'club/reports'                   => '/pages/dashboard-b/reports/index.php',
    'club/settings'                  => '/pages/dashboard-b/settings/index.php',
];

if (array_key_exists($uri, $routes)) {
    // Every state-changing request must carry this session's CSRF token.
    verifyCsrf();

    // Guard admin dashboard routes: must be a logged-in system admin
    $isAdminRoute = str_starts_with($uri, 'admin') && $uri !== 'admin/login';
    if ($isAdminRoute && !isSystemAdmin()) {
        redirect('/admin/login');
    }

    require BASE_PATH . $routes[$uri];
} else {
    require BASE_PATH . '/pages/landing/not-found.php';
}