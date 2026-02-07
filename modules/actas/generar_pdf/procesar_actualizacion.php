<?php
header('Content-Type: text/html; charset=utf-8');
include_once '../config.php';
include '../login/verificar_sesion.php';
include '../conexion.php';
include '../log/registrar_log.php';

$id = $_POST['id_persona'];
$cedula = $_POST['cedula'] ?? null;

// 1. CAPTURA Y SANEAMIENTO DE DATOS
$p_nom = $_POST['primer_nombre'] ?? '';
$s_nom = $_POST['segundo_nombre'] ?? '';
$t_nom = $_POST['tercer_nombre'] ?? '';
$p_ape = $_POST['primer_apellido'] ?? '';
$s_ape = $_POST['segundo_apellido'] ?? '';
$f_nac = $_POST['fecha_nacimiento'] ?? '';
$sexo  = $_POST['sexo'] ?? '';
$e_civ = $_POST['estado_civil'] ?? '';

// 2. VERIFICACIÓN DE CÉDULA (Si se proporciona)
if (!empty($cedula)) {
    $verificar = $conn->prepare("SELECT id_persona FROM personas WHERE cedula = ? AND id_persona != ?");
    $verificar->bind_param("si", $cedula, $id);
    $verificar->execute();
    $res = $verificar->get_result();
    if ($res->num_rows > 0) {
        echo "⚠️ La cédula ya está registrada en otra persona.";
        exit;
    }
}

// 3. CONSULTA DE ACTUALIZACIÓN (Columnas fijas para satisfacer a Snyk)
$sql = "UPDATE personas SET 
        primer_nombre = ?, 
        segundo_nombre = ?, 
        tercer_nombre = ?, 
        primer_apellido = ?, 
        segundo_apellido = ?, 
        fecha_nacimiento = ?, 
        sexo = ?, 
        estado_civil = ?, 
        cedula = ? 
        WHERE id_persona = ?";

$stmt = $conn->prepare($sql);
// Pasamos todos los valores, incluyendo la cédula (que puede ser null)
$stmt->bind_param("sssssssssi", $p_nom, $s_nom, $t_nom, $p_ape, $s_ape, $f_nac, $sexo, $e_civ, $cedula, $id);

if ($stmt->execute()) {
    // 📝 Registrar en log
    $nombre_completo = trim("$p_nom $s_nom $t_nom $p_ape $s_ape");
    $detalle = "Modificación de datos para persona ID $id. Nombre: $nombre_completo, F.Nac: $f_nac, Edo.Civil: $e_civ, Sexo: $sexo" . ($cedula ? ", Cédula: $cedula" : "");

    registrarLog($conn, $_SESSION['usuario'], "Personas", "Modificación", $detalle);

    echo "✅ Datos actualizados correctamente. <a href='buscar_persona.php'>Volver</a>";
} else {
    echo "❌ Error al actualizar.";
}

$conn->close();
