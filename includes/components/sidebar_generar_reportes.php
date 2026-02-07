<aside class="report-sidebar">
    <div class="report-card-mini">
        <header class="report-header">
            <h2 class="subtitle">GENERAR</h2>
            <h3 class="title-main">REPORTES</h3>
        </header>

        <div class="report-section" style="display: flex; flex-direction: column; gap: 10px;">
            <a href="?modo=general" class="btn-normal <?= ($modo == 'general') ? 'active' : '' ?>">Reporte General</a>
            <a href="?modo=fecha" class="btn-normal <?= ($modo == 'fecha') ? 'active' : '' ?>">Por fecha de actas</a>
            <a href="?modo=registro" class="btn-normal <?= ($modo == 'registro') ? 'active' : '' ?>">Por fecha de registro</a>

            <a href="visualizar_graficos.php" class="btn-normal btn-graficos <?= (basename($_SERVER['PHP_SELF']) == 'visualizar_graficos.php') ? 'active' : '' ?>">
                Modo Gráficos
            </a>
        </div>

        <hr class="content-divider">

        <form action="generar_reportes.php" method="GET">
            <input type="hidden" name="modo" value="<?= htmlspecialchars($modo) ?>">

            <?php
            // Mostramos el rango de fechas si estamos en los modos de tabla o en la página de gráficos
            $es_grafico = (basename($_SERVER['PHP_SELF']) == 'visualizar_graficos.php');
            if ($modo === 'fecha' || $modo === 'registro' || $es_grafico):
            ?>
                <div id="section-fechas" class="date-container" style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 15px;">
                    <label class="subtitle">Rango de búsqueda:</label>
                    <input type="date" name="desde" value="<?= htmlspecialchars($desde) ?>" class="input-date">
                    <input type="date" name="hasta" value="<?= htmlspecialchars($hasta) ?>" class="input-date">
                </div>
            <?php endif; ?>

            <section class="report-section">
                <div class="titles">
                    <h2 class="subtitle">Selección de tipo(s)</h2>
                    <h3 class="title-sub">De actas</h3>
                </div>
                <div class="checkbox-group-mini">
                    <?php
                    $opciones = ['nacimiento' => 'Nacimiento', 'matrimonio' => 'Matrimonio', 'union' => 'Unión estable', 'defuncion' => 'Defunción'];
                    foreach ($opciones as $val => $label): ?>
                        <label class="check-item-mini">
                            <input type="checkbox" name="tipo[]" value="<?= $val ?>" <?= in_array($val, $tipos_sel ?? []) ? 'checked' : '' ?>>
                            <span><?= $label ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </section>

            <button type="submit" class="btn-normal btn-normal--generar">Aplicar Filtros</button>
        </form>
    </div>
</aside>