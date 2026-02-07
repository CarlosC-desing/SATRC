<?php
require_once __DIR__ . '/../../../vendor/autoload.php';
// Asegúrate de que estas rutas sean correctas según tu estructura de carpetas
include_once __DIR__ . '/../../../includes/db/config.php';
include __DIR__ . '/../../../includes/db/conexion.php';

use Mpdf\HTMLParserMode;

// 1. VALIDACIÓN Y CONSULTA DE DATOS
if (!isset($_GET['numero_acta'])) {
    die("Error: No se especificó el número de acta.");
}

$numero_acta = $_GET['numero_acta'];

// --- CONSULTA OPTIMIZADA ---
$sql = "SELECT 
            n.*,
            -- Registrador (Siempre datos actuales o del momento de firma)
            reg.primer_nombre as reg_nom1, reg.primer_apellido as reg_ape1, reg.cedula as reg_cedula,
            
            -- Madre (Prioridad: Datos congelados en tabla nacimiento)
            mad.primer_nombre as mad_nom1, mad.segundo_nombre as mad_nom2, 
            mad.primer_apellido as mad_ape1, mad.segundo_apellido as mad_ape2, 
            mad.cedula as mad_cedula, mad.nacionalidad as mad_nac, 
            COALESCE(n.madre_profesion, mad.profesion) as mad_prof, 
            COALESCE(n.madre_residencia, mad.residencia) as mad_res, 
            mad.fecha_nacimiento as mad_fnac,

            -- Padre (Prioridad: Datos congelados en tabla nacimiento)
            pad.primer_nombre as pad_nom1, pad.segundo_nombre as pad_nom2, 
            pad.primer_apellido as pad_ape1, pad.segundo_apellido as pad_ape2, 
            pad.cedula as pad_cedula, pad.nacionalidad as pad_nac, 
            COALESCE(n.padre_profesion, pad.profesion) as pad_prof, 
            COALESCE(n.padre_residencia, pad.residencia) as pad_res, 
            pad.fecha_nacimiento as pad_fnac,

            -- Declarante (Generalmente datos de persona, salvo que coincida con padres)
            dec_p.primer_nombre as dec_nom1, dec_p.segundo_nombre as dec_nom2, 
            dec_p.primer_apellido as dec_ape1, dec_p.segundo_apellido as dec_ape2, 
            dec_p.cedula as dec_cedula, dec_p.nacionalidad as dec_nac, 
            dec_p.profesion as dec_prof, dec_p.residencia as dec_res, 
            dec_p.fecha_nacimiento as dec_fnac,

            -- Testigo 1 (Prioridad: Datos congelados en tabla nacimiento)
            t1.primer_nombre as t1_nom1, t1.primer_apellido as t1_ape1, 
            t1.cedula as t1_cedula, t1.nacionalidad as t1_nac, 
            COALESCE(n.t1_profesion, t1.profesion) as t1_prof, 
            COALESCE(n.t1_residencia, t1.residencia) as t1_res, 
            t1.fecha_nacimiento as t1_fnac,

            -- Testigo 2 (Prioridad: Datos congelados en tabla nacimiento)
            t2.primer_nombre as t2_nom1, t2.primer_apellido as t2_ape1, 
            t2.cedula as t2_cedula, t2.nacionalidad as t2_nac, 
            COALESCE(n.t2_profesion, t2.profesion) as t2_prof, 
            COALESCE(n.t2_residencia, t2.residencia) as t2_res, 
            t2.fecha_nacimiento as t2_fnac

        FROM nacimiento n
        LEFT JOIN personas reg ON n.id_registrador = reg.id_persona
        LEFT JOIN personas mad ON n.id_madre = mad.id_persona
        LEFT JOIN personas pad ON n.id_padre = pad.id_persona
        LEFT JOIN personas dec_p ON n.id_declarante = dec_p.id_persona
        LEFT JOIN personas t1 ON n.id_testigo1 = t1.id_persona
        LEFT JOIN personas t2 ON n.id_testigo2 = t2.id_persona
        WHERE n.numero_acta = ? LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $numero_acta);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    die("Error: Acta no encontrada.");
}

$d = $res->fetch_assoc(); // $d contiene todos los datos

// --- CORRECCIÓN SEXO ---
// Traducimos M -> MASCULINO y F -> FEMENINO
$sexo_display = ($d['sexo'] === 'M') ? 'MASCULINO' : 'FEMENINO';


