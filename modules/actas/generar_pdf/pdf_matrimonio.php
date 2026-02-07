<?php
require_once __DIR__ . '/../../../vendor/autoload.php';
include_once '../../../includes/db/config.php';
include '../../../includes/db/conexion.php';

use Mpdf\HTMLParserMode;

// 1. VALIDACIÓN Y CONSULTA DE DATOS
$num_acta = $_GET['numero_acta'] ?? '';

if (empty($num_acta)) {
    die("Error: No se especificó el número de acta.");
}

// 2. CONSULTA ACTUALIZADA (SNAPSHOT + RELACIONES)
// Se mantienen los datos personales base de la tabla 'personas', pero se priorizan los datos
// congelados (Snapshot) de la tabla 'matrimonio' para lo que cambia (profesión, residencia, estado civil).
$sql = "SELECT m.*, 
        -- Esposo (u1)
        p1.primer_nombre as u1_n1, p1.segundo_nombre as u1_n2, p1.primer_apellido as u1_a1, p1.segundo_apellido as u1_a2, 
        p1.cedula as u1_ced, p1.fecha_nacimiento as u1_fnac, p1.nacionalidad as u1_nac,
        p1.pais_nacimiento as u1_pais, p1.estado_nacimiento as u1_edo, p1.municipio_nacimiento as u1_mun, p1.parroquia_nacimiento as u1_par,
        
        -- Esposa (u2)
        p2.primer_nombre as u2_n1, p2.segundo_nombre as u2_n2, p2.primer_apellido as u2_a1, p2.segundo_apellido as u2_a2, 
        p2.cedula as u2_ced, p2.fecha_nacimiento as u2_fnac, p2.nacionalidad as u2_nac,
        p2.pais_nacimiento as u2_pais, p2.estado_nacimiento as u2_edo, p2.municipio_nacimiento as u2_mun, p2.parroquia_nacimiento as u2_par,
        
        -- Autoridad (ra)
        ra.primer_nombre as r_n1, ra.segundo_nombre as r_n2, ra.primer_apellido as r_a1, ra.segundo_apellido as r_a2, ra.cedula as r_ced,
        
        -- Testigos (t1, t2)
        t1.primer_nombre as t1_n1, t1.primer_apellido as t1_a1, t1.cedula as t1_ced, t1.nacionalidad as t1_nac, t1.fecha_nacimiento as t1_fnac,
        t2.primer_nombre as t2_n1, t2.primer_apellido as t2_a1, t2.cedula as t2_ced, t2.nacionalidad as t2_nac, t2.fecha_nacimiento as t2_fnac
        
        FROM matrimonio m
        LEFT JOIN personas p1 ON m.id_contrayente1 = p1.id_persona
        LEFT JOIN personas p2 ON m.id_contrayente2 = p2.id_persona
        LEFT JOIN personas ra ON m.id_autoridad = ra.id_persona
        LEFT JOIN personas t1 ON m.id_t1 = t1.id_persona
        LEFT JOIN personas t2 ON m.id_t2 = t2.id_persona
        WHERE m.numero_acta = ? LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $num_acta);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    die("Error: Acta no encontrada.");
}

$d = $res->fetch_assoc();

// 3. CONSULTA DE HIJOS (Relacionados al ID de matrimonio)
$hijos = [];
if (!empty($d['id'])) {
    $sql_h = "SELECT * FROM matrimonio_hijos WHERE id_matrimonio = ?";
    $stmt_h = $conn->prepare($sql_h);
    $stmt_h->bind_param("i", $d['id']);
    $stmt_h->execute();
    $res_h = $stmt_h->get_result();
    while ($row = $res_h->fetch_assoc()) {
        $hijos[] = $row;
    }
}

// --- FUNCIONES DE AYUDA ---
function fechaE($fecha)
{
    if (!$fecha || $fecha == '0000-00-00') return ['d' => '', 'm' => '', 'a' => ''];
    $t = strtotime($fecha);
    $meses = [1 => "ENERO", 2 => "FEBRERO", 3 => "MARZO", 4 => "ABRIL", 5 => "MAYO", 6 => "JUNIO", 7 => "JULIO", 8 => "AGOSTO", 9 => "SEPTIEMBRE", 10 => "OCTUBRE", 11 => "NOVIEMBRE", 12 => "DICIEMBRE"];
    return [
        'd' => date('d', $t),
        'm' => $meses[date('n', $t)],
        'a' => date('Y', $t)
    ];
}
function edad($fnac, $fref)
{
    if (!$fnac) return '';
    try {
        return date_diff(date_create($fnac), date_create($fref ?: 'today'))->y;
    } catch (Exception $e) {
        return '';
    }
}
function chk($val)
{
    return $val ? '✓' : '&nbsp;';
}

// Preparar variables fecha registro
$fr = fechaE($d['fecha_registro']);
// Preparar variables fecha matrimonio
// CORRECCIÓN: Usar 'fecha_celebracion' que es la columna real en BD
$fecha_acto = $d['fecha_celebracion'] ?? $d['fecha_registro'];
$fm = fechaE($fecha_acto);
// Preparar variables fecha resolución y gaceta
$fres = fechaE($d['resolucion_fecha']);
$fgac = fechaE($d['gaceta_fecha']);
// Hora
// CORRECCIÓN: Usar 'hora_celebracion' que es la columna real en BD
$hora_f = $d['hora_celebracion'] ? date('h:i', strtotime($d['hora_celebracion'])) : '';
$es_am = $d['hora_celebracion'] ? (date('A', strtotime($d['hora_celebracion'])) == 'AM') : false;

