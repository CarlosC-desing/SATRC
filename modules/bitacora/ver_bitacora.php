<?php
header('Content-Type: text/html; charset=utf-8');
require_once '../../includes/db/config.php';
include ROOT_PATH . 'modules/login/verificar_sesion.php';
include ROOT_PATH . 'includes/db/conexion.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    die("🔒 Acceso restringido.");
}

// IMPORTANTE: Estos valores deben ser IDÉNTICOS a lo que hay en la columna 'modulo' de tu BD.
$modulos_disponibles = ['Personas', 'Solicitudes', 'Nacimiento', 'Matrimonio', 'Defunción', 'Unión Estable', 'Expediente'];

// Configuración de Paginación
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = 50;
$inicio = ($pagina - 1) * $por_pagina;

// Lógica del Filtro
$condicion = '';
$params_filtro = [];
$types = "";

// Verificamos si hay módulos seleccionados
if (!empty($_GET['modulo']) && is_array($_GET['modulo'])) {
    // Saneamiento básico del array
    $filtros_limpios = array_map('strip_tags', $_GET['modulo']);

    // Creamos los placeholders (?,?,?) dinámicamente
    $placeholders = implode(',', array_fill(0, count($filtros_limpios), '?'));
    $condicion = "WHERE modulo IN ($placeholders)";

    $params_filtro = $filtros_limpios;
    $types = str_repeat("s", count($params_filtro));
}

// 1. Consulta Principal
$sql = "SELECT * FROM historial_cambios $condicion ORDER BY fecha DESC LIMIT ?, ?";
$stmt = $conn->prepare($sql);

if (!empty($params_filtro)) {
    // Unimos los filtros con los límites de paginación
    $final_params = array_merge($params_filtro, [$inicio, $por_pagina]);
    $stmt->bind_param($types . "ii", ...$final_params);
} else {
    $stmt->bind_param("ii", $inicio, $por_pagina);
}

$stmt->execute();
$result = $stmt->get_result();

// 2. Consulta de Conteo (Total de páginas)
$sql_total = "SELECT COUNT(*) AS total FROM historial_cambios $condicion";
$stmt_t = $conn->prepare($sql_total);
if (!empty($params_filtro)) {
    $stmt_t->bind_param($types, ...$params_filtro);
}
$stmt_t->execute();
$total_res = $stmt_t->get_result();
$total = $total_res ? $total_res->fetch_assoc()['total'] : 0;
$total_pag = ceil($total / $por_pagina);

// Generar Query String para mantener los filtros en los links de paginación
// Si no hay filtros, esto devuelve cadena vacía
$query_string_filtros = '';
if (!empty($_GET['modulo'])) {
    $query_string_filtros = '&' . http_build_query(['modulo' => $_GET['modulo']]);
}

$titulo_pagina = "Bitácora - Registro Civil";
include ROOT_PATH . 'includes/components/header.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/bitacora.css">

<main class="main-registro">
    <div class="contenedor-bitacora">
        <h2 class="main-registro__title">Historial de Cambios</h2>

        <form method="GET" action="ver_bitacora.php" class="form-filtro">
            <fieldset class="form-filtro__fieldset">
                <legend class="form-filtro__legend">Filtrar por módulo:</legend>
                <div class="form-filtro__checkbox-group">
                    <?php foreach ($modulos_disponibles as $mod): ?>
                        <label class="form-filtro__label">
                            <input type="checkbox" name="modulo[]" value="<?= htmlspecialchars($mod) ?>"
                                <?= (isset($_GET['modulo']) && in_array($mod, $_GET['modulo'])) ? 'checked' : '' ?>>
                            <span><?= htmlspecialchars($mod) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>
            <button type="submit" class="form-filtro__btn">Aplicar filtros</button>

            <?php if (!empty($_GET['modulo'])): ?>
                <a href="ver_bitacora.php" class="form-solicitud__btn-back" style="font-size: 12px; margin-left: 10px;">Limpiar</a>
            <?php endif; ?>
        </form>

        <div class="tabla-responsive">
            <table class="tabla-bitacora">
                <thead>
                    <tr>
                        <th class="tabla-bitacora__th">Fecha</th>
                        <th class="tabla-bitacora__th">Usuario</th>
                        <th class="tabla-bitacora__th">Módulo</th>
                        <th class="tabla-bitacora__th">Acción</th>
                        <th class="tabla-bitacora__th">Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()):
                            // Clase CSS segura
                            $clase_accion = str_replace(['ó', ' '], ['o', ''], strtolower($row['accion']));
                        ?>
                            <tr class="accion-<?= htmlspecialchars($clase_accion) ?>">
                                <td class="tabla-bitacora__td"><?= htmlspecialchars($row['fecha']) ?></td>
                                <td class="tabla-bitacora__td"><?= htmlspecialchars($row['usuario']) ?></td>
                                <td class="tabla-bitacora__td"><?= htmlspecialchars($row['modulo']) ?></td>
                                <td class="tabla-bitacora__td"><?= htmlspecialchars($row['accion']) ?></td>
                                <td class="tabla-bitacora__td"><?= htmlspecialchars($row['detalle']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="tabla-bitacora__td" style="text-align:center; padding: 20px;">
                                No se encontraron registros con los filtros seleccionados.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pag > 1): ?>
            <div class="paginacion">
                <?php if ($pagina > 1): ?>
                    <a class="paginacion__link" href="ver_bitacora.php?pagina=<?= ($pagina - 1) . $query_string_filtros ?>">&laquo; Ant</a>
                <?php endif; ?>

                <?php for ($i = max(1, $pagina - 2); $i <= min($total_pag, $pagina + 2); $i++): ?>
                    <a href="ver_bitacora.php?pagina=<?= $i . $query_string_filtros ?>"
                        class="paginacion__link <?= ($i === $pagina) ? 'paginacion__link--active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($pagina < $total_pag): ?>
                    <a class="paginacion__link" href="ver_bitacora.php?pagina=<?= ($pagina + 1) . $query_string_filtros ?>">Sig &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <a class="form-solicitud__btn-back" href="<?= BASE_URL ?>public/menu_principal.php">
        <div class="button-back">🔙 Volver al menú</div>
    </a>
</main>
</body>

</html>