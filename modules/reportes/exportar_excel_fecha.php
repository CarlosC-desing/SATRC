<?php
// 1. INICIALIZACIÓN Y CONFIGURACIÓN
require_once '../../includes/db/config.php';
include ROOT_PATH . 'modules/login/verificar_sesion.php';
include ROOT_PATH . 'includes/db/conexion.php';

// 2. RECIBIR PARÁMETROS
$tipo   = $_GET['tipo'] ?? '';
$origen = $_GET['origen'] ?? 'general';
$desde  = $_GET['desde'] ?? '';
$hasta  = $_GET['hasta'] ?? '';

// Si no hay tipo, detenemos todo
if (!$tipo) {
    die("Error: No se especificó el tipo de reporte.");
}

// Ajuste de horas para filtro de registro
if ($origen == 'registro') {
    $desde = $desde ? $desde . ' 00:00:00' : '';
    $hasta = $hasta ? $hasta . ' 23:59:59' : '';
}

// 3. HEADERS PARA EXCEL
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=reporte_{$tipo}_" . date('Y-m-d') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// BOM para UTF-8 (Tildes y Ñ correctas)
echo "\xEF\xBB\xBF";

// 4. ESTILOS CSS INCRUSTADOS PARA LA TABLA
$style_header = "background-color: #2b388f; color: #ffffff; font-weight: bold; border: 1px solid #000000;";
$style_cell   = "border: 1px solid #000000; vertical-align: middle;";

echo "<table border='1'>";

// 5. CONSTRUCCIÓN DE LA CONSULTA SQL
$sql = "";
$columnas_excel = []; // Define qué mostrar en el TH

switch ($tipo) {
    case 'nacimiento':
        $columnas_excel = ['N° Acta', 'Cédula', 'Nombre Completo', 'Fecha Nacimiento', 'Lugar Nacimiento', 'Fecha Registro'];
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
        break;

    case 'defuncion':
        $columnas_excel = ['N° Acta', 'Cédula', 'Fallecido', 'Fecha Defunción', 'Causa', 'Fecha Registro'];
        $col_fecha = ($origen == 'registro') ? "d.fecha_registro" : "d.fecha_defuncion";

        $sql = "SELECT d.numero_acta, 
                p.cedula,
                CONCAT_WS(' ', p.primer_nombre, p.segundo_nombre, p.tercer_nombre, p.primer_apellido, p.segundo_apellido) AS nombre,
                d.fecha_defuncion,
                d.causa_defuncion,
                d.fecha_registro
                FROM defuncion d
                JOIN personas p ON p.id_persona = d.id_persona";
        break;

    case 'matrimonio':
        $columnas_excel = ['N° Acta', 'Fecha Boda', 'Lugar', 'Contrayente 1', 'Cédula 1', 'Contrayente 2', 'Cédula 2'];
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
        break;

    case 'union':
        $columnas_excel = ['N° Acta', 'Fecha Unión', 'Lugar', 'Solicitante 1', 'Cédula 1', 'Solicitante 2', 'Cédula 2'];
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
        break;
}

// Agregar filtro de fecha si aplica
if ($origen != 'general' && $desde && $hasta) {
    $sql .= " WHERE $col_fecha BETWEEN '$desde' AND '$hasta'";
}

// 6. IMPRIMIR ENCABEZADOS
echo "<tr>";
foreach ($columnas_excel as $th) {
    echo "<th style='$style_header'>$th</th>";
}
echo "</tr>";

// 7. EJECUTAR Y MOSTRAR DATOS
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $valor) {
            echo "<td style='$style_cell'>" . htmlspecialchars($valor) . "</td>";
        }
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='" . count($columnas_excel) . "' style='$style_cell text-align:center;'>No se encontraron registros para exportar.</td></tr>";
}

echo "</table>";
$conn->close();