// Determinar Checks de Tipo de Registro
$is_art66 = ($d['modo_registro'] === 'art_66');
$is_art70 = ($d['modo_registro'] === 'art_70');
$is_ins   = ($d['modo_registro'] === 'insercion');
$is_norm  = ($d['modo_registro'] === 'normal');

// Decodificación de JSONs (Snapshot de Estado Civil)
$c1_ant = json_decode($d['c1_datos_anterior'] ?? '[]', true);
$c2_ant = json_decode($d['c2_datos_anterior'] ?? '[]', true);

// --- INICIO MPDF ---
$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'Legal',
    'margin_top' => 10,
    'margin_left' => 10,
    'margin_right' => 10
]);

$h_texto = '12px';
$h_min   = '8px';
$f_label = '5pt';

// Cargar CSS (Asegúrate que la ruta sea correcta)
$stylesheet = file_get_contents('../../../assets/css/estilos_pdf.css');
$mpdf->WriteHTML($stylesheet, HTMLParserMode::HEADER_CSS);

// --- HELPER FUNCTIONS DEL FORMATO ---
function getTitulo($texto, $extraStyle = '')
{
    return "<div class='titulo-seccion' style='$extraStyle'>$texto</div>";
}
function getColsFecha($h_min)
{
    return "
    <td width='15%' style='border-right: 1px solid #000; text-align: center; padding: 0;'>
        <div style='font-size: 5pt; border-bottom: 0.5px solid #000;'>DÍA</div><div style='height: $h_min;'></div>
    </td>
    <td width='20%' style='border-right: 1px solid #000; text-align: center; padding: 0;'>
        <div style='font-size: 5pt; border-bottom: 0.5px solid #000;'>MES</div><div style='height: $h_min;'></div>
    </td>
    <td width='15%' style='text-align: center; padding: 0;'>
        <div style='font-size: 5pt; border-bottom: 0.5px solid #000;'>AÑO</div><div style='height: $h_min;'></div>
    </td>";
}
function getColsFechaVal($d, $m, $a, $h_min)
{ // Versión con valores
    return "
    <td width='15%' style='border-right: 1px solid #000; text-align: center; padding: 0;'>
        <div style='font-size: 5pt; border-bottom: 0.5px solid #000;'>DÍA</div><div style='height: $h_min; font-size:10px;'>$d</div>
    </td>
    <td width='20%' style='border-right: 1px solid #000; text-align: center; padding: 0;'>
        <div style='font-size: 5pt; border-bottom: 0.5px solid #000;'>MES</div><div style='height: $h_min; font-size:8px;'>$m</div>
    </td>
    <td width='15%' style='text-align: center; padding: 0;'>
        <div style='font-size: 5pt; border-bottom: 0.5px solid #000;'>AÑO</div><div style='height: $h_min; font-size:10px;'>$a</div>
    </td>";
}
function getBloqueLineas($num = 3)
{
    return str_repeat('<div style="border-bottom: 1px dotted #000; margin-top: 20px;"></div>', $num);
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

// --- PÁGINA 1: CONSTRUCCIÓN DEL HTML ---
$html = '
<table class="header-table">
    <tr>
        <td width="35%" class="texto-institucional">
            <b>República Bolivariana de Venezuela</b><br><b>Consejo Nacional Electoral</b><br><b>Comisión de Registro Civil y Electoral</b><br>
            Estado <span class="linea-rellenable">Yaracuy</span><br>Municipio <span class="linea-rellenable">Peña</span><br>Parroquia <span class="linea-rellenable">Yaritagua</span>
        </td>
        <td width="30%" style="text-align: center;">
            <div class="logo-cne"><img class="img-cne" src="../../../assets/img/CNE_23_.png"><div class="poder-electoral">PODER ELECTORAL</div></div>
        </td>
        <td width="35%">
            <table class="tabla-acta-header">
                <tr><td class="text-right">ACTA N°</td><td class="borde-bottom-acta"><b>' . $d['numero_acta'] . '</b></td></tr>
                <tr><td class="text-right">DÍA</td><td class="borde-bottom-acta"><b>' . $fr['d'] . '</b></td></tr>
                <tr><td class="text-right">MES</td><td class="borde-bottom-acta"><b>' . $fr['m'] . '</b></td></tr>
                <tr><td class="text-right">AÑO</td><td class="borde-bottom-acta"><b>' . $fr['a'] . '</b></td></tr>
            </table>
        </td>
    </tr>
</table>

<table width="100%" class="tabla-tipo-registro">
    <tr>
        <td>REGISTRO DE MATRIMONIO <span class="check-box">' . chk($is_norm) . '</span></td>
        <td style="text-align: center;">ARTÍCULO 66 <span class="check-box">' . chk($is_art66) . '</span></td>
        <td style="text-align: center;">ARTÍCULO 70 <span class="check-box">' . chk($is_art70) . '</span></td>
        <td style="text-align: right;">INSERCIÓN <span class="check-box">' . chk($is_ins) . '</span></td>
    </tr>
</table>

' . getTitulo('A Datos del Registrador (a) Civil') . '
<table class="tabla-datos">
    <tr><td width="50%"><span class="sub-label">NOMBRES <span style="font-size: 12px;">' . ($d['r_n1'] . ' ' . $d['r_n2']) . '</span></span></td><td width="50%"><span class="sub-label">APELLIDOS <span style="font-size: 12px">' . ($d['r_a1'] . ' ' . $d['r_a2']) . '</span></span></td></tr>
    <tr><td><span class="sub-label">DOCUMENTO DE IDENTIDAD N° <span style="font-size: 12px">' . $d['r_ced'] . '</span></span></td><td><span class="sub-label">OFICINA O UNIDAD DE REGISTRO CIVIL <span style="font-size: 12px">REGISTRO CIVIL YARITAGUA</span></span></td></tr>
</table>
<table class="tabla-datos no-top">
    <tr>
        <td width="30%"><span class="sub-label">RESOLUCIÓN N° <br><span style="font-size:11px;color:#000;">' . ($d['resolucion_numero'] ?? '') . '</span></span></td>
        <td width="15%"><span class="sub-label">FECHA <br><span style="font-size:11px;color:#000;">' . ($fres['d'] . '/' . $fres['m'] . '/' . $fres['a']) . '</span></span></td>
        <td width="20%"><span class="sub-label">GACETA N° <br><span style="font-size:11px;color:#000;">' . ($d['gaceta_numero'] ?? '') . '</span></span></td>
        <td width="20%"><div class="gaceta-checks"><span class="check-box">✓</span> MUNICIPAL<br><span class="check-box"> </span> OFICIAL</div></td>
        <td width="15%"><span class="sub-label">FECHA <br><span style="font-size:11px;color:#000;">' . ($fgac['d'] . '/' . $fgac['m'] . '/' . $fgac['a']) . '</span></span></td>
    </tr>
</table>

' . getTitulo('B Lugar, Hora y Fecha de Celebración del Matrimonio') . '
<table class="tabla-datos" style="width:100%; border-collapse: collapse; border: 1px solid #000;">
    <tr>
        <td width="42%" style="border-right: 1px solid #000; vertical-align: top; padding: 1px;"><span class="sub-label" style="font-size: ' . $f_label . '">LUGAR</span><div style="height: ' . $h_min . '; font-size:9px;">' . $d['lugar_matrimonio'] . '</div></td>
        <td width="12%" style="border-right: 1px solid #000; vertical-align: top; padding: 1px;"><span class="sub-label" style="font-size: ' . $f_label . '">HORA</span><div style="height: ' . $h_min . '; font-size:10px;">' . $hora_f . '</div></td>
        <td width="8%" style="border-right: 1px solid #000; text-align: center; font-size: 6pt; padding: 0; vertical-align: middle;">
            <div style="line-height: 7px;"><span class="check-box" style="width:7px; height:7px;">' . ($es_am ? '✓' : '') . '</span> AM</div>
            <div style="line-height: 7px; margin-top: 1px;"><span class="check-box" style="width:7px; height:7px;">' . (!$es_am && $hora_f ? '✓' : '') . '</span> PM</div>
        </td>
        <td width="10%" style="border-right: 1px solid #000; text-align: center; vertical-align: middle; padding: 1px;"><span class="sub-label" style="font-size: ' . $f_label . '">FECHA</span></td>
        ' . getColsFechaVal($fm['d'], $fm['m'], $fm['a'], $h_min) . '
    </tr>
</table>';

// --- SECCIONES C y D (CON DATOS DINÁMICOS CORREGIDOS) ---
function getContrayenteHTML($letra, $titulo, $h_texto, $p, $h_min, $ant_json)
{
    if (!$p) $p = []; // Evitar errores si null
    $nac = fechaE($p['fnac'] ?? '');

    // Parsear Estado Civil Anterior del JSON
    $edo_civil_txt = "SOLTERO(A)";
    if (!empty($ant_json)) {
        if (($ant_json['tipo'] ?? '') == 'DIVORCIADO') {
            $edo_civil_txt = "DIVORCIADO(A). Sentencia: " . ($ant_json['sentencia'] ?? '') . ", Tribunal: " . ($ant_json['tribunal'] ?? '') . ", Fecha: " . ($ant_json['fecha'] ?? '');
        } elseif (($ant_json['tipo'] ?? '') == 'VIUDO') {
            $edo_civil_txt = "VIUDO(A). Acta Defunción: " . ($ant_json['acta'] ?? '') . ", Fecha: " . ($ant_json['fecha'] ?? '');
        }
    }

    return getTitulo("$letra Datos del $titulo") . '
    <table class="tabla-datos" style="width:100%; border-collapse: collapse; border: 1px solid #000;">
        <tr>
            <td width="50%" style="border-right: 1px solid #000;"><span class="sub-label">PRIMER APELLIDO</span><div style="height: ' . $h_texto . '; font-size:11px;">' . ($p['a1'] ?? '') . '</div></td>
            <td width="50%"><span class="sub-label">SEGUNDO APELLIDO</span><div style="height: ' . $h_texto . '; font-size:11px;">' . ($p['a2'] ?? '') . '</div></td>
        </tr>
    </table>
    <table class="tabla-datos no-top" style="width:100%; border-collapse: collapse; border: 1px solid #000; border-top:none;">
        <tr>
            <td width="55%" style="border-right: 1px solid #000;"><span class="sub-label">NOMBRES</span><div style="height: ' . $h_texto . '; font-size:11px;">' . ($p['n1'] ?? '') . ' ' . ($p['n2'] ?? '') . '</div></td>
            <td width="45%"><span class="sub-label">ESTADO CIVIL ANTERIOR</span><div style="height: ' . $h_texto . '; font-size:9px;">' . $edo_civil_txt . '</div></td>
        </tr>
    </table>
    <table class="tabla-datos no-top" style="width:100%; border-collapse: collapse; border: 1px solid #000; border-top:none;">
        <tr>
            <td width="12%" style="border-right: 1px solid #000;"><span class="sub-label">FECHA DE<br>NACIMIENTO</span></td>
            <td width="7%" style="border-right: 1px solid #000; text-align:center;"><span class="sub-label">DÍA</span><br><span style="font-size:9px">' . ($nac['d']) . '</span></td>
            <td width="13%" style="border-right: 1px solid #000; text-align:center;"><span class="sub-label">MES</span><br><span style="font-size:7px">' . ($nac['m']) . '</span></td>
            <td width="10%" style="border-right: 1px solid #000; text-align:center;"><span class="sub-label">AÑO</span><br><span style="font-size:9px">' . ($nac['a']) . '</span></td>
            <td width="8%" style="border-right: 1px solid #000; text-align:center;"><span class="sub-label">EDAD</span><br><span style="font-size:10px">' . edad($p['fnac'] ?? '', $p['f_acto']) . '</span></td>
            <td width="25%" style="border-right: 1px solid #000;"><span class="sub-label">DOCUMENTO DE IDENTIDAD</span></td>
            <td width="12%" style="border-right: 1px solid #000; text-align:center;"><span class="sub-label">CÉDULA</span><br><span style="font-size:10px">' . ($p['ced'] ?? '') . '</span></td>
            <td width="13%" style="text-align:center;"><span class="sub-label">PASAPORTE</span><br><span class="check-box">&nbsp;</span></td>
        </tr>
    </table>
    <table class="tabla-datos no-top" style="width:100%; border-collapse: collapse; border: 1px solid #000; border-top:none;">
        <tr>
            <td width="12%" style="border-right: 1px solid #000;"><span class="sub-label">LUGAR DE<br>NACIMIENTO</span></td>
            <td width="20%" style="border-right: 1px solid #000;"><span class="sub-label">PAÍS</span><div style="height: 12px; font-size:9px;">' . ($p['pais'] ?? 'VENEZUELA') . '</div></td>
            <td width="23%" style="border-right: 1px solid #000;"><span class="sub-label">ESTADO</span><div style="height: 12px; font-size:9px;">' . ($p['edo'] ?? '') . '</div></td>
            <td width="23%" style="border-right: 1px solid #000;"><span class="sub-label">MUNICIPIO</span><div style="height: 12px; font-size:9px;">' . ($p['mun'] ?? '') . '</div></td>
            <td width="22%"><span class="sub-label">PARROQUIA</span><div style="height: 12px; font-size:9px;">' . ($p['par'] ?? '') . '</div></td>
        </tr>
    </table>
    <table class="tabla-datos no-top" style="width:100%; border-collapse: collapse; border: 1px solid #000; border-top:none;">
        <tr>
            <td width="26%" style="border-right: 1px solid #000;"><span class="sub-label">NACIONALIDAD</span><div style="height: ' . $h_texto . '; font-size:10px;">' . ($p['nac'] ?? '') . '</div></td>
            <td width="29%" style="border-right: 1px solid #000;"><span class="sub-label">PROFESIÓN U OCUPACIÓN</span><div style="height: ' . $h_texto . '; font-size:10px;">' . ($p['prof'] ?? '') . '</div></td>
            <td width="45%"><span class="sub-label">RESIDENCIA</span><div style="height: ' . $h_texto . '; font-size:9px;">' . ($p['res'] ?? '') . '</div></td>
        </tr>
    </table>';
}

// Preparar arrays con nombres de columnas SNAPSHOT (c1_profesion, c1_residencia, etc.)
$p1 = [
    'n1' => $d['u1_n1'],
    'n2' => $d['u1_n2'],
    'a1' => $d['u1_a1'],
    'a2' => $d['u1_a2'],
    'ced' => $d['u1_ced'],
    'fnac' => $d['u1_fnac'],
    'f_acto' => $fecha_acto,
    'nac' => $d['u1_nac'],
    'prof' => $d['c1_profesion'], // Dato Snapshot
    'res' => $d['c1_residencia'], // Dato Snapshot
    'pais' => $d['u1_pais'],
    'edo' => $d['u1_edo'],
    'mun' => $d['u1_mun'],
    'par' => $d['u1_par']
];
$p2 = [
    'n1' => $d['u2_n1'],
    'n2' => $d['u2_n2'],
    'a1' => $d['u2_a1'],
    'a2' => $d['u2_a2'],
    'ced' => $d['u2_ced'],
    'fnac' => $d['u2_fnac'],
    'f_acto' => $fecha_acto,
    'nac' => $d['u2_nac'],
    'prof' => $d['c2_profesion'], // Dato Snapshot
    'res' => $d['c2_residencia'], // Dato Snapshot
    'pais' => $d['u2_pais'],
    'edo' => $d['u2_edo'],
    'mun' => $d['u2_mun'],
    'par' => $d['u2_par']
];

$html .= getContrayenteHTML('C', 'Contrayente 1', $h_texto, $p1, $h_min, $c1_ant);
$html .= getContrayenteHTML('D', 'Contrayente 2', $h_texto, $p2, $h_min, $c2_ant);

// --- SECCIONES E, F, G, H, I ---
// E. HIJOS (Dinámico desde BD)
$html .= getTitulo('E Datos de Hijos o Hijas (Declarados)');
$filas_hijos = '';
$count_h = 0;
foreach ($hijos as $idx => $h) {
    $count_h++;
    $num = $idx + 1;
    $rec = ($h['reconocido'] == 'SI') ? 'SI <span class="check-box">✓</span> NO <span class="check-box">&nbsp;</span>' : 'SI <span class="check-box">&nbsp;</span> NO <span class="check-box">✓</span>';

    $filas_hijos .= '
    <table class="tabla-datos ' . ($num > 1 ? 'no-top' : '') . '">
        <tr>
            <td width="40%"><span class="sub-label" style="font-size: ' . $f_label . '">' . $num . ') NOMBRES Y APELLIDOS</span><div style="height: ' . $h_min . '; font-size:10px;">' . $h['nombre_hijo'] . '</div></td>
            <td width="25%"><span class="sub-label" style="font-size: ' . $f_label . '">ACTA NAC. N°</span><div style="height: ' . $h_min . '; font-size:10px;">' . $h['acta_hijo'] . '</div></td>
            <td width="20%" style="text-align:center; vertical-align: middle;"><span style="font-size: 5pt; display: block; border-bottom: 0.5px solid #000;">RECONOCIDO (A)</span><div style="font-size: 7pt; padding-top: 2px;">' . $rec . '</div></td>
            <td width="15%"><span class="sub-label" style="font-size: ' . $f_label . '">FIRMA</span><div style="height: ' . $h_min . ';"></div></td>
        </tr>
    </table>';
}
if ($count_h == 0) {
    // Si no hay hijos, mostrar estructura vacía
    $html .= '
    <table class="tabla-datos" style="width:100%; border-collapse: collapse; border: 1px solid #000;">
        <tr>
            <td width="45%" style="border-right: 1px solid #000; padding: 1px;"><span class="sub-label" style="font-size: ' . $f_label . '">1) NOMBRES Y APELLIDOS</span><div style="height: ' . $h_min . ';"></div></td>
            <td width="20%" style="border-right: 1px solid #000; padding: 1px;"><span class="sub-label" style="font-size: ' . $f_label . '">CÉDULA DE IDENTIDAD</span><div style="height: ' . $h_min . ';"></div></td>
            <td width="8%" style="border-right: 1px solid #000; text-align:center; padding: 1px;"><span class="sub-label" style="font-size: ' . $f_label . '">EDAD</span><div style="height: ' . $h_min . ';"></div></td>
            <td width="27%" style="padding: 1px;"><span class="sub-label" style="font-size: ' . $f_label . '">ACTA DE NACIMIENTO N° / FECHA</span><div style="height: ' . $h_min . ';"></div></td>
        </tr>
    </table>
    <table class="tabla-datos no-top" style="width:100%; border-collapse: collapse; border: 1px solid #000; border-top:none;">
        <tr>
            <td width="65%" style="border-right: 1px solid #000; padding: 1px;"><span class="sub-label" style="font-size: ' . $f_label . '">OFICINA O UNIDAD DE REGISTRO CIVIL</span><div style="height: ' . $h_min . ';"></div></td>
            <td width="15%" style="border-right: 1px solid #000; text-align: center; vertical-align: middle; padding: 0;"><span style="font-size: 5pt; display: block; border-bottom: 0.5px solid #000;">RECONOCIDO (A)</span><div style="font-size: 7pt; padding-top: 2px;">SÍ <span class="check-box" style="width:7px; height:7px;">&nbsp;</span> NO <span class="check-box" style="width:7px; height:7px;">&nbsp;</span></div></td>
            <td width="20%" style="text-align: center; vertical-align: middle; padding: 1px;"><span class="sub-label" style="font-size: ' . $f_label . '">FIRMA</span><div style="height: ' . $h_min . ';"></div></td>
        </tr>
    </table>';
} else {
    $html .= $filas_hijos;
}

