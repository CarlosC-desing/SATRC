<?php
require_once '../../includes/db/config.php';
include ROOT_PATH . 'modules/login/verificar_sesion.php';
include ROOT_PATH . 'includes/db/conexion.php';

// --- CONFIGURACIÓN DE FILTROS ---
// Buscamos datos desde el año 2000 por defecto para asegurar que aparezca algo
$desde = $_GET['desde'] ?? '2000-01-01';
$hasta = $_GET['hasta'] ?? date('Y-12-31');
$agrupar = $_GET['agrupar'] ?? 'MONTH';
$modo = 'graficos';

// --- FUNCIONES AUXILIARES ---

// 1. KPI: Conteo Simple
function contar($conn, $tabla, $desde, $hasta)
{
    $sql = "SELECT COUNT(*) as total FROM $tabla WHERE fecha_registro BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $desde, $hasta);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['total'] ?? 0;
}

$kpi_nac = contar($conn, 'nacimiento', $desde, $hasta);
$kpi_mat = contar($conn, 'matrimonio', $desde, $hasta);
$kpi_def = contar($conn, 'defuncion', $desde, $hasta);
$kpi_uni = contar($conn, 'union_estable', $desde, $hasta);

// 2. Gráfico: Top Frecuencia (con validación de array vacío)
function obtenerTop($conn, $tabla, $columna, $desde, $hasta, $limit = 5)
{
    $sql = "SELECT $columna as etiqueta, COUNT(*) as total 
            FROM $tabla 
            WHERE fecha_registro BETWEEN ? AND ? 
            AND $columna IS NOT NULL AND $columna != ''
            GROUP BY $columna 
            ORDER BY total DESC LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $desde, $hasta, $limit);
    $stmt->execute();
    $res = $stmt->get_result();

    $labels = [];
    $values = [];

    while ($row = $res->fetch_assoc()) {
        $labels[] = substr($row['etiqueta'], 0, 15) . '...';
        $values[] = $row['total'];
    }

    // Si está vacío, enviamos un array por defecto para que no rompa el JS
    if (empty($labels)) {
        return ['labels' => ['Sin datos'], 'values' => [0]];
    }

    return ['labels' => $labels, 'values' => $values];
}

// 3. Gráfico: Evolución Temporal
function obtenerTendencia($conn, $tabla, $agrupar, $desde, $hasta)
{
    $sql = "SELECT $agrupar(fecha_registro) as unidad, COUNT(*) as total 
            FROM $tabla WHERE fecha_registro BETWEEN ? AND ? 
            GROUP BY unidad ORDER BY unidad ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $desde, $hasta);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = [];
    while ($r = $res->fetch_assoc()) {
        $data[$r['unidad']] = $r['total'];
    }
    return $data;
}

// --- CONSULTAS ESPECÍFICAS (CORREGIDAS) ---

// A. Nacimientos por Sexo (Robustez: tomamos la primera letra mayúscula)
// Usamos SUBSTRING(UPPER(sexo), 1, 1) para que "Masculino" y "M" cuenten igual.
$sql_sexo_nac = "SELECT SUBSTRING(UPPER(sexo), 1, 1) as letra_sexo, COUNT(*) as total 
                 FROM nacimiento 
                 WHERE fecha_registro BETWEEN ? AND ? 
                 GROUP BY letra_sexo";
$stmt_sn = $conn->prepare($sql_sexo_nac);
$stmt_sn->bind_param("ss", $desde, $hasta);
$stmt_sn->execute();
$res_sn = $stmt_sn->get_result();
$nac_m = 0;
$nac_f = 0;
while ($r = $res_sn->fetch_assoc()) {
    if ($r['letra_sexo'] == 'M') $nac_m = $r['total'];
    if ($r['letra_sexo'] == 'F') $nac_f = $r['total'];
}

// B. Top Causas y Lugares
$top_causas = obtenerTop($conn, 'defuncion', 'causa_defuncion', $desde, $hasta, 5);
$top_lugares_nac = obtenerTop($conn, 'nacimiento', 'lugar_nacimiento', $desde, $hasta, 5);

// C. Evolución Temporal (Líneas)
$data_nac = obtenerTendencia($conn, 'nacimiento', $agrupar, $desde, $hasta);
$data_mat = obtenerTendencia($conn, 'matrimonio', $agrupar, $desde, $hasta);
$data_def = obtenerTendencia($conn, 'defuncion', $agrupar, $desde, $hasta);
$data_uni = obtenerTendencia($conn, 'union_estable', $agrupar, $desde, $hasta);

