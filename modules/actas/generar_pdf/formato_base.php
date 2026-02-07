<?php
require_once __DIR__ . '/../../../vendor/autoload.php';

use Mpdf\HTMLParserMode;

// --- 1. CONFIGURACIÓN Y HELPERS ---
$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'Legal',
    'margin_top' => 12,
    'margin_bottom' => 12,
    'margin_left' => 15,
    'margin_right' => 15
]);

$cssPath = '../../../assets/css/estilos_pdf.css';
$stylesheet = file_exists($cssPath) ? file_get_contents($cssPath) : '';
$mpdf->WriteHTML($stylesheet, HTMLParserMode::HEADER_CSS);

// --- FUNCIONES DE AYUDA (Refactorizadas) ---

// 1. Genera etiqueta + valor
function campo($label, $val = '', $sz = '12px')
{
    return "<span class='sub-label'>$label<br><span style='font-size:$sz; color:#000;'>" . ($val ?: '&nbsp;') . "</span></span>";
}

// 2. Genera filas de tabla
function fila($cols, $topBorder = true)
{
    $html = '<table class="tabla-datos' . (!$topBorder ? ' no-top' : '') . '"><tr>';
    foreach ($cols as $c) {
        $w = isset($c['w']) ? 'width="' . $c['w'] . '"' : '';
        $style = isset($c['style']) ? 'style="' . $c['style'] . '"' : '';
        $html .= "<td $w $style>{$c['html']}</td>";
    }
    return $html . '</tr></table>';
}

// 3. Títulos de sección (A, B, C...)
function titulo($letra, $texto)
{
    $badge = $letra ? "<span style='color:#fff; padding:0 4px; margin-right:5px;'>$letra</span>" : '';
    return "<div class='titulo-seccion'>$badge $texto</div>";
}

// 4. Cuadrícula de Fecha pequeña (Día/Mes/Año)
function gridFecha($titulo, $d = '', $m = '', $a = '')
{
    return '<table width="100%" style="border-collapse: collapse; text-align: center;">
        <tr><td colspan="3" style="font-size:6pt; border-bottom:1px solid #000; line-height:7px;">' . $titulo . '</td></tr>
        <tr>
            <td width="33%" style="font-size:5pt; border-right:1px solid #000;">DÍA<br><div style="height:9px;">' . $d . '</div></td>
            <td width="33%" style="font-size:5pt; border-right:1px solid #000;">MES<br><div style="height:9px;">' . $m . '</div></td>
            <td width="33%" style="font-size:5pt;">AÑO<br><div style="height:9px;">' . $a . '</div></td>
        </tr></table>';
}

// 5. Checkbox
function chk($val = false)
{
    return "<span class='check-box'>" . ($val ? '✓' : '&nbsp;') . "</span>";
}

// 6. Celdas de Firma (LA QUE FALTABA)
function generarCeldasFirma($etiquetas, $h)
{
    $html = '';
    foreach ($etiquetas as $e) {
        $html .= "<td style='height:{$h}px; border:1px solid #000; vertical-align:top; padding:2px;'>$e</td>";
    }
    return $html;
}

// --- 2. CONSTRUCCIÓN DEL HTML (PÁGINA 1) ---

// Header
$html = '
<table class="header-table">
    <tr>
        <td width="35%" class="texto-institucional">
            <b>República Bolivariana de Venezuela</b><br><b>Consejo Nacional Electoral</b><br>
            <b>Comisión de Registro Civil y Electoral</b><br>
            Estado <span class="linea-rellenable">YARACUY</span><br>
            Municipio <span class="linea-rellenable">PEÑA</span><br>Parroquia <span class="linea-rellenable">YARITAGUA</span>
        </td>
        <td width="30%" align="center"><img src="../../../assets/img/CNE_23_.png" width="60"><br><div class="poder-electoral">PODER ELECTORAL</div></td>
        <td width="35%">
            <table class="tabla-acta-header">
                <tr><td align="right">ACTA N°</td><td class="borde-bottom-acta"><b>2026-0000</b></td></tr>
                <tr><td align="right">DÍA</td><td class="borde-bottom-acta"><b>06</b></td></tr>
                <tr><td align="right">MES</td><td class="borde-bottom-acta"><b>FEBRERO</b></td></tr>
                <tr><td align="right">AÑO</td><td class="borde-bottom-acta"><b>2026</b></td></tr>
            </table>
        </td>
    </tr>
