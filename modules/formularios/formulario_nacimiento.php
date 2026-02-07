<?php
header('Content-Type: text/html; charset=utf-8');
include_once '../../includes/db/config.php';
include '../../modules/login/verificar_sesion.php';
include '../../includes/db/conexion.php';
include '../../functions/registrar_log.php';

registrarLog($conn, $_SESSION['usuario'], "Nacimiento", "Ingreso", "Formulario de Nacimiento");
$titulo_pagina = "Registro de Nacimiento";
include '../../includes/components/header.php';
include '../../includes/components/sidebar_busqueda_cedula.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/estilos_formularios.css">
<style>
    details {
        background: #f8f9fa;
        margin-bottom: 10px;
        border-radius: 5px;
        border: 1px solid #e9ecef;
        overflow: hidden;
    }

    details summary {
        background: #2980b9;
        color: white;
        padding: 12px;
        font-weight: bold;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        list-style: none;
    }

    details summary::after {
        content: '+';
        font-size: 1.2rem;
    }

    details[open] summary::after {
        content: '-';
    }

    details[open] {
        border-left: 5px solid #2980b9;
    }

    .details-content {
        padding: 15px;
        background: white;
    }

    .hidden-section {
        display: none !important;
    }

    .control-panel {
        background: #eaf2f8;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        border: 1px solid #3498db;
    }

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

    /* Nuevos estilos para campos extra */
    .extra-info {
        font-size: 0.9em;
        color: #666;
        margin-top: 5px;
        border-top: 1px dashed #ccc;
        padding-top: 5px;
    }
</style>

