<?php
header('Content-Type: text/html; charset=utf-8');
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

<style>
    .grupo-documento {
        display: flex;
        gap: 10px;
    }

    .select-corto {
        width: 80px;
        /* Ancho fijo para el tipo de documento */
        flex-shrink: 0;
    }

    .input-largo {
        flex-grow: 1;
    }
</style>

<main class="main-registro">
    <div class="main-registro__container">
        <h2 class="main-registro__title">Registro de Solicitud de Copia de Acta</h2>

        <form class="form-solicitud" method="POST" action="guardar_solicitud.php">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <label class="form-solicitud__label" for="cedula">Documento de Identidad:</label>

            <div class="grupo-documento">
                <select id="tipo_doc" class="form-solicitud__select select-corto">
                    <option value="V">V- Cédula</option>
                    <option value="E">E- Extranejo</option>
                    <option value="P">P- Pasaporte</option>
                </select>

                <input class="form-solicitud__input input-largo" type="text" name="cedula" id="cedula" autocomplete="off" placeholder="Ej: 12345678" required>
            </div>

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
    function seleccionarPersona(cedulaCompleta, id, nombre) {
        // Al seleccionar, ponemos el valor completo en el input visual
        // Opcional: Podrías separar la letra y ponerla en el select, pero ponerlo todo en el input es más fácil
        document.getElementById('cedula').value = cedulaCompleta;
        document.getElementById('id_persona_hidden').value = id;
        document.getElementById('resultado_busqueda').innerHTML = '<span style="color:green; font-weight:bold; margin-top:5px; display:block;">✅ ' + nombre + '</span>';
    }

    // Función centralizada de búsqueda
    function buscarPersona() {
        const tipo = document.getElementById('tipo_doc').value;
        const numero = document.getElementById('cedula').value.trim();

        // Limpiar ID si se edita
        document.getElementById('id_persona_hidden').value = '';

        // Solo buscar si hay números escritos
        if (numero.length >= 3) {
            // Concatenamos aquí: "V" + "-" + "123456"
            // Si el usuario ya escribió "V-123", limpiamos para no enviar "V-V-123"
            // Pero asumimos que el usuario solo escribe números en el input

            // Limpieza básica por si el usuario escribe "V-123" dentro del input
            let numeroLimpio = numero.replace(/^[VEPvep]-/, '');

            const busquedaCompleta = tipo + '-' + numeroLimpio;

            fetch('buscar_persona_ajax.php?cedula=' + encodeURIComponent(busquedaCompleta))
                .then(res => res.text())
                .then(data => {
                    document.getElementById('resultado_busqueda').innerHTML = data;
                })
                .catch(err => {
                    console.error("Error:", err);
                });
        } else {
            document.getElementById('resultado_busqueda').innerHTML = '';
        }
    }

    // Escuchar cambios en el input de texto
    document.getElementById('cedula').addEventListener('input', buscarPersona);

    // Escuchar cambios en el select (por si cambia de V a E con el número ya escrito)
    document.getElementById('tipo_doc').addEventListener('change', buscarPersona);
</script>
</body>

</html>