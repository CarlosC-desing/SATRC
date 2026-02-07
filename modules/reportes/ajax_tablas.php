<?php
// modules/reportes/ajax_tablas.php
require_once '../../includes/db/config.php';
include ROOT_PATH . 'modules/login/verificar_sesion.php';
include ROOT_PATH . 'includes/db/conexion.php';

// Recibir parámetros
$tipo       = $_REQUEST['tipo'] ?? '';
$origen     = $_REQUEST['origen'] ?? 'general'; // general, fecha, registro
$pagina     = isset($_REQUEST['pagina']) ? (int)$_REQUEST['pagina'] : 1;
$desde      = $_REQUEST['desde'] ?? '';
$hasta      = $_REQUEST['hasta'] ?? '';
$por_pagina = 10;
$inicio     = ($pagina - 1) * $por_pagina;

// Ajuste de fechas para reporte 'registro'
if ($origen == 'registro') {
    $desde = $desde ? $desde . ' 00:00:00' : '';
    $hasta = $hasta ? $hasta . ' 23:59:59' : '';
}

$sql = "";
$columnas = [];
$titulo = "";

// --- LÓGICA SQL POR TIPO ---
switch ($tipo) {
    case 'nacimiento':
        $columnas = ['numero_acta' => 'N° Acta', 'cedula' => 'Cédula', 'nombre' => 'Nombre Completo'];
        $col_fecha = ($origen == 'registro') ? "n.fecha_registro" : "n.fecha_nacimiento";
        $columnas['fecha'] = ($origen == 'registro') ? 'Registrado el' : 'Fecha Nac.';

        $sql = "SELECT n.numero_acta, $col_fecha as fecha, COALESCE(p.cedula, 'S/C') as cedula, 
                IF(p.id_persona IS NOT NULL, 
                   CONCAT_WS(' ', p.primer_nombre, p.segundo_nombre, p.tercer_nombre, p.primer_apellido, p.segundo_apellido),
                   CONCAT_WS(' ', n.primer_nombre, n.segundo_nombre, n.primer_apellido, n.segundo_apellido)
                ) AS nombre 
                FROM nacimiento n 
                LEFT JOIN personas p ON n.id_nacido = p.id_persona";

        if ($origen != 'general' && $desde && $hasta) {
            $sql .= " WHERE $col_fecha BETWEEN '$desde' AND '$hasta'";
        }
        $titulo = "Reporte de Nacimientos";
        break;

    case 'defuncion':
        $columnas = ['numero_acta' => 'N° Acta', 'cedula' => 'Cédula', 'nombre' => 'Fallecido'];
        $col_fecha = ($origen == 'registro') ? "d.fecha_registro" : "d.fecha_defuncion";
        $columnas['fecha'] = ($origen == 'registro') ? 'Registrado el' : 'Fecha Def.';

        $sql = "SELECT d.numero_acta, $col_fecha as fecha, p.cedula, 
                CONCAT_WS(' ', p.primer_nombre, p.segundo_nombre, p.tercer_nombre, p.primer_apellido, p.segundo_apellido) AS nombre 
                FROM defuncion d 
                JOIN personas p ON d.id_persona = p.id_persona";

        if ($origen != 'general' && $desde && $hasta) {
            $sql .= " WHERE $col_fecha BETWEEN '$desde' AND '$hasta'";
        }
        $titulo = "Reporte de Defunciones";
        break;

    case 'matrimonio':
        $columnas = ['numero_acta' => 'N° Acta', 'pareja' => 'Contrayentes'];
        $col_fecha = ($origen == 'registro') ? "m.fecha_registro" : "m.fecha_celebracion";
        $columnas['fecha'] = ($origen == 'registro') ? 'Registrado el' : 'Fecha Boda';

        $sql = "SELECT m.numero_acta, $col_fecha as fecha, 
                CONCAT(
                    CONCAT_WS(' ', p1.primer_nombre, p1.segundo_nombre, p1.tercer_nombre, p1.primer_apellido, p1.segundo_apellido), ' (', p1.cedula, ') y ', 
                    CONCAT_WS(' ', p2.primer_nombre, p2.segundo_nombre, p2.tercer_nombre, p2.primer_apellido, p2.segundo_apellido), ' (', p2.cedula, ')'
                ) AS pareja 
                FROM matrimonio m 
                JOIN personas p1 ON m.id_contrayente1 = p1.id_persona 
                JOIN personas p2 ON m.id_contrayente2 = p2.id_persona";

        if ($origen != 'general' && $desde && $hasta) {
            $sql .= " WHERE $col_fecha BETWEEN '$desde' AND '$hasta'";
        }
        $titulo = "Reporte de Matrimonios";
        break;

    case 'union':
        $columnas = ['numero_acta' => 'N° Acta', 'pareja' => 'Convivientes'];
        $col_fecha = ($origen == 'registro') ? "u.fecha_registro" : "u.fecha_inicio_union";
        $columnas['fecha'] = ($origen == 'registro') ? 'Registrado el' : 'Fecha Unión';

        $sql = "SELECT u.numero_acta, $col_fecha as fecha, 
                CONCAT(
                    CONCAT_WS(' ', p1.primer_nombre, p1.segundo_nombre, p1.tercer_nombre, p1.primer_apellido, p1.segundo_apellido), ' (', p1.cedula, ') y ', 
                    CONCAT_WS(' ', p2.primer_nombre, p2.segundo_nombre, p2.tercer_nombre, p2.primer_apellido, p2.segundo_apellido), ' (', p2.cedula, ')'
                ) AS pareja 
                FROM union_estable u 
                JOIN personas p1 ON u.id_persona1 = p1.id_persona 
                JOIN personas p2 ON u.id_persona2 = p2.id_persona";

        if ($origen != 'general' && $desde && $hasta) {
            $sql .= " WHERE $col_fecha BETWEEN '$desde' AND '$hasta'";
        }
        $titulo = "Reporte de Uniones Estables";
        break;
}

