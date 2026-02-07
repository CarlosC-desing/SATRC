<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../includes/db/config.php';
include ROOT_PATH . 'modules/login/verificar_sesion.php';
include ROOT_PATH . 'includes/db/conexion.php';
include ROOT_PATH . 'functions/registrar_log.php';
include '../../functions/validaciones.php';
$_POST = sanear($_POST);

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['status' => 'error', 'message' => 'Error de seguridad: Token inválido.']);
    exit;
}

$id = $_POST['id_persona'];

// 1. Obtener la cédula actual para protegerla
$query_actual = $conn->prepare("SELECT cedula FROM personas WHERE id_persona = ?");
$query_actual->bind_param("i", $id);
$query_actual->execute();
$cedula_db = $query_actual->get_result()->fetch_assoc()['cedula'];

$nueva_cedula = !empty($_POST['cedula']) ? $_POST['cedula'] : null;

// 2. Protección: Si ya existe en DB, ignoramos lo que venga del POST para que no cambie
if (!empty($cedula_db)) {
    $nueva_cedula = $cedula_db;
}
// 3. Si es nueva, validamos duplicados
elseif (!empty($nueva_cedula)) {
    $verificar = $conn->prepare("SELECT id_persona FROM personas WHERE cedula = ? AND id_persona != ?");
    $verificar->bind_param("si", $nueva_cedula, $id);
    $verificar->execute();
    if ($verificar->get_result()->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => '⚠️ La cédula ya existe en otro registro.']);
        exit;
    }
}

// Preparamos los campos (Asegúrate de que coincidan con tu DB)
$campos = [
    'primer_nombre'    => $_POST['primer_nombre'],
    'segundo_nombre'   => $_POST['segundo_nombre'],
    'tercer_nombre'    => $_POST['tercer_nombre'],
    'primer_apellido'  => $_POST['primer_apellido'],
    'segundo_apellido' => $_POST['segundo_apellido'],
    'cedula'           => $nueva_cedula,
    'edad'             => $_POST['edad'],
    'profesion'        => $_POST['profesion'],
    'residencia'       => $_POST['residencia'],
    'fecha_nacimiento' => $_POST['fecha_nacimiento'],
    'sexo'             => $_POST['sexo'],
    'estado_civil'     => $_POST['estado_civil']
];

$set = implode(", ", array_map(fn($k) => "$k = ?", array_keys($campos)));
$valores = array_values($campos);
$tipos = str_repeat("s", count($valores));
$valores[] = $id;
$tipos .= "i";

$stmt = $conn->prepare("UPDATE personas SET $set WHERE id_persona = ?");
$stmt->bind_param($tipos, ...$valores);

if ($stmt->execute()) {
    $nombre = trim("{$_POST['primer_nombre']} {$_POST['primer_apellido']}");
    registrarLog($conn, $_SESSION['usuario'], "Personas", "Modificación", "Actualización ID $id - $nombre");
    echo json_encode(['status' => 'success', 'message' => '✅ Datos actualizados correctamente.']);
} else {
    echo json_encode(['status' => 'error', 'message' => '❌ Error al actualizar.']);
}
$conn->close();
