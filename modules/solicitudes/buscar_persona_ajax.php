<?php
require_once '../../includes/db/config.php';
include '../../modules/login/verificar_sesion.php';
include '../../includes/db/conexion.php';
include_once '../../functions/validaciones.php';
$_POST = sanear($_POST);
$_GET = sanear($_GET);

$cedula = $_GET['cedula'] ?? '';

// Saneamiento básico de entrada
$cedula = preg_replace('/[^0-9]/', '', $cedula);

$sql = "SELECT id_persona, cedula, primer_nombre, primer_apellido FROM personas WHERE cedula LIKE ? LIMIT 5";
$stmt = $conn->prepare($sql);
$cedula_param = $cedula . '%';
$stmt->bind_param("s", $cedula_param);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<ul class='lista-busqueda'>";
    while ($p = $result->fetch_assoc()) {
        // CORRECCIÓN SEGURIDAD: Escapar salida para evitar XSS almacenado
        $nombre_safe = htmlspecialchars($p['primer_nombre'] . ' ' . $p['primer_apellido']);
        $cedula_safe = htmlspecialchars($p['cedula']);
        $id_safe = (int)$p['id_persona']; // Casting a int es seguro

        // Nota: En onclick usamos addslashes para no romper el JS
        $nombre_js = addslashes($p['primer_nombre'] . ' ' . $p['primer_apellido']);

        echo "<li style='cursor:pointer; padding:5px; border-bottom:1px solid #ddd;' 
                  onclick=\"seleccionarPersona('$cedula_safe', '$id_safe', '$nombre_js')\">
                  $cedula_safe — $nombre_safe
              </li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color:red;'>No hay coincidencias</p>";
}
