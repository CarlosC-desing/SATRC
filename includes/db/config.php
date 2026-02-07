<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Credenciales
define('DB_HOST', 'localhost');
define('DB_USER', 'civil_user');
define('DB_PASS', 'RCP2026#.rcp');
define('DB_NAME', 'registro_civil_2');

// Rutas (Ajustadas según tu estructura de carpetas)
define('ROOT_PATH', realpath(__DIR__ . '/../../') . '/');
define('BASE_URL', 'http://localhost/Registro_civil_ordenado/');
