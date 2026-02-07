<?php
include '../login/verificar_sesion.php';
include '../log/registrar_log.php'; // 🕵️ Auditoría

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tipo_acta = $_POST['tipo_acta'];

    if ($tipo_acta == "nacimiento") {
        header("Location: formulario_nacimiento.php");
    } elseif ($tipo_acta == "matrimonio") {
        header("Location: formulario_matrimonio.php");
    } elseif ($tipo_acta == "defuncion") {
        header("Location: formulario_defuncion.php");
    } elseif ($tipo_acta == "union") {
        header("Location: formulario_union.php");
    } else {
        echo "Tipo de acta no válido.";
    }
    exit();
}
?>