// --- PREPARACIÓN DE DATOS (HELPERS DE FORMATO) ---

// Decodificar JSON de datos especiales
$esp = json_decode($d['datos_especiales_json'] ?? '[]', true);

// Función para concatenar nombres completos
function nombreCompleto($n1, $n2, $a1, $a2)
{
    if (!$n1 && !$a1) return "__________________________";
    return mb_strtoupper(trim("$n1 $n2")) . " " . mb_strtoupper(trim("$a1 $a2"));
}
function soloNombres($n1, $n2)
{
    return mb_strtoupper(trim("$n1 $n2")) ?: "__________________________";
}
function soloApellidos($a1, $a2)
{
    return mb_strtoupper(trim("$a1 $a2")) ?: "__________________________";
}

// Calculadora de edad simple basada en fecha del evento vs fecha nacimiento
function calcularEdad($fecha_nac, $fecha_referencia = null)
{
    if (!$fecha_nac) return "____";
    try {
        $nac = new DateTime($fecha_nac);
        $hoy = new DateTime($fecha_referencia ?? 'now'); // Si no hay referencia, usa hoy
        return $hoy->diff($nac)->y;
    } catch (Exception $e) {
        return "____";
    }
}

// Formateador de fechas
function partesFecha($fecha)
{
    if (!$fecha) return ['d' => '', 'm' => '', 'a' => ''];
    $t = strtotime($fecha);
    $meses = ['', 'ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];
    return [
        'd' => date('d', $t),
        'm' => $meses[date('n', $t)],
        'a' => date('Y', $t)
    ];
}

$f_reg = partesFecha($d['fecha_registro']);
$f_nac = partesFecha($d['fecha_nacimiento']);
$f_cert = partesFecha($d['fecha_certificado']);


// --- INICIO DE MPDF ---
$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'Legal',
    'margin_top' => 12,
    'margin_bottom' => 12,
    'margin_left' => 15,
    'margin_right' => 15
]);

// Cargar CSS
$cssPath = '../../../assets/css/estilos_pdf.css';
$stylesheet = file_exists($cssPath) ? file_get_contents($cssPath) : '';
$mpdf->WriteHTML($stylesheet, HTMLParserMode::HEADER_CSS);

// --- FUNCIONES DE HTML ---

function filaDatos($datos)
{
    $html = '<table class="tabla-datos' . (isset($datos['no-top']) ? ' no-top' : '') . '"><tr>';
    foreach ($datos['cols'] as $col) {
        $width = isset($col['width']) ? 'width="' . $col['width'] . '"' : '';
        $colspan = isset($col['colspan']) ? 'colspan="' . $col['colspan'] . '"' : '';
        $html .= "<td $width $colspan>{$col['content']}</td>";
    }
    $html .= '</tr></table>';
    return $html;
}

function campo($label, $valor, $fontSize = '12px', $color = '#000')
{
    $val = $valor ?: '&nbsp;'; // Si está vacío, pon un espacio para mantener altura
    return "<span class=\"sub-label\">$label <br><span style=\"font-size: $fontSize; color:$color;\">$val</span></span>";
}

function tablaFecha($titulo, $dia, $mes, $anio)
{
    return '
    <table width="100%" style="border-collapse: collapse; text-align: center;">
        <tr><td colspan="3" style="font-size: 7pt; border-bottom: 1px solid #000;">' . $titulo . '</td></tr>
        <tr>
            <td width="33%" style="font-size: 8pt; border-right: 1px solid #000;">DÍA<br><b>' . $dia . '</b></td>
            <td width="33%" style="font-size: 8pt; border-right: 1px solid #000;">MES<br><b>' . $mes . '</b></td>
            <td width="33%" style="font-size: 8pt;">AÑO<br><b>' . $anio . '</b></td>
        </tr>
    </table>';
}

function seccionEspecial($letra, $titulo, $contenidoHTML, $colorFondo = '#555', $textoBlanco = true)
{
    $estiloTitulo = $textoBlanco ? 'color: white; background-color: ' . $colorFondo . ';' : 'background-color: ' . $colorFondo . ';';
    $badge = $letra ? ($textoBlanco ? '<span style="color: #fff; padding: 0 4px; margin-right: 5px;">' . $letra . '</span>' : '<span style="color: #000; padding: 0 4px; margin-right: 5px;">' . $letra . '</span>') : '';

    return '
    <table style="width: 100%; border-collapse: collapse; font-family: sans-serif; font-size: 9pt; border: 1px solid #000; margin-bottom: 0px;">
        <tr>
            <td colspan="3" style="' . $estiloTitulo . ' font-weight: bold; border: 1px solid #000; padding: 4px;">
                ' . $badge . $titulo . '
            </td>
        </tr>
        ' . $contenidoHTML . '
    </table>';
}

