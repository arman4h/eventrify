<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/redirect.php';

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function old(string $key, $default = ''): ?string
{
    return $_SESSION['old'][$key] ?? $default;
}

function setOld(array $data): void
{
    $_SESSION['old'] = $data;
}

function flash(string $key, ?string $value = null)
{
    if ($value !== null) {
        $_SESSION['flash'][$key] = $value;
        return null;
    }

    if (isset($_SESSION['flash'][$key])) {
        $value = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $value;
    }

    return null;
}

function clearOld(): void
{
    unset($_SESSION['old']);
}

function isPost(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function isGet(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

function post(string $key, $default = ''): string
{
    return trim($_POST[$key] ?? $default);
}

function get(string $key, $default = ''): string
{
    return trim($_GET[$key] ?? $default);
}

function abort(int $code = 404, string $message = 'Not Found'): void
{
    http_response_code($code);
    echo "<h1>$code - $message</h1>";
    exit;
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function formatDate(string $date, string $format = 'M d, Y'): string
{
    return date($format, strtotime($date));
}

function requireAuth(): void
{
    if (!isLoggedIn()) {
        redirect('/login');
    }
}

function requireStudent(): void
{
    requireAuth();
    if (!isStudent()) {
        redirect('/');
    }
}

function requireClubUser(): void
{
    requireAuth();
    if (!isClubUser()) {
        redirect('/');
    }
}

function requireSystemAdmin(): void
{
    requireAuth();
    if (!isSystemAdmin()) {
        redirect('/admin/login');
    }
}

function requireAdmin(): void
{
    requireSystemAdmin();
}
