<?php
// CORRECCIÓN: Rutas ajustadas para subir dos niveles y entrar a las carpetas correctas
require_once '../../includes/db/config.php';
include '../../modules/login/verificar_sesion.php';
include '../../includes/db/conexion.php';
include '../../functions/registrar_log.php';

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    header("Location: solicitudes.php?error=csrf");
    exit;
}

// Recibimos el ID desde el campo oculto que genera el AJAX en solicitudes.php
$id_persona = $_POST['id_persona'] ?? '';
$tipo_acta = $_POST['tipo_acta'] ?? '';
$motivo = $_POST['motivo'] ?? '';

if ($id_persona && $tipo_acta && $motivo) {
    // Insertar solicitud
    $stmt = $conn->prepare("INSERT INTO solicitudes_actas (id_persona, tipo_acta, motivo, estado, fecha_solicitud) VALUES (?, ?, ?, 'pendiente', NOW())");
    $stmt->bind_param("iss", $id_persona, $tipo_acta, $motivo);

    if ($stmt->execute()) {
        // Registrar en historial de cambios 📝
        $detalle = "Solicitud registrada para persona ID $id_persona. Tipo: $tipo_acta.";
        registrarLog($conn, $_SESSION['usuario'], "Solicitudes", "Registro de solicitud", $detalle);

        header("Location: panel_solicitudes.php?mensaje=ok");
        exit;
    } else {
        echo "❌ Error al guardar: " . $conn->error;
    }
} else {
    // Nota: El enlace vuelve a solicitudes.php que es tu archivo de registro
    echo "<p>❌ Error: No se seleccionó una persona válida o faltan campos. <a href='solicitudes.php'>Volver</a></p>";
}

$conn->close();
