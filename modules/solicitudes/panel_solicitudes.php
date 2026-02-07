<?php
require_once '../../includes/db/config.php';
include ROOT_PATH . 'modules/login/verificar_sesion.php';
include ROOT_PATH . 'includes/db/conexion.php';
include ROOT_PATH . 'functions/registrar_log.php';

$estado = $_GET['estado'] ?? '';
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = 20;
$inicio = ($pagina - 1) * $por_pagina;

// --- 1. Estadísticas de estados ---
$stats = ['pendiente' => 0, 'en_proceso' => 0, 'entregada' => 0];
$stat_query = $conn->query("SELECT estado, COUNT(*) AS total FROM solicitudes_actas GROUP BY estado");
if ($stat_query) {
    while ($row = $stat_query->fetch_assoc()) {
        $stats[$row['estado']] = $row['total'];
    }
}

// --- 2. Estadísticas por tipo de acta ---
$por_tipo = [];
$tipo_query = $conn->query("SELECT tipo_acta, COUNT(*) AS total FROM solicitudes_actas GROUP BY tipo_acta");
if ($tipo_query) {
    while ($row = $tipo_query->fetch_assoc()) {
        $por_tipo[$row['tipo_acta']] = $row['total'];
    }
}

// --- 3. Procesar Actualización de Estado ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_solicitud']) && isset($_POST['nuevo_estado'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Error de validación de seguridad (CSRF).");
    }

    $id = (int)$_POST['id_solicitud'];
    $nuevo_estado = $_POST['nuevo_estado'];

    if (in_array($nuevo_estado, ['pendiente', 'en_proceso', 'entregada'])) {
        $stmt = $conn->prepare("UPDATE solicitudes_actas SET estado = ? WHERE id_solicitud = ?");
        $stmt->bind_param("si", $nuevo_estado, $id);
        if ($stmt->execute()) {
            registrarLog($conn, $_SESSION['usuario'], "Solicitudes", "Cambio de estado", "ID $id a $nuevo_estado");
        }
        $stmt->close();
    }
    header("Location: panel_solicitudes.php" . ($estado ? "?estado=" . urlencode($estado) : ""));
    exit;
}

// --- 4. Consulta Principal ---
$sql_base = "SELECT s.id_solicitud, s.tipo_acta, s.motivo, s.fecha_solicitud, s.estado,
                    CONCAT(p.primer_nombre, ' ', p.primer_apellido) AS solicitante
             FROM solicitudes_actas s
             JOIN personas p ON p.id_persona = s.id_persona";

if (!empty($estado)) {
    $stmt_t = $conn->prepare("SELECT COUNT(*) FROM solicitudes_actas WHERE estado = ?");
    $stmt_t->bind_param("s", $estado);
    $stmt_t->execute();
    $total = $stmt_t->get_result()->fetch_row()[0];
    $stmt_t->close();

    $sql_paginado = $sql_base . " WHERE s.estado = ? ORDER BY s.fecha_solicitud DESC LIMIT ?, ?";
    $stmt_p = $conn->prepare($sql_paginado);
    $stmt_p->bind_param("sii", $estado, $inicio, $por_pagina);
} else {
    $total_res = $conn->query("SELECT COUNT(*) FROM solicitudes_actas");
    $total = $total_res->fetch_row()[0];

    $sql_paginado = $sql_base . " ORDER BY s.fecha_solicitud DESC LIMIT ?, ?";
    $stmt_p = $conn->prepare($sql_paginado);
    $stmt_p->bind_param("ii", $inicio, $por_pagina);
}

$stmt_p->execute();
$result = $stmt_p->get_result();

$titulo_pagina = "Panel Solicitudes - Registro Civil";
include ROOT_PATH . 'includes/components/header.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/panel.css">