// --- GENERAR HTML DE RESPUESTA ---
if ($sql) {
    // 1. Contar total
    $total_result = $conn->query($sql);
    $total = $total_result ? $total_result->num_rows : 0;

    // 2. Obtener datos paginados
    $sql .= " LIMIT $inicio, $por_pagina";
    $result = $conn->query($sql);

    echo "<div class='card-result fade-in'>";
    echo "<h3 class='title-sub'>$titulo</h3>";
    echo "<p style='color: white'>Total de registros: $total</p>";
    $link_excel = "exportar_excel.php?tipo=$tipo&origen=$origen&desde=$desde&hasta=$hasta";
    echo "<a href='$link_excel' class='download-btn' target='_blank'>📥 Exportar Excel</a>";

    echo "<div class='table-container'><table class='report-table'><thead><tr>";
    foreach ($columnas as $etiqueta) echo "<th>$etiqueta</th>";
    echo "<th>Acción</th></tr></thead><tbody>";

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr style='background-color: white';>";
            foreach (array_keys($columnas) as $campo) echo "<td>" . htmlspecialchars($row[$campo] ?? '') . "</td>";
            $url_pdf = "../actas/generar_pdf/pdf_{$tipo}.php?numero_acta=" . urlencode($row['numero_acta']);
            echo "<td><a href='$url_pdf' class='download-btn' target='_blank'>📥 PDF</a></td></tr>";
        }
    } else {
        echo "<tr><td colspan='" . (count($columnas) + 1) . "' style='text-align:center'>No se encontraron datos.</td></tr>";
    }
    echo "</tbody></table></div>";

    // --- PAGINACIÓN INTELIGENTE (AJAX) ---
    $total_paginas = ceil($total / $por_pagina);
    if ($total_paginas > 1) {
        echo "<div class='paginacion'>";

        // Llamada JS: cargarTabla('nacimiento', 'general', '', '', 2)
        $js_args = "'$tipo', '$origen', '$desde', '$hasta'";

        // Botón Anterior
        if ($pagina > 1) {
            echo "<button onclick=\"cargarTabla($js_args, " . ($pagina - 1) . ")\" class='btn-nav'>&laquo; Anterior</button>";
        } else {
            echo "<button class='btn-nav disabled' disabled>&laquo; Anterior</button>";
        }

        // Números Inteligentes
        $rango = 2;
        for ($i = 1; $i <= $total_paginas; $i++) {
            if ($i == 1 || $i == $total_paginas || ($i >= $pagina - $rango && $i <= $pagina + $rango)) {
                $cls = ($i == $pagina) ? 'active' : '';
                echo "<button onclick=\"cargarTabla($js_args, $i)\" class='$cls'>$i</button>";
            } elseif ($i == $pagina - $rango - 1 || $i == $pagina + $rango + 1) {
                echo "<span class='dots'>...</span>";
            }
        }

        // Botón Siguiente
        if ($pagina < $total_paginas) {
            echo "<button onclick=\"cargarTabla($js_args, " . ($pagina + 1) . ")\" class='btn-nav'>Siguiente &raquo;</button>";
        } else {
            echo "<button class='btn-nav disabled' disabled>Siguiente &raquo;</button>";
        }
        echo "</div>";
    }
    echo "</div><hr class='content-divider'>";
}