</table>
<table width="100%" class="tabla-tipo-registro">
    <tr>
        <td>REGISTRO DE DEFUNCIÓN ' . chk(true) . '</td>
        <td align="center">ORDINARIA ' . chk() . '</td>
        <td align="right">EXTEMPORÁNEA ' . chk() . '</td>
    </tr>
</table>';

// A. Registrador
$html .= titulo('A', 'Datos del Registrador (a) Civil');
$html .= fila([
    ['w' => '50%', 'html' => campo('NOMBRES', 'NOMBRE REGISTRADOR', '14px')],
    ['w' => '50%', 'html' => campo('APELLIDOS', 'APELLIDO REGISTRADOR', '14px')]
]);
$html .= fila([
    ['html' => campo('DOCUMENTO DE IDENTIDAD N°', 'V-00.000.000', '14px')],
    ['html' => campo('OFICINA O UNIDAD DE REGISTRO CIVIL', 'REGISTRO CIVIL DE YARITAGUA', '13px')]
], false);

// B. Fallecido
$html .= titulo('B', 'Datos del Fallecido (a)');
$html .= fila([
    ['w' => '50%', 'html' => campo('NOMBRES', '')],
    ['w' => '25%', 'html' => campo('PRIMER APELLIDO', '')],
    ['w' => '25%', 'html' => campo('SEGUNDO APELLIDO', '')]
]);

$gridB = '<table width="100%" style="border-collapse:collapse;"><tr>
    <td width="35%" style="font-size:7pt; border-right:1px solid #000;">FECHA DE NACIMIENTO</td>
    <td width="20%" style="border-right:1px solid #000; text-align:center; font-size:5pt;">DÍA<br>&nbsp;</td>
    <td width="25%" style="border-right:1px solid #000; text-align:center; font-size:5pt;">MES<br>&nbsp;</td>
    <td width="20%" style="text-align:center; font-size:5pt;">AÑO<br>&nbsp;</td>
</tr></table>';
$html .= fila([
    ['w' => '48%', 'style' => 'padding:0;', 'html' => $gridB],
    ['w' => '52%', 'style' => 'border-left:1px solid #000;', 'html' => campo('LUGAR DE NACIMIENTO', '')]
], false);

$html .= fila([
    ['w' => '25%', 'html' => campo('DOCUMENTO DE IDENTIDAD N°', '')],
    ['w' => '15%', 'style' => 'border-left:1px solid #000; font-size:6pt; text-align:center;', 'html' => 'CÉDULA ' . chk() . ' PASAPORTE ' . chk()],
    ['w' => '10%', 'style' => 'border-left:1px solid #000;', 'html' => campo('EDAD', '')],
    ['w' => '15%', 'style' => 'border-left:1px solid #000;', 'html' => campo('SEXO', '')],
    ['w' => '35%', 'style' => 'border-left:1px solid #000;', 'html' => campo('ESTADO CIVIL', '')]
], false);

$html .= fila([
    ['w' => '25%', 'html' => campo('NACIONALIDAD', '')],
    ['w' => '40%', 'html' => campo('PROFESIÓN U OCUPACIÓN', '')],
    ['w' => '35%', 'html' => campo('PUEBLO O COMUNIDAD INDÍGENA', '')]
], false);
$html .= fila([['w' => '100%', 'html' => campo('RESIDENCIA', '')]], false);

// C. Defunción
$html .= titulo('C', 'Datos de la Defunción');
$gridC = '<table width="100%" style="border-collapse:collapse;"><tr>
    <td width="40%" style="font-size:7pt; border-right:1px solid #000;">FECHA DE LA DEFUNCIÓN</td>
    <td width="20%" style="border-right:1px solid #000; text-align:center; font-size:5pt;">DÍA<br>&nbsp;</td>
    <td width="20%" style="border-right:1px solid #000; text-align:center; font-size:5pt;">MES<br>&nbsp;</td>
    <td width="20%" style="text-align:center; font-size:5pt;">AÑO<br>&nbsp;</td>
</tr></table>';

