<?php
/**
 * Registra una acción en la tabla historial_cambios.
 */
function registrarLog($conn, $usuario, $modulo, $accion, $descripcion) {
    if (!$conn) return false;

    // Se ajustaron los nombres de columnas según tu captura:
    // 'detalle' en lugar de 'descripcion'
    $sql = "INSERT INTO historial_cambios (usuario, modulo, accion, detalle, fecha) 
            VALUES (?, ?, ?, ?, NOW())";
            
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        // Pasamos la descripción al campo 'detalle' de la tabla
        $stmt->bind_param("ssss", $usuario, $modulo, $accion, $descripcion);
        $stmt->execute();
        $stmt->close();
        return true;
    }
    
    return false;
}
?>