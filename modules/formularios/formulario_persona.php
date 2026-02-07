<?php
header('Content-Type: text/html; charset=utf-8');
require_once '../../includes/db/config.php';
include '../../modules/login/verificar_sesion.php';
include '../../includes/db/conexion.php';
include '../../functions/registrar_log.php';

registrarLog($conn, $_SESSION['usuario'], "Personas", "Ingreso al módulo", "Accedió a formulario persona");

$titulo_pagina = "Registro Persona - Registro Civil";
include '../../includes/components/header.php';
include '../../includes/components/sidebar_busqueda_cedula.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/estilos_formularios.css">

<div class="main-content--formulario" id="main-content">
    <div class="titulo-con-modo">
        <h2>Registrar Persona</h2>
    </div>

    <form class="formulario" id="form-persona" action="procesar_persona.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <div class="section-title">Información Personal Básica</div>
        <div class="form-row">
            <div class="form-group">
                <label class="form__label">Primer Nombre:</label>
                <input class="form__input validate-text" type="text" id="primer_nombre" name="primer_nombre" required>
            </div>
            <div class="form-group">
                <label class="form__label">Segundo Nombre:</label>
                <input class="form__input validate-text" type="text" id="segundo_nombre" name="segundo_nombre">
            </div>
            <div class="form-group">
                <label class="form__label">Tercer Nombre:</label>
                <input class="form__input validate-text" type="text" id="tercer_nombre" name="tercer_nombre">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form__label">Primer Apellido:</label>
                <input class="form__input validate-text" type="text" id="primer_apellido" name="primer_apellido" required>
            </div>
            <div class="form-group">
                <label class="form__label">Segundo Apellido:</label>
                <input class="form__input validate-text" type="text" id="segundo_apellido" name="segundo_apellido">
            </div>
            <div class="form-group">
                <label class="form__label">Documento de Identidad:</label>
                <div style="display: flex; gap: 5px;">
                    <select class="form__input" name="tipo_doc" style="flex: 0.3;">
                        <option value="V">V</option>
                        <option value="E">E</option>
                        <option value="P">P</option>
                    </select>
                    <input class="form__input validate-number" type="text" id="cedula" name="cedula" required style="flex: 1;" placeholder="Número">
                </div>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form__label">Nacionalidad:</label>
                <input class="form__input validate-text" type="text" id="nacionalidad" name="nacionalidad" required placeholder="Ej: Venezolana">
            </div>
            <div class="form-group">
                <label class="form__label">Sexo:</label>
                <select class="form__input" name="sexo" required>
                    <option value="M">Masculino</option>
                    <option value="F">Femenino</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form__label">Estado Civil:</label>
                <select class="form__input" name="estado_civil" required>
                    <option value="soltero">Soltero/a</option>
                    <option value="casado">Casado/a</option>
                    <option value="divorciado">Divorciado/a</option>
                    <option value="viudo">Viudo/a</option>
                </select>
            </div>
        </div>

        <div class="section-title">Lugar y Datos de Nacimiento</div>
        <div class="form-row">
            <div class="form-group">
                <label class="form__label">Fecha de Nacimiento:</label>
                <input class="form__input" type="date" name="fecha_nacimiento" required>
            </div>
            <div class="form-group">
                <label class="form__label">País de Nacimiento:</label>
                <input class="form__input validate-text" type="text" id="pais_nacimiento" name="pais_nacimiento" required>
            </div>
            <div class="form-group">
                <label class="form__label">Estado de Nacimiento:</label>
                <input class="form__input validate-text" type="text" id="estado_nacimiento" name="estado_nacimiento" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form__label">Municipio de Nacimiento:</label>
                <input class="form__input validate-text" type="text" id="municipio_nacimiento" name="municipio_nacimiento" required>
            </div>
            <div class="form-group">
                <label class="form__label">Parroquia de Nacimiento:</label>
                <input class="form__input validate-text" type="text" id="parroquia_nacimiento" name="parroquia_nacimiento" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form__label">N° Acta de Nacimiento:</label>
                <input class="form__input" type="text" name="num_acta_nac" placeholder="Solo números y guiones">
            </div>
            <div class="form-group" style="flex: 2;">
                <label class="form__label">Oficina de Registro (Nacimiento):</label>
                <input class="form__input" type="text" name="oficina_registro_nac">
            </div>
        </div>

        <div class="section-title">Información de Residencia y Trabajo</div>
        <div class="form-row">
            <div class="form-group" style="flex: 2;">
                <label class="form__label">Dirección de Residencia:</label>
                <input class="form__input" type="text" id="residencia" name="residencia" required>
            </div>
            <div class="form-group">
                <label class="form__label">Profesión u Ocupación:</label>
                <input class="form__input validate-text" type="text" id="profesion" name="profesion" required>
            </div>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <button type="submit" class="main__button">Registrar Persona</button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('form-persona');

        // Validación numérica estricta
        document.querySelectorAll('.validate-number').forEach(input => {
            input.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        });

        // Validación de texto con Formato de Nombres Propios
        document.querySelectorAll('.validate-text').forEach(input => {
            input.addEventListener('input', function() {
                this.value = this.value.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñ\s']/g, '')
                    .split(' ')
                    .map(p => p ? p.charAt(0).toUpperCase() + p.slice(1).toLowerCase() : '')
                    .join(' ');
            });
        });

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            fetch('procesar_persona.php', {
                    method: 'POST',
                    body: new FormData(this)
                })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.status === 'success') form.reset();
                })
                .catch(() => alert("Error crítico en el servidor."));
        });
    });
</script>