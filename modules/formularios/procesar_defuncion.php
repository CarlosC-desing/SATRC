<?php
ob_start();

include_once '../../includes/db/config.php';
include '../../modules/login/verificar_sesion.php';
include '../../includes/db/conexion.php';
include '../../functions/registrar_log.php';
include '../../functions/validaciones.php';

$_POST = sanear($_POST);

if (ob_get_length()) ob_clean();
header('Content-Type: application/json');

mysqli_report(MYSQLI_REPORT_OFF);

/**
 * Obtiene ID y Nombre completo para llenar campos de texto e IDs relacionales
 */
function obtenerDatosPersona($conn, $cedula)
{
    if (empty($cedula)) return null;
    $sql = "SELECT id_persona, CONCAT(primer_nombre, ' ', primer_apellido) as nombre FROM personas WHERE cedula = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $ced = trim($cedula);
    $stmt->bind_param("s", $ced);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

try {
    $msj_error_gen = "Ocurrió un error inesperado al procesar el acta de defunción.";
    $msj_error_ced = "Una de las cédulas ingresadas no está registrada en el sistema.";

    if (!isset($conn) || $conn->connect_error) throw new Exception("db_error");

    $conn->begin_transaction();

    // 1. Validación y Obtención de datos de las personas involucradas
    $p_fallecido = obtenerDatosPersona($conn, $_POST['cedula_persona']);
    $p_autoridad = obtenerDatosPersona($conn, $_POST['cedula_autoridad']);
    $p_testigo1  = obtenerDatosPersona($conn, $_POST['cedula_t1']);
    $p_testigo2  = obtenerDatosPersona($conn, $_POST['cedula_t2']);

    if (!$p_fallecido || !$p_autoridad || !$p_testigo1 || !$p_testigo2) {
        throw new Exception("CODE_CEDULA_NOT_FOUND");
    }

    // 2. Generación de número de acta correlativo
    $anio_actual = date('Y');
    $query_ultimo = "SELECT numero_acta FROM defuncion WHERE numero_acta LIKE 'D%-$anio_actual' ORDER BY id DESC LIMIT 1";
    $res_ultimo = $conn->query($query_ultimo);
    $nuevo_num = 1;
    if ($res_ultimo && $res_ultimo->num_rows > 0) {
        $row_u = $res_ultimo->fetch_assoc();
        $ultimo_valor = explode('-', str_replace('D', '', $row_u['numero_acta']))[0];
        $nuevo_num = (int)$ultimo_valor + 1;
    }
    $num_acta = "D" . str_pad($nuevo_num, 7, "0", STR_PAD_LEFT) . "-" . $anio_actual;

    // 3. Inserción en las 19 columnas identificadas en tu BD
    $sql = "INSERT INTO defuncion (
                id_persona, fecha_defuncion, hora_defuncion, lugar_defuncion, causa_defuncion, 
                id_autoridad, numero_acta, nombre_medico, nombre_declarante, 
                cedula_declarante, parentesco_declarante, fecha_acta, usuario_responsable, 
                id_testigo1, id_testigo2, testigo_1, cedula_t1, testigo_2, cedula_t2
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception("prepare_error: " . $conn->error);

    $stmt->bind_param(
        "issssisssssssiissss",
        $p_fallecido['id_persona'],
        $_POST['fecha_defuncion'],
        $_POST['hora_defuncion'],
        $_POST['lugar_defuncion'],
        $_POST['causa_defuncion'],
        $p_autoridad['id_persona'],
        $num_acta,
        $_POST['nombre_medico'],
        $_POST['nombre_declarante'],
        $_POST['cedula_declarante'],
        $_POST['parentesco_declarante'],
        $_POST['fecha_acta'],
        $_SESSION['usuario'],
        $p_testigo1['id_persona'],
        $p_testigo2['id_persona'],
        $p_testigo1['nombre'],
        $_POST['cedula_t1'],
        $p_testigo2['nombre'],
        $_POST['cedula_t2']
    );

    if (!$stmt->execute()) throw new Exception("execution_error: " . $stmt->error);

    $conn->commit();
    registrarLog($conn, $_SESSION['usuario'], "Defunción", "Registro Exitoso", "Acta: $num_acta");

    if (ob_get_length()) ob_clean();
    echo json_encode([
        'status' => 'success',
        'pdf_url' => "../actas/generar_pdf/pdf_defuncion.php?numero_acta=" . urlencode($num_acta)
    ]);
} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();

    // Log para el programador
    error_log("Error Defunción: " . $e->getMessage());

    // Mensaje para el usuario
    $mensaje_final = (strpos($e->getMessage(), "CODE_CEDULA") !== false) ? $msj_error_ced : $msj_error_gen;

    if (ob_get_length()) ob_clean();
    echo json_encode([
        'status' => 'error',
        'message' => $mensaje_final
    ]);
}
