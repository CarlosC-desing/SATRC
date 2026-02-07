<?php
// Archivo: functions/buscar_persona_json.php
include '../includes/db/config.php';
include '../includes/db/conexion.php';

header('Content-Type: application/json');

$cedula = $_GET['cedula'] ?? '';

if (empty($cedula)) {
    echo json_encode(['status' => 'error']);
    exit;
}

// Busca nombre y apellido
$sql = "SELECT primer_nombre, primer_apellido FROM personas WHERE cedula = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $cedula);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    echo json_encode([
        'status' => 'success',
        'nombre' => $row['primer_nombre'] . ' ' . $row['primer_apellido']
    ]);
} else {
    echo json_encode(['status' => 'error']);
}
