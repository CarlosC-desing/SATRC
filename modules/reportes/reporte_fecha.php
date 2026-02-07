<?php
$tipos = $_GET['tipo'] ?? [];
$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';

// Aseguramos que sea un array
if (!is_array($tipos)) $tipos = [$tipos];
?>

<script src="../../assets/js/paginacion.js"></script>

<?php if ($desde && $hasta): ?>

    <div class="reportes-wrapper">
        <?php foreach ($tipos as $tipo): ?>

            <div id="tabla-<?= $tipo ?>" class="tabla-ajax-container">
                <div style="text-align:center; padding: 40px; color:#666;">
                    <p>Buscando <strong><?= ucfirst($tipo) ?></strong> entre <?= $desde ?> y <?= $hasta ?>...</p>
                </div>
            </div>

        <?php endforeach; ?>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            <?php foreach ($tipos as $tipo): ?>
                // Modo 'fecha' con el rango seleccionado
                cargarTabla('<?= $tipo ?>', 'fecha', '<?= $desde ?>', '<?= $hasta ?>', 1);
            <?php endforeach; ?>
        });
    </script>

<?php else: ?>
    <div class='empty-state' style="text-align:center; padding: 50px;">
        <h3 style="color: #2b388f;">⚠️ Rango de fechas incompleto</h3>
        <p>Por favor, seleccione una fecha "Desde" y "Hasta" para generar el reporte.</p>
    </div>
<?php endif; ?>