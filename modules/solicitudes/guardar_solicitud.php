<?php
require_once '../../includes/db/config.php';
include '../../modules/login/verificar_sesion.php';
include '../../includes/db/conexion.php';
include '../../functions/registrar_log.php';

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    header("Location: solicitudes.php?error=csrf");
    exit;
}

$id_persona = $_POST['id_persona'] ?? '';
$tipo_acta = $_POST['tipo_acta'] ?? '';
$motivo = $_POST['motivo'] ?? '';

if ($id_persona && $tipo_acta && $motivo) {
    // Insertar solicitud
    $stmt = $conn->prepare("INSERT INTO solicitudes_actas (id_persona, tipo_acta, motivo, estado, fecha_solicitud) VALUES (?, ?, ?, 'pendiente', NOW())");
    $stmt->bind_param("iss", $id_persona, $tipo_acta, $motivo);

    if ($stmt->execute()) {
        $detalle = "Solicitud registrada para persona ID $id_persona. Tipo: $tipo_acta.";
        registrarLog($conn, $_SESSION['usuario'], "Solicitudes", "Registro de solicitud", $detalle);

        header("Location: panel_solicitudes.php?mensaje=ok");
        exit;
    } else {
        echo "❌ Error al guardar: " . $conn->error;
    }
} else {
    echo "<p>❌ Error: No se seleccionó una persona válida o faltan campos. <a href='solicitudes.php'>Volver</a></p>";
}

$conn->close();
