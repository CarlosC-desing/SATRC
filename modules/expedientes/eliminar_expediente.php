<?php
require_once '../../includes/db/config.php';
include ROOT_PATH . 'modules/login/verificar_sesion.php';
include ROOT_PATH . 'includes/db/conexion.php';
include ROOT_PATH . 'functions/registrar_log.php';

if (isset($_GET['id_exp']) && isset($_GET['id_persona'])) {
    $id_exp = (int)$_GET['id_exp'];
    $id_persona = (int)$_GET['id_persona'];

    // 1. Obtener datos del archivo y la cédula del ciudadano (JOIN) para el log
    $sql = "SELECT e.ruta_archivo, e.tipo_documento, p.cedula 
            FROM expedientes e 
            JOIN personas p ON e.id_persona = p.id_persona 
            WHERE e.id_expediente = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_exp);
    $stmt->execute();
    $resultado = $stmt->get_result()->fetch_assoc();

    if ($resultado) {
        $ruta_completa = ROOT_PATH . $resultado['ruta_archivo']; // Ajuste de ruta física

        // 2. Eliminar archivo físico
        if (file_exists($ruta_completa)) {
            unlink($ruta_completa);
        }

        // 3. Eliminar registro de la BD
        $delete = $conn->prepare("DELETE FROM expedientes WHERE id_expediente = ?");
        $delete->bind_param("i", $id_exp);

        if ($delete->execute()) {
            // --- LOG PERSONALIZADO ---
            $desc_log = "Eliminó el documento " . $resultado['tipo_documento'] . " al ciudadano " . $resultado['cedula'];
            registrarLog($conn, $_SESSION['usuario'], "Expedientes", "Elimino", $desc_log);
            // -------------------------
        }
        $delete->close();
    }
    $stmt->close();

    // Redirigir
    header("Location: ver_expediente.php?id=" . $id_persona . "&msg=eliminado");
    exit;
} else {
    header("Location: ../actas/buscar.php");
    exit;
}