$html .= fila([
    ['w' => '55%', 'style' => 'padding:0; border-right:1px solid #000;', 'html' => $gridC],
    ['w' => '35%', 'style' => 'border-right:1px solid #000;', 'html' => campo('HORA', '')],
    ['w' => '10%', 'style' => 'text-align:center; font-size:6pt;', 'html' => 'AM ' . chk() . '<br>PM ' . chk()]
]);
$html .= fila([
    ['w' => '10%', 'style' => 'font-size:7pt; font-weight:bold; text-align:center; border-right:1px solid #000;', 'html' => 'LUGAR'],
    ['w' => '20%', 'style' => 'border-right:1px solid #000;', 'html' => campo('PAÍS', '')],
    ['w' => '23%', 'style' => 'border-right:1px solid #000;', 'html' => campo('ESTADO', '')],
    ['w' => '23%', 'style' => 'border-right:1px solid #000;', 'html' => campo('MUNICIPIO', '')],
    ['w' => '24%', 'html' => campo('PARROQUIA', '')]
], false);
$html .= fila([
    ['w' => '10%', 'style' => 'font-size:7pt; font-weight:bold; text-align:center; border-right:1px solid #000;', 'html' => 'CAUSAS'],
    ['w' => '90%', 'html' => '<div style="height:25px;"></div>']
], false);

// D. Certificado
$html .= titulo('D', 'Datos del Certificado de Defunción');
$html .= fila([
    ['w' => '50%', 'style' => 'border-right:1px solid #000;', 'html' => campo('CERTIFICADO N°', '')],
    ['w' => '50%', 'style' => 'padding:0;', 'html' => gridFecha('FECHA DE EXPEDICIÓN')]
]);
$html .= fila([
    ['w' => '50%', 'style' => 'border-right:1px solid #000;', 'html' => campo('AUTORIDAD QUE LO EXPIDE', '')],
    ['w' => '25%', 'style' => 'border-right:1px solid #000;', 'html' => campo('DOCUMENTO N°', '')],
    ['w' => '25%', 'html' => campo('N° MPPS', '')]
], false);
$html .= fila([['w' => '100%', 'html' => campo('DENOMINACIÓN DE LA DEPENDENCIA DE SALUD', '')]], false);

// E. Familiares
$html .= titulo('E', 'Datos Familiares');
$html .= fila([
    ['w' => '85%', 'style' => 'border-right:1px solid #000;', 'html' => campo('NOMBRES Y APELLIDOS DEL CÓNYUGE', '')],
    ['w' => '15%', 'style' => 'text-align:center; font-size:6pt;', 'html' => 'VIVE<br>SI ' . chk() . ' NO ' . chk()]
]);
$html .= fila([
    ['w' => '20%', 'style' => 'border-right:1px solid #000;', 'html' => campo('DOCUMENTO N°', '')],
    ['w' => '20%', 'style' => 'border-right:1px solid #000; text-align:center; font-size:6pt;', 'html' => 'CÉDULA ' . chk() . ' PASAPORTE ' . chk()],
    ['w' => '35%', 'style' => 'border-right:1px solid #000;', 'html' => campo('PROFESIÓN', '')],
    ['w' => '25%', 'html' => campo('NACIONALIDAD', '')]
], false);
$html .= fila([['w' => '100%', 'html' => campo('RESIDENCIA', '')]], false);

// F. Hijos (Tabla estática optimizada)
$html .= '<div class="titulo-seccion" style="text-align:center; border-bottom:none;">HIJOS E HIJAS DEL FALLECIDO (A)</div>';
$html .= '<table width="100%" style="border-collapse:collapse; border:1px solid #000; font-family:sans-serif;">
<thead><tr style="font-size:6pt; text-align:center; font-weight:bold; background:#f2f2f2;">
    <td width="50%" style="border:1px solid #000;">NOMBRES Y APELLIDOS</td>
    <td width="20%" style="border:1px solid #000;">DOCUMENTO N°</td>
    <td width="10%" style="border:1px solid #000;">EDAD</td>
    <td width="20%" style="border:1px solid #000;">VIVE (SI/NO)</td>
