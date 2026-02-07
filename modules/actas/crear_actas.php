<?php
// 1. Carga de configuración (sube dos niveles desde modules/actas/)
require_once '../../includes/db/config.php';

// 2. Inclusión de archivos usando ROOT_PATH para evitar errores de ruta
include ROOT_PATH . 'modules/login/verificar_sesion.php';
include ROOT_PATH . 'includes/db/conexion.php';
include ROOT_PATH . 'functions/registrar_log.php';

// Registro en bitácora
registrarLog($conn, $_SESSION['usuario'], "Actas", "Ingreso al módulo", "Accedió a crear_actas.php");

$titulo_pagina = "Generar Actas";

// Ajuste de ruta para componentes
include ROOT_PATH . 'includes/components/header.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/actas.css">

<main class="main">
    <div class="main__contenedor">
        <div class="contenedor__card card--nacimiento">
            <a href="<?= BASE_URL ?>modules/formularios/formulario_nacimiento.php" class="card__link">
                <img src="<?= BASE_URL ?>assets/img/SVG/ICONONAC.svg" alt="" class="item__icon">
                <span class="card__text">Registro de Nacimiento</span>
            </a>
        </div>

        <div class="contenedor__card card--matrimonio">
            <a href="<?= BASE_URL ?>modules/formularios/formulario_matrimonio.php" class="card__link">
                <img src="<?= BASE_URL ?>assets/img/SVG/ICONOMATRI.svg" alt="" class="item__icon">
                <span class="card__text">Matrimonio</span>
            </a>
        </div>

        <div class="contenedor__card card--union">
            <a href="<?= BASE_URL ?>modules/formularios/formulario_union.php" class="card__link">
                <img src="<?= BASE_URL ?>assets/img/SVG/ICONOUNIONESTAB.svg" alt="" class="item__icon">
                <span class="card__text">Unión Estable de Hecho</span>
            </a>
        </div>

        <div class="contenedor__card card--defuncion">
            <a href="<?= BASE_URL ?>modules/formularios/formulario_defuncion.php" class="card__link">
                <img src="<?= BASE_URL ?>assets/img/SVG/ICONODEFUN.svg" alt="" class="item__icon">
                <span class="card__text">Defunción</span>
            </a>
        </div>
    </div>
</main>
</body>
</html>