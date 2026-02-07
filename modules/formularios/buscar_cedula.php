<?php
// Limpiar cualquier salida previa (como errores de sesión) para que no se peguen al copiar
ob_clean();
header('Content-Type: text/html; charset=utf-8');

include_once '../../includes/db/config.php';
include '../../modules/login/verificar_sesion.php';
include '../../includes/db/conexion.php';

$cedula = $_GET['cedula'] ?? '';
if ($cedula === '') {
    echo "<p style='color: white;'>Debe ingresar una cédula.</p>";
    exit;
}

$sql = "SELECT id_persona, primer_nombre, segundo_nombre, tercer_nombre, primer_apellido, segundo_apellido 
        FROM personas WHERE cedula = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $cedula);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    while ($fila = $resultado->fetch_assoc()) {
        $nombre = trim("{$fila['primer_nombre']} {$fila['segundo_nombre']} {$fila['tercer_nombre']} {$fila['primer_apellido']} {$fila['segundo_apellido']}");
        $id_html = "id" . $fila['id_persona'];

        echo "
            <div class='container__resultado'>
                <div class='resultado__cabecera'>
                    <div class='resultado__container'>
                        <p class='resultado__p'>ID:</p>
                        <span class='resultado__span' id='{$id_html}'>{$fila['id_persona']}</span> 
                    </div>
                    <div class='resultado__button'>
                        <button class='resultado__button' type='button' onclick=\"copiarTexto(event, '{$id_html}')\">
                            <svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='white' style='width:30px;'>
                                <path stroke-linecap='round' stroke-linejoin='round' d='M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75' />
                            </svg>
                        </button>
                    </div>
                </div>
                <div class='resultado__line'></div>
                <div class='resultado__cuerpo'>
                    <p class='resultado__label'>Nombre</p>
                    <p class='resultado__nombre'>{$nombre}</p>
                </div>
            </div>";
    }
} else {
    echo "<p style='color: #ffcc00;'>No se encontró ninguna persona con esa cédula.</p>";
}

$stmt->close();
$conn->close();
