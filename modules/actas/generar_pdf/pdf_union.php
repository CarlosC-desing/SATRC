<?php
require_once __DIR__ . '/../../../vendor/autoload.php';
include_once '../../../includes/db/config.php';
include '../../../includes/db/conexion.php';

use Mpdf\HTMLParserMode;

// 1. VALIDACIÓN
$num_acta = $_GET['numero_acta'] ?? '';
if (empty($num_acta)) {
    die("Error: No se especificó el número de acta.");
}

// 2. CONSULTA ACTUALIZADA (SNAPSHOT + RELACIONES)
// Priorizamos datos de 'union_estable' (Snapshot) sobre 'personas'
$sql = "SELECT u.*, 
        -- Declarante 1 (p1)
        p1.primer_nombre as u1_n1, p1.segundo_nombre as u1_n2, p1.primer_apellido as u1_a1, p1.segundo_apellido as u1_a2, 
        p1.cedula as u1_ced, p1.fecha_nacimiento as u1_fnac, p1.nacionalidad as u1_nac,
        p1.pais_nacimiento as u1_pais, p1.estado_nacimiento as u1_edo, p1.municipio_nacimiento as u1_mun, p1.parroquia_nacimiento as u1_par,
        
        -- Declarante 2 (p2)
        p2.primer_nombre as u2_n1, p2.segundo_nombre as u2_n2, p2.primer_apellido as u2_a1, p2.segundo_apellido as u2_a2, 
        p2.cedula as u2_ced, p2.fecha_nacimiento as u2_fnac, p2.nacionalidad as u2_nac,
        p2.pais_nacimiento as u2_pais, p2.estado_nacimiento as u2_edo, p2.municipio_nacimiento as u2_mun, p2.parroquia_nacimiento as u2_par,
        
        -- Autoridad (ra)
        ra.primer_nombre as r_n1, ra.segundo_nombre as r_n2, ra.primer_apellido as r_a1, ra.segundo_apellido as r_a2, ra.cedula as r_ced,
        
        -- Testigos (t1, t2)
        t1.primer_nombre as t1_n1, t1.primer_apellido as t1_a1, t1.cedula as t1_ced, t1.nacionalidad as t1_nac, t1.fecha_nacimiento as t1_fnac,
        t2.primer_nombre as t2_n1, t2.primer_apellido as t2_a1, t2.cedula as t2_ced, t2.nacionalidad as t2_nac, t2.fecha_nacimiento as t2_fnac
        
        FROM union_estable u
        LEFT JOIN personas p1 ON u.id_persona1 = p1.id_persona
        LEFT JOIN personas p2 ON u.id_persona2 = p2.id_persona
        LEFT JOIN personas ra ON u.id_autoridad = ra.id_persona
        LEFT JOIN personas t1 ON u.id_testigo1 = t1.id_persona
        LEFT JOIN personas t2 ON u.id_testigo2 = t2.id_persona
        WHERE u.numero_acta = ? LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $num_acta);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) die("Error: Acta no encontrada.");
$datos = $res->fetch_assoc();

// 3. CONSULTA DE HIJOS
$hijos = [];
if (!empty($datos['id'])) {
    $sql_h = "SELECT * FROM union_hijos WHERE id_union = ?";
    $stmt_h = $conn->prepare($sql_h);
    $stmt_h->bind_param("i", $datos['id']);
    $stmt_h->execute();
    $res_h = $stmt_h->get_result();
    while ($row = $res_h->fetch_assoc()) {
        $hijos[] = $row;
    }
}

// --- HELPER FUNCTIONS ---
function fechaE($fecha)
{
    if (empty($fecha) || $fecha == '0000-00-00') return ['d' => '', 'm' => '', 'a' => ''];
    $t = strtotime($fecha);
    $meses = [1 => "ENERO", 2 => "FEBRERO", 3 => "MARZO", 4 => "ABRIL", 5 => "MAYO", 6 => "JUNIO", 7 => "JULIO", 8 => "AGOSTO", 9 => "SEPTIEMBRE", 10 => "OCTUBRE", 11 => "NOVIEMBRE", 12 => "DICIEMBRE"];
    return ['d' => date('d', $t), 'm' => $meses[date('n', $t)], 'a' => date('Y', $t)];
}
function edad($fnac)
{
    if (empty($fnac)) return '';
    try {
        return date_diff(date_create($fnac), date_create('today'))->y;
    } catch (Exception $e) {
        return '';
    }
}
function chk($val)
{
    return $val ? '✓' : '&nbsp;';
}
function n($n1, $n2, $a1, $a2)
{
    return mb_strtoupper(trim("$n1 $n2 $a1 $a2"));
}

