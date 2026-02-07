<?php
require_once '../includes/db/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario'])) exit('Acceso denegado');

// 1. Total general de personas
$res = $conn->query("SELECT COUNT(*) AS total FROM personas");
$total_personas = ($res && $row = $res->fetch_assoc()) ? (int)$row['total'] : 0;

// 2. Personas con al menos un acta (Unificando nombres de columnas con AS)
// 2. Personas con al menos un acta
$sql_vinculadas = "
    SELECT COUNT(DISTINCT id_persona) AS total FROM (
        -- NACIMIENTO: Usamos a la Madre y al Padre porque 'id_persona' (niño) no existe en esta tabla
        SELECT id_madre AS id_persona FROM nacimiento WHERE id_madre IS NOT NULL
        UNION
        SELECT id_padre AS id_persona FROM nacimiento WHERE id_padre IS NOT NULL
        
        UNION
        -- DEFUNCIÓN: Verificamos el fallecido
        SELECT id_persona AS id_persona FROM defuncion
        
        UNION
        -- MATRIMONIO: Esposo y Esposa
        SELECT id_contrayente1 AS id_persona FROM matrimonio
        UNION
        SELECT id_contrayente2 AS id_persona FROM matrimonio
        
        UNION
        -- UNIÓN ESTABLE: Ambas partes
        SELECT id_persona1 AS id_persona FROM union_estable
        UNION
        SELECT id_persona2 AS id_persona FROM union_estable
    ) AS relacionadas
";

$res_v = $conn->query($sql_vinculadas);
$con_acta = ($res_v && $row_v = $res_v->fetch_assoc()) ? (int)$row_v['total'] : 0;

// 3. Personas sin ningún acta
$sin_acta = $total_personas - $con_acta;

// Mostrar dashboard
echo "<div class='dashboard'>
        <h3 class='dashboard__title'>Resumen del Sistema</h3>
        <div class='dashboard__contadores'>
            <div class='contadores__personas'>
                <img src='" . BASE_URL . "assets/img/SVG/ICONOPERSONAS.svg' alt='' class='item__icon'>
                <p class='personas__parrafo'>Total de personas: $total_personas</p>
            </div>
            <div class='contadores__conacta'>
                <img src='" . BASE_URL . "assets/img/SVG/ICONOPANELSOLIC.svg' alt='' class='item__icon'>
                <p class='acta__parrafo'>Con al menos un acta: $con_acta</p>
            </div>
            <div class='contadores__sinacta'>
                <img src='" . BASE_URL . "assets/img/SVG/ICONOSINACTA.svg' alt='' class='item__icon'>
                <p class='sacta__parrafo'>Sin ningún acta: $sin_acta</p>
            </div>
        </div>
    </div>";
