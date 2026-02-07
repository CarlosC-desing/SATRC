<?php
// 1. Carga de configuración
require_once __DIR__ . '/../../includes/db/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// 🚫 Evita que el navegador muestre páginas guardadas
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// 1. Verifica si el usuario está autenticado
if (!isset($_SESSION['usuario'])) {
    header("Location: " . BASE_URL . "public/index.php");
    exit();
}

// 2. SEGURIDAD EXTRA: Si por alguna razón el token se borró pero la sesión sigue activa, lo regeneramos
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