// --- PREPARACIÓN DE DATOS ---
$fr   = fechaE($datos['fecha_registro']);
$fini = fechaE($datos['fecha_inicio_union']); // Fecha Inicio Unión
$fres = fechaE($datos['resolucion_fecha']);
$fgac = fechaE($datos['gaceta_fecha']);

// Decodificar JSONs de Estado Civil Anterior
$d1_ant = json_decode($datos['d1_datos_anterior'] ?? '[]', true);
$d2_ant = json_decode($datos['d2_datos_anterior'] ?? '[]', true);

// MPDF SETUP
$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'Legal',
    'margin_top' => 10,
    'margin_left' => 10,
    'margin_right' => 10
]);

$stylesheet = file_get_contents('../../../assets/css/estilos_pdf.css');
$mpdf->WriteHTML($stylesheet, HTMLParserMode::HEADER_CSS);

// --- HELPER DE TABLAS ---
function generarCuadroFecha($d, $m, $a)
{
    return '<table width="100%" class="mini-fecha" style="border-collapse:collapse; text-align:center; border:1px solid #000;">
        <tr><td rowspan="2" style="border-right:1px solid #000; width:30%; font-size:6pt;">FECHA</td><td style="border-right:1px solid #000; border-bottom:1px solid #000; font-size:5pt;">DÍA</td><td style="border-right:1px solid #000; border-bottom:1px solid #000; font-size:5pt;">MES</td><td style="border-bottom:1px solid #000; font-size:5pt;">AÑO</td></tr>
        <tr><td style="border-right:1px solid #000; font-size:8pt;">' . $d . '</td><td style="border-right:1px solid #000; font-size:6pt;">' . $m . '</td><td style="font-size:8pt;">' . $a . '</td></tr>
    </table>';
}
function generarCeldasFirma($etiquetas, $altura)
{
    $tds = '';
    foreach ($etiquetas as $e) {
        $tds .= "<td style='width: 25%; height: {$altura}px; border: 1px solid #000; vertical-align: bottom; padding: 5px; text-align: center; font-size: 8pt;'>
            <div style='" . (strpos($e, 'FIRMA') !== false ? "border-top: 1px solid #000;" : "") . " padding-top: 2px;'>$e</div></td>";
    }
    return $tds;
}
function sub($lbl, $val, $sz = '10px')
{
    return "<span class='sub-label'>$lbl <br><span style='font-size:$sz; color:#000; font-weight:normal;'>" . ($val ?: '&nbsp;') . "</span></span>";
}
function titulo($t)
{
    return "<div class='titulo-seccion'>$t</div>";
}

// --- PÁGINA 1 ---
$html = '
<table class="header-table">
    <tr>
        <td width="35%" class="texto-institucional">
            <b>República Bolivariana de Venezuela</b><br><b>Consejo Nacional Electoral</b><br><b>Comisión de Registro Civil y Electoral</b><br>
            Estado <span class="linea-rellenable">YARACUY</span><br>Municipio <span class="linea-rellenable">PEÑA</span><br>Parroquia <span class="linea-rellenable">YARITAGUA</span>
        </td>
        <td width="30%" style="text-align: center;">
            <div class="logo-cne"><img class="img-cne" src="../../../assets/img/CNE_23_.png"><div class="poder-electoral">PODER ELECTORAL</div></div>
        </td>
        <td width="35%">
            <table class="tabla-acta-header">
                <tr><td class="text-right">ACTA N°</td><td class="borde-bottom-acta"><b>' . $datos['numero_acta'] . '</b></td></tr>
                <tr><td class="text-right">DÍA</td><td class="borde-bottom-acta"><b>' . $datos['dia_acta'] . '</b></td></tr>
                <tr><td class="text-right">MES</td><td class="borde-bottom-acta"><b>' . strtoupper($datos['mes_acta']) . '</b></td></tr>
                <tr><td class="text-right">AÑO</td><td class="borde-bottom-acta"><b>' . $datos['anio_acta'] . '</b></td></tr>
            </table>
        </td>
    </tr>
