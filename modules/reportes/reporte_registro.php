<?php
$tipos = $_GET['tipo'] ?? [];
$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';

if (!is_array($tipos)) $tipos = [$tipos];
?>

<script src="../../assets/js/paginacion.js"></script>

<?php if ($desde && $hasta): ?>

    <div class="reportes-wrapper">
        <?php foreach ($tipos as $tipo): ?>

            <div id="tabla-<?= $tipo ?>" class="tabla-ajax-container">
                <div style="text-align:center; padding: 40px; color:#666;">
                    <p>Buscando registros de <strong><?= ucfirst($tipo) ?></strong>...</p>
                </div>
            </div>

        <?php endforeach; ?>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            <?php foreach ($tipos as $tipo): ?>
                // Modo 'registro' (busca por fecha_registro)
                cargarTabla('<?= $tipo ?>', 'registro', '<?= $desde ?>', '<?= $hasta ?>', 1);
            <?php endforeach; ?>
        });
    </script>

<?php else: ?>
    <div class='empty-state' style="text-align:center; padding: 50px;">
        <h3 style="color: #2b388f;">⚠️ Rango de fechas incompleto</h3>
        <p>Seleccione el rango de fechas en que se realizó el registro en el sistema.</p>
    </div>
<?php endif; ?>