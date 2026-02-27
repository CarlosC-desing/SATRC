<?php
function registrarLog($conn, $usuario, $modulo, $accion, $descripcion)
{
    if (!$conn) return false;

    $sql = "INSERT INTO historial_cambios (usuario, modulo, accion, detalle, fecha) 
            VALUES (?, ?, ?, ?, NOW())";

    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("ssss", $usuario, $modulo, $accion, $descripcion);
        $stmt->execute();
        $stmt->close();
        return true;
    }

    return false;
}
