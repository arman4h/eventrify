<?php

require_once __DIR__ . '/app.php';

$host = env('DB_HOST', 'localhost');
$port = (int) env('DB_PORT', 3306);
$user = env('DB_USER', 'root');
$password = env('DB_PASSWORD', '');
$database = env('DB_NAME', 'eventrify');

if (!extension_loaded('mysqli')) {
    die("The mysqli PHP extension is not enabled. Enable it in php.ini (XAMPP has it on by default).");
}

try {
    $db = new mysqli($host, $user, $password, $database, $port);
} catch (mysqli_sql_exception $e) {
    die("Can't connect to MySQL at $host:$port — is XAMPP MySQL running? (error: " . $e->getMessage() . ")");
}

if ($db->connect_error) {
    die("Database connection failed: " . $db->connect_error . " (check your .env / start MySQL in XAMPP)");
}

$db->set_charset('utf8mb4');

function db(): mysqli
{
    global $db;
    return $db;
}

/**
 * Run a prepared statement, reporting failure as false instead of throwing.
 *
 * mysqli is in exception mode, so a bare `$stmt->execute()` aborts the request
 * with a 500 on any constraint violation. Callers that already branch on the
 * result should use this so the user sees their "please try again" message.
 */
function dbExec(mysqli_stmt $stmt): bool
{
    try {
        return $stmt->execute();
    } catch (mysqli_sql_exception $e) {
        error_log('SQL execute failed: ' . $e->getMessage());

        return false;
    }
}

/**
 * Turn a database failure into something worth showing a user.
 *
 * Error 2006/2013 means the server closed the connection — usually because a
 * packet exceeded max_allowed_packet, or the server restarted. Nothing was
 * written, and any query that follows on the same handle will fail too, so the
 * page must not keep going as if the save worked.
 */
function dbErrorMessage(mysqli_sql_exception $e, string $fallback): string
{
    if ($e->getCode() === 2006 || $e->getCode() === 2013) {
        return 'The database connection was dropped, so nothing was saved. Please try again.';
    }

    return $fallback;
}
