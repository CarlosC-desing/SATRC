<?php
header('Content-Type: text/html; charset=utf-8');
// RUTAS: Ajustadas para la nueva ubicación en modules/solicitudes/
require_once '../../includes/db/config.php';
include '../../modules/login/verificar_sesion.php';
include '../../includes/db/conexion.php';
include '../../functions/registrar_log.php';

registrarLog($conn, $_SESSION['usuario'], "Solicitudes", "Ingreso al módulo", "Accedió a registro de solicitudes");

$titulo_pagina = "Registro Solicitudes - Registro Civil";
include '../../includes/components/header.php';
include ROOT_PATH . 'includes/components/sidebar_busqueda_cedula.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/panel.css">

<main class="main-registro">
    <div class="main-registro__container">
        <h2 class="main-registro__title">Registro de Solicitud de Copia de Acta</h2>

        <form class="form-solicitud" method="POST" action="guardar_solicitud.php">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <label class="form-solicitud__label" for="cedula">Cédula del solicitante:</label>
            <input class="form-solicitud__input" type="text" name="cedula" id="cedula" autocomplete="off" placeholder="Ej: 12345678" required>

            <input type="hidden" name="id_persona" id="id_persona_hidden">

            <div id="resultado_busqueda" class="form-solicitud__search-results"></div>

            <label class="form-solicitud__label" for="tipo_acta">Tipo de Acta:</label>
            <select class="form-solicitud__select" name="tipo_acta" id="tipo_acta" required>
                <option value="">Seleccionar tipo</option>
                <option value="nacimiento">Nacimiento</option>
                <option value="defuncion">Defunción</option>
                <option value="matrimonio">Matrimonio</option>
                <option value="union">Unión Estable</option>
            </select>

            <label class="form-solicitud__label" for="motivo">Motivo de la Solicitud:</label>
            <textarea class="form-solicitud__textarea" name="motivo" id="motivo" rows="3" placeholder="Indique el uso del acta..." required></textarea>

            <button type="submit" class="form-solicitud__btn-submit">Enviar Solicitud</button>
            <div class="form-solicitud__actions">
                <a href="panel_solicitudes.php" class="form-solicitud__btn-back">Ver Panel de Solicitudes</a>
            </div>
        </form>
    </div>
</main>

<script>
    /**
     * Función que se ejecuta desde buscar_persona_ajax.php 
     * al hacer clic en un resultado de la lista.
     */
    function seleccionarPersona(cedula, id, nombre) {
        document.getElementById('cedula').value = cedula;
        document.getElementById('id_persona_hidden').value = id;
        document.getElementById('resultado_busqueda').innerHTML = '<span style="color:green">✅ Seleccionado: ' + nombre + '</span>';
    }

    // Escuchador para la búsqueda en tiempo real
    document.getElementById('cedula').addEventListener('input', function() {
        let cedula = this.value;
        // Limpiar el ID oculto si el usuario borra la cédula
        document.getElementById('id_persona_hidden').value = '';

        if (cedula.length >= 4) {
            fetch('buscar_persona_ajax.php?cedula=' + cedula)
                .then(res => res.text())
                .then(data => {
                    document.getElementById('resultado_busqueda').innerHTML = data;
                })
                .catch(err => {
                    console.error("Error en búsqueda:", err);
                    document.getElementById('resultado_busqueda').innerHTML = 'Error en la conexión';
                });
        } else {
            document.getElementById('resultado_busqueda').innerHTML = '';
        }
    });
</script>
</body>

</html>