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

// --- FUNCIONES AUXILIARES ---
function obtenerIdPorCedula($conn, $cedula)
{
    if (empty($cedula)) return NULL;
    $sql = "SELECT id_persona FROM personas WHERE cedula = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return NULL;
    $cedula = trim($cedula);
    $stmt->bind_param("s", $cedula);
    $stmt->execute();
    $res = $stmt->get_result();
    return ($row = $res->fetch_assoc()) ? $row['id_persona'] : NULL;
}

function obtenerApellidosPersona($conn, $id)
{
    if (!$id) return ['primer_apellido' => '', 'segundo_apellido' => ''];
    $sql = "SELECT primer_apellido, segundo_apellido FROM personas WHERE id_persona = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

try {
    // 1. VALIDACIÓN DE SEGURIDAD: ACTA DUPLICADA
    $num_acta = trim($_POST['numero_acta']);
    if (empty($num_acta)) throw new Exception("El número de acta es obligatorio.");

    $checkSql = "SELECT numero_acta FROM nacimiento WHERE numero_acta = ? LIMIT 1";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("s", $num_acta);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        $checkStmt->close();
        throw new Exception("ERROR CRÍTICO: El Número de Acta '{$num_acta}' ya está registrado en el sistema. Verifique el consecutivo.");
    }
    $checkStmt->close();

    // INICIO TRANSACCIÓN
    $conn->begin_transaction();
    $usuario_sesion = $_SESSION['usuario'];

    // 2. RECUPERAR INTERVINIENTES
    $id_registrador = obtenerIdPorCedula($conn, $_POST['cedula_registrador']);
    $id_declarante  = obtenerIdPorCedula($conn, $_POST['cedula_declarante']);

    if (!$id_registrador || !$id_declarante) {
        throw new Exception("Error: Cédula del Registrador o Declarante no encontrada.");
    }

    $id_madre = obtenerIdPorCedula($conn, $_POST['cedula_madre']);
    $id_padre = obtenerIdPorCedula($conn, $_POST['cedula_padre']);
    $id_t1    = obtenerIdPorCedula($conn, $_POST['cedula_t1']);
    $id_t2    = obtenerIdPorCedula($conn, $_POST['cedula_t2']);

    if (!$id_madre) throw new Exception("Error: La cédula de la madre es obligatoria y debe estar registrada.");

    // 3. LÓGICA DE APELLIDOS AUTOMÁTICA
    $datos_madre = obtenerApellidosPersona($conn, $id_madre);
    $p_apellido = "";
    $s_apellido = "";

    if ($id_padre) {
        // CON PADRE: 1er Apellido Padre + 1er Apellido Madre
        $datos_padre = obtenerApellidosPersona($conn, $id_padre);
        $p_apellido = $datos_padre['primer_apellido'];
        $s_apellido = $datos_madre['primer_apellido'];
    } else {
        // MADRE SOLTERA: 1er Apellido Madre + 2do Apellido Madre
        $p_apellido = $datos_madre['primer_apellido'];
        $s_apellido = $datos_madre['segundo_apellido'];
    }

    // 4. DATOS DEL NIÑO (Corregido para recibir inputs separados)
    $p_nombre = isset($_POST['nacido_primer_nombre']) ? trim($_POST['nacido_primer_nombre']) : '';
    $s_nombre = isset($_POST['nacido_segundo_nombre']) ? trim($_POST['nacido_segundo_nombre']) : '';

    if (empty($p_nombre)) throw new Exception("El primer nombre del nacido es obligatorio.");

    // 5. CORRECCIÓN DEL SEXO (Solución definitiva)
    // Toma lo que llegue, quita espacios, convierte a mayúsculas y verifica la primera letra.
    $sexo_raw = strtoupper(trim($_POST['nacido_sexo'] ?? ''));

    if (empty($sexo_raw)) throw new Exception("Debe seleccionar el sexo del nacido.");

    // Si empieza por 'M' (M, Masculino, MASCULINO) asigna 'M', sino 'F'
    $sexo_final = (strpos($sexo_raw, 'M') === 0) ? 'M' : 'F';

    // 6. INSERTAR PERSONA (RECIÉN NACIDO)
    $sql_persona = "INSERT INTO personas (primer_nombre, segundo_nombre, primer_apellido, segundo_apellido, sexo, fecha_nacimiento) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_p = $conn->prepare($sql_persona);
    $stmt_p->bind_param("ssssss", $p_nombre, $s_nombre, $p_apellido, $s_apellido, $sexo_final, $_POST['nacido_fecha']);

    if (!$stmt_p->execute()) {
        throw new Exception("Error al registrar persona: " . $stmt_p->error);
    }
    $id_nacido = $conn->insert_id;

    // 7. PREPARAR DATOS ESPECIALES (JSON)
    $modo = $_POST['modo_registro'];
    $datos_especiales = [];
    switch ($modo) {
        case 'insercion':
            $datos_especiales = ['acta_num' => $_POST['ins_h_numero'] ?? '', 'fecha' => $_POST['ins_h_fecha'] ?? '', 'autoridad' => $_POST['ins_h_autoridad'] ?? ''];
            break;
        case 'medida_proteccion':
            $datos_especiales = ['consejo' => $_POST['ins_i_consejo'] ?? '', 'medida_num' => $_POST['ins_i_medida'] ?? '', 'fecha' => $_POST['ins_i_fecha'] ?? '', 'consejero' => $_POST['ins_i_consejero'] ?? ''];
            break;
        case 'sentencia_judicial':
            $datos_especiales = ['tribunal' => $_POST['ins_j_tribunal'] ?? '', 'sentencia_num' => $_POST['ins_j_sentencia'] ?? '', 'fecha' => $_POST['ins_j_fecha'] ?? '', 'juez' => $_POST['ins_j_juez'] ?? ''];
            break;
        case 'extemporanea':
            $datos_especiales = ['datos_informe' => $_POST['ins_k_datos'] ?? '', 'fecha' => $_POST['ins_k_fecha'] ?? '', 'autoridad' => $_POST['ins_k_autoridad'] ?? ''];
            break;
    }
    $json_especial = !empty($datos_especiales) ? json_encode($datos_especiales, JSON_UNESCAPED_UNICODE) : null;

    // 8. INSERTAR ACTA DE NACIMIENTO
    $sql = "INSERT INTO nacimiento (
        numero_acta, fecha_registro, id_registrador, id_nacido, resolucion_numero, gaceta_numero,
        primer_nombre, segundo_nombre, primer_apellido, segundo_apellido,
        sexo, fecha_nacimiento, hora_nacimiento, lugar_nacimiento, direccion_nacimiento,
        certificado_num, mpps_num, fecha_certificado, autoridad_medica,
        id_madre, madre_profesion, madre_residencia,
        id_padre, padre_profesion, padre_residencia,
        id_declarante, calidad_declarante,
        id_testigo1, t1_profesion, t1_residencia,
        id_testigo2, t2_profesion, t2_residencia,
        modo_registro, datos_especiales_json,
        observaciones, documentos_presentados, nota_marginal
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    // Total: 38 variables
    $stmt->bind_param(
        "ssiisssssssssssssssississisississsssss",
        $num_acta,                  // 1. Validado al inicio
        $_POST['fecha_registro'],
        $id_registrador,
        $id_nacido,
        $_POST['resolucion_numero'],
        $_POST['gaceta_numero'],
        $p_nombre,
        $s_nombre,
        $p_apellido,
        $s_apellido,
        $sexo_final,                // 11. Sexo corregido
        $_POST['nacido_fecha'],
        $_POST['nacido_hora'],
        $_POST['nacido_lugar'],
        $_POST['nacido_direccion_lugar'],
        $_POST['cert_numero'],
        $_POST['cert_mpps'],
        $_POST['cert_fecha'],
        $_POST['cert_autoridad_medica'],
        $id_madre,
        $_POST['madre_profesion'],
        $_POST['madre_residencia'],
        $id_padre,
        $_POST['padre_profesion'],
        $_POST['padre_residencia'],
        $id_declarante,
        $_POST['declarante_caracter'],
        $id_t1,
        $_POST['t1_profesion'],
        $_POST['t1_residencia'],
        $id_t2,
        $_POST['t2_profesion'],
        $_POST['t2_residencia'],
        $modo,
        $json_especial,
        $_POST['obs_circunstancias'],
        $_POST['obs_documentos'],
        $_POST['nota_marginal']
    );

    if (!$stmt->execute()) {
        // Si el error es por duplicado (código 1062), lanzamos excepción clara
        if ($conn->errno == 1062) {
            throw new Exception("Error: El número de acta o cédula ya está registrado (Duplicado).");
        }
        throw new Exception("Error al guardar el acta: " . $stmt->error);
    }

    $conn->commit();
    registrarLog($conn, $usuario_sesion, "Nacimiento", "Registro Exitoso", "Acta " . $num_acta);

    echo json_encode([
        'status' => 'success',
        'message' => 'Nacimiento registrado exitosamente.',
        'pdf_url' => '../actas/generar_pdf/pdf_nacimiento.php?numero_acta=' . urlencode($num_acta)
    ]);
} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    error_log("Error Nacimiento: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
