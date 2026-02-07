<?php
include_once '../../functions/validaciones.php';
$_POST = sanear($_POST);
$_GET = sanear($_GET);
?>

<form method="GET" action="buscar.php">
    <input type="hidden" name="modo" value="personas">
    <label for="cedula">Cédula:</label>
    <input type="text" id="cedula" name="cedula">

    <label for="primer_nombre">Primer Nombre:</label>
    <input type="text" id="primer_nombre" name="primer_nombre">

    <label for="segundo_nombre">Segundo Nombre:</label>
    <input type="text" id="segundo_nombre" name="segundo_nombre">

    <label for="tercer_nombre">Tercer Nombre:</label>
    <input type="text" id="tercer_nombre" name="tercer_nombre">

    <label for="primer_apellido">Primer Apellido:</label>
    <input type="text" id="primer_apellido" name="primer_apellido">

    <label for="segundo_apellido">Segundo Apellido:</label>
    <input type="text" id="segundo_apellido" name="segundo_apellido">

    <button type="submit">Buscar</button>
</form>