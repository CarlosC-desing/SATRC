<?php
// 1. RUTAS CORREGIDAS
require_once '../../includes/db/config.php';
include ROOT_PATH . 'modules/login/verificar_sesion.php';
include ROOT_PATH . 'includes/db/conexion.php';

// 2. RECIBIR PARÁMETROS (IGUAL QUE EN EL REPORTE)
$tipo   = $_GET['tipo'] ?? '';
$origen = $_GET['origen'] ?? 'general';
$desde  = $_GET['desde'] ?? '';
$hasta  = $_GET['hasta'] ?? '';

// Ajuste de horas para filtro de registro
if ($origen == 'registro') {
    $desde = $desde ? $desde . ' 00:00:00' : '';
    $hasta = $hasta ? $hasta . ' 23:59:59' : '';
}

// 3. ENCABEZADOS PARA FORZAR DESCARGA EN EXCEL
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=reporte_{$tipo}_" . date('Y-m-d') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// BOM para que Excel lea bien las tildes y ñ
echo "\xEF\xBB\xBF";

echo "<table border='1'>";

// 4. LÓGICA SQL ADAPTADA A TU NUEVA ESTRUCTURA
$sql = "";

switch ($tipo) {
    case 'nacimiento':
        echo "<tr>
                <th style='background-color:#2b388f; color:white;'>N° Acta</th>
                <th style='background-color:#2b388f; color:white;'>Cédula</th>
                <th style='background-color:#2b388f; color:white;'>Nombre Completo</th>
                <th style='background-color:#2b388f; color:white;'>Fecha Nacimiento</th>
                <th style='background-color:#2b388f; color:white;'>Lugar</th>
                <th style='background-color:#2b388f; color:white;'>Fecha Registro</th>
              </tr>";

        $col_fecha = ($origen == 'registro') ? "n.fecha_registro" : "n.fecha_nacimiento";

        $sql = "SELECT n.numero_acta, 
                COALESCE(p.cedula, 'S/C') as cedula,
                IF(p.id_persona IS NOT NULL, 
                   CONCAT_WS(' ', p.primer_nombre, p.segundo_nombre, p.tercer_nombre, p.primer_apellido, p.segundo_apellido),
                   CONCAT_WS(' ', n.primer_nombre, n.segundo_nombre, n.primer_apellido, n.segundo_apellido)
                ) AS nombre,
                n.fecha_nacimiento,
                n.lugar_nacimiento,
                n.fecha_registro
                FROM nacimiento n 
                LEFT JOIN personas p ON n.id_nacido = p.id_persona";

        if ($origen != 'general' && $desde && $hasta) {
            $sql .= " WHERE $col_fecha BETWEEN '$desde' AND '$hasta'";
        }
        break;

    case 'defuncion':
        echo "<tr>
                <th style='background-color:#e74c3c; color:white;'>N° Acta</th>
                <th style='background-color:#e74c3c; color:white;'>Cédula</th>
                <th style='background-color:#e74c3c; color:white;'>Fallecido</th>
                <th style='background-color:#e74c3c; color:white;'>Fecha Defunción</th>
                <th style='background-color:#e74c3c; color:white;'>Causa</th>
                <th style='background-color:#e74c3c; color:white;'>Fecha Registro</th>
              </tr>";

        $col_fecha = ($origen == 'registro') ? "d.fecha_registro" : "d.fecha_defuncion";

        $sql = "SELECT d.numero_acta, 
                p.cedula,
                CONCAT_WS(' ', p.primer_nombre, p.segundo_nombre, p.tercer_nombre, p.primer_apellido, p.segundo_apellido) AS nombre,
                d.fecha_defuncion,
                d.causa_defuncion,
                d.fecha_registro
                FROM defuncion d
                JOIN personas p ON p.id_persona = d.id_persona";

        if ($origen != 'general' && $desde && $hasta) {
            $sql .= " WHERE $col_fecha BETWEEN '$desde' AND '$hasta'";
        }
        break;

    case 'matrimonio':
        echo "<tr>
                <th style='background-color:#27ae60; color:white;'>N° Acta</th>
                <th style='background-color:#27ae60; color:white;'>Fecha Celebración</th>
                <th style='background-color:#27ae60; color:white;'>Lugar</th>
                <th style='background-color:#27ae60; color:white;'>Contrayente 1</th>
                <th style='background-color:#27ae60; color:white;'>Cédula 1</th>
                <th style='background-color:#27ae60; color:white;'>Contrayente 2</th>
                <th style='background-color:#27ae60; color:white;'>Cédula 2</th>
              </tr>";

        $col_fecha = ($origen == 'registro') ? "m.fecha_registro" : "m.fecha_celebracion";

        $sql = "SELECT m.numero_acta, 
                m.fecha_celebracion,
                m.lugar_matrimonio,
                CONCAT_WS(' ', p1.primer_nombre, p1.primer_apellido) as nombre1,
                p1.cedula as cedula1,
                CONCAT_WS(' ', p2.primer_nombre, p2.primer_apellido) as nombre2,
                p2.cedula as cedula2
                FROM matrimonio m
                JOIN personas p1 ON p1.id_persona = m.id_contrayente1
                JOIN personas p2 ON p2.id_persona = m.id_contrayente2";

        if ($origen != 'general' && $desde && $hasta) {
            $sql .= " WHERE $col_fecha BETWEEN '$desde' AND '$hasta'";
        }
        break;

    case 'union':
        echo "<tr>
                <th style='background-color:#9b59b6; color:white;'>N° Acta</th>
                <th style='background-color:#9b59b6; color:white;'>Fecha Unión</th>
                <th style='background-color:#9b59b6; color:white;'>Lugar</th>
                <th style='background-color:#9b59b6; color:white;'>Solicitante 1</th>
                <th style='background-color:#9b59b6; color:white;'>Cédula 1</th>
                <th style='background-color:#9b59b6; color:white;'>Solicitante 2</th>
                <th style='background-color:#9b59b6; color:white;'>Cédula 2</th>
              </tr>";

        $col_fecha = ($origen == 'registro') ? "u.fecha_registro" : "u.fecha_inicio_union";

        $sql = "SELECT u.numero_acta, 
                u.fecha_inicio_union,
                u.lugar_union,
                CONCAT_WS(' ', p1.primer_nombre, p1.primer_apellido) as nombre1,
                p1.cedula as cedula1,
                CONCAT_WS(' ', p2.primer_nombre, p2.primer_apellido) as nombre2,
                p2.cedula as cedula2
                FROM union_estable u
                JOIN personas p1 ON p1.id_persona = u.id_persona1
                JOIN personas p2 ON p2.id_persona = u.id_persona2";

        if ($origen != 'general' && $desde && $hasta) {
            $sql .= " WHERE $col_fecha BETWEEN '$desde' AND '$hasta'";
        }
        break;

    default:
        echo "<tr><td>Tipo de reporte no válido.</td></tr></table>";
        exit;
}

// 5. EJECUTAR Y MOSTRAR DATOS
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $valor) {
            // Decodificamos caracteres especiales por si acaso
            echo "<td>" . htmlspecialchars($valor) . "</td>";
        }
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='6'>No hay datos para exportar con los filtros seleccionados.</td></tr>";
}

echo "</table>";
$conn->close();