// F. UNION ESTABLE
$html .= getTitulo('F Datos de la Unión Estable de Hecho (sólo en caso de artículo 70)') . '
<table class="tabla-datos" style="width:100%; border-collapse: collapse; border: 1px solid #000;">
    <tr>
        <td width="40%" style="border-right: 1px solid #000; vertical-align: top; padding: 1px;"><span class="sub-label" style="font-size: ' . $f_label . '">N° DE ACTA</span><div style="height: ' . $h_min . ';"></div></td>
        <td width="10%" style="border-right: 1px solid #000; text-align: center; vertical-align: middle; padding: 1px;"><span class="sub-label" style="font-size: ' . $f_label . '">FECHA</span></td>
        ' . getColsFecha($h_min) . '
    </tr>
</table>
<table class="tabla-datos no-top" style="width:100%; border-collapse: collapse; border: 1px solid #000; border-top:none;">
    <tr><td width="100%" style="vertical-align: top; padding: 1px;"><span class="sub-label" style="font-size: ' . $f_label . '">OFICINA O UNIDAD DE REGISTRO CIVIL</span><div style="height: ' . $h_min . ';"></div></td></tr>
</table>';

// G. CAPITULACIONES (CORREGIDO PARA LEER DE BD)
$tiene_cap = !empty($d['cap_numero']);
$fcap = fechaE($d['cap_fecha'] ?? '');