$unidades = array_unique(array_merge(
    array_keys($data_nac),
    array_keys($data_mat),
    array_keys($data_def),
    array_keys($data_uni)
));
sort($unidades);

// Si no hay datos temporales, creamos un punto dummy para que la gráfica no falle
if (empty($unidades)) {
    $unidades = [date('m')]; // Mes actual
}

$labels_time = array_map(function ($u) use ($agrupar) {
    if ($agrupar == 'MONTH') {
        $meses = ["", "Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul", "Ago", "Sep", "Oct", "Nov", "Dic"];
        return $meses[(int)$u] ?? "Mes $u";
    }
    return $u;
}, $unidades);

$series_nac = [];
$series_mat = [];
$series_def = [];
$series_uni = [];
foreach ($unidades as $u) {
    $series_nac[] = $data_nac[$u] ?? 0;
    $series_mat[] = $data_mat[$u] ?? 0;
    $series_def[] = $data_def[$u] ?? 0;
    $series_uni[] = $data_uni[$u] ?? 0;
}

// D. DEMOGRAFÍA: GRUPOS DE EDAD (Pre-llenado obligatorio)
// Inicializamos el array con 0 para asegurar que las etiquetas existan
$datos_edad = [
    'Niños (0-12)'     => 0,
    'Adolesc. (13-17)' => 0,
    'Jóvenes (18-35)'  => 0,
    'Adultos (36-60)'  => 0,
    'Mayor (60+)'      => 0
];

// D. DEMOGRAFÍA: GRUPOS DE EDAD (Unificado: Personas + Nacimientos)
// Inicializamos el array en 0 para mantener el orden visual
$datos_edad = [
    'Niños (0-12)'     => 0,
    'Adolesc. (13-17)' => 0,
    'Jóvenes (18-35)'  => 0,
    'Adultos (36-60)'  => 0,
    'Mayor (60+)'      => 0
];

// Usamos UNION para combinar personas y nacimientos sin duplicar (por ID)
$sql_edad = "SELECT 
    CASE 
        WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 0 AND 12 THEN 'Niños (0-12)'
        WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 13 AND 17 THEN 'Adolesc. (13-17)'
        WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 18 AND 35 THEN 'Jóvenes (18-35)'
        WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 36 AND 60 THEN 'Adultos (36-60)'
        ELSE 'Mayor (60+)'
    END as rango, 
    COUNT(*) as cantidad
    FROM (
        -- Seleccionamos de personas
        SELECT id_persona, fecha_nacimiento FROM personas 
        WHERE fecha_nacimiento IS NOT NULL AND fecha_nacimiento != '0000-00-00'
        UNION
        -- Unimos con nacimientos (el UNION elimina duplicados de ID automáticamente)
        SELECT id_nacido as id_persona, fecha_nacimiento FROM nacimiento 
        WHERE fecha_nacimiento IS NOT NULL AND fecha_nacimiento != '0000-00-00'
    ) as poblacion_unificada
    GROUP BY rango";

$res_edad = $conn->query($sql_edad);

if ($res_edad) {
    while ($row = $res_edad->fetch_assoc()) {
        if (isset($datos_edad[$row['rango']])) {
            $datos_edad[$row['rango']] = $row['cantidad'];
        }
    }
}

$labels_edad = array_keys($datos_edad);
$data_edad = array_values($datos_edad);

$pob_m = $conn->query("SELECT COUNT(*) as t FROM personas WHERE sexo = 'M'")->fetch_assoc()['t'] ?? 0;
$pob_f = $conn->query("SELECT COUNT(*) as t FROM personas WHERE sexo = 'F'")->fetch_assoc()['t'] ?? 0;
$pob_total = $pob_m + $pob_f;

$titulo_pagina = "Gráficos Estadísticos";
include ROOT_PATH . 'includes/components/header.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/graficos.css">