// --- CONSTRUCCIÓN DEL HTML ---

// LOGO HEADER
$html = '
<table class="header-table">
    <tr>
        <td width="35%" class="texto-institucional">
            <b>República Bolivariana de Venezuela</b><br>
            <b>Consejo Nacional Electoral</b><br>
            <b>Comisión de Registro Civil y Electoral</b><br>
            Estado <span class="linea-rellenable">YARACUY</span><br>
            Municipio <span class="linea-rellenable">PEÑA</span><br>
            Parroquia <span class="linea-rellenable">YARITAGUA</span>
        </td>
        <td width="30%" style="text-align: center;">
             <div class="logo-cne"><img class="img-cne" src="../../../assets/img/CNE_23_.png" width="60"><div class="poder-electoral">PODER ELECTORAL</div></div>
        </td>
        <td width="35%">
            <table class="tabla-acta-header">
                <tr><td class="text-right">ACTA N°</td><td class="borde-bottom-acta"><b>' . $d['numero_acta'] . '</b></td></tr>
                <tr><td class="text-right">DÍA</td><td class="borde-bottom-acta"><b>' . $f_reg['d'] . '</b></td></tr>
                <tr><td class="text-right">MES</td><td class="borde-bottom-acta"><b>' . $f_reg['m'] . '</b></td></tr>
                <tr><td class="text-right">AÑO</td><td class="borde-bottom-acta"><b>' . $f_reg['a'] . '</b></td></tr>
            </table>
        </td>
    </tr>
</table>

<table width="100%" class="tabla-tipo-registro">
    <tr>
        <td>REGISTRO DE NACIMIENTO <span class="check-box">' . ($d['modo_registro'] == 'normal' ? '✓' : '&nbsp;') . '</span></td>
        <td style="text-align: center;">RECONOCIMIENTO <span class="check-box">' . ($d['modo_registro'] != 'normal' ? '✓' : '&nbsp;') . '</span></td>
        <td style="text-align: right;">INSERCIÓN<span class="check-box">' . ($d['modo_registro'] != 'normal' ? '✓' : '&nbsp;') . '</span></td>
    </tr>
</table>';

// A. Datos del Registrador
$html .= '<div class="titulo-seccion">A Datos del Registrador (a) Civil</div>';
$html .= filaDatos(['cols' => [
    ['width' => '50%', 'content' => campo('NOMBRES', soloNombres($d['reg_nom1'], ''), '14px')],
    ['width' => '50%', 'content' => campo('APELLIDOS', soloApellidos($d['reg_ape1'], ''), '14px')]
]]);
$html .= filaDatos(['cols' => [
    ['content' => campo('DOCUMENTO DE IDENTIDAD N°', $d['reg_cedula'], '15px')],
    ['content' => campo('OFICINA O UNIDAD DE REGISTRO CIVIL', $d['oficina_registro'] ?? 'REGISTRO CIVIL YARITAGUA', '13px')]
]]);
$html .= filaDatos(['no-top' => true, 'cols' => [
    ['width' => '30%', 'content' => campo('RESOLUCIÓN N°', $d['resolucion_numero'])],
    ['width' => '15%', 'content' => campo('FECHA', '')],
    ['width' => '20%', 'content' => campo('GACETA N°', $d['gaceta_numero'])],
    ['width' => '20%', 'content' => '<div class="gaceta-checks"><span class="check-box">✓</span> MUNICIPAL<br><span class="check-box"> </span> OFICIAL</div>'],
    ['width' => '15%', 'content' => campo('FECHA', '')]
]]);