$html .= getTitulo('G Capitulaciones Matrimoniales &nbsp;&nbsp;&nbsp; SI <span class="check-box" style="width:8px; height:8px;">' . chk($tiene_cap) . '</span> &nbsp;&nbsp; NO <span class="check-box" style="width:8px; height:8px;">' . chk(!$tiene_cap) . '</span>') . '
<table class="tabla-datos" style="width:100%; border-collapse: collapse; border: 1px solid #000;">
    <tr>
        <td width="15%" style="border-right: 1px solid #000; vertical-align: top; padding: 1px;"><span class="sub-label" style="font-size: ' . $f_label . '">N° REGISTRO</span><div style="height: ' . $h_min . '; font-size:10px;">' . ($d['cap_numero'] ?? '') . '</div></td>
        <td width="12%" style="border-right: 1px solid #000; vertical-align: top; padding: 1px;"><span class="sub-label" style="font-size: ' . $f_label . '">LIBRO</span><div style="height: ' . $h_min . '; font-size:10px;">' . ($d['cap_libro'] ?? '') . '</div></td>
        <td width="13%" style="border-right: 1px solid #000; vertical-align: top; padding: 1px;"><span class="sub-label" style="font-size: ' . $f_label . '">PROTOCOLO</span><div style="height: ' . $h_min . '; font-size:10px;">' . ($d['cap_protocolo'] ?? '') . '</div></td>
        <td width="10%" style="border-right: 1px solid #000; text-align: center; vertical-align: middle; padding: 1px;"><span class="sub-label" style="font-size: ' . $f_label . '">FECHA</span></td>
        ' . getColsFechaVal($fcap['d'], $fcap['m'], $fcap['a'], $h_min) . '
    </tr>
