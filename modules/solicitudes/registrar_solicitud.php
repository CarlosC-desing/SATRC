<?php
require_once '../includes/db/config.php';
include ROOT_PATH . 'modules/login/verificar_sesion.php';
include ROOT_PATH . 'includes/db/conexion.php';
include ROOT_PATH . 'functions/registrar_log.php';

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    header("Location: solicitudes.php?error=csrf");
    exit;
}

$cedula = $_POST['cedula'] ?? '';
$tipo_acta = $_POST['tipo_acta'] ?? '';
$motivo = $_POST['motivo'] ?? '';

if ($cedula && $tipo_acta && $motivo) {
    $sql = "SELECT id_persona FROM personas WHERE cedula = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $cedula);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $id_persona = $result->fetch_assoc()['id_persona'];

        $stmt = $conn->prepare("INSERT INTO solicitudes_actas (id_persona, tipo_acta, motivo, estado, fecha_solicitud) VALUES (?, ?, ?, 'pendiente', NOW())");
        $stmt->bind_param("iss", $id_persona, $tipo_acta, $motivo);
        $stmt->execute();

        $detalle = "Solicitud registrada para persona C.I. $cedula. Tipo: $tipo_acta.";
        registrarLog($conn, $_SESSION['usuario'], "Solicitudes", "Registro", $detalle);

        header("Location: panel_solicitudes.php");
        exit;
    } else {
        echo "<p>❌ No se encontró la cédula. <a href='solicitudes.php'>Volver</a></p>";
    }
} else {
    echo "<p>❌ Campos obligatorios vacíos. <a href='solicitudes.php'>Volver</a></p>";
}

$conn->close();