</tr></thead><tbody>';
for ($i = 1; $i <= 7; $i++) {
    $html .= '<tr style="height:17px;"><td style="border:1px solid #000; font-size:7pt;">' . $i . ')</td><td style="border:1px solid #000;"></td><td style="border:1px solid #000;"></td><td style="border:1px solid #000; text-align:center;">' . chk() . ' ' . chk() . '</td></tr>';
}
// Padres
$html .= '<tr style="height:18px;"><td style="border:1px solid #000; font-size:6pt; font-weight:bold;">MADRE</td><td style="border:1px solid #000;"></td><td style="border:1px solid #000; background:#eee;"></td><td style="border:1px solid #000; text-align:center;">' . chk() . ' ' . chk() . '</td></tr>';
$html .= '<tr style="height:18px;"><td style="border:1px solid #000; font-size:6pt; font-weight:bold;">PADRE</td><td style="border:1px solid #000;"></td><td style="border:1px solid #000; background:#eee;"></td><td style="border:1px solid #000; text-align:center;">' . chk() . ' ' . chk() . '</td></tr>';
$html .= '</tbody></table>';

// G. Declarante
$html .= titulo('G', 'Datos de la Persona que Declara');
$html .= fila([['w' => '100%', 'html' => campo('NOMBRE Y APELLIDO', '')]]);
$html .= fila([
    ['w' => '20%', 'style' => 'border-right:1px solid #000;', 'html' => campo('DOCUMENTO N°', '')],
    ['w' => '20%', 'style' => 'border-right:1px solid #000; text-align:center; font-size:6pt;', 'html' => 'E-17256320'],
    ['w' => '8%',  'style' => 'border-right:1px solid #000;', 'html' => campo('EDAD', '')],
    ['w' => '27%', 'style' => 'border-right:1px solid #000;', 'html' => campo('PROFESIÓN', '')],
    ['w' => '25%', 'html' => campo('NACIONALIDAD', '')]
], false);
$html .= fila([['w' => '100%', 'html' => campo('RESIDENCIA', '')]], false);

// H. Extracto Consular (Usando los helpers)
$html .= '
<table style="width:100%; border-collapse:collapse; font-family:sans-serif; border:1px solid #000; margin-top:0;">
    <tr><td colspan="3" style="background:#555; color:#fff; font-weight:bold; padding:4px; font-size:9pt;"><span style="margin-right:5px;">H</span> Datos del Extracto Consular</td></tr>
    <tr>
        <td width="35%" style="border:1px solid #000; padding:2px;">' . campo('EXTRACTO N°', '') . '</td>
        <td width="30%" style="border:1px solid #000; padding:0;">' . gridFecha('FECHA EXPEDICIÓN') . '</td>
        <td width="35%" style="border:1px solid #000; padding:2px;">' . campo('AUTORIDAD', '') . '</td>
    </tr>
</table>';

$mpdf->WriteHTML($html, HTMLParserMode::HTML_BODY);

// --- 3. CONSTRUCCIÓN DEL HTML (PÁGINA 2) ---
$mpdf->AddPage();
// --- SECCIÓN I: INSCRIPCIÓN POR DECISIÓN JUDICIAL ---
$html2 .= titulo('I', 'Inscripción por Decisión Judicial (llenar en caso de sentencia judicial)');

// Fila 1: Tribunal y N° de Sentencia
$html2 .= fila([
    ['w' => '70%', 'html' => campo('TRIBUNAL O JUZGADO', '')],
    ['w' => '30%', 'style' => 'border-left:1px solid #000;', 'html' => campo('SENTENCIA N°', '')]
]);

// Fila 2: Juez y Fecha de la Sentencia
$html2 .= fila([
    ['w' => '70%', 'html' => campo('NOMBRES Y APELLIDOS DEL JUEZ (A)', '')],
    ['w' => '30%', 'style' => 'padding:0; border-left:1px solid #000;', 'html' => gridFecha('FECHA')]
], false);

// Fila 3: Extracto de la Sentencia (con líneas punteadas para escritura manual)
$lineasPunteadas = str_repeat('<div style="border-bottom:1px dotted #000; height:18px; margin-top:2px;"></div>', 3);

$html2 .= fila([
    ['w' => '100%', 'html' => campo('EXTRACTO DE LA SENTENCIA', '') . $lineasPunteadas]
], false);

// --- SECCIÓN I: DATOS DE LOS TESTIGOS ---
// Título simple sin fecha compartida
$html2 .= titulo('I', 'Datos de los Testigos');