</table>
<table class="tabla-datos no-top" style="width:100%; border-collapse: collapse; border: 1px solid #000; border-top:none;">
    <tr><td width="100%" style="vertical-align: top; padding: 1px;"><span class="sub-label" style="font-size: ' . $f_label . '">AUTORIDAD QUE LO EXPIDE</span><div style="height: ' . $h_min . '; font-size:10px;">' . ($d['cap_autoridad'] ?? '') . '</div></td></tr>
</table>';

// H. APODERADO (CORREGIDO PARA LEER DE BD)
$fapo = fechaE($d['apoderado_fecha'] ?? '');
$html .= getTitulo('H Datos del Apoderado (llenar sólo en caso de matrimonios por Apoderado)') . '
<table class="tabla-datos" style="width:100%; border-collapse: collapse; border: 1px solid #000;">
    <tr>
        <td width="70%" style="border-right: 1px solid #000; vertical-align: top; padding: 1px;"><span class="sub-label" style="font-size: ' . $f_label . '">NOMBRES Y APELLIDOS</span><div style="height: ' . $h_min . '; font-size:10px;">' . ($d['apoderado_nombre'] ?? '') . '</div></td>
        <td width="30%" style="vertical-align: top; padding: 1px;"><span class="sub-label" style="font-size: ' . $f_label . '">DOCUMENTO DE IDENTIDAD N°</span><div style="height: ' . $h_min . '; font-size:10px;">' . ($d['apoderado_cedula'] ?? '') . '</div></td>
    </tr>
