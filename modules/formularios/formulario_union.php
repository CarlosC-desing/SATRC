<?php
header('Content-Type: text/html; charset=utf-8');
include_once '../../includes/db/config.php';
include '../../modules/login/verificar_sesion.php';
include '../../includes/db/conexion.php';
include '../../functions/registrar_log.php';

registrarLog($conn, $_SESSION['usuario'], "Unión Estable", "Ingreso", "Formulario de Unión Estable");
$titulo_pagina = "Registro de Unión Estable de Hecho";
include '../../includes/components/header.php';
include '../../includes/components/sidebar_busqueda_cedula.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/estilos_formularios.css">
<style>
    /* Estilos Acordeón (Mismos que matrimonio) */
    details {
        background: #f8f9fa;
        margin-bottom: 10px;
        border-radius: 5px;
        border: 1px solid #e9ecef;
        overflow: hidden;
    }

    details summary {
        background: #d35400;
        color: white;
        /* Color Diferente para diferenciar de matrimonio */
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
        border-left: 5px solid #d35400;
    }

    .details-content {
        padding: 15px;
        background: white;
    }

    .hidden-section {
        display: none !important;
    }

    .hidden-row {
        display: none;
        background: #fff3cd;
        padding: 10px;
        border-radius: 5px;
        border: 1px solid #ffeeba;
        margin-top: 10px;
    }

    .control-panel {
        background: #fcebe6;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        border: 1px solid #d35400;
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
</style>

<div class="main-content--formulario" id="main-content">
    <div class="titulo-con-modo">
        <h2>Registro de Unión Estable de Hecho</h2>
    </div>

    <form class="formulario" id="form-union" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <div class="control-panel">
            <div class="form-group">
                <label class="form__label" style="color: #d35400;">TIPO DE OPERACIÓN:</label>
                <select class="form__input" name="tipo_operacion" id="tipo_operacion" required>
                    <option value="NORMAL">NORMAL (CONSTITUCIÓN)</option>
                    <option value="INSERCION">INSERCIÓN (DOCUMENTO EXTERNO)</option>
                    <option value="DISOLUCION">DISOLUCIÓN (TERMINACIÓN)</option>
                </select>
            </div>
        </div>

        <details open>
            <summary>A. Datos del Acta y Autoridad</summary>
            <div class="details-content">
                <div class="form-row">
                    <div class="form-group"><label class="form__label">Número de Acta:</label><input class="form__input" type="text" name="numero_acta" required placeholder="Ej: 2026-UEH-001"></div>
                    <div class="form-group"><label class="form__label">Tomo / Folio:</label><input class="form__input" type="text" name="tomo_folio" placeholder="Tomo 1, Folio 20"></div>
                    <div class="form-group"><label class="form__label">Fecha Registro:</label><input class="form__input" type="date" name="fecha_registro" required value="<?= date('Y-m-d') ?>"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label class="form__label">Cédula Registrador(a):</label><input class="form__input cedula-lookup" type="text" name="cedula_autoridad" required><span class="nombre-feedback"></span></div>
                    <div class="form-group"><label class="form__label">Oficina:</label><input class="form__input" type="text" value="REGISTRO CIVIL YARITAGUA" readonly></div>
                </div>

                <hr style="border: 0; border-top: 1px dashed #ccc; margin: 15px 0;">

                <div class="form-row">
                    <div class="form-group"><label class="form__label">Resolución N°:</label><input class="form__input" type="text" name="resolucion_numero"></div>
                    <div class="form-group"><label class="form__label">Fecha Resolución:</label><input class="form__input" type="date" name="resolucion_fecha"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label class="form__label">Gaceta N°:</label><input class="form__input" type="text" name="gaceta_numero"></div>
                    <div class="form-group"><label class="form__label">Fecha Gaceta:</label><input class="form__input" type="date" name="gaceta_fecha"></div>
                </div>
            </div>
        </details>

        <details id="sec-union" open>
            <summary>B. Datos de la Unión</summary>
            <div class="details-content">
                <div class="form-row">
                    <div class="form-group" style="flex:2">
                        <label class="form__label">Lugar de Constitución/Registro:</label>
                        <input class="form__input" type="text" name="lugar_union" placeholder="Ej: Oficina de Registro Civil">
                    </div>
                    <div class="form-group">
                        <label class="form__label">Fecha Inicio Unión:</label>
                        <input class="form__input" type="date" name="fecha_inicio_union" required>
                    </div>
                </div>
            </div>
        </details>

        <details open>
            <summary>C. Datos de los Declarantes</summary>
            <div class="details-content">
                <fieldset>
                    <legend>Declarante 1</legend>
                    <div class="form-row">
                        <div class="form-group"><label class="form__label">Cédula:</label><input class="form__input cedula-lookup" type="text" name="cedula_d1" required><span class="nombre-feedback"></span></div>
                        <div class="form-group"><label class="form__label">Profesión Actual:</label><input class="form__input" type="text" name="d1_profesion" required></div>
                    </div>
                    <div class="form-group"><label class="form__label">Residencia Actual:</label><input class="form__input" type="text" name="d1_residencia" required></div>

                    <div class="form-group">
                        <label class="form__label">Estado Civil ANTERIOR:</label>
                        <select class="form__input selector-edo" id="sel_d1" name="d1_edo_civil_anterior">
                            <option value="SOLTERO">SOLTERO(A)</option>
                            <option value="DIVORCIADO">DIVORCIADO(A)</option>
                            <option value="VIUDO">VIUDO(A)</option>
                        </select>
                    </div>
                    <div id="d1_div" class="hidden-row">
                        <div class="form-row"><input class="form__input" type="text" name="d1_div_tribunal" placeholder="Tribunal"><input class="form__input" type="text" name="d1_div_sentencia" placeholder="Nº Sentencia"><input class="form__input" type="date" name="d1_div_fecha"></div>
                    </div>
                    <div id="d1_viu" class="hidden-row">
                        <div class="form-row"><input class="form__input" type="text" name="d1_viu_acta" placeholder="Nº Acta Defunción"><input class="form__input" type="date" name="d1_viu_fecha"></div>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Declarante 2</legend>
                    <div class="form-row">
                        <div class="form-group"><label class="form__label">Cédula:</label><input class="form__input cedula-lookup" type="text" name="cedula_d2" required><span class="nombre-feedback"></span></div>
                        <div class="form-group"><label class="form__label">Profesión Actual:</label><input class="form__input" type="text" name="d2_profesion" required></div>
                    </div>
                    <div class="form-group"><label class="form__label">Residencia Actual:</label><input class="form__input" type="text" name="d2_residencia" required></div>

                    <div class="form-group">
                        <label class="form__label">Estado Civil ANTERIOR:</label>
                        <select class="form__input selector-edo" id="sel_d2" name="d2_edo_civil_anterior">
                            <option value="SOLTERO">SOLTERO(A)</option>
                            <option value="DIVORCIADO">DIVORCIADO(A)</option>
                            <option value="VIUDO">VIUDO(A)</option>
                        </select>
                    </div>
                    <div id="d2_div" class="hidden-row">
                        <div class="form-row"><input class="form__input" type="text" name="d2_div_tribunal" placeholder="Tribunal"><input class="form__input" type="text" name="d2_div_sentencia" placeholder="Nº Sentencia"><input class="form__input" type="date" name="d2_div_fecha"></div>
                    </div>
                    <div id="d2_viu" class="hidden-row">
                        <div class="form-row"><input class="form__input" type="text" name="d2_viu_acta" placeholder="Nº Acta Defunción"><input class="form__input" type="date" name="d2_viu_fecha"></div>
                    </div>
                </fieldset>
            </div>
        </details>

        <details>
            <summary>D. Datos del Apoderado</summary>
            <div class="details-content">
                <div class="form-row">
                    <div class="form-group"><label class="form__label">Nombre:</label><input class="form__input" type="text" name="apoderado_nombre"></div>
                    <div class="form-group"><label class="form__label">Cédula:</label><input class="form__input" type="text" name="apoderado_cedula"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label class="form__label">Registro/Notaría (Protocolo):</label><input class="form__input" type="text" name="protocolo_notaria"></div>
                    <div class="form-group"><label class="form__label">N° Poder:</label><input class="form__input" type="text" name="apoderado_num_poder"></div>
                    <div class="form-group"><label class="form__label">Fecha:</label><input class="form__input" type="date" name="apoderado_fecha"></div>
                </div>
            </div>
        </details>

        <details>
            <summary>E. Hijos Reconocidos (De la Unión)</summary>
            <div class="details-content">
                <?php for ($i = 1; $i <= 4; $i++): ?>
                    <div class="form-row" style="border-bottom: 1px dashed #ccc; padding: 5px 0;">
                        <div class="form-group" style="flex:2">
                            <label class="form__label">Hijo <?= $i ?>:</label>
                            <input class="form__input" type="text" name="hijo_nom[]" placeholder="Nombres y Apellidos">
                        </div>
                        <div class="form-group">
                            <label class="form__label">Acta Nac.:</label>
                            <input class="form__input" type="text" name="hijo_acta[]">
                        </div>
                        <div class="form-group">
                            <label class="form__label">Reconocer:</label>
                            <select class="form__input" name="hijo_rec[]">
                                <option value="NO">Mención</option>
                                <option value="SI">Reconocimiento</option>
                            </select>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </details>

        <details id="sec-testigos">
            <summary>F. Testigos</summary>
            <div class="details-content">
                <div class="form-row">
                    <div class="form-group"><label class="form__label">Cédula T1:</label><input class="form__input cedula-lookup" type="text" name="cedula_t1"><span class="nombre-feedback"></span></div>
                    <div class="form-group"><label class="form__label">Profesión T1:</label><input class="form__input" type="text" name="t1_profesion"></div>
                </div>
                <div class="form-group"><label class="form__label">Residencia T1:</label><input class="form__input" type="text" name="t1_residencia"></div>
                <hr>
                <div class="form-row">
                    <div class="form-group"><label class="form__label">Cédula T2:</label><input class="form__input cedula-lookup" type="text" name="cedula_t2"><span class="nombre-feedback"></span></div>
                    <div class="form-group"><label class="form__label">Profesión T2:</label><input class="form__input" type="text" name="t2_profesion"></div>
                </div>
                <div class="form-group"><label class="form__label">Residencia T2:</label><input class="form__input" type="text" name="t2_residencia"></div>
            </div>
        </details>

        <details id="sec-insercion" class="hidden-section">
            <summary>G. Datos de Inserción / Disolución</summary>
            <div class="details-content">
                <div class="form-row">
                    <div class="form-group"><label class="form__label">N° Doc/Sentencia:</label><input class="form__input" type="text" name="ins_h_numero"></div>
                    <div class="form-group"><label class="form__label">Autoridad/Tribunal:</label><input class="form__input" type="text" name="ins_h_autoridad"></div>
                </div>
                <div class="form-group"><label class="form__label">Extracto:</label><textarea class="form__input" name="ins_h_extracto" rows="3"></textarea></div>
            </div>
        </details>

        <details open>
            <summary>H. Observaciones y Notas</summary>
            <div class="details-content">
                <div class="form-group"><label class="form__label">Observaciones:</label><textarea class="form__input" name="observaciones"></textarea></div>
                <div class="form-group"><label class="form__label">Documentos:</label><textarea class="form__input" name="documentos_presentados"></textarea></div>
                <div class="form-group"><label class="form__label">Nota Marginal:</label><textarea class="form__input" name="nota_marginal"></textarea></div>
            </div>
        </details>

        <div style="text-align: center; margin: 30px 0;">
            <button type="submit" class="main__button">Registrar Unión Estable</button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Lógica de Modos
        const modo = document.getElementById('tipo_operacion');
        const secUnion = document.getElementById('sec-union');
        const secIns = document.getElementById('sec-insercion');
        const secTes = document.getElementById('sec-testigos');

        function actualizarModo() {
            secUnion.classList.remove('hidden-section');
            secTes.classList.remove('hidden-section');
            secIns.classList.add('hidden-section');

            if (modo.value === 'INSERCION' || modo.value === 'DISOLUCION') {
                secIns.classList.remove('hidden-section');
                secIns.open = true;
            }
        }
        modo.addEventListener('change', actualizarModo);

        // Lógica Estados Civiles
        const manejarEdo = (selId, divId, viuId) => {
            const sel = document.getElementById(selId);
            sel.addEventListener('change', () => {
                document.getElementById(divId).style.display = (sel.value === 'DIVORCIADO') ? 'block' : 'none';
                document.getElementById(viuId).style.display = (sel.value === 'VIUDO') ? 'block' : 'none';
            });
        };
        manejarEdo('sel_d1', 'd1_div', 'd1_viu');
        manejarEdo('sel_d2', 'd2_div', 'd2_viu');

        // Búsqueda Standard
        document.querySelectorAll('.cedula-lookup').forEach(input => {
            input.addEventListener('blur', function() {
                const val = this.value.trim();
                const feed = this.nextElementSibling;
                if (val.length > 4) {
                    feed.innerHTML = 'Buscando...';
                    fetch(`../../functions/buscar_persona_json.php?cedula=${val}`)
                        .then(r => r.json())
                        .then(d => {
                            feed.innerHTML = d.status === 'success' ? `✔ ${d.nombre}` : `✘ No registrada`;
                            feed.className = `nombre-feedback ${d.status === 'success' ? 'text-success' : 'text-error'}`;
                        });
                }
            });
        });

        // Envío AJAX
        document.getElementById('form-union').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('button');
            btn.disabled = true;
            btn.innerText = 'Procesando...';

            fetch('procesar_union.php', {
                    method: 'POST',
                    body: new FormData(this)
                })
                .then(res => res.text())
                .then(text => {
                    console.log("Respuesta Servidor:", text);
                    try {
                        const data = JSON.parse(text);
                        if (data.status === 'success') {
                            window.open(data.pdf_url, '_blank');
                            location.reload();
                        } else {
                            alert("ERROR DETECTADO: " + data.message);
                            btn.disabled = false;
                            btn.innerText = 'Registrar Unión';
                        }
                    } catch (err) {
                        alert("ERROR CRÍTICO DEL SERVIDOR. Revisa la consola (F12) para ver el detalle.");
                        console.error("Error parsing JSON:", err);
                        console.error("Respuesta cruda:", text);
                        btn.disabled = false;
                        btn.innerText = 'Reintentar';
                    }
                })
                .catch(e => {
                    console.error("Error de Red:", e);
                    alert("Error de conexión con el servidor");
                    btn.disabled = false;
                });
        });
    });
</script>