</table>

<table width="100%" class="tabla-tipo-registro">
    <tr>
        <td>REGISTRO DE UNIÓN ESTABLE DE HECHO <span class="check-box">' . (($datos['tipo_operacion'] == 'NORMAL') ? '✓' : '&nbsp;') . '</span></td>
        <td style="text-align: center;">INSERCIÓN <span class="check-box">' . (($datos['tipo_operacion'] == 'INSERCION') ? '✓' : '&nbsp;') . '</span></td>
        <td style="text-align: right;">DISOLUCIÓN <span class="check-box">' . (($datos['tipo_operacion'] == 'DISOLUCION') ? '✓' : '&nbsp;') . '</span></td>
    </tr>
</table>

' . titulo('A Datos del Registrador (a) Civil') . '
<table class="tabla-datos">
    <tr><td width="50%">' . sub('NOMBRES', $datos['r_n1'] . ' ' . $datos['r_n2']) . '</td><td width="50%">' . sub('APELLIDOS', $datos['r_a1'] . ' ' . $datos['r_a2']) . '</td></tr>
    <tr><td>' . sub('DOCUMENTO DE IDENTIDAD N°', $datos['r_ced']) . '</td><td>' . sub('OFICINA O UNIDAD DE REGISTRO CIVIL', 'REGISTRO CIVIL YARITAGUA') . '</td></tr>
</table>
<table class="tabla-datos no-top">
    <tr>
        <td width="30%">' . sub('RESOLUCIÓN N°', $datos['resolucion_numero']) . '</td>
        <td width="15%">' . sub('FECHA', $fres['d'] . '/' . $fres['m'] . '/' . $fres['a']) . '</td>
        <td width="20%">' . sub('GACETA N°', $datos['gaceta_numero']) . '</td>
        <td width="20%"><div class="gaceta-checks"><span class="check-box">✓</span> MUNICIPAL<br><span class="check-box"> </span> OFICIAL</div></td>
        <td width="15%">' . sub('FECHA', $fgac['d'] . '/' . $fgac['m'] . '/' . $fgac['a']) . '</td>
    </tr>
</table>';

// SECCIONES B Y C (Unidos) - Corregido para usar d1_profesion, d1_residencia (Snapshot)
function bloqueUnido($letra, $titulo, $d, $u, $prof_col, $res_col, $ant_json)
{
    $edad = edad($d[$u . '_fnac']);
    $edo_civil_txt = "SOLTERO(A)";
    if (!empty($ant_json)) {
        if (($ant_json['tipo'] ?? '') == 'DIVORCIADO') {
            $edo_civil_txt = "DIVORCIADO(A). Sentencia: " . ($ant_json['sentencia'] ?? '');
        } elseif (($ant_json['tipo'] ?? '') == 'VIUDO') {
            $edo_civil_txt = "VIUDO(A). Acta Defunción: " . ($ant_json['acta'] ?? '');
        }
    }
    $nac = fechaE($d[$u . '_fnac']);

    return titulo("$letra Datos $titulo") . '
    <table class="tabla-datos">
        <tr><td width="50%">' . sub('NOMBRES', $d[$u . '_n1'] . ' ' . $d[$u . '_n2']) . '</td><td>' . sub('APELLIDOS', $d[$u . '_a1'] . ' ' . $d[$u . '_a2']) . '</td></tr>
    </table>
    <table class="tabla-datos no-top">
        <tr>
            <td width="35%"><span class="sub-label">FECHA DE NACIMIENTO</span> <span style="font-size:8px;">DÍA: ' . $nac['d'] . ' MES: ' . $nac['m'] . ' AÑO: ' . $nac['a'] . '</span></td>
            <td width="8%">' . sub('EDAD', $edad) . '</td>
            <td width="32%">' . sub('CÉDULA DE IDENTIDAD N°', $d[$u . '_ced']) . '</td>
            <td width="25%">' . sub('ESTADO CIVIL', $edo_civil_txt) . '</td>
        </tr>
    </table>
    <table class="tabla-datos no-top">
        <tr><td>' . sub('PAÍS NAC.', $d[$u . '_pais']) . '</td><td>' . sub('ESTADO', $d[$u . '_edo']) . '</td><td>' . sub('MUNICIPIO', $d[$u . '_mun']) . '</td><td>' . sub('PARROQUIA', $d[$u . '_par']) . '</td></tr>
        <tr><td>' . sub('NACIONALIDAD', $d[$u . '_nac']) . '</td><td colspan="2">' . sub('PROFESIÓN', $d[$prof_col]) . '</td><td colspan="1">' . sub('RESIDENCIA', $d[$res_col]) . '</td></tr>
    </table>';
}

