<?php
header('Content-Type: text/html; charset=utf-8');
require_once '../../includes/db/config.php';
include ROOT_PATH . 'modules/login/verificar_sesion.php';
include ROOT_PATH . 'includes/db/conexion.php';
include ROOT_PATH . 'functions/registrar_log.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificación de seguridad CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Error de seguridad: Acción no autorizada.");
    }

    if (isset($_POST['id_persona'])) {
        $id_persona = (int)$_POST['id_persona'];

        // CONSULTA PREPARADA: Tabla 'personas' en minúsculas
        $stmt = $conn->prepare("DELETE FROM personas WHERE id_persona = ?");
        $stmt->bind_param("i", $id_persona);

        if ($stmt->execute()) {
            registrarLog($conn, $_SESSION['usuario'], "Actas", "Eliminación", "Eliminó ID: $id_persona");
            echo "Acta eliminada correctamente.";
        } else {
            echo "Error al eliminar: " . $conn->error;
        }
        $stmt->close();
    }
}
$conn->close();