// B. Datos del Nacido
$hora_fmt = date("h:i A", strtotime($d['hora_nacimiento']));
$html .= '<div class="titulo-seccion">B.- Datos del Nacido o Nacida</div>';
$html .= filaDatos(['cols' => [
    ['width' => '50%', 'content' => campo('NOMBRES', soloNombres($d['primer_nombre'], $d['segundo_nombre']), '15px')],
    ['width' => '50%', 'content' => campo('APELLIDOS', soloApellidos($d['primer_apellido'], $d['segundo_apellido']), '15px')]
]]);
$html .= filaDatos(['no-top' => true, 'cols' => [
    // AQUÍ SE USA LA VARIABLE CORREGIDA $sexo_display
    ['width' => '15%', 'content' => campo('SEXO', $sexo_display)],
    ['width' => '30%', 'content' => tablaFecha('FECHA DE NACIMIENTO', $f_nac['d'], $f_nac['m'], $f_nac['a'])],
    ['width' => '15%', 'content' => campo('HORA', $hora_fmt)],
    ['width' => '40%', 'content' => campo('N° CERTIFICADO DE NACIMIENTO', $d['certificado_num'])]
]]);
$html .= filaDatos(['no-top' => true, 'cols' => [['content' => campo('CENTRO DE SALUD O LUGAR DONDE OCURRIÓ EL NACIMIENTO', mb_strtoupper($d['lugar_nacimiento']))]]]);
$html .= filaDatos(['no-top' => true, 'cols' => [['content' => campo('DIRECCIÓN DEL LUGAR DONDE OCURRIÓ EL NACIMIENTO', mb_strtoupper($d['direccion_nacimiento']))]]]);

// C. Certificado Médico
$html .= '<div class="titulo-seccion">C.- Datos del Certificado Médico de Nacimiento</div>';
$html .= filaDatos(['cols' => [
    ['width' => '20%', 'content' => campo('NÚMERO DE CERTIFICADO', $d['certificado_num'], '13px')],
    ['width' => '20%', 'content' => campo('N° MPPS', $d['mpps_num'], '13px')],
    ['width' => '25%', 'content' => tablaFecha('FECHA DE EXPEDICIÓN', $f_cert['d'], $f_cert['m'], $f_cert['a'])],
    ['width' => '35%', 'content' => campo('AUTORIDAD QUE LO EXPIDE', mb_strtoupper($d['autoridad_medica']))]
]]);
$html .= filaDatos(['no-top' => true, 'cols' => [
    ['width' => '40%', 'content' => campo('CENTRO DE SALUD', mb_strtoupper($d['lugar_nacimiento']))],
    ['width' => '60%', 'content' => campo('DIRECCIÓN DEL CENTRO DE SALUD', mb_strtoupper($d['direccion_nacimiento']))]
]]);

// D/E. Padres y Declarante (Función auxiliar con parámetro check)
function datosPersonaBloque($nom1, $nom2, $ape1, $ape2, $ced, $nac, $prof, $res, $edad, $es_seccion_declarante, $rol_declarante, $marcar_check = false)
{
    $nombres = soloNombres($nom1, $nom2);
    $apellidos = soloApellidos($ape1, $ape2);
    $cedula = $ced ?: "___________";
    $edad_val = $edad ?: "____";
    $nacionalidad = $nac ?: "__________________";
    $profesion = $prof ?: "_________________________";
    $residencia = $res ?: "____________________________________________________________";

    $chk_declarante = $marcar_check ? '✓' : '&nbsp;';
    $chk_doc = '&nbsp;';
    $chk_const = '&nbsp;';
    $chk_test = '&nbsp;';

    $caracter = $rol_declarante ?: '_________________________';

    $h = filaDatos(['cols' => [
        ['width' => '50%', 'content' => campo('NOMBRES', $nombres, '14px')],
        ['width' => '50%', 'content' => campo('APELLIDOS', $apellidos, '14px')]
    ]]);
    $h .= filaDatos(['no-top' => true, 'cols' => [
        ['width' => '20%', 'content' => campo('CÉDULA / PASAPORTE', $cedula)],
        ['width' => '10%', 'content' => campo('EDAD', $edad_val)],
        ['width' => '30%', 'content' => campo('NACIONALIDAD', $nacionalidad)],
        ['width' => '40%', 'content' => campo($es_seccion_declarante ? 'CARÁCTER CON QUE ACTÚA' : 'PROFESIÓN O OCUPACIÓN', $es_seccion_declarante ? $caracter : $profesion)]
    ]]);

    $colsResidencia = [
        ['width' => '50%', 'content' => campo('RESIDENCIA (Dirección exacta)', $residencia, '11px')],
        ['width' => '30%', 'content' => campo('COMUNIDAD O PUEBLO INDÍGENA', '_________________________________', '11px')]
    ];
    if ($es_seccion_declarante) {
        $colsResidencia[0]['width'] = '60%';
        $colsResidencia[1]['width'] = '40%';
    } else {
        $colsResidencia[] = ['width' => '20%', 'content' => '<span class="check-box">' . $chk_declarante . '</span> DECLARANTE'];
    }
    $h .= filaDatos(['no-top' => true, 'cols' => $colsResidencia]);

    $h .= '<table class="tabla-datos no-top"><tr>
            <td width="' . ($es_seccion_declarante ? '30%' : '40%') . '" style="font-size: 7.5pt;">DECLARANTE SIN DOCUMENTO DE IDENTIFICACIÓN</td>
            <td width="20%" style="font-size: ' . ($es_seccion_declarante ? '7.5pt' : '5.5pt') . ';"><span class="check-box">' . $chk_doc . '</span> DOCUMENTO PÚBLICO</td>
            <td width="' . ($es_seccion_declarante ? '25%' : '20%') . '" style="font-size: ' . ($es_seccion_declarante ? '7.5pt' : '5.5pt') . ';"><span class="check-box">' . $chk_const . '</span> CONSTANCIA DEL CONSEJO COMUNAL</td>
            <td width="' . ($es_seccion_declarante ? '25%' : '20%') . '" style="font-size: ' . ($es_seccion_declarante ? '7.5pt' : '5.5pt') . ';"><span class="check-box">' . $chk_test . '</span> DECLARACIÓN DE TESTIGOS</td>
           </tr></table>';
    return $h;
}