</table>
<table class="tabla-datos no-top" style="width:100%; border-collapse: collapse; border: 1px solid #000; border-top:none;">
    <tr>
        <td width="35%" style="border-right: 1px solid #000; vertical-align: top; padding: 1px;"><span class="sub-label" style="font-size: ' . $f_label . '">REGISTRO O NOTARÍA</span><div style="height: ' . $h_min . '; font-size:10px;">' . ($d['apoderado_registro'] ?? '') . '</div></td>
        <td width="15%" style="border-right: 1px solid #000; vertical-align: top; padding: 1px;"><span class="sub-label" style="font-size: ' . $f_label . '">N°</span><div style="height: ' . $h_min . '; font-size:10px;">' . ($d['apoderado_num_poder'] ?? '') . '</div></td>
        <td width="10%" style="border-right: 1px solid #000; text-align: center; vertical-align: middle; padding: 1px;"><span class="sub-label" style="font-size: ' . $f_label . '">FECHA</span></td>
        ' . getColsFechaVal($fapo['d'], $fapo['m'], $fapo['a'], $h_min) . '
    </tr>
</table>';

// I. TESTIGOS (CORREGIDO PARA USAR SNAPSHOT DE BD)
$html .= getTitulo('I Datos de los Testigos');
$testigos = [
    1 => ['n' => ($d['t1_n1'] ?? '') . ' ' . ($d['t1_a1'] ?? ''), 'ced' => $d['t1_ced'] ?? '', 'edad' => edad($d['t1_fnac'] ?? '', $fecha_acto), 'nac' => $d['t1_nac'] ?? '', 'prof' => $d['t1_profesion'] ?? '', 'res' => $d['t1_residencia'] ?? ''],
    2 => ['n' => ($d['t2_n1'] ?? '') . ' ' . ($d['t2_a1'] ?? ''), 'ced' => $d['t2_ced'] ?? '', 'edad' => edad($d['t2_fnac'] ?? '', $fecha_acto), 'nac' => $d['t2_nac'] ?? '', 'prof' => $d['t2_profesion'] ?? '', 'res' => $d['t2_residencia'] ?? '']
];

