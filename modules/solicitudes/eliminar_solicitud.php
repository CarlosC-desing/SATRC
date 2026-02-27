<?php
require_once '../../includes/db/config.php';
include '../../modules/login/verificar_sesion.php';
include '../../includes/db/conexion.php';
include '../../functions/registrar_log.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_eliminar'])) {

    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: panel_solicitudes.php?error=csrf");
        exit;
    }

    $id = (int)$_POST['id_eliminar'];

    $stmt_select = $conn->prepare("SELECT tipo_acta, motivo FROM solicitudes_actas WHERE id_solicitud = ?");
    $stmt_select->bind_param("i", $id);
    $stmt_select->execute();
    $res = $stmt_select->get_result();
    $datos = $res->fetch_assoc();

    if ($datos) {
        $stmt = $conn->prepare("DELETE FROM solicitudes_actas WHERE id_solicitud = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $tipo = $datos['tipo_acta'] ?? 'Desconocido';
            $motivo = $datos['motivo'] ?? 'Sin motivo';
            $detalle = "Solicitud eliminada. ID: $id, Tipo: $tipo, Motivo: $motivo";

            registrarLog($conn, $_SESSION['usuario'], "Solicitudes", "Eliminación", $detalle);

            header("Location: panel_solicitudes.php?msg=eliminado");
            exit();
        } else {
            header("Location: panel_solicitudes.php?error=db");
            exit();
        }
        $stmt->close();
    } else {
        header("Location: panel_solicitudes.php?error=no_encontrado");
        exit();
    }
} else {
    header("Location: panel_solicitudes.php?error=invalid_request");
    exit();
}

$conn->close();
