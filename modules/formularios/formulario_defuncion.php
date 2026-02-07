<?php
header('Content-Type: text/html; charset=utf-8');
include_once '../../includes/db/config.php';
include '../../modules/login/verificar_sesion.php';
include '../../includes/db/conexion.php';
include '../../functions/registrar_log.php';

registrarLog($conn, $_SESSION['usuario'], "Defunción", "Ingreso al módulo", "Accedió a formulario_defuncion.php");

$titulo_pagina = "Registro Defunción - Registro Civil";
include '../../includes/components/header.php';
include '../../includes/components/sidebar_busqueda_cedula.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/estilos_formularios.css">
<style>
    .nombre-feedback {
        display: block;
        font-size: 0.85rem;
        margin-top: 4px;
        font-weight: 600;
        min-height: 18px;
    }

    .text-success {
        color: #27ae60;
    }

    .text-error {
        color: #e74c3c;
    }
</style>

<div class="main-content--formulario" id="main-content">
    <div class="titulo-con-modo">
        <h2>Registro de Acta de Defunción</h2>
        <div class="modo-selector">
            <button type="button" class="main__button modo-btn active" data-modo="nuevo">Modo Cédula</button>
            <button type="button" class="main__button modo-btn" data-modo="existente">Llenado Manual</button>
        </div>
    </div>

    <form class="formulario" id="form-defuncion" action="procesar_defuncion.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="modo_registro" id="modo_registro" value="nuevo">

        <div class="section-title">Datos del Fallecido</div>
        <div class="form-row">
            <div class="form-group">
                <label class="form__label">Cédula Fallecido:</label>
                <input class="form__input cedula-lookup" type="text" name="cedula_persona" required>
                <span class="nombre-feedback"></span>
            </div>
            <div class="form-group">
                <label class="form__label">Fecha Defunción:</label>
                <input class="form__input" type="date" name="fecha_defuncion" required>
            </div>
            <div class="form-group">
                <label class="form__label">Hora:</label>
                <input class="form__input" type="time" name="hora_defuncion" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form__label">Lugar:</label>
                <input class="form__input" type="text" name="lugar_defuncion" required>
            </div>
            <div class="form-group">
                <label class="form__label">Causa:</label>
                <input class="form__input" type="text" name="causa_defuncion" required>
            </div>
            <div class="form-group">
                <label class="form__label">Médico:</label>
                <input class="form__input" type="text" name="nombre_medico" required>
            </div>
        </div>

        <div class="section-title">Datos del Declarante</div>
        <div class="form-row">
            <div class="form-group">
                <label class="form__label">Nombre:</label>
                <input class="form__input" type="text" name="nombre_declarante" required>
            </div>
            <div class="form-group">
                <label class="form__label">Cédula:</label>
                <input class="form__input" type="text" name="cedula_declarante" required>
            </div>
            <div class="form-group">
                <label class="form__label">Parentesco:</label>
                <input class="form__input" type="text" name="parentesco_declarante" required>
            </div>
        </div>

        <div id="filiacion-id">
            <div class="section-title">Autoridad y Testigos (Cédulas)</div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form__label">Cédula Autoridad:</label>
                    <input class="form__input cedula-lookup req-lookup" type="text" name="cedula_autoridad">
                    <span class="nombre-feedback"></span>
                </div>
                <div class="form-group">
                    <label class="form__label">Cédula Testigo 1:</label>
                    <input class="form__input cedula-lookup req-lookup" type="text" name="cedula_t1">
                    <span class="nombre-feedback"></span>
                </div>
                <div class="form-group">
                    <label class="form__label">Cédula Testigo 2:</label>
                    <input class="form__input cedula-lookup req-lookup" type="text" name="cedula_t2">
                    <span class="nombre-feedback"></span>
                </div>
            </div>
        </div>

        <div id="filiacion-manual" class="hidden">
            <div class="section-title">Información Manual (Opcional)</div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form__label">Nombre Autoridad:</label>
                    <input class="form__input" type="text" name="aut_nom">
                </div>
                <div class="form-group">
                    <label class="form__label">Cédula Autoridad:</label>
                    <input class="form__input" type="text" name="aut_ced">
                </div>
                <div class="form-group"></div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form__label">Nombre Testigo 1:</label>
                    <input class="form__input" type="text" name="nom_t1">
                </div>
                <div class="form-group">
                    <label class="form__label">Cédula Testigo 1:</label>
                    <input class="form__input" type="text" name="ced_t1">
                </div>
                <div class="form-group"></div>
            </div>
        </div>

        <div class="section-title">Datos Finales</div>
        <div class="form-row">
            <div class="form-group">
                <label class="form__label">Fecha del Acta:</label>
                <input class="form__input" type="date" name="fecha_acta" value="<?= date('Y-m-d'); ?>" required>
            </div>
            <div class="form-group"></div>
            <div class="form-group"></div>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <button type="submit" class="main__button">Registrar y Generar PDF</button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const botonesModo = document.querySelectorAll('.modo-btn');
        const inputModo = document.getElementById('modo_registro');
        const seccionID = document.getElementById('filiacion-id');
        const seccionManual = document.getElementById('filiacion-manual');
        const lookupInputs = document.querySelectorAll('.req-lookup');

        // Lógica para alternar modos
        botonesModo.forEach(boton => {
            boton.addEventListener('click', function() {
                botonesModo.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                const modo = this.getAttribute('data-modo');
                inputModo.value = modo;

                const esNuevo = (modo === 'nuevo');
                seccionID.classList.toggle('hidden', !esNuevo);
                seccionManual.classList.toggle('hidden', esNuevo);

                lookupInputs.forEach(input => input.required = esNuevo);
            });
        });

        // Feedback de búsqueda por cédula
        document.querySelectorAll('.cedula-lookup').forEach(input => {
            input.addEventListener('blur', function() {
                const cedula = this.value.trim();
                const feedback = this.nextElementSibling;
                if (cedula.length > 0) {
                    feedback.innerHTML = 'Buscando...';
                    feedback.className = 'nombre-feedback';
                    fetch(`../../functions/buscar_persona_json.php?cedula=${cedula}`)
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 'success') {
                                feedback.innerHTML = `✔ ${data.nombre}`;
                                feedback.classList.add('text-success');
                            } else {
                                feedback.innerHTML = `✘ No encontrado`;
                                feedback.classList.add('text-error');
                            }
                        }).catch(() => feedback.innerHTML = '');
                } else {
                    feedback.innerHTML = '';
                }
            });
        });

        // Envío AJAX
        document.getElementById('form-defuncion').addEventListener('submit', function(e) {
            e.preventDefault();
            fetch('procesar_defuncion.php', {
                    method: 'POST',
                    body: new FormData(this)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert("✅ Registro exitoso");
                        window.open(data.pdf_url, '_blank');
                        location.reload();
                    } else {
                        alert("❌ Error: " + data.message);
                    }
                })
                .catch(err => alert("⚠️ Error de conexión"));
        });
    });
</script>
</body>

</html>