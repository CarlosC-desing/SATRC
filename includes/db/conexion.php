<?php
// Al estar en la misma carpeta, solo necesitamos el nombre del archivo
require_once __DIR__ . '/config.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    error_log("Error de conexión: " . $e->getMessage());
    die("Error técnico. Por favor, intente más tarde.");
}