<div class="panel-layout">
    <aside class="panel-sidebar">
        <div class="title-sidebar">
            <h2 class="title--small">Seguimiento de</h2>
            <h3 class="title--large">Solicitudes</h3>
        </div>

        <form class="filter-form" method="GET" action="panel_solicitudes.php">
            <div class="filter-form__group">
                <label class="filter-form__label" for="estado">Filtrar por estado:</label>
                <select class="filter-form__select" name="estado" id="estado">
                    <option value="">Todos</option>
                    <option value="pendiente" <?= $estado === 'pendiente' ? 'selected' : '' ?>>Pendiente (<?= $stats['pendiente'] ?>)</option>
                    <option value="en_proceso" <?= $estado === 'en_proceso' ? 'selected' : '' ?>>En proceso (<?= $stats['en_proceso'] ?>)</option>
                    <option value="entregada" <?= $estado === 'entregada' ? 'selected' : '' ?>>Entregada (<?= $stats['entregada'] ?>)</option>
                </select>
            </div>
            <button class="filter-form__btn-apply" type="submit">Filtrar</button>
        </form>

        <a href="solicitudes.php" class="filter-form__btn-new">Nueva Solicitud</a>
    </aside>

    <main class="panel-main">
        <div class="panel-sidebar__summary">
            <h2 class="panel-sidebar__summary-item">Total solicitudes: <?= (int)$total ?></h2>
            <div class="panel-sidebar__stats">
                <span>Nacimiento: <?= (int)($por_tipo['nacimiento'] ?? 0) ?></span>
                <span>Defunción: <?= (int)($por_tipo['defuncion'] ?? 0) ?></span>
                <span>Matrimonio: <?= (int)($por_tipo['matrimonio'] ?? 0) ?></span>
                <span>Unión: <?= (int)($por_tipo['union'] ?? 0) ?></span>
            </div>
        </div>

        <section class="panel-main__table-container">
            <table class="solicitudes-table">
                <thead>
                    <tr>
                        <th class="solicitudes-table__th">ID</th>
                        <th class="solicitudes-table__th">Solicitante</th>
                        <th class="solicitudes-table__th">Tipo</th>
                        <th class="solicitudes-table__th">Motivo</th>
                        <th class="solicitudes-table__th">Fecha</th>
                        <th class="solicitudes-table__th">Estado</th>
                        <th class="solicitudes-table__th">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr class="solicitudes-table__tr--<?= htmlspecialchars($row['estado']) ?>">
                                <td class="solicitudes-table__td"><?= (int)$row['id_solicitud'] ?></td>
                                <td class="solicitudes-table__td"><?= htmlspecialchars($row['solicitante']) ?></td>
                                <td class="solicitudes-table__td"><?= htmlspecialchars(ucfirst($row['tipo_acta'])) ?></td>
                                <td class="solicitudes-table__td">
                                    <textarea class="solicitudes-table__textarea-scroll" readonly><?= htmlspecialchars($row['motivo']) ?></textarea>
                                </td>
                                <td class="solicitudes-table__td"><?= date('d/m/Y', strtotime($row['fecha_solicitud'])) ?></td>
                                <td class="solicitudes-table__td">
                                    <span class="status-badge status-badge--<?= htmlspecialchars($row['estado']) ?>">
                                        <?= ucfirst(str_replace('_', ' ', $row['estado'])) ?>
                                    </span>
                                </td>
                                <td class="solicitudes-table__td">
                                    <div class="row-actions">
                                        <form method="POST" class="row-actions__form">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="id_solicitud" value="<?= (int)$row['id_solicitud'] ?>">
                                            <select name="nuevo_estado" class="row-actions__select">
                                                <option value="pendiente" <?= $row['estado'] == 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                                                <option value="en_proceso" <?= $row['estado'] == 'en_proceso' ? 'selected' : '' ?>>En proceso</option>
                                                <option value="entregada" <?= $row['estado'] == 'entregada' ? 'selected' : '' ?>>Entregada</option>
                                            </select>
                                            <button type="submit" class="row-actions__btn-update">✓</button>
                                        </form>

                                        <form method="POST" action="eliminar_solicitud.php" onsubmit="return confirm('¿Eliminar?');">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="id_eliminar" value="<?= (int)$row['id_solicitud'] ?>">
                                            <button type="submit" class="row-actions__btn-delete">✕</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="solicitudes-table__td" style="text-align:center;">No hay solicitudes registradas.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <?php
        $total_paginas = ceil($total / $por_pagina);
        if ($total_paginas > 1): ?>
            <div class="paginacion">
                <?php for ($i = 1; $i <= $total_paginas; $i++):
                    $href = "panel_solicitudes.php?pagina=$i" . ($estado ? "&estado=" . urlencode($estado) : "");
                    $active = ($i === $pagina) ? "class='active'" : "";
                ?>
                    <a href="<?= $href ?>" <?= $active ?>><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php
$stmt_p->close();
$conn->close();
?>