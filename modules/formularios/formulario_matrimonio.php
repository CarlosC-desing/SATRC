<?php
header('Content-Type: text/html; charset=utf-8');
include_once '../../includes/db/config.php';
include '../../modules/login/verificar_sesion.php';
include '../../includes/db/conexion.php';
include '../../functions/registrar_log.php';

registrarLog($conn, $_SESSION['usuario'], "Matrimonio", "Ingreso", "Formulario de Matrimonio");
$titulo_pagina = "Registro de Matrimonio";
include '../../includes/components/header.php';
include '../../includes/components/sidebar_busqueda_cedula.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/estilos_formularios.css">
<style>
    /* Estilos Acordeón */
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

    .hidden-row {
        display: none;
        background: #fff3cd;
        padding: 10px;
        border-radius: 5px;
        border: 1px solid #ffeeba;
        margin-top: 10px;
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
</style>

<div class="main-content--formulario" id="main-content">
    <div class="titulo-con-modo">
        <h2>Registro de Matrimonio</h2>
    </div>

    <form class="formulario" id="form-matrimonio" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <div class="control-panel">
            <div class="form-group">
                <label class="form__label" style="color: #2980b9;">MODO DE REGISTRO:</label>
                <select class="form__input" name="modo_registro" id="modo_registro" required>
                    <option value="normal">NORMAL (ORDINARIO)</option>
                    <option value="art_66">ARTÍCULO 66 (MORTIS CAUSA)</option>
                    <option value="art_70">ARTÍCULO 70 (LEGALIZACIÓN UNIÓN)</option>
                    <option value="insercion">INSERCIÓN (DOCUMENTO EXTERNO)</option>
                </select>
            </div>
        </div>

        <details open>
            <summary>A. Datos del Acta y Autoridad</summary>
            <div class="details-content">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form__label">Número de Acta:</label>
                        <input class="form__input" type="text" name="numero_acta" required placeholder="Ej: 2026-M-0012">
                    </div>
                    <div class="form-group">
                        <label class="form__label">Nº Expediente Esponsales:</label>
                        <input class="form__input" type="text" name="n_expediente" required placeholder="Nº Expediente">
                    </div>
                    <div class="form-group">
                        <label class="form__label">Fecha de Registro:</label>
                        <input class="form__input" type="date" name="fecha_registro" required value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form__label">Cédula Registrador(a):</label>
                        <input class="form__input cedula-lookup" type="text" name="cedula_autoridad" required>
                        <span class="nombre-feedback"></span>
                    </div>
                    <div class="form-group">
                        <label class="form__label">Oficina:</label>
                        <input class="form__input" type="text" value="REGISTRO CIVIL YARITAGUA" readonly>
                    </div>
                </div>

                <hr style="border: 0; border-top: 1px dashed #ccc; margin: 15px 0;">

                <div class="form-row">
                    <div class="form-group">
                        <label class="form__label">Resolución N°:</label>
                        <input class="form__input" type="text" name="resolucion_numero">
                    </div>
                    <div class="form-group">
                        <label class="form__label">Fecha Resolución:</label>
                        <input class="form__input" type="date" name="resolucion_fecha">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form__label">Gaceta N°:</label>
                        <input class="form__input" type="text" name="gaceta_numero">
                    </div>
                    <div class="form-group">
                        <label class="form__label">Fecha Gaceta:</label>
                        <input class="form__input" type="date" name="gaceta_fecha">
                    </div>
                </div>
            </div>
        </details>

        <details id="sec-celebracion" open>
            <summary>B. Datos de la Celebración</summary>
            <div class="details-content">
                <div class="form-row">
                    <div class="form-group" style="flex:2">
                        <label class="form__label">Lugar de Celebración:</label>
                        <input class="form__input" type="text" name="lugar_matrimonio" placeholder="Ej: Despacho del Registrador">
                    </div>
                    <div class="form-group">
                        <label class="form__label">Fecha del Acto:</label>
                        <input class="form__input" type="date" name="fecha_matrimonio" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form__label">Hora:</label>
                        <input class="form__input" type="time" name="hora_matrimonio">
                    </div>
                </div>
            </div>
        </details>

        <details open>
            <summary>C. Datos de los Contrayentes</summary>
            <div class="details-content">
                <fieldset>
                    <legend>Esposo (Contrayente 1)</legend>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form__label">Cédula:</label>
                            <input class="form__input cedula-lookup" type="text" name="cedula_esposo" required>
                            <span class="nombre-feedback"></span>
                        </div>
                        <div class="form-group">
                            <label class="form__label">Profesión Actual:</label>
                            <input class="form__input" type="text" name="c1_profesion" required placeholder="Ocupación al momento">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form__label">Residencia Actual:</label>
                        <input class="form__input" type="text" name="c1_residencia" required placeholder="Dirección exacta">
                    </div>

                    <div class="form-group">
                        <label class="form__label">Estado Civil ANTERIOR:</label>
                        <select class="form__input selector-edo" id="sel_c1" name="c1_edo_civil_anterior">
                            <option value="SOLTERO">SOLTERO(A)</option>
                            <option value="DIVORCIADO">DIVORCIADO(A)</option>
                            <option value="VIUDO">VIUDO(A)</option>
                        </select>
                    </div>
                    <div id="c1_div" class="hidden-row">
                        <div class="form-row">
                            <div class="form-group"><label class="form__label">Tribunal:</label><input class="form__input" type="text" name="c1_div_tribunal"></div>
                            <div class="form-group"><label class="form__label">Nº Sentencia:</label><input class="form__input" type="text" name="c1_div_sentencia"></div>
                            <div class="form-group"><label class="form__label">Fecha:</label><input class="form__input" type="date" name="c1_div_fecha"></div>
                        </div>
                    </div>
                    <div id="c1_viu" class="hidden-row">
                        <div class="form-row">
                            <div class="form-group"><label class="form__label">Nº Acta Defunción:</label><input class="form__input" type="text" name="c1_viu_acta"></div>
                            <div class="form-group"><label class="form__label">Fecha:</label><input class="form__input" type="date" name="c1_viu_fecha"></div>
                        </div>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Esposa (Contrayente 2)</legend>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form__label">Cédula:</label>
                            <input class="form__input cedula-lookup" type="text" name="cedula_esposa" required>
                            <span class="nombre-feedback"></span>
                        </div>
                        <div class="form-group">
                            <label class="form__label">Profesión Actual:</label>
                            <input class="form__input" type="text" name="c2_profesion" required placeholder="Ocupación al momento">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form__label">Residencia Actual:</label>
                        <input class="form__input" type="text" name="c2_residencia" required placeholder="Dirección exacta">
                    </div>

                    <div class="form-group">
                        <label class="form__label">Estado Civil ANTERIOR:</label>
                        <select class="form__input selector-edo" id="sel_c2" name="c2_edo_civil_anterior">
                            <option value="SOLTERO">SOLTERO(A)</option>
                            <option value="DIVORCIADO">DIVORCIADO(A)</option>
                            <option value="VIUDO">VIUDO(A)</option>
                        </select>
                    </div>
                    <div id="c2_div" class="hidden-row">
                        <div class="form-row">
                            <div class="form-group"><label class="form__label">Tribunal:</label><input class="form__input" type="text" name="c2_div_tribunal"></div>
                            <div class="form-group"><label class="form__label">Nº Sentencia:</label><input class="form__input" type="text" name="c2_div_sentencia"></div>
                            <div class="form-group"><label class="form__label">Fecha:</label><input class="form__input" type="date" name="c2_div_fecha"></div>
                        </div>
                    </div>
                    <div id="c2_viu" class="hidden-row">
                        <div class="form-row">
                            <div class="form-group"><label class="form__label">Nº Acta Defunción:</label><input class="form__input" type="text" name="c2_viu_acta"></div>
                            <div class="form-group"><label class="form__label">Fecha:</label><input class="form__input" type="date" name="c2_viu_fecha"></div>
                        </div>
                    </div>
                </fieldset>
            </div>
        </details>

        <details>
            <summary>D. Capitulaciones Matrimoniales</summary>
            <div class="details-content">
                <div class="form-group">
                    <label class="form__label">¿Existen Capitulaciones?</label>
                    <select class="form__input" id="tiene_cap">
                        <option value="NO">NO</option>
                        <option value="SI">SI</option>
                    </select>
                </div>
                <div id="sec-cap-datos" class="hidden-section">
                    <div class="form-row">
                        <div class="form-group"><label class="form__label">Oficina/Notaría:</label><input class="form__input" type="text" name="cap_oficina"></div>
                        <div class="form-group"><label class="form__label">Nº Documento:</label><input class="form__input" type="text" name="cap_numero"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label class="form__label">Libro/Tomo:</label><input class="form__input" type="text" name="cap_tomo"></div>
                        <div class="form-group"><label class="form__label">Protocolo/Folio:</label><input class="form__input" type="text" name="cap_folio"></div>
                        <div class="form-group"><label class="form__label">Fecha:</label><input class="form__input" type="date" name="cap_fecha"></div>
                    </div>
                </div>
            </div>
        </details>

        <details>
            <summary>E. Datos del Apoderado</summary>
            <div class="details-content">
                <div class="form-row">
                    <div class="form-group"><label class="form__label">Nombre:</label><input class="form__input" type="text" name="apoderado_nombre"></div>
                    <div class="form-group"><label class="form__label">Cédula:</label><input class="form__input" type="text" name="apoderado_cedula"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label class="form__label">Registro/Notaría:</label><input class="form__input" type="text" name="apoderado_registro"></div>
                    <div class="form-group"><label class="form__label">N° Poder:</label><input class="form__input" type="text" name="apoderado_num_poder"></div>
                    <div class="form-group"><label class="form__label">Fecha:</label><input class="form__input" type="date" name="apoderado_fecha"></div>
                </div>
            </div>
        </details>

        <details>
            <summary>F. Hijos a Reconocer (Legitimación)</summary>
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
            <summary>G. Testigos</summary>
            <div class="details-content">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form__label">Cédula T1:</label>
                        <input class="form__input cedula-lookup" type="text" name="cedula_t1">
                        <span class="nombre-feedback"></span>
                    </div>
                    <div class="form-group">
                        <label class="form__label">Profesión T1:</label>
                        <input class="form__input" type="text" name="t1_profesion">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form__label">Residencia T1:</label>
                    <input class="form__input" type="text" name="t1_residencia">
                </div>
                <hr>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form__label">Cédula T2:</label>
                        <input class="form__input cedula-lookup" type="text" name="cedula_t2">
                        <span class="nombre-feedback"></span>
                    </div>
                    <div class="form-group">
                        <label class="form__label">Profesión T2:</label>
                        <input class="form__input" type="text" name="t2_profesion">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form__label">Residencia T2:</label>
                    <input class="form__input" type="text" name="t2_residencia">
                </div>
            </div>
        </details>

        <details id="sec-insercion" class="hidden-section">
            <summary>H. Datos de Inserción</summary>
            <div class="details-content">
                <div class="form-row">
                    <div class="form-group"><label class="form__label">N° Doc:</label><input class="form__input" type="text" name="ins_numero_doc"></div>
                    <div class="form-group"><label class="form__label">Fecha Doc:</label><input class="form__input" type="date" name="ins_fecha_doc"></div>
                </div>
                <div class="form-group"><label class="form__label">Autoridad:</label><input class="form__input" type="text" name="ins_autoridad"></div>
                <div class="form-group"><label class="form__label">Extracto:</label><textarea class="form__input" name="ins_extracto" rows="3"></textarea></div>
            </div>
        </details>

        <details open>
            <summary>I. Observaciones y Notas</summary>
            <div class="details-content">
                <div class="form-group"><label class="form__label">Observaciones:</label><textarea class="form__input" name="observaciones"></textarea></div>
                <div class="form-group"><label class="form__label">Documentos:</label><textarea class="form__input" name="documentos_presentados"></textarea></div>
                <div class="form-group"><label class="form__label">Nota Marginal:</label><textarea class="form__input" name="nota_marginal"></textarea></div>
            </div>
        </details>

        <div style="text-align: center; margin: 30px 0;">
            <button type="submit" class="main__button">Registrar Matrimonio</button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Lógica de Modos (Original)
        const modo = document.getElementById('modo_registro');
        const secCel = document.getElementById('sec-celebracion');
        const secIns = document.getElementById('sec-insercion');
        const secTes = document.getElementById('sec-testigos');

        function actualizarModo() {
            secCel.classList.remove('hidden-section');
            secTes.classList.remove('hidden-section');
            secIns.classList.add('hidden-section');
            if (modo.value === 'insercion') {
                secCel.classList.add('hidden-section');
                secTes.classList.add('hidden-section');
                secIns.classList.remove('hidden-section');
                secIns.open = true;
            }
        }
        modo.addEventListener('change', actualizarModo);

        // Lógica Capitulaciones
        document.getElementById('tiene_cap').addEventListener('change', function() {
            document.getElementById('sec-cap-datos').classList.toggle('hidden-section', this.value === 'NO');
        });

        // Lógica Estados Civiles
        const manejarEdo = (selId, divId, viuId) => {
            const sel = document.getElementById(selId);
            sel.addEventListener('change', () => {
                document.getElementById(divId).style.display = (sel.value === 'DIVORCIADO') ? 'block' : 'none';
                document.getElementById(viuId).style.display = (sel.value === 'VIUDO') ? 'block' : 'none';
            });
        };
        manejarEdo('sel_c1', 'c1_div', 'c1_viu');
        manejarEdo('sel_c2', 'c2_div', 'c2_viu');

        // Búsqueda y Submit Standard
        document.querySelectorAll('.cedula-lookup').forEach(input => {
            input.addEventListener('blur', function() {
                const val = this.value.trim();
                const feed = this.nextElementSibling;
                if (val.length > 4) {
                    feed.innerHTML = 'Buscando...';
                    fetch(`../../functions/buscar_persona_json.php?cedula=${val}`).then(r => r.json())
                        .then(d => {
                            feed.innerHTML = d.status === 'success' ? `✔ ${d.nombre}` : `✘ No registrada`;
                            feed.className = `nombre-feedback ${d.status === 'success' ? 'text-success' : 'text-error'}`;
                        });
                }
            });
        });

        document.getElementById('form-matrimonio').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('button');
            btn.disabled = true;
            btn.innerText = 'Guardando...';
            fetch('procesar_matrimonio.php', {
                    method: 'POST',
                    body: new FormData(this)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        window.open(data.pdf_url, '_blank');
                        location.reload();
                    } else {
                        alert(data.message);
                        btn.disabled = false;
                        btn.innerText = 'Registrar';
                    }
                })
                .catch(e => {
                    console.error(e);
                    alert("Error servidor");
                    btn.disabled = false;
                });
        });
    });
</script>