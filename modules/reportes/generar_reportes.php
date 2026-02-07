<?php
require_once '../../includes/db/config.php';
include ROOT_PATH . 'modules/login/verificar_sesion.php';
include ROOT_PATH . 'includes/db/conexion.php';

// Captura de variables
$modo = $_GET['modo'] ?? 'general';
$desde = $_GET['desde'] ?? date('Y-01-01');
$hasta = $_GET['hasta'] ?? date('Y-12-31');
$tipos_sel = $_GET['tipo'] ?? [];

// Validación de seguridad
$modos_validos = ['general', 'fecha', 'registro', 'nacimiento', 'matrimonio', 'defuncion', 'union'];
if (!in_array($modo, $modos_validos)) {
    $modo = 'general';
}

$titulo_pagina = "Reportes - Registro Civil";
include ROOT_PATH . 'includes/components/header.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/reportes.css">

<div class="report-layout">
    <?php include ROOT_PATH . 'includes/components/sidebar_generar_reportes.php'; ?>

    <main class="content-area">
        <div class="container-fluid">
            <?php
            // Sistema de enrutamiento simple para las tablas
            $reportes = [
                'general'    => 'reporte_general.php',
                'fecha'      => 'reporte_fecha.php',
                'registro'   => 'reporte_registro.php',
                'nacimiento' => 'reporte_nacimiento.php',
                'matrimonio' => 'reporte_matrimonio.php',
                'defuncion'  => 'reporte_defuncion.php',
                'union'      => 'reporte_union.php'
            ];

            $archivo_a_incluir = $reportes[$modo] ?? 'reporte_general.php';

            // Verificamos que el archivo exista antes de incluirlo
            if (file_exists(__DIR__ . '/' . $archivo_a_incluir)) {
                include __DIR__ . '/' . $archivo_a_incluir;
            } else {
                echo "<div class='card-result'><h3>Error: Archivo de reporte no encontrado ($archivo_a_incluir)</h3></div>";
            }
            ?>
        </div>
    </main>
</div>
</body>

</html>