<main>
    <div class="full-layout">
        <main class="content-full">
            <div class="container-fluid">

                <div class="dashboard-section">
                    <header class="dashboard-header">
                        <div>
                            <h2 class="title-main-w">Panel de Control Registral</h2>
                            <p class="subtitle-w">
                                Visualizando datos desde <strong><?= date('d/m/Y', strtotime($desde)) ?></strong>
                                hasta <strong><?= date('d/m/Y', strtotime($hasta)) ?></strong>
                            </p>
                        </div>
                        <form class="main-filters" method="GET">
                            <div class="filter-group">
                                <label>Desde</label>
                                <input type="date" name="desde" value="<?= htmlspecialchars($desde) ?>">
                            </div>
                            <div class="filter-group">
                                <label>Hasta</label>
                                <input type="date" name="hasta" value="<?= htmlspecialchars($hasta) ?>">
                            </div>
                            <div class="filter-group">
                                <label>Agrupar</label>
                                <select name="agrupar">
                                    <option value="DAY" <?= $agrupar == 'DAY' ? 'selected' : '' ?>>Día</option>
                                    <option value="MONTH" <?= $agrupar == 'MONTH' ? 'selected' : '' ?>>Mes</option>
                                    <option value="YEAR" <?= $agrupar == 'YEAR' ? 'selected' : '' ?>>Año</option>
                                </select>
                            </div>
                            <button type="submit" class="btn-primary">Actualizar</button>
                        </form>
                    </header>

                    <div class="kpi-card-unified">
                        <div class="kpi-header">
                            <small>Población Total</small>
                            <h3><?= number_format($pob_total) ?> <small>Personas</small></h3>
                        </div>
                        <div class="gender-bar">
                            <div class="bar-m" style="width: <?= ($pob_total > 0) ? ($pob_m / $pob_total * 100) : 0 ?>%">
                                <span>Hombres: <?= number_format($pob_m) ?></span>
                            </div>
                            <div class="bar-f" style="width: <?= ($pob_total > 0) ? ($pob_f / $pob_total * 100) : 0 ?>%">
                                <span>Mujeres: <?= number_format($pob_f) ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="kpi-grid">
                        <div class="kpi-card kpi-blue">
                            <div class="kpi-icon">👶</div>
                            <div class="kpi-info">
                                <h3><?= number_format($kpi_nac) ?></h3>
                                <span>Nacimientos</span>
                            </div>
                        </div>
                        <div class="kpi-card kpi-green">
                            <div class="kpi-icon">💍</div>
                            <div class="kpi-info">
                                <h3><?= number_format($kpi_mat) ?></h3>
                                <span>Matrimonios</span>
                            </div>
                        </div>
                        <div class="kpi-card kpi-red">
                            <div class="kpi-icon">⚰️</div>
                            <div class="kpi-info">
                                <h3><?= number_format($kpi_def) ?></h3>
                                <span>Defunciones</span>
                            </div>
                        </div>
                        <div class="kpi-card kpi-purple">
                            <div class="kpi-icon">🤝</div>
                            <div class="kpi-info">
                                <h3><?= number_format($kpi_uni) ?></h3>
                                <span>Uniones</span>
                            </div>
                        </div>
                    </div>

                    <div class="charts-grid-2">
                        <div class="chart-box shadow">
                            <div class="chart-header">
                                <h4>Volumen de Trámites (Comparativa)</h4>
                                <button onclick="descargarChart('chartActas')" class="btn-mini">Descargar</button>
                            </div>
                            <div class="chart-canvas-container">
                                <canvas id="chartActas"></canvas>
                            </div>
                        </div>
                        <div class="chart-box shadow">
                            <div class="chart-header">
                                <h4>Nacimientos por Sexo</h4>
                                <button onclick="descargarChart('chartSexo')" class="btn-mini">Descargar</button>
                            </div>
                            <div class="chart-canvas-container">
                                <canvas id="chartSexo"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="chart-box shadow full-width">
                        <div class="chart-header">
                            <h4>Evolución Temporal de Registros</h4>
                            <button onclick="descargarChart('chartLineas')" class="btn-mini">Descargar</button>
                        </div>
                        <div class="chart-canvas-container-lg">
                            <canvas id="chartLineas"></canvas>
                        </div>
                    </div>

                    <div class="charts-grid-2">
                        <div class="chart-box shadow">
                            <div class="chart-header">
                                <h4>Top 5 Causas de Defunción</h4>
                                <button onclick="descargarChart('chartCausas')" class="btn-mini">Descargar</button>
                            </div>
                            <div class="chart-canvas-container">
                                <canvas id="chartCausas"></canvas>
                            </div>
                        </div>
                        <div class="chart-box shadow">
                            <div class="chart-header">
                                <h4>Top 5 Lugares de Nacimiento</h4>
                                <button onclick="descargarChart('chartLugares')" class="btn-mini">Descargar</button>
                            </div>
                            <div class="chart-canvas-container">
                                <canvas id="chartLugares"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="charts-grid-2">
                        <div class="chart-box shadow full-width">
                            <div class="chart-header">
                                <h4>Demografía: Rango de Edades</h4>
                                <button onclick="descargarChart('chartEdad')" class="btn-mini">Descargar</button>
                            </div>
                            <div class="chart-canvas-container">
                                <canvas id="chartEdad"></canvas>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>
