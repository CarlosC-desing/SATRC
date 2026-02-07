<?php
header('Content-Type: text/html; charset=utf-8');
require_once '../../includes/db/config.php';
include ROOT_PATH . 'modules/login/verificar_sesion.php';
include ROOT_PATH . 'includes/db/conexion.php';
include ROOT_PATH . 'functions/registrar_log.php';

if (!isset($_GET['id'])) {
    echo "ID no proporcionado.";
    exit;
}

$id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM personas WHERE id_persona = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Persona no encontrada.";
    exit;
}
$row = $result->fetch_assoc();

$titulo_pagina = "Actualizar Persona - Registro Civil";
include '../../includes/components/header.php';
include '../../includes/components/sidebar_busqueda_cedula.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/estilos_formularios.css">

<div class="main-content--formulario" id="main-content">
    <div class="titulo-con-modo">
        <h2>Actualizar Datos de Persona</h2>
    </div>

    <form class="formulario" id="form-update-persona" action="procesar_actualizacion.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

        <input type="hidden" name="id_persona" value="<?= $row['id_persona'] ?>">

        <div class="section-title">Información Personal</div>

        <div class="form-row">
            <div class="form-group">
                <label class="form__label" for="primer_nombre">Primer Nombre:</label>
                <input class="form__input" type="text" id="primer_nombre" name="primer_nombre" value="<?= $row['primer_nombre'] ?>" required pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ\s']+">
            </div>
            <div class="form-group">
                <label class="form__label" for="segundo_nombre">Segundo Nombre:</label>
                <input class="form__input" type="text" id="segundo_nombre" name="segundo_nombre" value="<?= $row['segundo_nombre'] ?>" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ\s']+">
            </div>
            <div class="form-group">
                <label class="form__label" for="tercer_nombre">Tercer Nombre:</label>
                <input class="form__input" type="text" id="tercer_nombre" name="tercer_nombre" value="<?= $row['tercer_nombre'] ?>" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ\s']+">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form__label" for="primer_apellido">Primer Apellido:</label>
                <input class="form__input" type="text" id="primer_apellido" name="primer_apellido" value="<?= $row['primer_apellido'] ?>" required pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ\s']+">
            </div>
            <div class="form-group">
                <label class="form__label" for="segundo_apellido">Segundo Apellido:</label>
                <input class="form__input" type="text" id="segundo_apellido" name="segundo_apellido" value="<?= $row['segundo_apellido'] ?>" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ\s']+">
            </div>
            <div class="form-group">
                <label class="form__label" for="cedula">Cédula:</label>
                <?php if (empty($row['cedula'])): ?>
                    <input class="form__input" type="text" id="cedula" name="cedula" placeholder="Asignar cédula (opcional)" pattern="\d+">
                <?php else: ?>
                    <input class="form__input" type="text" id="cedula_display" value="<?= $row['cedula'] ?>" readonly style="background-color: #f0f0f0; cursor: not-allowed;">
                    <input type="hidden" name="cedula" value="<?= $row['cedula'] ?>">
                <?php endif; ?>
            </div>
        </div>

        <div class="section-title">Datos Complementarios</div>

        <div class="form-row">
            <div class="form-group">
                <label class="form__label" for="edad">Edad:</label>
                <input class="form__input" type="number" id="edad" name="edad" value="<?= $row['edad'] ?>" required min="0" max="120">
            </div>
            <div class="form-group">
                <label class="form__label" for="estado_civil">Estado Civil:</label>
                <select class="form__input" id="estado_civil" name="estado_civil" required>
                    <option value="soltero" <?= $row['estado_civil'] == 'soltero' ? 'selected' : '' ?>>Soltero/a</option>
                    <option value="casado" <?= $row['estado_civil'] == 'casado' ? 'selected' : '' ?>>Casado/a</option>
                    <option value="divorciado" <?= $row['estado_civil'] == 'divorciado' ? 'selected' : '' ?>>Divorciado/a</option>
                    <option value="viudo" <?= $row['estado_civil'] == 'viudo' ? 'selected' : '' ?>>Viudo/a</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form__label" for="sexo">Sexo:</label>
                <select class="form__input" id="sexo" name="sexo" required>
                    <option value="M" <?= $row['sexo'] == 'M' ? 'selected' : '' ?>>Masculino</option>
                    <option value="F" <?= $row['sexo'] == 'F' ? 'selected' : '' ?>>Femenino</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form__label" for="profesion">Profesión:</label>
                <input class="form__input" type="text" id="profesion" name="profesion" value="<?= $row['profesion'] ?>" required>
            </div>
            <div class="form-group">
                <label class="form__label" for="residencia">Ciudad o Pueblo:</label>
                <input class="form__input" type="text" id="residencia" name="residencia" value="<?= $row['residencia'] ?>" required>
            </div>
            <div class="form-group">
                <label class="form__label" for="fecha_nacimiento">Fecha de Nacimiento:</label>
                <input class="form__input" type="date" id="fecha_nacimiento" name="fecha_nacimiento" value="<?= $row['fecha_nacimiento'] ?>" required>
            </div>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <button type="submit" class="main__button">GUARDAR CAMBIOS</button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const formUpdate = document.getElementById('form-update-persona');
        const soloLetras = /[^A-Za-zÁÉÍÓÚáéíóúÑñ\s']/g;
        const camposCapitalizar = ['primer_nombre', 'segundo_nombre', 'tercer_nombre', 'primer_apellido', 'segundo_apellido', 'profesion', 'residencia'];

        // Capitalización automática
        camposCapitalizar.forEach(id => {
            const inp = document.getElementById(id);
            if (inp) {
                inp.addEventListener('input', function() {
                    this.value = this.value.replace(soloLetras, '')
                        .split(' ')
                        .map(p => p ? p.charAt(0).toUpperCase() + p.slice(1).toLowerCase() : '')
                        .join(' ');
                });
            }
        });

        // Envío por AJAX
        formUpdate.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('procesar_actualizacion.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message); // Aquí sale el Alert indicando el resultado
                    if (data.status === 'success') {
                        // Opcional: recargar la página para ver cambios frescos
                        // location.reload(); 
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert("❌ Error crítico en el servidor.");
                });
        });
    });
</script>