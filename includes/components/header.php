<?php

require_once __DIR__ . '/../db/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$esAdmin = isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title><?= $titulo_pagina ?? 'Registro Civil' ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/estilos.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/actas.css">
</head>

<body>
    <header class="header">
        <div class="header--izquierda">
            <img class="header__logo" src="<?= BASE_URL ?>assets/img/SVG/ESCUDO_YARI.svg" alt="Escudo Yaritagua">
            <h1 class="header__title">Registro Civil de Peña</h1>
        </div>
        <div class="header--derecha">
            <nav class="header__nav">
                <div class="nav__actions">
                    <a class="nav__link" href="<?= BASE_URL ?>public/menu_principal.php">
                        <img class="nav__icon" src="<?= BASE_URL ?>assets/img/inicio.png" alt="Inicio">
                        <span>Inicio</span>
                    </a>
                    <a class="nav__link" href="<?= BASE_URL ?>modules/actas/crear_actas.php">
                        <img class="nav__icon" src="<?= BASE_URL ?>assets/img/crearacta.png" alt="Crear acta">
                        <span>Crear acta</span>
                    </a>
                    <a class="nav__link" href="<?= BASE_URL ?>modules/actas/buscar.php">
                        <img class="nav__icon" src="<?= BASE_URL ?>assets/img/buscar.png" alt="Buscar">
                        <span>Buscar</span>
                    </a>
                    <a class="nav__link" href="<?= BASE_URL ?>modules/reportes/generar_reportes.php">
                        <img class="nav__icon" src="<?= BASE_URL ?>assets/img/reporte.png" alt="Reporte">
                        <span>Reporte</span>
                    </a>
                    <a class="nav__link" href="<?= BASE_URL ?>modules/solicitudes/solicitudes.php">
                        <img class="nav__icon" src="<?= BASE_URL ?>assets/img/crearsoli.png" alt="Crear solicitud">
                        <span>Crear solicitud</span>
                    </a>
                    <a class="nav__link" href="<?= BASE_URL ?>modules/solicitudes/panel_solicitudes.php">
                        <img class="nav__icon" src="<?= BASE_URL ?>assets/img/cersolici.png" alt="Solicitudes">
                        <span>Solicitudes</span>
                    </a>
                    <a class="nav__link" href="<?= BASE_URL ?>modules/login/cerrar_sesion.php">
                        <img class="nav__icon" src="<?= BASE_URL ?>assets/img/cerrar.png" alt="Salir">
                        <span>Salir</span>
                    </a>
                </div>
            </nav>
        </div>
    </header>