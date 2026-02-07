<?php
// Ajustamos ruta: Salimos de 'modules/expedientes' (../../) para llegar a 'includes'
require_once '../../includes/db/config.php';
include ROOT_PATH . 'modules/login/verificar_sesion.php';
include ROOT_PATH . 'includes/db/conexion.php';

// Validar ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../actas/buscar.php");
    exit;
}
$id_persona = (int)$_GET['id'];

// Obtener datos de la persona
$stmt = $conn->prepare("SELECT primer_nombre, primer_apellido, cedula FROM personas WHERE id_persona = ?");
$stmt->bind_param("i", $id_persona);
$stmt->execute();
$persona = $stmt->get_result()->fetch_assoc();

if (!$persona) {
    die("Persona no encontrada.");
}

$titulo_pagina = "Expediente: " . $persona['primer_nombre'];
include ROOT_PATH . 'includes/components/header.php';
include ROOT_PATH . 'includes/components/sidebar_busqueda.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/expedientes.css">

<div class="expediente-wrapper">
    <h2>📂 Expediente Digital</h2>
    <p style="font-size: 1.1em; color: #555; margin-bottom: 20px;">
        Documentos asociados a: <strong><?= $persona['primer_nombre'] . " " . $persona['primer_apellido'] ?></strong>
        (C.I: <?= $persona['cedula'] ?>)
    </p>

    <div class="card-upload">
        <h4 style="margin-top: 0;">📎 Subir Nuevo Documento</h4>
        <form action="procesar_expediente.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id_persona" value="<?= $id_persona ?>">
            <input type="hidden" name="cedula_persona" value="<?= $persona['cedula'] ?>">

            <div class="form-row">
                <div class="input-group">
                    <label>Tipo de Documento:</label>
                    <select name="tipo_documento" class="custom-select" required>
                        <option value="" disabled selected>Seleccione...</option>
                        <option value="NACIMIENTO">Acta de Nacimiento</option>
                        <option value="MATRIMONIO">Acta de Matrimonio</option>
                        <option value="UNION">Acta de Union Estable de Hecho</option>
                        <option value="DEFUNCION">Acta de Defunción</option>
                        <option value="CEDULA">Copia de Cédula</option>
                        <option value="SENTENCIA">Sentencia Judicial</option>
                        <option value="CONSTANCIA">Constancia de Residencia</option>
                        <option value="OTRO">Otro Documento</option>
                    </select>
                </div>

                <div class="input-group">
                    <label>Archivo (PDF o Imagen):</label>
                    <input type="file" name="archivo" class="custom-file" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>

                <div class="input-group">
                    <button type="submit" class="btn-upload">Subir Archivo</button>
                </div>
            </div>
        </form>
    </div>

    <hr style="border: 0; border-top: 1px solid #eee; margin: 30px 0;">

    <h3 style="color: #2c3e50;">Documentos Archivados</h3>

    <div class="card-files">
        <?php
        $sql_docs = "SELECT * FROM expedientes WHERE id_persona = ? ORDER BY fecha_subida DESC";
        $stmt_docs = $conn->prepare($sql_docs);
        $stmt_docs->bind_param("i", $id_persona);
        $stmt_docs->execute();
        $result_docs = $stmt_docs->get_result();

        if ($result_docs->num_rows > 0) {
            while ($doc = $result_docs->fetch_assoc()) {
                $ext = strtolower(pathinfo($doc['ruta_archivo'], PATHINFO_EXTENSION));
                $icon = ($ext == 'pdf') ? '📄' : '🖼️';
                $color_icon = ($ext == 'pdf') ? '#e74c3c' : '#f39c12';
                $url_archivo = BASE_URL . $doc['ruta_archivo'];
        ?>
                <div class="file-item">
                    <span class="file-icon" style="color: <?= $color_icon ?>;"><?= $icon ?></span>
                    <span class="file-name"><?= htmlspecialchars($doc['tipo_documento']) ?></span>
                    <span class="file-meta">
                        <?= date('d/m/Y h:i A', strtotime($doc['fecha_subida'])) ?><br>
                        <?= strtoupper($ext) ?>
                    </span>
                    <a href="<?= $url_archivo ?>" target="_blank" class="btn-action btn-view">Ver Documento</a>
                    <button type="button"
                        onclick="confirmarEliminacion(<?= $doc['id_expediente'] ?>, <?= $id_persona ?>)"
                        class="btn-action btn-delete">
                        Eliminar
                    </button>
                </div>
        <?php
            }
        } else {
            echo '<div style="grid-column: 1/-1; text-align: center; color: #95a5a6; padding: 20px;">
                    <span style="font-size: 30px; display: block; margin-bottom: 10px;">📂</span>
                    No hay documentos cargados en este expediente.
                  </div>';
        }
        ?>
    </div>

    <a href="../actas/buscar.php" class="back-link">← Volver a la búsqueda</a>
</div>

<script>
    function confirmarEliminacion(idExp, idPer) {
        if (confirm('¿Estás seguro de que deseas eliminar este documento? Esta acción borrará el archivo físico y el registro permanentemente.')) {
            window.location.href = 'eliminar_expediente.php?id_exp=' + idExp + '&id_persona=' + idPer;
        }
    }
</script>

<?php
$stmt->close();
$conn->close();
?>