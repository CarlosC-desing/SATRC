<?php
header('Content-Type: text/html; charset=utf-8');
require_once '../../includes/db/config.php';
include ROOT_PATH . 'modules/login/verificar_sesion.php';
include ROOT_PATH . 'includes/db/conexion.php';
include ROOT_PATH . 'functions/registrar_log.php';
include '../../functions/validaciones.php';

$row = null;
$id_persona = isset($_GET['id_persona']) ? (int)$_GET['id_persona'] : 0;

if ($id_persona > 0) {
    // CONSULTA PREPARADA: Cambiado a minúsculas para compatibilidad (personas/nacimiento)
    $sql = "SELECT p.primer_nombre, p.primer_apellido, p.fecha_nacimiento, n.lugar_nacimiento, n.nombre_padre, n.nombre_madre 
            FROM personas p
            JOIN nacimiento n ON p.id_persona = n.id_persona
            WHERE p.id_persona = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_persona);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
}

if (!$row) {
    die("Error: No se encontró la persona o el acta vinculada.");
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Editar Acta de Nacimiento</title>
</head>

<body>
    <h2>Editar Acta de Nacimiento</h2>
    <form action="actualizar_acta.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="hidden" name="id_persona" value="<?php echo $id_persona; ?>">

        <label for="nombre">Nombre:</label>
        <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($row['primer_nombre']); ?>" required><br><br>

        <label for="apellido">Apellido:</label>
        <input type="text" id="apellido" name="apellido" value="<?php echo htmlspecialchars($row['primer_apellido']); ?>" required><br><br>

        <label for="fecha_nacimiento">Fecha de Nacimiento:</label>
        <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" value="<?php echo $row['fecha_nacimiento']; ?>" required><br><br>

        <label for="lugar_nacimiento">Lugar de Nacimiento:</label>
        <input type="text" id="lugar_nacimiento" name="lugar_nacimiento" value="<?php echo htmlspecialchars($row['lugar_nacimiento']); ?>" required><br><br>

        <label for="nombre_padre">Nombre del Padre:</label>
        <input type="text" id="nombre_padre" name="nombre_padre" value="<?php echo htmlspecialchars($row['nombre_padre']); ?>" required><br><br>

        <label for="nombre_madre">Nombre de la Madre:</label>
        <input type="text" id="nombre_madre" name="nombre_madre" value="<?php echo htmlspecialchars($row['nombre_madre']); ?>" required><br><br>

        <button type="submit">Actualizar Acta</button>
    </form>
</body>

</html>