// Generamos los bloques para 2 testigos
for ($i = 0; $i < 2; $i++) {
    // Fila 1: Nombres y Apellidos
    $html2 .= fila([
        ['w' => '100%', 'html' => campo('NOMBRES Y APELLIDOS', '')]
    ]);

    // Fila 2: Cédula, Edad, Profesión, Nacionalidad
    $html2 .= fila([
        ['w' => '30%', 'style' => 'border-right:1px solid #000;', 'html' => campo('CÉDULA DE IDENTIDAD N°', '')],
        ['w' => '10%', 'style' => 'border-right:1px solid #000;', 'html' => campo('EDAD', '')],
        ['w' => '35%', 'style' => 'border-right:1px solid #000;', 'html' => campo('PROFESIÓN U OCUPACIÓN', '')],
        ['w' => '25%', 'html' => campo('NACIONALIDAD', '')]
    ], false);

    // Fila 3: Residencia
    $html2 .= fila([
        ['w' => '100%', 'html' => campo('RESIDENCIA', '')]
    ], false);
}

// L y M (Observaciones)
$dotted = str_repeat('<div style="width:100%; height:20px; border-bottom:1px dotted #000;"></div>', 3);
$boxLargo = '<tr><td style="border:1px solid #000; height:80px; padding:5px;">' . $dotted . '</td></tr>';

$html2 .= '<table style="width:100%; border-collapse:collapse; font-family:sans-serif; font-size:9pt; margin-top:10px;">
<tr><td style="background:#555; color:#fff; font-weight:bold; padding:4px;"><span style="margin-right:5px;">L</span> Observaciones</td></tr>
' . $boxLargo . '
</table>';

$html2 .= '<table style="width:100%; border-collapse:collapse; font-family:sans-serif; font-size:9pt; margin-top:10px;">
<tr><td style="background:#555; color:#fff; font-weight:bold; padding:4px;"><span style="margin-right:5px;">M</span> Documentos Presentados</td></tr>
' . $boxLargo . '
</table>';

// Firmas
$html2 .= '
<div style="text-align:center; font-weight:bold; font-size:8pt; margin:10px 0;">CONFORMES CON EL CONTENIDO, FIRMAN:</div>
<table width="100%" style="border-collapse:collapse; font-size:7pt; border:1px solid #000;">
    <tr>' . generarCeldasFirma(['FIRMA DEL DECLARANTE', 'IMPRESIÓN DACTILAR', 'FAMILIAR', 'IMPRESIÓN DACTILAR'], 70) . '</tr>
    <tr>
        <td colspan="2" style="border:1px solid #000; height:60px; text-align:center; vertical-align:bottom;">FIRMA DEL REGISTRADOR (A)</td>
        <td colspan="2" style="border:1px solid #000; text-align:center;">SELLO HÚMEDO</td>
    </tr>
    <tr>' . generarCeldasFirma(['FIRMA TESTIGO 1', 'IMPRESIÓN DACTILAR', 'FIRMA TESTIGO 2', 'IMPRESIÓN DACTILAR'], 70) . '</tr>
</table>';

// Nota Marginal
$html2 .= '<div style="background:#555; color:#fff; font-weight:bold; font-size:9pt; margin-top:10px; padding-left:4px;">N.- Nota Marginal</div>
<table width="100%" style="border-collapse:collapse; border:1px solid #000;">
<tr><td height="22px" style="border-bottom:1px dotted #000;"></td></tr>
<tr><td height="22px" style="border-bottom:1px dotted #000;"></td></tr>
</table>';

$html2 .= '

<div style="width: 100%; margin-top: 30px; font-family: sans-serif; text-align: center;">
    <div style="display: inline-block; width: 100%;">
        <div style="font-weight: bold; font-size: 10pt; margin: 0 auto; width: 300px; border-top: 1px solid #000; padding-top: 5px;">
            FIRMA DEL REGISTRADOR (A) CIVIL<br>
            <span style="font-weight: normal; font-size: 9pt;"></span>
        </div>
        <div style="margin-top: 10px; font-size: 8pt;">SELLO HÚMEDO</div>
    </div>
</div>';

$mpdf->WriteHTML($html2, HTMLParserMode::HTML_BODY);
$mpdf->Output();