<div class="main-content--formulario" id="main-content">
    <div class="titulo-con-modo">
        <h2>Registro de Nacimiento</h2>
    </div>

    <form class="formulario" id="form-nacimiento" action="procesar_nacimiento.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <div class="control-panel">
            <div class="form-group">
                <label class="form__label" style="color: #2980b9;">MODO DE REGISTRO:</label>
                <select class="form__input" name="modo_registro" id="modo_registro" required>
                    <option value="normal">ORDINARIO (NORMAL)</option>
                    <option value="insercion">INSERCIÓN DE ACTA (H)</option>
                    <option value="medida_proteccion">MEDIDA DE PROTECCIÓN (I)</option>
                    <option value="sentencia_judicial">DECISIÓN JUDICIAL (J)</option>
                    <option value="extemporanea">EXTEMPORÁNEA (K)</option>
                </select>
            </div>
        </div>

        <details open>
            <summary>A. Datos del Acta y Registrador</summary>
            <div class="details-content">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form__label">Número de Acta:</label>
                        <input class="form__input" type="text" name="numero_acta" required placeholder="Ej: 2026-0000">
                    </div>
                    <div class="form-group">
                        <label class="form__label">Fecha de Registro:</label>
                        <input class="form__input" type="date" name="fecha_registro" id="fecha_registro" required value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form__label">Cédula Registrador(a):</label>
                        <input class="form__input cedula-lookup" type="text" name="cedula_registrador" required>
                        <span class="nombre-feedback"></span>
                    </div>
                    <div class="form-group">
                        <label class="form__label">Oficina:</label>
                        <input class="form__input" type="text" value="REGISTRO CIVIL YARITAGUA" readonly>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label class="form__label">Resolución N°:</label><input class="form__input" type="text" name="resolucion_numero"></div>
                    <div class="form-group"><label class="form__label">Gaceta N°:</label><input class="form__input" type="text" name="gaceta_numero"></div>
                </div>
            </div>
        </details>

        <details open>
            <summary>B. Datos del Nacido(a)</summary>
            <div class="details-content">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form__label">Primer Nombre:</label>
                        <input class="form__input" type="text" name="nacido_primer_nombre" required placeholder="Ej: José">
                    </div>
                    <div class="form-group">
                        <label class="form__label">Segundo Nombre (Opcional):</label>
                        <input class="form__input" type="text" name="nacido_segundo_nombre" placeholder="Ej: Alberto">
                    </div>
                </div>

                <div class="form-group" style="background: #f1f9fe; padding: 10px; border-left: 4px solid #3498db; margin-bottom: 15px;">
                    <small style="color: #2c3e50; font-weight: bold;">ℹ️ Nota sobre Apellidos:</small>
                    <span style="font-size: 0.9em; color: #555;">
                        Los apellidos se asignarán automáticamente según la filiación:<br>
                        • Con Padre: 1er Apellido Padre + 1er Apellido Madre.<br>
                        • Sin Padre: 1er Apellido Madre + 2do Apellido Madre.
                    </span>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form__label">Sexo:</label>
                        <select class="form__input" name="nacido_sexo">
                            <option value="M">MASCULINO</option>
                            <option value="F">FEMENINO</option>
                        </select>
                    </div>
                    <div class="form-group"><label class="form__label">Fecha Nacimiento:</label><input class="form__input" type="date" name="nacido_fecha" id="nacido_fecha" required></div>
                    <div class="form-group"><label class="form__label">Hora:</label><input class="form__input" type="time" name="nacido_hora" required></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label class="form__label">Centro de Salud / Hospital:</label><input class="form__input" type="text" name="nacido_lugar" placeholder="Ej. Hosp. Dr. Rafael Rangel"></div>
                    <div class="form-group"><label class="form__label">Dirección Exacta (Parroquia/Mcpio):</label><input class="form__input" type="text" name="nacido_direccion_lugar" placeholder="Estado, Municipio, Parroquia..."></div>
                </div>
            </div>
        </details>

        <details open>
            <summary>C. Datos del Certificado Médico (EV-25)</summary>
            <div class="details-content">
                <div class="form-row">
                    <div class="form-group"><label class="form__label">N° Certificado (Serie):</label><input class="form__input" type="text" name="cert_numero" required></div>
                    <div class="form-group"><label class="form__label">Fecha Expedición:</label><input class="form__input" type="date" name="cert_fecha" required></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label class="form__label">Nombre del Médico:</label><input class="form__input" type="text" name="cert_autoridad_medica" placeholder="Dr/Dra. Nombre Apellido"></div>
                    <div class="form-group"><label class="form__label">N° Matrícula MPPS:</label><input class="form__input" type="text" name="cert_mpps" placeholder="Número de matrícula"></div>
                </div>
            </div>
        </details>

        <details open>
            <summary>D/E. Datos de los Padres</summary>
            <div class="details-content">
                <fieldset style="border: 1px solid #ccc; padding: 10px; margin-bottom: 10px; border-radius: 5px;">
                    <legend style="color: #2980b9; font-weight: bold; padding: 0 5px;">Datos de la Madre</legend>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form__label">Cédula:</label>
                            <input class="form__input cedula-lookup" type="text" name="cedula_madre" required>
                            <span class="nombre-feedback"></span>
                        </div>
                        <div class="form-group">
                            <label class="form__label">Profesión Actual:</label>
                            <input class="form__input" type="text" name="madre_profesion" required placeholder="Ocupación al momento">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form__label">Residencia Actual:</label>
                        <input class="form__input" type="text" name="madre_residencia" required placeholder="Dirección completa">
                    </div>
                </fieldset>

                <fieldset style="border: 1px solid #ccc; padding: 10px; border-radius: 5px;">
                    <legend style="color: #2980b9; font-weight: bold; padding: 0 5px;">Datos del Padre (Opcional)</legend>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form__label">Cédula:</label>
                            <input class="form__input cedula-lookup" type="text" name="cedula_padre">
                            <span class="nombre-feedback"></span>
                        </div>
                        <div class="form-group">
                            <label class="form__label">Profesión Actual:</label>
                            <input class="form__input" type="text" name="padre_profesion" placeholder="Ocupación al momento">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form__label">Residencia Actual:</label>
                        <input class="form__input" type="text" name="padre_residencia" placeholder="Dirección completa">
                    </div>
                </fieldset>
            </div>
        </details>

        <details open>
            <summary>F. Datos del Declarante</summary>
            <div class="details-content">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form__label">Cédula Declarante:</label>
                        <input class="form__input cedula-lookup" type="text" name="cedula_declarante" required>
                        <span class="nombre-feedback"></span>
                    </div>
                    <div class="form-group">
                        <label class="form__label">Carácter con que actúa:</label>
                        <select class="form__input" name="declarante_caracter">
                            <option value="MADRE">MADRE</option>
                            <option value="PADRE">PADRE</option>
                            <option value="ABUELO/A">ABUELO/A</option>
                            <option value="MANDATARIO">MANDATARIO (PODER)</option>
                            <option value="CONSEJERO">CONSEJERO DE PROTECCIÓN</option>
                        </select>
                    </div>
                </div>
            </div>
        </details>

        <details open>
            <summary>G. Testigos del Acto</summary>
            <div class="details-content">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form__label">Cédula Testigo 1:</label>
                        <input class="form__input cedula-lookup" type="text" name="cedula_t1" required>
                        <span class="nombre-feedback"></span>
                    </div>
                    <div class="form-group">
                        <label class="form__label">Profesión T1:</label>
                        <input class="form__input" type="text" name="t1_profesion" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form__label">Residencia Testigo 1:</label>
                    <input class="form__input" type="text" name="t1_residencia" required>
                </div>
                <hr style="margin: 10px 0; border: 0; border-top: 1px dashed #ccc;">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form__label">Cédula Testigo 2:</label>
                        <input class="form__input cedula-lookup" type="text" name="cedula_t2" required>
                        <span class="nombre-feedback"></span>
                    </div>
                    <div class="form-group">
                        <label class="form__label">Profesión T2:</label>
                        <input class="form__input" type="text" name="t2_profesion" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form__label">Residencia Testigo 2:</label>
                    <input class="form__input" type="text" name="t2_residencia" required>
                </div>
            </div>
        </details>

        <details id="sec-h" class="hidden-section">
            <summary>H. Datos del Acta a Insertar</summary>
            <div class="details-content">
                <div class="form-row">
                    <div class="form-group"><label class="form__label">Acta N°:</label><input class="form__input" type="text" name="ins_h_numero"></div>
                    <div class="form-group"><label class="form__label">Fecha Acta:</label><input class="form__input" type="date" name="ins_h_fecha"></div>
                    <div class="form-group"><label class="form__label">Autoridad:</label><input class="form__input" type="text" name="ins_h_autoridad"></div>
                </div>
            </div>
        </details>
        <details id="sec-i" class="hidden-section">
            <summary>I. Inscripción por Medida de Protección</summary>
            <div class="details-content">
                <div class="form-row">
                    <div class="form-group"><label class="form__label">Consejo de Protección:</label><input class="form__input" type="text" name="ins_i_consejo"></div>
                    <div class="form-group"><label class="form__label">Medida N°:</label><input class="form__input" type="text" name="ins_i_medida"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label class="form__label">Fecha:</label><input class="form__input" type="date" name="ins_i_fecha"></div>
                    <div class="form-group"><label class="form__label">Consejero(a):</label><input class="form__input" type="text" name="ins_i_consejero"></div>
                </div>
            </div>
        </details>
        <details id="sec-j" class="hidden-section">
            <summary>J. Inscripción por Decisión Judicial</summary>
            <div class="details-content">
                <div class="form-row">
                    <div class="form-group"><label class="form__label">Tribunal:</label><input class="form__input" type="text" name="ins_j_tribunal"></div>
                    <div class="form-group"><label class="form__label">Sentencia N°:</label><input class="form__input" type="text" name="ins_j_sentencia"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label class="form__label">Fecha:</label><input class="form__input" type="date" name="ins_j_fecha"></div>
                    <div class="form-group"><label class="form__label">Juez(a):</label><input class="form__input" type="text" name="ins_j_juez"></div>
                </div>
            </div>
        </details>
        <details id="sec-k" class="hidden-section">
            <summary>K. Inscripción Extemporánea</summary>
            <div class="details-content">
                <div class="form-group"><label class="form__label">Datos Informe/Providencia N°:</label><input class="form__input" type="text" name="ins_k_datos"></div>
                <div class="form-row">
                    <div class="form-group"><label class="form__label">Fecha:</label><input class="form__input" type="date" name="ins_k_fecha"></div>
                    <div class="form-group"><label class="form__label">Autoridad:</label><input class="form__input" type="text" name="ins_k_autoridad"></div>
                </div>
            </div>
        </details>

        <details open>
            <summary>L/M. Observaciones y Notas</summary>
            <div class="details-content">
                <div class="form-group"><label class="form__label">L. Circunstancias Especiales:</label><textarea class="form__input" name="obs_circunstancias"></textarea></div>
                <div class="form-group"><label class="form__label">M. Documentos Presentados:</label><textarea class="form__input" name="obs_documentos">Certificado Médico de Nacimiento EV-25, Copias de Cédulas de Identidad de los Padres y Testigos.</textarea></div>
                <div class="form-group"><label class="form__label">M. Nota Marginal:</label><textarea class="form__input" name="nota_marginal"></textarea></div>
            </div>
        </details>

        <div style="text-align: center; margin: 30px 0;">
            <button type="submit" class="main__button">Generar Acta de Nacimiento</button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // --- VALIDACION DE FECHAS ---
        const fechaNac = document.getElementById('nacido_fecha');
        const fechaReg = document.getElementById('fecha_registro');

        function validarFechas() {
            if (fechaNac.value) {
                // La fecha de registro no puede ser menor a la de nacimiento
                fechaReg.min = fechaNac.value;
            }
        }
        fechaNac.addEventListener('change', validarFechas);

        // --- LOGICA EXISTENTE DE MODO ---
        const modo = document.getElementById('modo_registro');
        const secH = document.getElementById('sec-h');
        const secI = document.getElementById('sec-i');
        const secJ = document.getElementById('sec-j');
        const secK = document.getElementById('sec-k');

        function actualizarModo() {
            secH.classList.add('hidden-section');
            secI.classList.add('hidden-section');
            secJ.classList.add('hidden-section');
            secK.classList.add('hidden-section');
            switch (modo.value) {
                case 'insercion':
                    secH.classList.remove('hidden-section');
                    secH.open = true;
                    break;
                case 'medida_proteccion':
                    secI.classList.remove('hidden-section');
                    secI.open = true;
                    break;
                case 'sentencia_judicial':
                    secJ.classList.remove('hidden-section');
                    secJ.open = true;
                    break;
                case 'extemporanea':
                    secK.classList.remove('hidden-section');
                    secK.open = true;
                    break;
            }
        }
        modo.addEventListener('change', actualizarModo);
        actualizarModo();

        // --- BUSQUEDA AJAX ---
        document.querySelectorAll('.cedula-lookup').forEach(input => {
            input.addEventListener('blur', function() {
                const cedula = this.value.trim();
                const feedback = this.nextElementSibling;
                if (cedula.length > 4) {
                    feedback.innerHTML = 'buscando...';
                    fetch(`../../functions/buscar_persona_json.php?cedula=${cedula}`)
                        .then(res => res.json())
                        .then(data => {
                            feedback.innerHTML = data.status === 'success' ? `✔ ${data.nombre}` : `✘ No registrada (Llenado manual)`;
                            feedback.className = `nombre-feedback ${data.status === 'success' ? 'text-success' : 'text-error'}`;
                        }).catch(() => feedback.innerHTML = '');
                }
            });
        });

        // --- ENVIO FORMULARIO ---
        document.getElementById('form-nacimiento').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('button');
            btn.disabled = true;
            btn.innerText = 'Generando PDF...';

            fetch('procesar_nacimiento.php', {
                    method: 'POST',
                    body: new FormData(this)
                })
                .then(res => res.text())
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        if (data.status === 'success') {
                            window.open(data.pdf_url, '_blank');
                            location.reload();
                        } else {
                            alert(data.message || "Error desconocido");
                            btn.disabled = false;
                            btn.innerText = 'Generar Acta de Nacimiento';
                        }
                    } catch (e) {
                        console.error("Respuesta no válida:", text);
                        alert("Error en el servidor. Revisa la consola.");
                        btn.disabled = false;
                    }
                })
                .catch(err => {
                    alert("Error de red");
                    btn.disabled = false;
                });
        });
    });
</script>