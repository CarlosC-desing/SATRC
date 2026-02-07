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

$mensaje_usuario = "Ocurrió un error al procesar el registro de la persona.";

try {
    if (!isset($conn) || $conn->connect_error) {
        $mensaje_usuario = "Error de conexión con la base de datos.";
        throw new Exception("db_conn_error");
    }

    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $mensaje_usuario = "Error de seguridad: Token inválido o sesión expirada.";
        throw new Exception("csrf_error");
    }

    $conn->begin_transaction();

    $tipo_doc = $_POST['tipo_doc'] ?? 'V';
    $num_ced  = trim($_POST['cedula'] ?? '');
    $ced      = $tipo_doc . "-" . $num_ced;

    // Datos básicos
    $p1_nom = trim($_POST['primer_nombre']    ?? '');
    $s1_nom = trim($_POST['segundo_nombre']   ?? '');
    $t1_nom = trim($_POST['tercer_nombre']    ?? '');
    $p1_ape = trim($_POST['primer_apellido']  ?? '');
    $s1_ape = trim($_POST['segundo_apellido'] ?? '');
    $e_civ  = trim($_POST['estado_civil']     ?? '');
    $prof   = trim($_POST['profesion']        ?? '');
    $res    = trim($_POST['residencia']       ?? '');
    $f_nac  = trim($_POST['fecha_nacimiento'] ?? '');
    $sx     = trim($_POST['sexo']             ?? '');

    // Ubicación y Registro
    $nac    = trim($_POST['nacionalidad']         ?? '');
    $p_nac  = trim($_POST['pais_nacimiento']      ?? '');
    $e_nac  = trim($_POST['estado_nacimiento']    ?? '');
    $m_nac  = trim($_POST['municipio_nacimiento'] ?? '');
    $pa_nac = trim($_POST['parroquia_nacimiento'] ?? '');
    $n_acta = trim($_POST['num_acta_nac']         ?? '');
    $o_reg  = trim($_POST['oficina_registro_nac'] ?? '');

    $u_resp = $_SESSION['usuario'] ?? 'Sistema';

    // Cálculo automático de edad
    if (!empty($f_nac)) {
        $fecha_nac = new DateTime($f_nac);
        $hoy = new DateTime();
        $diferencia = $hoy->diff($fecha_nac);
        $ed = $diferencia->y;
    } else {
        throw new Exception("La fecha de nacimiento es obligatoria.");
    }

    // Manejo de nulos para base de datos
    $s1_nom_ref = ($s1_nom !== '') ? $s1_nom : null;
    $t1_nom_ref = ($t1_nom !== '') ? $t1_nom : null;
    $s1_ape_ref = ($s1_ape !== '') ? $s1_ape : null;
    $n_acta_ref = ($n_acta !== '') ? $n_acta : null;
    $o_reg_ref  = ($o_reg  !== '') ? $o_reg  : null;

    $sql = "INSERT INTO personas (
                primer_nombre, segundo_nombre, tercer_nombre, 
                primer_apellido, segundo_apellido, cedula, 
                edad, estado_civil, profesion, residencia, 
                fecha_nacimiento, sexo, nacionalidad, 
                pais_nacimiento, estado_nacimiento, municipio_nacimiento, 
                parroquia_nacimiento, num_acta_nac, oficina_registro_nac, 
                usuario_responsable
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("prep_error: " . $conn->error);
    }

    $stmt->bind_param(
        "ssssssisssssssssssss",
        $p1_nom,
        $s1_nom_ref,
        $t1_nom_ref,
        $p1_ape,
        $s1_ape_ref,
        $ced,
        $ed,
        $e_civ,
        $prof,
        $res,
        $f_nac,
        $sx,
        $nac,
        $p_nac,
        $e_nac,
        $m_nac,
        $pa_nac,
        $n_acta_ref,
        $o_reg_ref,
        $u_resp
    );

    if (!$stmt->execute()) {
        if ($conn->errno === 1062) {
            $mensaje_usuario = "La identificación ($ced) ya se encuentra registrada.";
            throw new Exception("duplicate_cedula");
        }
        throw new Exception("exec_error: " . $stmt->error);
    }

    $nuevo_id = $stmt->insert_id;
    $conn->commit();

    registrarLog($conn, $_SESSION['usuario'], "Personas", "Registro", "ID: $nuevo_id - CI: $ced");

    if (ob_get_length()) ob_clean();
    echo json_encode(['status' => 'success', 'message' => "✅ Persona registrada con éxito"]);
} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    error_log("Error Persona: [" . $e->getMessage() . "] por: " . ($_SESSION['usuario'] ?? 'N/A'));

    if (ob_get_length()) ob_clean();
    echo json_encode([
        'status' => 'error',
        'message' => (string)$mensaje_usuario
    ]);
}

if (isset($conn)) $conn->close();