foreach ($testigos as $i => $t) {
    $borderTop = ($i > 1) ? 'border-top:none;' : '';
    $html .= '
    <table class="tabla-datos" style="width:100%; border-collapse: collapse; border: 1px solid #000; ' . $borderTop . '">
        <tr>
            <td width="50%" style="border-right: 1px solid #000; vertical-align: top;"><span class="sub-label">NOMBRES Y APELLIDOS</span><div style="height: ' . $h_texto . '; font-size:10px;">' . $t['n'] . '</div></td>
            <td width="30%" style="border-right: 1px solid #000; vertical-align: top;"><span class="sub-label">DOCUMENTO DE IDENTIDAD</span><div style="height: ' . $h_texto . '; font-size:10px;">' . $t['ced'] . '</div></td>
            <td width="20%" style="vertical-align: top;"><span class="sub-label">EDAD</span><div style="height: ' . $h_texto . '; font-size:10px;">' . $t['edad'] . '</div></td>
        </tr>
    </table>
    <table class="tabla-datos no-top" style="width:100%; border-collapse: collapse; border: 1px solid #000; border-top:none;">
        <tr><td width="35%" style="border-right: 1px solid #000; vertical-align: top;"><span class="sub-label">NACIONALIDAD</span><div style="height: ' . $h_texto . '; font-size:10px;">' . $t['nac'] . '</div></td><td width="65%" style="vertical-align: top;"><span class="sub-label">PROFESIÓN U OCUPACIÓN</span><div style="height: ' . $h_texto . '; font-size:10px;">' . $t['prof'] . '</div></td></tr>
    </table>
    <table class="tabla-datos no-top" style="width:100%; border-collapse: collapse; border: 1px solid #000; border-top:none;">
        <tr><td width="100%" style="vertical-align: top;"><span class="sub-label">RESIDENCIA</span><div style="height: ' . $h_texto . '; font-size:9px;">' . $t['res'] . '</div></td></tr>
    </table>';
}

$mpdf->WriteHTML($html, HTMLParserMode::HTML_BODY);

// --- PÁGINA 2 ---
$mpdf->AddPage();

$seccionJ = getTitulo('J Lectura de los Deberes y Derechos de los Cónyuges', 'background-color:#555; color:white; border:1px solid #000;') . '
<div style="border: 1px solid #000; border-top: none; padding: 10px; font-family: sans-serif;">
    <p style="font-size: 10pt; line-height: 1.4; text-align: justify; margin: 0; color: #000;">
        • Con el matrimonio el marido y la mujer adquieren los mismos derechos y asumen los mismos deberes. Del matrimonio deriva la obligación de los cónyuges de vivir juntos, guardarse fidelidad y socorrerse mutuamente.<br>
        • La mujer casada podrá usar el apellido del marido. Este derecho subsiste aún después de la disolución del matrimonio por causa de muerte, mientras no contraiga nuevas nupcias. La negativa de la mujer casada a usar el apellido del marido no se considerará, en ningún caso, como falta a los deberes que la Ley impone por efecto del matrimonio.<br>
        • El marido y la mujer están obligados a contribuir en la medida de los recursos de cada uno, al cuidado y mantenimiento del hogar común, y a las cargas y demás gastos matrimoniales.<br>
        • En esta misma forma ambos cónyuges deben asistirse recíprocamente en la satisfacción de sus necesidades. Esta obligación cesa para con el cónyuge que se separe del hogar sin justa causa.<br>
        • El cónyuge que dejare de cumplir, sin causa justificada, con estas obligaciones, podrá ser obligado judicialmente a ello, a solicitud del otro.
    </p>
</div>';

$seccionK = getTitulo('K Aceptación de los Contrayentes', 'background-color:#555; color:white; border:1px solid #000; border-top:none;') . '
<div style="border: 1px solid #000; border-top: none; padding: 10px; font-family: sans-serif;">
    <p style="font-size: 9.5pt; line-height: 1.4; text-align: justify; margin: 0; color: #000;">
        Se le preguntó a los contrayentes si se reciben como marido y mujer, a lo cual respondieron afirmativamente y en consecuencia se les declaró unidos en matrimonio civil en nombre de la República Bolivariana de Venezuela y por la autoridad de la Ley.
    </p>
</div>';

