<?php
// --- CONFIGURACIÓN DE DEPURACIÓN ---
// Activamos reporte de errores internos pero evitamos que se impriman en pantalla para no romper el JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);

ob_start();
header('Content-Type: application/json');
mysqli_report(MYSQLI_REPORT_OFF);

try {
    // [DEBUG 1] Verificando Inclusiones
    if (!file_exists('../../includes/db/config.php')) throw new Exception("[DEBUG 1] No se encuentra config.php");
    include_once '../../includes/db/config.php';

    if (!file_exists('../../includes/db/conexion.php')) throw new Exception("[DEBUG 1] No se encuentra conexion.php");
    include '../../includes/db/conexion.php';

    include '../../modules/login/verificar_sesion.php';
    include '../../functions/registrar_log.php';
    include '../../functions/validaciones.php';

    // [DEBUG 2] Verificando Conexión y Datos POST
    if (!isset($conn) || $conn->connect_error) throw new Exception("[DEBUG 2] Error de conexión a BD: " . ($conn->connect_error ?? 'Variable $conn nula'));
    if (empty($_POST)) throw new Exception("[DEBUG 2] No llegaron datos del formulario (POST vacío). Revise el tamaño de los archivos o el formulario.");

    // Limpieza
    $_POST = sanear($_POST);
    $conn->begin_transaction();
    $usuario_resp = $_SESSION['usuario'] ?? 'Sistema';

    // --- FUNCIONES INTERNAS ---
    function obtenerIdDebug($conn, $cedula, $nombre_campo)
    {
        if (empty($cedula)) return NULL;
        $sql = "SELECT id_persona FROM personas WHERE cedula = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception("[DEBUG SQL] Error preparando consulta para $nombre_campo: " . $conn->error);
        $cedula = trim($cedula);
        $stmt->bind_param("s", $cedula);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        return $row ? $row['id_persona'] : false; // Retorna false si no existe para diferenciar de NULL
    }

    function fechaNull($fecha)
    {
        return (isset($fecha) && $fecha !== '') ? $fecha : NULL;
    }

    // [DEBUG 3] Obteniendo IDs y Validando Existencia
    $ced_d1 = $_POST['cedula_d1'] ?? '';
    $id_d1  = obtenerIdDebug($conn, $ced_d1, 'Declarante 1');
    if ($id_d1 === false) throw new Exception("[DEBUG 3] La cédula del Declarante 1 ($ced_d1) no existe en la tabla personas.");

    $ced_d2 = $_POST['cedula_d2'] ?? '';
    $id_d2  = obtenerIdDebug($conn, $ced_d2, 'Declarante 2');
    if ($id_d2 === false) throw new Exception("[DEBUG 3] La cédula del Declarante 2 ($ced_d2) no existe en la tabla personas.");

    $ced_aut = $_POST['cedula_autoridad'] ?? '';
    $id_aut = obtenerIdDebug($conn, $ced_aut, 'Autoridad');
    if ($id_aut === false) throw new Exception("[DEBUG 3] La cédula de la Autoridad ($ced_aut) no existe en la tabla personas.");

    // Testigos (Opcionales en formulario, verificamos si se enviaron)
    $ced_t1 = $_POST['cedula_t1'] ?? '';
    $id_t1  = (!empty($ced_t1)) ? obtenerIdDebug($conn, $ced_t1, 'Testigo 1') : NULL;
    if (!empty($ced_t1) && $id_t1 === false) throw new Exception("[DEBUG 3] La cédula del Testigo 1 ($ced_t1) no existe.");

    $ced_t2 = $_POST['cedula_t2'] ?? '';
    $id_t2  = (!empty($ced_t2)) ? obtenerIdDebug($conn, $ced_t2, 'Testigo 2') : NULL;
    if (!empty($ced_t2) && $id_t2 === false) throw new Exception("[DEBUG 3] La cédula del Testigo 2 ($ced_t2) no existe.");

    // [DEBUG 4] Preparando Variables y Snapshots
    $d1_ant = [];
    $edo_d1 = $_POST['d1_edo_civil_anterior'] ?? 'SOLTERO';
    if ($edo_d1 == 'DIVORCIADO') $d1_ant = ['tipo' => 'DIVORCIADO', 'tribunal' => $_POST['d1_div_tribunal'] ?? '', 'sentencia' => $_POST['d1_div_sentencia'] ?? '', 'fecha' => $_POST['d1_div_fecha'] ?? ''];
    elseif ($edo_d1 == 'VIUDO') $d1_ant = ['tipo' => 'VIUDO', 'acta' => $_POST['d1_viu_acta'] ?? '', 'fecha' => $_POST['d1_viu_fecha'] ?? ''];
    $json_d1 = !empty($d1_ant) ? json_encode($d1_ant, JSON_UNESCAPED_UNICODE) : null;

    $d2_ant = [];
    $edo_d2 = $_POST['d2_edo_civil_anterior'] ?? 'SOLTERO';
    if ($edo_d2 == 'DIVORCIADO') $d2_ant = ['tipo' => 'DIVORCIADO', 'tribunal' => $_POST['d2_div_tribunal'] ?? '', 'sentencia' => $_POST['d2_div_sentencia'] ?? '', 'fecha' => $_POST['d2_div_fecha'] ?? ''];
    elseif ($edo_d2 == 'VIUDO') $d2_ant = ['tipo' => 'VIUDO', 'acta' => $_POST['d2_viu_acta'] ?? '', 'fecha' => $_POST['d2_viu_fecha'] ?? ''];
    $json_d2 = !empty($d2_ant) ? json_encode($d2_ant, JSON_UNESCAPED_UNICODE) : null;

    $f_registro = $_POST['fecha_registro'] ?? date('Y-m-d');
    $time_reg   = strtotime($f_registro);
    $dia_acta   = date('d', $time_reg);
    $meses_es   = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'];
    $mes_acta   = $meses_es[date('n', $time_reg)];
    $anio_acta  = date('Y', $time_reg);

    // [DEBUG 5] Preparando INSERT
    $sql = "INSERT INTO union_estable (
        numero_acta, tipo_operacion, dia_acta, mes_acta, anio_acta, fecha_registro, usuario_registro,
        id_persona1, id_persona2, id_autoridad, id_testigo1, id_testigo2,
        resolucion_numero, resolucion_fecha, gaceta_numero, gaceta_fecha,
        lugar_union, fecha_inicio_union, tomo_folio, status,
        apoderado_nombre, apoderado_cedula, protocolo_notaria, apoderado_num_poder, apoderado_fecha,
        ins_h_autoridad, ins_h_numero, ins_h_extracto,
        observaciones, documentos_presentados, nota_marginal,
        d1_profesion, d1_residencia, d1_datos_anterior,
        d2_profesion, d2_residencia, d2_datos_anterior,
        t1_profesion, t1_residencia,
        t2_profesion, t2_residencia
    ) VALUES (?,?,?,?,?,?,?, ?,?,?,?,?, ?,?,?,?, ?,?,?,?, ?,?,?,?,?, ?,?,?, ?,?,?, ?,?,?, ?,?,?, ?,?, ?,?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception("[DEBUG 5] Falló prepare del INSERT principal: " . $conn->error);

    $status_def = 'activo';
    $f_inicio = fechaNull($_POST['fecha_inicio_union'] ?? '');
    $f_res = fechaNull($_POST['resolucion_fecha'] ?? '');
    $f_gac = fechaNull($_POST['gaceta_fecha'] ?? '');
    $f_apo = fechaNull($_POST['apoderado_fecha'] ?? '');

    // Bind Param
    $bind = $stmt->bind_param(
        "sssssssiiiiisssssssssssssssssssssssssssss",
        $_POST['numero_acta'],
        $_POST['tipo_operacion'],
        $dia_acta,
        $mes_acta,
        $anio_acta,
        $f_registro,
        $usuario_resp,
        $id_d1,
        $id_d2,
        $id_aut,
        $id_t1,
        $id_t2,
        $_POST['resolucion_numero'],
        $f_res,
        $_POST['gaceta_numero'],
        $f_gac,
        $_POST['lugar_union'],
        $f_inicio,
        $_POST['tomo_folio'],
        $status_def,
        $_POST['apoderado_nombre'],
        $_POST['apoderado_cedula'],
        $_POST['protocolo_notaria'],
        $_POST['apoderado_num_poder'],
        $f_apo,
        $_POST['ins_h_autoridad'],
        $_POST['ins_h_numero'],
        $_POST['ins_h_extracto'],
        $_POST['observaciones'],
        $_POST['documentos_presentados'],
        $_POST['nota_marginal'],
        $_POST['d1_profesion'],
        $_POST['d1_residencia'],
        $json_d1,
        $_POST['d2_profesion'],
        $_POST['d2_residencia'],
        $json_d2,
        $_POST['t1_profesion'],
        $_POST['t1_residencia'],
        $_POST['t2_profesion'],
        $_POST['t2_residencia']
    );

    if (!$bind) throw new Exception("[DEBUG 6] Falló bind_param. Revisa tipos de datos.");

    if (!$stmt->execute()) {
        throw new Exception("[DEBUG 7] Falló Execute: " . $stmt->error);
    }
    $id_union_final = $conn->insert_id;

    // [DEBUG 8] Hijos
    if (!empty($_POST['hijo_nom'])) {
        $sql_h = "INSERT INTO union_hijos (id_union, nombre_hijo, acta_hijo, reconocido, usuario_registro, fecha_registro) VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt_h = $conn->prepare($sql_h);
        if (!$stmt_h) throw new Exception("[DEBUG 8] Falló prepare INSERT HIJOS: " . $conn->error);

        foreach ($_POST['hijo_nom'] as $idx => $nombre) {
            if (!empty(trim($nombre))) {
                $acta_h = $_POST['hijo_acta'][$idx] ?? '';
                $rec_h  = $_POST['hijo_rec'][$idx] ?? 'NO';
                $stmt_h->bind_param("issss", $id_union_final, $nombre, $acta_h, $rec_h, $usuario_resp);
                if (!$stmt_h->execute()) {
                    // No detenemos todo por un hijo, pero podríamos loguearlo
                }
            }
        }
        $stmt_h->close();
    }

    $conn->commit();
    registrarLog($conn, $usuario_resp, "Unión Estable", "Registro Exitoso", "Acta: " . $_POST['numero_acta']);

    ob_end_clean();
    echo json_encode([
        'status' => 'success',
        'message' => 'REGISTRO EXITOSO.',
        'pdf_url' => "../actas/generar_pdf/pdf_union.php?numero_acta=" . urlencode($_POST['numero_acta'])
    ]);
} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    ob_end_clean(); // Limpiar cualquier salida previa
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
