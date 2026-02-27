<?php
$tipos = $_GET['tipo'] ?? [];
if (!is_array($tipos)) $tipos = [$tipos];
?>

<script src="../../assets/js/paginacion.js"></script>

<div class="reportes-wrapper">
    <?php foreach ($tipos as $tipo): ?>

        <div id="tabla-<?= $tipo ?>" class="tabla-ajax-container">
            <div style="text-align:center; padding: 40px; color:#666;">
                <p>Cargando reporte de <strong><?= ucfirst($tipo) ?></strong>...</p>
                <small>Por favor espere...</small>
            </div>
        </div>

    <?php endforeach; ?>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        <?php foreach ($tipos as $tipo): ?>
            cargarTabla('<?= $tipo ?>', 'general', '', '', 1);
        <?php endforeach; ?>
    });
</script>