$html .= bloqueUnido('B', 'del Unido', $datos, 'u1', 'd1_profesion', 'd1_residencia', $d1_ant);
$html .= bloqueUnido('C', 'de la Unida', $datos, 'u2', 'd2_profesion', 'd2_residencia', $d2_ant);

// D. MANIFESTACIÓN
$html .= titulo('D Manifestación expresa') . '
<table class="tabla-datos">
    <tr><td width="80%" style="padding:10px;"><b>Los declarantes manifiestan que tienen una Unión Estable de Hecho aproximadamente desde</b></td>
    <td width="20%">' . generarCuadroFecha($fini['d'], $fini['m'], $fini['a']) . '</td></tr>
</table>';

// E. HIJOS
$html .= titulo('E Datos de Hijos o Hijas');
$filas_hijos = '';
if (count($hijos) > 0) {
    foreach ($hijos as $idx => $h) {
        $num = $idx + 1;
        $rec = ($h['reconocido'] == 'SI') ? 'SI [✓] NO [ ]' : 'SI [ ] NO [✓]';
        $filas_hijos .= '<table class="tabla-datos" style="border-top:' . ($idx > 0 ? 'none' : '1px solid #000') . ';">
            <tr><td width="45%">' . sub($num . ') NOMBRES Y APELLIDOS', $h['nombre_hijo']) . '</td><td width="20%">' . sub('DOCUMENTO ID', '') . '</td><td width="8%">' . sub('EDAD', '') . '</td><td width="27%">' . sub('ACTA NAC. N°', $h['acta_hijo']) . '</td></tr>
            <tr><td colspan="3">' . sub('OFICINA REGISTRO CIVIL', '') . '</td><td>' . sub('RECONOCIDO', $rec) . '</td></tr>
        </table>';
    }
} else {
    $filas_hijos = '<table class="tabla-datos"><tr><td>SIN HIJOS REGISTRADOS</td></tr></table>';
}
$html .= $filas_hijos;

// F. TESTIGOS (Datos Snapshot de union_estable)
$html .= titulo('F Datos de los Testigos');
foreach (['t1', 't2'] as $t) {
    $html .= '<table class="tabla-datos" style="border-top: 1px solid #000;">
        <tr><td width="50%">' . sub('NOMBRES', $datos[$t . '_n1'] . ' ' . $datos[$t . '_n1']) . '</td><td>' . sub('APELLIDOS', $datos[$t . '_a1'] . ' ' . $datos[$t . '_a2']) . '</td></tr>
    </table>
    <table class="tabla-datos no-top">
        <tr><td width="22%">' . sub('CÉDULA', $datos[$t . '_ced']) . '</td><td width="8%">' . sub('EDAD', edad($datos[$t . '_fnac'])) . '</td><td width="45%">' . sub('PROFESIÓN', $datos[$t . '_profesion']) . '</td><td width="25%">' . sub('NACIONALIDAD', $datos[$t . '_nac']) . '</td></tr>
        <tr><td colspan="4">' . sub('RESIDENCIA', $datos[$t . '_residencia']) . '</td></tr>
    </table>';
}