$es_madre_declarante = (!empty($d['id_madre']) && !empty($d['id_declarante']) && $d['id_madre'] == $d['id_declarante']);
$es_padre_declarante = (!empty($d['id_padre']) && !empty($d['id_declarante']) && $d['id_padre'] == $d['id_declarante']);

// Sección D (Madre)
$html .= '<div class="titulo-seccion">D.- Hijo o Hija (Datos del Padre)</div>'; // Nota: En el formato original a veces es Madre y luego Padre, o viceversa. Aquí mantuve el título "Datos del Padre" que estaba en tu código anterior para D, aunque pasas los datos de la madre. Ajusta si es necesario. (En tu código original, D tenía datos de Madre pero título "Datos del Padre"? No, en tu código original decia Datos de la Madre. Lo corrijo a Madre para consistencia).
$html = str_replace('D.- Hijo o Hija (Datos del Padre)', 'D.- Hijo o Hija (Datos de la Madre)', $html); // Pequeña corrección al vuelo si estaba mal.

$edadMadre = calcularEdad($d['mad_fnac'], $d['fecha_registro']);
$html .= datosPersonaBloque($d['mad_nom1'], $d['mad_nom2'], $d['mad_ape1'], $d['mad_ape2'], $d['mad_cedula'], $d['mad_nac'], $d['mad_prof'], $d['mad_res'], $edadMadre, false, '', $es_madre_declarante);

// Sección E (Padre)
$html .= '<div class="titulo-seccion">E.- Hijo o Hija (Datos del Padre)</div>';
$edadPadre = calcularEdad($d['pad_fnac'], $d['fecha_registro']);
$html .= datosPersonaBloque($d['pad_nom1'], $d['pad_nom2'], $d['pad_ape1'], $d['pad_ape2'], $d['pad_cedula'], $d['pad_nac'], $d['pad_prof'], $d['pad_res'], $edadPadre, false, '', $es_padre_declarante);

// Sección F (Declarante)
$html .= '<div class="titulo-seccion">F.- Datos del Declarante</div>';
$edadDec = calcularEdad($d['dec_fnac'], $d['fecha_registro']);
$profDeclarante = ($es_madre_declarante) ? $d['mad_prof'] : (($es_padre_declarante) ? $d['pad_prof'] : $d['dec_prof']);
$resDeclarante = ($es_madre_declarante) ? $d['mad_res'] : (($es_padre_declarante) ? $d['pad_res'] : $d['dec_res']);

$html .= datosPersonaBloque($d['dec_nom1'], $d['dec_nom2'], $d['dec_ape1'], $d['dec_ape2'], $d['dec_cedula'], $d['dec_nac'], $profDeclarante, $resDeclarante, $edadDec, true, $d['calidad_declarante'], false);


// Sección G (Testigos)
$html .= '<div class="titulo-seccion">G.- Datos de los Testigos</div>';

