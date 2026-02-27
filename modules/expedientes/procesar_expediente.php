<?php
require_once '../../includes/db/config.php';
include ROOT_PATH . 'modules/login/verificar_sesion.php';
include ROOT_PATH . 'includes/db/conexion.php';
include ROOT_PATH . 'functions/registrar_log.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['archivo'])) {

    $id_persona = (int)$_POST['id_persona'];

    $tipo_doc = preg_replace('/[^a-zA-Z0-9]/', '', $_POST['tipo_documento']);
    $cedula = !empty($_POST['cedula_persona']) ? $_POST['cedula_persona'] : 'SN';


    $carpeta_destino = ROOT_PATH . "uploads/expedientes/";

    $carpeta_bd = "uploads/expedientes/";


    if (!file_exists($carpeta_destino)) {
        mkdir($carpeta_destino, 0777, true);
    }

    $archivo = $_FILES['archivo'];
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    $permitidos = ['pdf', 'jpg', 'jpeg', 'png'];


    if (!in_array($extension, $permitidos)) {
        die("<script>alert('Error: Formato no permitido. Solo PDF e imágenes.'); window.history.back();</script>");
    }

    if ($archivo['size'] > 10 * 1024 * 1024) {
        die("<script>alert('Error: El archivo es demasiado grande (Máx 10MB).'); window.history.back();</script>");
    }


    $nombre_archivo = "DOC_{$id_persona}_{$cedula}_{$tipo_doc}_" . time() . "." . $extension;

    $ruta_fisica_final = $carpeta_destino . $nombre_archivo;
    $ruta_bd_final = $carpeta_bd . $nombre_archivo;

    if (move_uploaded_file($archivo['tmp_name'], $ruta_fisica_final)) {

        $sql = "INSERT INTO expedientes (id_persona, tipo_documento, ruta_archivo) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $id_persona, $_POST['tipo_documento'], $ruta_bd_final);

        if ($stmt->execute()) {

            $descripcion_log = "Agregó un documento con título " . $_POST['tipo_documento'] . " al ciudadano " . $cedula;

            registrarLog($conn, $_SESSION['usuario'], "Expedientes", "Adjunto", $descripcion_log);


            header("Location: ver_expediente.php?id=" . $id_persona . "&msg=ok");
            exit;
        } else {
            echo "Error DB: " . $conn->error;
        }
        $stmt->close();
    } else {
        echo "Error al guardar el archivo físico. Verifique permisos de la carpeta 'uploads'.";
    }
} else {

    header("Location: ../actas/buscar.php");
}
