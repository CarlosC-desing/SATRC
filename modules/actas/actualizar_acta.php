<?php
header('Content-Type: text/html; charset=utf-8');
include_once '../config.php';
include '../login/verificar_sesion.php';
include '../conexion.php';
include '../log/registrar_log.php';

// Desactivar reportes automáticos para evitar fugas de información
mysqli_report(MYSQLI_REPORT_OFF);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // VALIDACIÓN CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("⚠️ Error de seguridad: Acción no autorizada.");
    }

    $id_persona = $_POST['id_persona'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $fecha_n = $_POST['fecha_nacimiento'];
    $lugar_n = $_POST['lugar_nacimiento'];
    $padre = $_POST['nombre_padre'];
    $madre = $_POST['nombre_madre'];

    $conn->begin_transaction();

    try {
        // SQL SEGURO CON BIND_PARAM
        $stmt1 = $conn->prepare("UPDATE Persona SET nombre=?, apellido=?, fecha_nacimiento=? WHERE id_persona=?");
        if (!$stmt1) throw new Exception("Error en preparación de datos personales.");

        $stmt1->bind_param("sssi", $nombre, $apellido, $fecha_n, $id_persona);
        $stmt1->execute();

        $stmt2 = $conn->prepare("UPDATE Nacimiento SET lugar_nacimiento=?, nombre_padre=?, nombre_madre=? WHERE id_persona=?");
        if (!$stmt2) throw new Exception("Error en preparación de datos de acta.");

        $stmt2->bind_param("sssi", $lugar_n, $padre, $madre, $id_persona);
        $stmt2->execute();

        $conn->commit();

        // Registrar Log después del commit
        registrarLog($conn, $_SESSION['usuario'], "Actas", "Actualización", "ID: $id_persona");

        echo "✅ Acta actualizada correctamente. <a href='buscar_persona.php'>Volver</a>";
    } catch (Exception $e) {
        if (isset($conn)) $conn->rollback();
        // Snyk corregido: No imprimimos $e->getMessage()
        echo "❌ Error técnico: No se pudo completar la actualización de los datos.";
    }
}

$conn->close();