function bloqueTestigo($nom, $ape, $ced, $nac, $prof, $res, $fnac, $f_reg)
{
    $edad = calcularEdad($fnac, $f_reg);
    $h = filaDatos(['cols' => [
        ['width' => '50%', 'content' => campo('NOMBRES', soloNombres($nom, ''), '14px')],
        ['width' => '50%', 'content' => campo('APELLIDOS', soloApellidos($ape, ''), '14px')]
    ]]);
    $h .= filaDatos(['no-top' => true, 'cols' => [
        ['width' => '25%', 'content' => campo('CÉDULA / PASAPORTE', $ced)],
        ['width' => '15%', 'content' => campo('EDAD', $edad)],
        ['width' => '30%', 'content' => campo('NACIONALIDAD', $nac)],
        ['width' => '30%', 'content' => campo('PROFESIÓN O OCUPACIÓN', $prof)],
    ]]);
    $h .= filaDatos(['no-top' => true, 'cols' => [['colspan' => 4, 'content' => campo('RESIDENCIA (Dirección exacta)', $res)]]]);
    return $h;
}

$html .= bloqueTestigo($d['t1_nom1'], $d['t1_ape1'], $d['t1_cedula'], $d['t1_nac'], $d['t1_prof'], $d['t1_res'], $d['t1_fnac'], $d['fecha_registro']);
$html .= bloqueTestigo($d['t2_nom1'], $d['t2_ape1'], $d['t2_cedula'], $d['t2_nac'], $d['t2_prof'], $d['t2_res'], $d['t2_fnac'], $d['fecha_registro']);


// --- PÁGINA 2 ---
$mpdf->WriteHTML($html, HTMLParserMode::HTML_BODY);
$mpdf->AddPage();
$html2 = '';

// Helper fecha interna
function fechaInterna($fStr)
{
    $fp = partesFecha($fStr);
    $d = $fp['d'] ?: '';
    $m = $fp['m'] ?: '';
    $a = $fp['a'] ?: '';
    return '
    <table style="width: 100%; border-collapse: collapse; height: 100%;">
        <tr>
            <td rowspan="2" style="font-size: 8pt; text-align: center; border-right: 1px solid #000;">FECHA</td>
            <td style="text-align: center; font-size: 6pt; border-bottom: 1px solid #000;">DÍA</td>
            <td style="text-align: center; font-size: 6pt; border-bottom: 1px solid #000; border-left: 1px solid #000;">MES</td>
            <td style="text-align: center; font-size: 6pt; border-bottom: 1px solid #000; border-left: 1px solid #000;">AÑO</td>
        </tr>
        <tr>
            <td style="height: 15px; font-size: 7pt; text-align: center;">' . $d . '</td>
            <td style="border-left: 1px solid #000; font-size: 7pt; text-align: center;">' . $m . '</td>
            <td style="border-left: 1px solid #000; font-size: 7pt; text-align: center;">' . $a . '</td>
        </tr>
    </table>';
}

// H. Inserción
$h_acta = ($d['modo_registro'] == 'insercion') ? ($esp['acta_num'] ?? '') : '';
$h_aut  = ($d['modo_registro'] == 'insercion') ? ($esp['autoridad'] ?? '') : '';
$h_fec  = ($d['modo_registro'] == 'insercion') ? fechaInterna($esp['fecha'] ?? '') : fechaInterna('');

$contenidoH = '
<tr>
    <td style="width: 35%; border: 1px solid #000; height: 35px; vertical-align: top; padding: 2px;">' . campo('ACTA N°', $h_acta, '9pt') . '</td>
    <td style="width: 35%; border: 1px solid #000; height: 35px; vertical-align: top; padding: 2px;">' . campo('ACTA N°', $h_acta, '9pt') . '</td> <td style="width: 30%; border: 1px solid #000; padding: 0;">' . $h_fec . '</td>
</tr>
<tr><td colspan="3" style="border: 1px solid #000; height: 30px; vertical-align: top; padding: 2px;">' . campo('AUTORIDAD QUE LO EXPIDE', $h_aut, '9pt') . '</td></tr>';
$html2 .= seccionEspecial('', 'H.- Datos del Acta a Insertar (llenar sólo en caso de inserción de Acta)', $contenidoH);


// I. Medida Protección
$i_cons = ($d['modo_registro'] == 'medida_proteccion') ? ($esp['consejo'] ?? '') : '';
$i_med  = ($d['modo_registro'] == 'medida_proteccion') ? ($esp['medida_num'] ?? '') : '';
$i_consej = ($d['modo_registro'] == 'medida_proteccion') ? ($esp['consejero'] ?? '') : '';
$i_fec  = ($d['modo_registro'] == 'medida_proteccion') ? fechaInterna($esp['fecha'] ?? '') : fechaInterna('');

