<?php
// modules/solicitudes/buscar_persona_ajax.php
require_once '../../includes/db/config.php';
// include '../../modules/login/verificar_sesion.php'; // Descomenta si usas sesión
include '../../includes/db/conexion.php';

$cedula = $_GET['cedula'] ?? '';

// 1. SANEAMIENTO MODIFICADO:
// Permitimos números, letras (V, E, P) y el guion (-). 
// La 'i' al final hace que no importe si es mayúscula o minúscula.
$cedula = preg_replace('/[^0-9VEPvep-]/', '', $cedula);

if (empty($cedula)) {
    exit('');
}

// 2. CONSULTA SEGURA
// Buscamos coincidencia exacta del inicio (ej: V-123...)
$sql = "SELECT id_persona, cedula, primer_nombre, primer_apellido 
        FROM personas 
        WHERE cedula LIKE ? 
        LIMIT 5";

$stmt = $conn->prepare($sql);
$cedula_param = $cedula . '%';
$stmt->bind_param("s", $cedula_param);
$stmt->execute();
$result = $stmt->get_result();

// 3. GENERAR HTML
if ($result->num_rows > 0) {
    echo "<ul class='lista-resultados-busqueda'>";

    while ($p = $result->fetch_assoc()) {
        $nombre_completo = $p['primer_nombre'] . ' ' . $p['primer_apellido'];

        $nombre_safe = htmlspecialchars($nombre_completo);
        $cedula_safe = htmlspecialchars($p['cedula']);
        $id_safe = (int)$p['id_persona'];

        // Preparar para JS
        $nombre_js = json_encode($nombre_completo);
        $nombre_js_limpio = trim($nombre_js, '"');

        echo "<li onclick=\"seleccionarPersona('$cedula_safe', '$id_safe', '$nombre_js_limpio')\">
                <strong>$cedula_safe</strong> — $nombre_safe
              </li>";
    }
    echo "</ul>";
} else {
    echo "<div class='sin-resultados'>No se encontraron coincidencias</div>";
}

$stmt->close();
$conn->close();
