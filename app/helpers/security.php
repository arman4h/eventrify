<?php

/**
 * Cross-site request forgery (CSRF) protection, session hardening and
 * login throttling.
 *
 * Every state-changing form in the application posts a per-session token.
 * `verifyCsrf()` is called once per request by the front controller, so a
 * forged or stale token is rejected before any page logic runs.
 */

const CSRF_SESSION_KEY    = 'csrf_token';
const CSRF_FIELD_NAME     = 'csrf_token';
const CSRF_FAILURE_STATUS = 419;

/**
 * Return (creating if needed) the CSRF token bound to this session.
 */
function csrfToken(): string
{
    if (empty($_SESSION[CSRF_SESSION_KEY]) || !is_string($_SESSION[CSRF_SESSION_KEY])) {
        $_SESSION[CSRF_SESSION_KEY] = bin2hex(random_bytes(32));
    }

    return $_SESSION[CSRF_SESSION_KEY];
}

/**
 * Rotates the CSRF token. Called after any privilege change (login/logout)
 * so a token captured before authentication cannot be replayed afterwards.
 */
function rotateCsrfToken(): string
{
    $_SESSION[CSRF_SESSION_KEY] = bin2hex(random_bytes(32));
    return $_SESSION[CSRF_SESSION_KEY];
}

/**
 * Hidden input to drop inside every POST form.
 */
function csrfField(): string
{
    return '<input type="hidden" name="' . CSRF_FIELD_NAME . '" value="' . e(csrfToken()) . '">';
}

/**
 * Read the token out of a request body (POST or JSON-ish fallback).
 */
function submittedCsrfToken(): string
{
    $token = $_POST[CSRF_FIELD_NAME] ?? '';
    if ($token === '' || !is_string($token)) {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }

    return is_string($token) ? $token : '';
}

function csrfTokenIsValid(): bool
{
    $expected = $_SESSION[CSRF_SESSION_KEY] ?? '';
    $given    = submittedCsrfToken();

    if (!is_string($expected) || $expected === '' || $given === '') {
        return false;
    }

    return hash_equals($expected, $given);
}

/**
 * Reject any POST whose token is missing or wrong. Terminates the request.
 */
function verifyCsrf(): void
{
    if (!isPost()) {
        return;
    }

    if (csrfTokenIsValid()) {
        return;
    }

    renderCsrfFailure();
}

function renderCsrfFailure(): void
{
    http_response_code(CSRF_FAILURE_STATUS);

    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
    }

    $e = 'e';

    echo <<<HTML
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Session expired &middot; Eventrify</title>
    <link rel="stylesheet" href="{$e(BASE_URL)}/assets/css/app.css">
</head>
<body class="min-h-full flex items-center justify-center p-6">
    <div class="card w-full max-w-md p-8 text-center">
        <span class="inline-flex w-14 h-14 rounded-full bg-amber-50 text-amber-600 items-center justify-center mb-5">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.74-3L13.74 4a2 2 0 00-3.48 0l-7 12a2 2 0 001.74 3z"/></svg>
        </span>
        <h1 class="text-xl font-bold text-gray-900">Session expired</h1>
        <p class="text-sm text-gray-500 mt-2 leading-relaxed">
            For your security this form was rejected because the security token was missing or has expired.
            This usually means the page sat open too long, or it was opened in a second tab.
        </p>
        <div class="mt-6 flex flex-col sm:flex-row gap-3 justify-center">
            <a href="{$e(BASE_URL)}" class="btn-primary">Go to Eventrify</a>
            <button type="button" class="btn-secondary" onclick="history.back()">Go back</button>
        </div>
    </div>
</body>
</html>
HTML;

    exit;
}

/**
 * Issue a new session id while keeping session data. Used on every
 * privilege change to defeat session fixation.
 */
function regenerateSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

/**
 * Progressive login throttle. State lives in the session, so it needs no
 * extra table, and it resets naturally when the session is dropped.
 *
 * @return array{count:int, wait:int, locked:bool}
 */
function loginThrottleState(string $bucket): array
{
    $window = 300; // attempts older than this no longer count

    $state = $_SESSION['login_throttle'][$bucket] ?? null;
    if (!is_array($state) || !isset($state['count'], $state['first'])) {
        $state = ['count' => 0, 'first' => time()];
    }

    if ((time() - (int) $state['first']) > $window) {
        $state = ['count' => 0, 'first' => time()];
    }

    $count = (int) $state['count'];
    $wait  = 0;
    if ($count >= 5) {
        $wait = min(60, ($count - 4) * 4);
    }

    $_SESSION['login_throttle'][$bucket] = $state;

    return ['count' => $count, 'wait' => $wait, 'locked' => $wait > 0];
}

function loginThrottleRemaining(string $bucket): int
{
    return (int) loginThrottleState($bucket)['wait'];
}

function recordLoginFailure(string $bucket): void
{
    loginThrottleState($bucket);
    $state = $_SESSION['login_throttle'][$bucket];
    $state['count'] = (int) $state['count'] + 1;
    $state['last']  = time();
    $_SESSION['login_throttle'][$bucket] = $state;
}

function clearLoginFailures(string $bucket): void
{
    unset($_SESSION['login_throttle'][$bucket]);
}

/**
 * Enforce the throttle before credentials are checked. Sleeps for the
 * remaining backoff window, then returns a human-readable reason.
 */
function enforceLoginThrottle(string $bucket, string $label): ?string
{
    $wait = loginThrottleRemaining($bucket);
    if ($wait <= 0) {
        return null;
    }

    sleep($wait);

    return "Too many failed {$label} attempts. Please wait {$wait} seconds and try again.";
}

/**
 * Block the same account/IP combination from being spammed with a fresh
 * session by throttling on the identity being tried as well as the session.
 */
function throttleBucketFor(string $identity): string
{
    $identity = strtolower(trim($identity));
    $ip       = $_SERVER['REMOTE_ADDR'] ?? 'cli';

    return 'auth:' . substr(hash('sha256', $identity . '|' . $ip), 0, 32);
}