$contenidoI = '
<tr>
    <td style="width: 35%; border: 1px solid #000; height: 35px; vertical-align: top; padding: 2px;">' . campo('CONSEJO DE PROTECCIÓN', $i_cons, '8pt') . '</td>
    <td style="width: 35%; border: 1px solid #000; height: 35px; vertical-align: top; padding: 2px;">' . campo('MEDIDA N°', $i_med, '9pt') . '</td>
    <td style="width: 30%; border: 1px solid #000; padding: 0;">' . $i_fec . '</td>
</tr>
<tr><td colspan="3" style="border: 1px solid #000; height: 30px; vertical-align: top; padding: 2px;">' . campo('NOMBRE Y APELLIDOS DEL CONSEJERO (A)', $i_consej, '9pt') . '</td></tr>';
$html2 .= seccionEspecial('I.-', 'Inscripción por Medida de Protección (llenar sólo cuando exista medida de protección)', $contenidoI);


// J. Decisión Judicial
$j_trib = ($d['modo_registro'] == 'sentencia_judicial') ? ($esp['tribunal'] ?? '') : '';
$j_sent = ($d['modo_registro'] == 'sentencia_judicial') ? ($esp['sentencia_num'] ?? '') : '';
$j_juez = ($d['modo_registro'] == 'sentencia_judicial') ? ($esp['juez'] ?? '') : '';
$j_fec  = ($d['modo_registro'] == 'sentencia_judicial') ? fechaInterna($esp['fecha'] ?? '') : fechaInterna('');

$contenidoJ = '
<tr>
    <td style="width: 35%; border: 1px solid #000; height: 35px; vertical-align: top; padding: 2px;">' . campo('TRIBUNAL O JUZGADO', $j_trib, '8pt') . '</td>
    <td style="width: 35%; border: 1px solid #000; height: 35px; vertical-align: top; padding: 2px;">' . campo('SENTENCIA N°', $j_sent, '9pt') . '</td>
    <td style="width: 30%; border: 1px solid #000; padding: 0;">' . $j_fec . '</td>
</tr>
<tr><td colspan="3" style="border: 1px solid #000; height: 30px; vertical-align: top; padding: 2px;">' . campo('NOMBRE Y APELLIDOS DEL JUEZ (A)', $j_juez, '9pt') . '</td></tr>';
$html2 .= seccionEspecial('J.-', 'Inscripción por Decisión Judicial (llenar sólo en caso de sentencia Judicial)', $contenidoJ);


// K. Extemporánea
$k_dat = ($d['modo_registro'] == 'extemporanea') ? ($esp['datos_informe'] ?? '') : '';
$k_aut = ($d['modo_registro'] == 'extemporanea') ? ($esp['autoridad'] ?? '') : '';
$k_fec = ($d['modo_registro'] == 'extemporanea') ? fechaInterna($esp['fecha'] ?? '') : fechaInterna('');

$contenidoK = '
<tr>
    <td style="width: 70%; border: 1px solid #000; height: 35px; vertical-align: top; padding: 2px;">' . campo('DATOS DEL INFORME DEL CONSEJO DE PROTECCIÓN O PROVIDENCIA ADMINISTRATIVA N°', $k_dat, '8pt') . '</td>
    <td style="width: 30%; border: 1px solid #000; padding: 0;">' . $k_fec . '</td>
</tr>
<tr><td colspan="3" style="border: 1px solid #000; height: 30px; vertical-align: top; padding: 2px;">' . campo('AUTORIDAD QUE LO EXPIDE', $k_aut, '9pt') . '</td></tr>';
$html2 .= seccionEspecial('K.-', 'Inscripción Extemporánea', $contenidoK);


// L y M. Observaciones
$obs_texto = nl2br($d['observaciones'] ?? '');
$docs_texto = nl2br($d['documentos_presentados'] ?? '');
$marg_texto = nl2br($d['nota_marginal'] ?? '');

function contenidoTextoLargo($texto)
{
    if (!empty(trim($texto))) {
        return '<tr><td style="border: 1px solid #000; height: 80px; vertical-align: top; padding: 5px; font-size: 8pt;">' . $texto . '</td></tr>';
    } else {
        $lineasPunteadas = str_repeat('<div style="width: 100%; height: 20px; border-bottom: 1px dotted #000;"></div>', 3);
        return '<tr><td style="border: 1px solid #000; height: 80px; vertical-align: top; padding: 5px;">' . $lineasPunteadas . '</td></tr>';
    }
}

