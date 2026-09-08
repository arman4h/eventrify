<?php

define('BASE_PATH', dirname(__DIR__, 2));

define('APP_NAME', 'Eventrify');
define('APP_URL', 'http://localhost:8000');
define('APP_ENV', 'development');

define('BASE_URL', APP_URL);

session_start();

require_once BASE_PATH . '/app/config/bootstrap.php';
