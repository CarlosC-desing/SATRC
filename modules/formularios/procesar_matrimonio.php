<?php
ob_start();

// --- RUTAS E INCLUDES ---
include_once '../../includes/db/config.php';
include '../../modules/login/verificar_sesion.php';
include '../../includes/db/conexion.php';
include '../../functions/registrar_log.php';
include '../../functions/validaciones.php';

// Limpieza de entrada
$_POST = sanear($_POST);

if (ob_get_length()) ob_clean();
header('Content-Type: application/json');

// Desactivar reporte de errores visuales en la respuesta JSON
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

function fechaNull($fecha)
{
    return (isset($fecha) && $fecha !== '') ? $fecha : NULL;
}

try {
    // --- MENSAJES DE ERROR ---
    $msj_servidor   = "Ocurrió un error inesperado al procesar el registro.";
    $msj_cedula     = "Error: Alguna cédula (Contrayentes/Autoridad) no está registrada.";
    $msj_duplicado  = "Ya existe un registro de matrimonio con este Número de Acta.";

    // 1. Verificaciones
    if (!isset($conn) || $conn->connect_error) throw new Exception("db_conn_error");

    $conn->begin_transaction();
    $usuario_resp = $_SESSION['usuario'] ?? 'Sistema';

    // --- 2. OBTENCIÓN DE IDs ---
    $id_c1  = obtenerIdPorCedula($conn, $_POST['cedula_esposo'] ?? '');
    $id_c2  = obtenerIdPorCedula($conn, $_POST['cedula_esposa'] ?? '');
    $id_aut = obtenerIdPorCedula($conn, $_POST['cedula_autoridad'] ?? '');
    $id_t1  = obtenerIdPorCedula($conn, $_POST['cedula_t1'] ?? '');
    $id_t2  = obtenerIdPorCedula($conn, $_POST['cedula_t2'] ?? '');

    if (!$id_c1 || !$id_c2 || !$id_aut) throw new Exception("CODE_CEDULA_NOT_FOUND");

    // --- 3. PREPARAR DATOS SNAPSHOT (Estado Civil Anterior) ---
    // JSON para Contrayente 1
    $c1_ant = [];
    $edo_c1 = $_POST['c1_edo_civil_anterior'] ?? 'SOLTERO';
    if ($edo_c1 == 'DIVORCIADO') {
        $c1_ant = ['tipo' => 'DIVORCIADO', 'tribunal' => $_POST['c1_div_tribunal'], 'sentencia' => $_POST['c1_div_sentencia'], 'fecha' => $_POST['c1_div_fecha']];
    } elseif ($edo_c1 == 'VIUDO') {
        $c1_ant = ['tipo' => 'VIUDO', 'acta' => $_POST['c1_viu_acta'], 'fecha' => $_POST['c1_viu_fecha']];
    }
    $json_c1_anterior = !empty($c1_ant) ? json_encode($c1_ant, JSON_UNESCAPED_UNICODE) : null;

    // JSON para Contrayente 2
    $c2_ant = [];
    $edo_c2 = $_POST['c2_edo_civil_anterior'] ?? 'SOLTERO';
    if ($edo_c2 == 'DIVORCIADO') {
        $c2_ant = ['tipo' => 'DIVORCIADO', 'tribunal' => $_POST['c2_div_tribunal'], 'sentencia' => $_POST['c2_div_sentencia'], 'fecha' => $_POST['c2_div_fecha']];
    } elseif ($edo_c2 == 'VIUDO') {
        $c2_ant = ['tipo' => 'VIUDO', 'acta' => $_POST['c2_viu_acta'], 'fecha' => $_POST['c2_viu_fecha']];
    }
    $json_c2_anterior = !empty($c2_ant) ? json_encode($c2_ant, JSON_UNESCAPED_UNICODE) : null;

    // --- 4. PREPARAR VARIABLES Y FECHAS ---
    $f_registro    = fechaNull($_POST['fecha_registro']);
    $f_celebracion = fechaNull($_POST['fecha_matrimonio']); // Del form name="fecha_matrimonio" a BD fecha_celebracion
    $h_celebracion = !empty($_POST['hora_matrimonio']) ? $_POST['hora_matrimonio'] : NULL; // Del form name="hora_matrimonio"

    $f_resolucion  = fechaNull($_POST['resolucion_fecha']);
    $f_gaceta      = fechaNull($_POST['gaceta_fecha']);

    $f_cap_fecha   = fechaNull($_POST['cap_fecha']);
    $f_ins_fecha   = fechaNull($_POST['ins_fecha_doc']);
    $f_apo_fecha   = fechaNull($_POST['apoderado_fecha']);

    // Si es inserción, la fecha de celebración es la del documento
    if ($_POST['modo_registro'] == 'insercion') {
        $f_celebracion = $f_ins_fecha;
    }

    // --- 5. INSERT EN TABLA MATRIMONIO ---
    $sql = "INSERT INTO matrimonio (
        -- 1-5
        numero_acta, n_expediente, fecha_registro, usuario_responsable, modo_registro,
        -- 6-9
        resolucion_numero, resolucion_fecha, gaceta_numero, gaceta_fecha,
        -- 10-13
        lugar_matrimonio, fecha_celebracion, hora_celebracion, id_autoridad,
        -- 14-17 (C1)
        id_contrayente1, c1_profesion, c1_residencia, c1_datos_anterior,
        -- 18-21 (C2)
        id_contrayente2, c2_profesion, c2_residencia, c2_datos_anterior,
        -- 22-24 (T1)
        id_t1, t1_profesion, t1_residencia,
        -- 25-27 (T2)
        id_t2, t2_profesion, t2_residencia,
        -- 28-32 (Capitulaciones)
        cap_numero, cap_libro, cap_protocolo, cap_fecha, cap_autoridad,
        -- 33-36 (Insercion)
        ins_fecha_doc, ins_numero_doc, ins_autoridad, ins_extracto,
        -- 37-41 (Apoderado)
        apoderado_nombre, apoderado_cedula, apoderado_registro, apoderado_num_poder, apoderado_fecha,
        -- 42-44 (Notas)
        observaciones, documentos_presentados, nota_marginal
    ) VALUES (
        ?, ?, ?, ?, ?, 
        ?, ?, ?, ?, 
        ?, ?, ?, ?, 
        ?, ?, ?, ?, 
        ?, ?, ?, ?, 
        ?, ?, ?, 
        ?, ?, ?, 
        ?, ?, ?, ?, ?, 
        ?, ?, ?, ?, 
        ?, ?, ?, ?, ?, 
        ?, ?, ?
    )";

    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception("Error BD Prepare: " . $conn->error);

    // BIND PARAM (44 variables exactas)
    // Tipos: s=string, i=integer (IDs)
    // IDs son: id_aut, id_c1, id_c2, id_t1, id_t2 => 5 enteros ('i')
    // Total letras: 44. (5 IDs son 'i', resto 's')

    // String de tipos (44 caracteres):
    // sssss ssss sssi isss isss iss iss sssss ssss sssss sss
    $types = "ssssssssssssiisssisssississsssssssssssssssss"; // 44 chars

    $stmt->bind_param(
        $types,
        // 1-5
        $_POST['numero_acta'],
        $_POST['n_expediente'],
        $f_registro,
        $usuario_resp,
        $_POST['modo_registro'],
        // 6-9
        $_POST['resolucion_numero'],
        $f_resolucion,
        $_POST['gaceta_numero'],
        $f_gaceta,
        // 10-13
        $_POST['lugar_matrimonio'],
        $f_celebracion,
        $h_celebracion,
        $id_aut, // int
        // 14-17 (C1)
        $id_c1, // int
        $_POST['c1_profesion'],
        $_POST['c1_residencia'],
        $json_c1_anterior,
        // 18-21 (C2)
        $id_c2, // int
        $_POST['c2_profesion'],
        $_POST['c2_residencia'],
        $json_c2_anterior,
        // 22-24 (T1)
        $id_t1, // int
        $_POST['t1_profesion'],
        $_POST['t1_residencia'],
        // 25-27 (T2)
        $id_t2, // int
        $_POST['t2_profesion'],
        $_POST['t2_residencia'],
        // 28-32 (Cap) - Mapeo de nombres del HTML a columnas BD
        $_POST['cap_numero'],
        $_POST['cap_tomo'],      // En BD: cap_libro
        $_POST['cap_folio'],     // En BD: cap_protocolo
        $f_cap_fecha,
        $_POST['cap_oficina'],   // En BD: cap_autoridad (Form usa cap_oficina)
        // 33-36 (Ins)
        $f_ins_fecha,
        $_POST['ins_numero_doc'],
        $_POST['ins_autoridad'],
        $_POST['ins_extracto'],
        // 37-41 (Apo)
        $_POST['apoderado_nombre'],
        $_POST['apoderado_cedula'],
        $_POST['apoderado_registro'],
        $_POST['apoderado_num_poder'],
        $f_apo_fecha,
        // 42-44
        $_POST['observaciones'],
        $_POST['documentos_presentados'],
        $_POST['nota_marginal']
    );

    if (!$stmt->execute()) {
        if ($conn->errno == 1062) throw new Exception("duplicate_acta_error");
        throw new Exception("Error Execute: " . $stmt->error);
    }

    $id_matrimonio_final = $conn->insert_id;

    // --- 6. PROCESAR HIJOS ---
    if (!empty($_POST['hijo_nom'])) {
        $sql_hijo = "INSERT INTO matrimonio_hijos (id_matrimonio, nombre_hijo, acta_hijo, reconocido, usuario_registro) VALUES (?, ?, ?, ?, ?)";
        $stmt_h = $conn->prepare($sql_hijo);

        foreach ($_POST['hijo_nom'] as $idx => $nombre) {
            if (!empty(trim($nombre))) {
                $acta_h = $_POST['hijo_acta'][$idx] ?? '';
                $rec_h  = $_POST['hijo_rec'][$idx] ?? 'NO';
                $stmt_h->bind_param("issss", $id_matrimonio_final, $nombre, $acta_h, $rec_h, $usuario_resp);
                if (!$stmt_h->execute()) {
                    // Opcional: loguear error de hijo pero no detener todo
                    error_log("Error hijo: " . $stmt_h->error);
                }
            }
        }
        $stmt_h->close();
    }

    $conn->commit();
    registrarLog($conn, $usuario_resp, "Matrimonio", "Registro Exitoso", "Acta: " . $_POST['numero_acta']);

    echo json_encode([
        'status' => 'success',
        'message' => 'Matrimonio registrado correctamente.',
        'pdf_url' => "../actas/generar_pdf/pdf_matrimonio.php?numero_acta=" . urlencode($_POST['numero_acta'])
    ]);
} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();

    $msg = $msj_servidor;
    if ($e->getMessage() === "CODE_CEDULA_NOT_FOUND") $msg = $msj_cedula;
    elseif ($e->getMessage() === "duplicate_acta_error") $msg = $msj_duplicado;
    else $msg = "Error técnico: " . $e->getMessage();

    echo json_encode(['status' => 'error', 'message' => $msg]);
}