$html2 .= seccionEspecial('L.-', 'Circunstancias Especiales del Acto/Observaciones', contenidoTextoLargo($obs_texto));
$html2 .= seccionEspecial('M.-', 'Documentos Presentados', contenidoTextoLargo($docs_texto));


// Firmas y Cierre
function generarCeldaFirma($titulo, $nombre)
{
    $n = $nombre ? $nombre : '&nbsp;';
    return '<td style="width: 25%; height: 70px; border: 1px solid #000; vertical-align: top; padding: 2px;">
                <div style="font-size: 6pt; margin-bottom: 30px;">' . $titulo . '</div>
                <div style="text-align: center; font-size: 7pt;">' . $n . '</div>
            </td>';
}

$html2 .= '
<div style="text-align: center; font-family: sans-serif; font-weight: bold; font-size: 8pt; margin: 10px 0;">LEÍDA LA PRESENTE ACTA Y CONFORMES CON EL CONTENIDO DE LA MISMA, FIRMAN:</div>
<table width="100%" style="border-collapse: collapse; font-family: sans-serif; font-size: 7pt; border: 1px solid #000;">
    <tr>' .
    generarCeldaFirma('FIRMA DEL DECLARANTE', nombreCompleto($d['dec_nom1'], $d['dec_nom2'], $d['dec_ape1'], $d['dec_ape2'])) .
    generarCeldaFirma('IMPRESIÓN DACTILAR', '') .
    generarCeldaFirma('FIRMA: (OTRO)', '') .
    generarCeldaFirma('IMPRESIÓN DACTILAR', '') .
    '</tr>
    <tr>
        <td colspan="2" style="border: 1px solid #000; height: 60px; text-align: center; vertical-align: bottom; font-weight: bold;">
            FIRMA DEL REGISTRADOR (A)<br>
            <span style="font-weight: normal; font-size: 8pt;">' . nombreCompleto($d['reg_nom1'], '', $d['reg_ape1'], '') . '</span>
        </td>
        <td colspan="2" style="border: 1px solid #000; height: 60px; text-align: center; vertical-align: top; font-weight: bold;">SELLO HÚMEDO</td>
    </tr>
    <tr>' .
    generarCeldaFirma('FIRMA DEL TESTIGO 1', nombreCompleto($d['t1_nom1'], '', $d['t1_ape1'], '')) .
    generarCeldaFirma('IMPRESIÓN DACTILAR', '') .
    generarCeldaFirma('FIRMA DEL TESTIGO 2', nombreCompleto($d['t2_nom1'], '', $d['t2_ape1'], '')) .
    generarCeldaFirma('IMPRESIÓN DACTILAR', '') .
    '</tr>
</table>

<div style="background-color: #555; color: white; font-family: sans-serif; font-weight: bold; font-size: 9pt; margin-top: 10px; padding-left: 4px;"> N.- Nota Marginal</div>
<table width="100%" style="border-collapse: collapse; font-family: sans-serif; font-size: 9pt; border: 1px solid #000;">
    <tr><td height="22px" valign="top" style="border-bottom: 1px dotted #000; padding: 5px;">' . ($marg_texto ?: '&nbsp;') . '</td></tr>';
if (strlen($marg_texto) < 100) {
    for ($l = 0; $l < 2; $l++) {
        $html2 .= '<tr><td height="22px" style="border-bottom: 1px dotted #000;">&nbsp;</td></tr>';
    }
}
$html2 .= '</table>

<div style="width: 100%; margin-top: 30px; font-family: sans-serif; text-align: center;">
    <div style="display: inline-block; width: 100%;">
        <div style="font-weight: bold; font-size: 10pt; margin: 0 auto; width: 300px; border-top: 1px solid #000; padding-top: 5px;">
            FIRMA DEL REGISTRADOR (A) CIVIL<br>
            <span style="font-weight: normal; font-size: 9pt;">' . nombreCompleto($d['reg_nom1'], '', $d['reg_ape1'], '') . '</span>
        </div>
        <div style="margin-top: 10px; font-size: 8pt;">SELLO HÚMEDO</div>
    </div>
</div>';

$mpdf->WriteHTML($html2, HTMLParserMode::HTML_BODY);
$mpdf->Output();
