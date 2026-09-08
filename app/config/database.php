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