// G. APODERADO
$fapo = fechaE($datos['apoderado_fecha'] ?? '');
$html .= titulo('G Datos del Apoderado (Si aplica)') . '
<table class="tabla-datos">
    <tr><td width="65%">' . sub('NOMBRES Y APELLIDOS', $datos['apoderado_nombre']) . '</td><td width="35%">' . sub('CÉDULA', $datos['apoderado_cedula']) . '</td></tr>
</table>
<table class="tabla-datos no-top">
    <tr><td width="15%">' . sub('N° PODER', $datos['apoderado_num_poder']) . '</td><td width="40%">' . sub('REGISTRO/NOTARÍA', $datos['apoderado_registro']) . '</td><td width="45%">' . generarCuadroFecha($fapo['d'], $fapo['m'], $fapo['a']) . '</td></tr>
</table>';

$mpdf->WriteHTML($html, HTMLParserMode::HTML_BODY);

// --- PÁGINA 2 ---
$mpdf->AddPage();

// H. INSERCION DOCUMENTO
$fins = fechaE($datos['ins_fecha_doc'] ?? ''); // OJO: Tu tabla tiene ins_h_... no fecha directa, ajustaré visual
$html2 = titulo('H Inserción por Documento Público') . '
<table class="tabla-datos"><tr><td width="35%">' . sub('AUTORIDAD', $datos['ins_h_autoridad']) . '</td><td width="30%">' . sub('N° DOC', $datos['ins_h_numero']) . '</td><td width="35%">' . sub('FECHA', 'Ver Extracto') . '</td></tr></table>
<table class="tabla-datos no-top"><tr><td height="40px">' . sub('EXTRACTO', $datos['ins_h_extracto']) . '</td></tr></table>';

// I. INSERCION JUDICIAL
$html2 .= titulo('I Inserción por Decisión Judicial') . '
<table class="tabla-datos"><tr><td width="35%">' . sub('TRIBUNAL', $datos['ins_i_tribunal']) . '</td><td width="30%">' . sub('SENTENCIA N°', $datos['ins_i_sentencia']) . '</td><td width="35%">' . sub('FECHA', 'Ver Extracto') . '</td></tr></table>
<table class="tabla-datos no-top"><tr><td width="65%">' . sub('JUEZ', $datos['ins_i_juez']) . '</td><td width="35%">' . sub('FECHA', '') . '</td></tr></table>
<table class="tabla-datos no-top"><tr><td height="40px">' . sub('EXTRACTO', $datos['ins_i_extracto']) . '</td></tr></table>';

// J, K, L (Notas)
$html2 .= titulo('J Disolución') . '<div style="border:1px solid #000; padding:5px; height:30px;">' . $datos['disolucion_datos'] . '</div>';
$html2 .= titulo('K Observaciones') . '<div style="border:1px solid #000; padding:5px; height:40px;">' . $datos['observaciones'] . '</div>';
$html2 .= titulo('L Documentos Presentados') . '<div style="border:1px solid #000; padding:5px; height:30px;">' . $datos['documentos_presentados'] . '</div>';

// FIRMAS
$html2 .= '<div class="titulo-seccion text-center">LEÍDA LA PRESENTE ACTA Y CONFORMES CON EL CONTENIDO DE LA MISMA, FIRMAN:</div>
<table width="100%" class="tabla-firmas-cuadros">
    <tr>' . generarCeldasFirma(['FIRMA UNIDO 1', 'HUELLA', 'FIRMA UNIDO 2', 'HUELLA'], 60) . '</tr>
    <tr><td colspan="2" class="cuadro-firma-largo" style="height:60px; vertical-align:bottom; text-align:center;">FIRMA REGISTRADOR</td><td colspan="2" class="cuadro-firma-largo" style="vertical-align:bottom; text-align:center;">SELLO</td></tr>
    <tr>' . generarCeldasFirma(['TESTIGO 1', 'HUELLA', 'TESTIGO 2', 'HUELLA'], 60) . '</tr>
</table>';

// M. NOTA MARGINAL
$html2 .= titulo('M Nota Marginal') . '<div style="border:1px solid #000; padding:10px; height:100px;">' . $datos['nota_marginal'] . '</div>';

$mpdf->WriteHTML($html2, HTMLParserMode::HTML_BODY);
$mpdf->Output('Acta_Union_' . $num_acta . '.pdf', 'I');
