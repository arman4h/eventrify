<?php

/**
 * Log out: drop all session data, expire the cookie with the same attributes
 * it was set with, and rotate the CSRF token for whatever session follows.
 *
 * This is a GET because it is reached from plain links in the sidebars. A
 * state change on GET is only acceptable here because logging out is
 * idempotent and can never grant access to anything.
 */

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 42000,
        'path'     => $params['path'],
        'domain'   => $params['domain'],
        'secure'   => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?? 'Lax',
    ]);
}

if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

// Start a fresh session purely so the "you have been signed out" flash has
// somewhere to live.
session_start();
$_SESSION['flash']['success'] = 'You have been signed out.';

redirect('/');
