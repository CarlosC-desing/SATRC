<?php
// CORRECCIÓN DE RUTA: public/ está al mismo nivel que includes/
require_once '../includes/db/config.php';
include ROOT_PATH . 'modules/login/verificar_sesion.php';
include ROOT_PATH . 'includes/db/conexion.php';
include ROOT_PATH . 'functions/registrar_log.php';

header('Content-Type: text/html; charset=utf-8');

registrarLog($conn, $_SESSION['usuario'], "Menú Principal", "Acceso al sistema", "Usuario accedió al menú principal");

// Consulta de notificaciones para el badge
$sql_notif = "SELECT COUNT(*) AS total FROM solicitudes_actas WHERE estado = 'pendiente'";
$result_notif = $conn->query($sql_notif);
$row_notif = $result_notif ? $result_notif->fetch_assoc() : ['total' => 0];
$pendientes = $row_notif['total'];

$esAdmin = isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
$claseBitacora = $esAdmin ? '' : 'oculto';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Registro Civil - Menú</title>
    <link rel="stylesheet" href="<?= BASE_URL; ?>assets/css/menu_principal.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@100;400;700&display=swap" rel="stylesheet">
</head>

<body>
    <header class="header">
        <div class="header--izquierda">
            <img class="header__logo" src="<?= BASE_URL; ?>assets/img/SVG/ESCUDO_YARI.svg" alt="Escudo Yaritagua">
            <h1 class="header__title">Registro Civil de Peña</h1>
        </div>
        <div class="hedaer--derecha">
            <nav class="header__nav">
                <div class="nav__actions">
                    <?php if ($esAdmin): ?>
                        <a class="nav__linked--registrar" href="<?= BASE_URL; ?>modules/login/registrar_usuario.php">
                            <img class="header__svg" src="<?= BASE_URL ?>assets/img/usuario.png" alt="Inicio">
                            <span>Registrar Usuario</span>
                        </a>
                    <?php endif; ?>
                    <a class="nav__linked--cerrar" href="<?= BASE_URL; ?>modules/login/cerrar_sesion.php">
                        <img class="header__svg" src="<?= BASE_URL ?>assets/img/cerrar.png" alt="Inicio">
                        <span>Cerrar Sesión</span>
                    </a>
                </div>
            </nav>
        </div>
    </header>

    <main class="main">
        <h2 class="main__title">¡Bienvenido, <?= htmlspecialchars($_SESSION['usuario']); ?>!</h2>
        <div class="main__contenedor">
            <div class="main__menu">
                <a class="menu__item item-crear" href="<?= BASE_URL; ?>modules/actas/crear_actas.php">
                    <img src="<?= BASE_URL; ?>assets/img/SVG/ICONOC-ACTA.svg" alt="" class="item__icon">
                    <span>Crear Actas</span>
                </a>

                <a class="menu__item item-registrar" href="<?= BASE_URL; ?>modules/formularios/formulario_persona.php">
                    <img src="<?= BASE_URL; ?>assets/img/SVG/ICONOPERSONAS.svg" alt="" class="item__icon">
                    <span>Registrar Persona</span>
                </a>

                <a class="menu__item item-buscar" href="<?= BASE_URL; ?>modules/actas/buscar.php">
                    <img src="<?= BASE_URL; ?>assets/img/SVG/ICONOBUSCAR.svg" alt="" class="item__icon">
                    <span>Buscar</span>
                </a>

                <a class="menu__item item-reportes" href="<?= BASE_URL; ?>modules/reportes/generar_reportes.php">
                    <img src="<?= BASE_URL; ?>assets/img/SVG/ICONOREPORTE.svg" alt="" class="item__icon">
                    <span>Generar Reportes</span>
                </a>

                <a class="menu__item item-nueva-sol" href="<?= BASE_URL; ?>modules/solicitudes/solicitudes.php">
                    <img src="<?= BASE_URL; ?>assets/img/SVG/ICONONUEVASOLIC.svg" alt="" class="item__icon">
                    <span>Nueva Solicitud</span>
                </a>

                <a class="menu__item item-panel" href="<?= BASE_URL; ?>modules/solicitudes/panel_solicitudes.php">
                    <img src="<?= BASE_URL; ?>assets/img/SVG/ICONOPANELSOLIC.svg" alt="" class="item__icon">
                    <span>
                        Panel de Solicitudes
                        <?php if ($pendientes > 0): ?>
                            <span class="notificacion-numero"><?= $pendientes ?></span>
                        <?php endif; ?>
                    </span>
                </a>

                <a class="menu__item item-bitacora <?= $claseBitacora ?>" href="<?= BASE_URL; ?>modules/bitacora/ver_bitacora.php">
                    <img src="<?= BASE_URL; ?>assets/img/SVG/ICONOBITACORA.svg" alt="" class="item__icon">
                    <span>Ver Bitácora</span>
                </a>
            </div>
            <div class="main__dashboard">
                <?php include ROOT_PATH . 'public/dashboard_resumen.php'; ?>
            </div>
        </div>
    </main>
</body>

</html>