// L. DATOS INSERCIÓN (Dinámicos CORREGIDOS PARA BD)
$fins = fechaE($d['ins_fecha_doc'] ?? '');
$seccionL = '
<table width="100%" style="border-collapse: collapse; font-family: sans-serif; border: 1px solid #000; border-top: none; background-color: #555; color: white;">
    <tr>
        <td style="padding: 4px 10px; font-weight: bold; font-size: 11pt; vertical-align: middle;">
            L Datos del Documento a Insertar
        </td>
        <td width="35%" style="padding: 0; border-left: 1px solid #000;">
            <table width="100%" style="border-collapse: collapse; font-size: 6pt; text-align: center; color: white;">
                <tr>
                    <td rowspan="2" width="25%" style="border-right: 1px solid #fff; font-weight: bold; font-size: 8pt;">FECHA</td>
                    <td width="25%" style="border-right: 1px solid #fff; border-bottom: 0.5px solid #fff;">DÍA</td>
                    <td width="25%" style="border-right: 1px solid #fff; border-bottom: 0.5px solid #fff;">MES</td>
                    <td width="25%" style="border-bottom: 0.5px solid #fff;">AÑO</td>
                </tr>
                <tr>
                    <td style="height: ' . $h_min . '; border-right: 1px solid #fff; font-size:9px;">' . $fins['d'] . '</td>
                    <td style="border-right: 1px solid #fff; font-size:7px;">' . $fins['m'] . '</td>
                    <td style="font-size:9px;">' . $fins['a'] . '</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<div style="border: 1px solid #000; border-top: none; padding: 5px 10px 10px 10px; font-family: sans-serif;">
    <div style="margin-top: 5px;">
        <span style="font-size: ' . $f_label . '; display: block;">N° DEL DOCUMENTO</span>
        <div style="border-bottom: 1px solid #000; height: ' . $h_texto . '; margin-bottom: 10px; font-size:10px;">' . ($d['ins_numero_doc'] ?? '') . '</div>
    </div>
    
    <div>
        <span style="font-size: ' . $f_label . '; display: block;">AUTORIDAD QUE LO EXPIDE</span>
        <div style="border-bottom: 1px solid #000; height: ' . $h_texto . '; margin-bottom: 10px; font-size:10px;">' . ($d['ins_autoridad'] ?? '') . '</div>
    </div>
    
    <div>
        <span style="font-size: ' . $f_label . '; display: block;">EXTRACTO DEL DOCUMENTO</span>
        <div style="font-size:10px; text-align:justify; margin-top:5px;">' . ($d['ins_extracto'] ?? '') . '</div>
        ' . getBloqueLineas(1) . '
    </div>
</div>';

// M, N y O (Observaciones y Notas)
$seccionMyN = getTitulo('M Circunstancias Especiales del Acto/Observaciones', 'background-color:#555; color:white; border:1px solid #000; border-top:none;') . '
<div style="border: 1px solid #000; border-top: none; padding: 5px 10px 15px 10px; font-family: sans-serif; font-size:10px;">' . ($d['observaciones'] ?? '') . getBloqueLineas(2) . '</div>' .
    getTitulo('N Documentos que reposan en el expediente esponsalicio', 'background-color:#555; color:white; border:1px solid #000; border-top:none;') . '
<div style="border: 1px solid #000; border-top: none; padding: 5px 10px 15px 10px; font-family: sans-serif; font-size:10px;">' . ($d['documentos_presentados'] ?? '') . getBloqueLineas(2) . '</div>';

$seccionO = '
<div style="background-color: #555; color: white; padding: 4px 10px; font-weight: bold; font-size: 11pt; font-family: sans-serif; border: 1px solid #000; text-align: center;">LEÍDA LA PRESENTE ACTA Y CONFORMES CON EL CONTENIDO DE LA MISMA, FIRMAN:</div>
<table width="100%" style="border-collapse: collapse; font-family: sans-serif; border: 1px solid #000; border-top: none;">
    <tr>' . generarCeldasFirma(['FIRMA DEL CONTRAYENTE', 'IMPRESIÓN DACTILAR', 'FIRMA DE LA CONTRAYENTE', 'IMPRESIÓN DACTILAR'], 70) . '</tr>
    <tr>
        <td colspan="2" style="height: 70px; border: 1px solid #000; vertical-align: bottom; padding: 5px; text-align: center; font-size: 9pt; font-weight: bold;">FIRMA DEL REGISTRADOR (A)</td>
        <td colspan="2" style="height: 70px; border: 1px solid #000; vertical-align: bottom; padding: 5px; text-align: center; font-size: 9pt; font-weight: bold;">SELLO HÚMEDO</td>
    </tr>
    <tr>' . generarCeldasFirma(['FIRMA DEL TESTIGO 1', 'IMPRESIÓN DACTILAR', 'FIRMA DEL TESTIGO 2', 'IMPRESIÓN DACTILAR'], 70) . '</tr>
</table>';

$seccionP = getTitulo('O Nota Marginal', 'background-color:#555; color:white; border:1px solid #000; border-top:none;') . '
<div style="border: 1px solid #000; border-top: none; padding: 10px; font-family: sans-serif; min-height: 100px; font-size:10px;">' . ($d['nota_marginal'] ?? '') . getBloqueLineas(2) . '</div>';

$seccionQ = '
<div style="width: 100%; margin-top: 80px; text-align: center; font-family: sans-serif;">
    <table width="100%" style="border-collapse: collapse;"><tr><td align="center">
        <div style="width: 500px; border-top: 1.5px solid #000; padding-top: 5px; margin: 0 auto;">
            <span style="font-size: 9pt; font-weight: bold; letter-spacing: 1px; display: block;">FIRMA DEL REGISTRADOR (A) CIVIL</span>
            <span style="font-size: 7pt; display: block; margin-top: 2px;">SELLO HÚMEDO</span>
        </div>
    </td></tr></table>
</div>';

$mpdf->WriteHTML($seccionJ . $seccionK . $seccionL . $seccionMyN . $seccionO . $seccionP . $seccionQ);
$mpdf->Output('Acta_Matrimonio_' . $num_acta . '.pdf', 'I');