</main>

<script src="../../assets/lib/chart.umd.js"></script>
<script>
    Chart.defaults.font.family = "'Roboto', sans-serif";
    Chart.defaults.color = '#555';
    const colors = {
        nac: '#3498db',
        mat: '#27ae60',
        def: '#e74c3c',
        uni: '#9b59b6',
        m: '#2980b9',
        f: '#fd79a8',
        bg: ['#f1c40f', '#e67e22', '#1abc9c', '#34495e', '#95a5a6']
    };

    // 1. Distribución General (Pie)
    const totalActas = <?= $kpi_nac + $kpi_mat + $kpi_def + $kpi_uni ?>;
    const pieData = totalActas > 0 ? [<?= $kpi_nac ?>, <?= $kpi_mat ?>, <?= $kpi_def ?>, <?= $kpi_uni ?>] : [0, 0, 0, 0];

    new Chart(document.getElementById('chartActas'), {
        type: 'pie',
        data: {
            labels: ['Nacimientos', 'Matrimonios', 'Defunciones', 'Uniones'],
            datasets: [{
                data: pieData,
                backgroundColor: [colors.nac, colors.mat, colors.def, colors.uni]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                title: {
                    display: totalActas === 0,
                    text: 'Sin datos registrados en este periodo'
                }
            }
        }
    });

    // 2. Nacimientos por Sexo (Doughnut)
    // Forzamos la visualización aunque sea 0 para que se vea el circulo vacio o la leyenda
    new Chart(document.getElementById('chartSexo'), {
        type: 'doughnut',
        data: {
            labels: ['Masculino', 'Femenino'],
            datasets: [{
                data: [<?= $nac_m ?>, <?= $nac_f ?>],
                backgroundColor: [colors.m, colors.f]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // 3. Evolución Temporal (Line)
    new Chart(document.getElementById('chartLineas'), {
        type: 'line',
        data: {
            labels: <?= json_encode($labels_time) ?>,
            datasets: [{
                    label: 'Nacimientos',
                    data: <?= json_encode($series_nac) ?>,
                    borderColor: colors.nac,
                    tension: 0.3,
                    fill: false
                },
                {
                    label: 'Matrimonios',
                    data: <?= json_encode($series_mat) ?>,
                    borderColor: colors.mat,
                    tension: 0.3,
                    fill: false
                },
                {
                    label: 'Defunciones',
                    data: <?= json_encode($series_def) ?>,
                    borderColor: colors.def,
                    tension: 0.3,
                    fill: false
                },
                {
                    label: 'Uniones',
                    data: <?= json_encode($series_uni) ?>,
                    borderColor: colors.uni,
                    tension: 0.3,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // 4. Causas de Defunción
    new Chart(document.getElementById('chartCausas'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($top_causas['labels']) ?>,
            datasets: [{
                label: 'Fallecidos',
                data: <?= json_encode($top_causas['values']) ?>,
                backgroundColor: colors.def,
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // 5. Lugares de Nacimiento
    new Chart(document.getElementById('chartLugares'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($top_lugares_nac['labels']) ?>,
            datasets: [{
                label: 'Nacimientos',
                data: <?= json_encode($top_lugares_nac['values']) ?>,
                backgroundColor: colors.nac,
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // 6. Rango de Edades (Bar)
    new Chart(document.getElementById('chartEdad'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($labels_edad) ?>,
            datasets: [{
                label: 'Personas',
                data: <?= json_encode($data_edad) ?>,
                backgroundColor: colors.bg,
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    function descargarChart(id) {
        const link = document.createElement('a');
        link.download = id + '.png';
        link.href = document.getElementById(id).toDataURL();
        link.click();
    }
</script>
</body>

</html>