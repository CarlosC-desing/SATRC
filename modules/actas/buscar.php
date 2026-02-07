<?php
require_once '../../includes/db/config.php';
include ROOT_PATH . 'modules/login/verificar_sesion.php';
include ROOT_PATH . 'includes/db/conexion.php';
include ROOT_PATH . 'functions/registrar_log.php';

header('Content-Type: text/html; charset=utf-8');

$titulo_pagina = "Buscar - Registro Civil";
include ROOT_PATH . 'includes/components/header.php';
include ROOT_PATH . 'includes/components/sidebar_busqueda.php';

$id_persona_consulta = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/buscadores.css">

<div class="main__content" id="main-content">
    <?php
    if ($id_persona_consulta) {
        // --- CONSULTA UNIFICADA DE ACTAS ---
        // Se buscan actas donde la persona aparezca como protagonista (Nacido, Difunto, Esposo/a, Unido/a)

        $sql = "
            /* 1. NACIMIENTO (Usando la nueva columna id_nacido) */
            SELECT 'Nacimiento' AS tipo, n.numero_acta, CONCAT_WS(' ', p.primer_nombre, p.primer_apellido) AS nombre, p.cedula, n.lugar_nacimiento AS lugar
            FROM personas p 
            JOIN nacimiento n ON p.id_persona = n.id_nacido 
            WHERE p.id_persona = ?
            
            UNION
            
            /* 2. DEFUNCIÓN */
            SELECT 'Defunción' AS tipo, d.numero_acta, CONCAT_WS(' ', p.primer_nombre, p.primer_apellido) AS nombre, p.cedula, d.lugar_defuncion AS lugar
            FROM personas p 
            JOIN defuncion d ON p.id_persona = d.id_persona 
            WHERE p.id_persona = ?
            
            UNION
            
            /* 3. MATRIMONIO (Como Esposo) */
            SELECT 'Matrimonio' AS tipo, m.numero_acta, CONCAT_WS(' ', p.primer_nombre, p.primer_apellido) AS nombre, p.cedula, m.lugar_matrimonio AS lugar
            FROM personas p 
            JOIN matrimonio m ON p.id_persona = m.id_esposo 
            WHERE p.id_persona = ?
            
            UNION
            
            /* 4. MATRIMONIO (Como Esposa) */
            SELECT 'Matrimonio' AS tipo, m.numero_acta, CONCAT_WS(' ', p.primer_nombre, p.primer_apellido) AS nombre, p.cedula, m.lugar_matrimonio AS lugar
            FROM personas p 
            JOIN matrimonio m ON p.id_persona = m.id_esposa 
            WHERE p.id_persona = ?

            UNION

            /* 5. UNIÓN ESTABLE (Persona 1) */
            SELECT 'Unión Estable' AS tipo, u.numero_acta, CONCAT_WS(' ', p.primer_nombre, p.primer_apellido) AS nombre, p.cedula, 'Registro Civil' AS lugar
            FROM personas p 
            JOIN union_estable u ON p.id_persona = u.id_persona1 
            WHERE p.id_persona = ?

            UNION

            /* 6. UNIÓN ESTABLE (Persona 2) */
            SELECT 'Unión Estable' AS tipo, u.numero_acta, CONCAT_WS(' ', p.primer_nombre, p.primer_apellido) AS nombre, p.cedula, 'Registro Civil' AS lugar
            FROM personas p 
            JOIN union_estable u ON p.id_persona = u.id_persona2 
            WHERE p.id_persona = ?
        ";

        $stmt = $conn->prepare($sql);

        // Vinculamos 6 parámetros (uno por cada SELECT del UNION)
        $stmt->bind_param(
            "iiiiii",
            $id_persona_consulta,
            $id_persona_consulta,
            $id_persona_consulta,
            $id_persona_consulta,
            $id_persona_consulta,
            $id_persona_consulta
        );

        $stmt->execute();
        $result = $stmt->get_result();

        echo "<h2 class='table-title'>Actas de la persona ID: " . htmlspecialchars((string)$id_persona_consulta) . "</h2>";

        if ($result && $result->num_rows > 0) {
            echo "<table class='bitacora-table'><thead><tr><th>Tipo</th><th>Número</th><th>Nombre</th><th>Cédula</th><th>Lugar</th><th>Acciones</th></tr></thead><tbody>";
            while ($row = $result->fetch_assoc()) {
                $tipo = htmlspecialchars($row['tipo']);
                $num = htmlspecialchars($row['numero_acta']);

                $nombre_decode = htmlspecialchars_decode($row['nombre']);

                // Selección de ruta PDF según el tipo
                $ruta_pdf = match (strtolower($tipo)) {
                    'nacimiento' => 'generar_pdf/pdf_nacimiento.php',
                    'matrimonio' => 'generar_pdf/pdf_matrimonio.php',
                    'defunción', 'defuncion' => 'generar_pdf/pdf_defuncion.php',
                    'unión estable', 'union estable' => 'generar_pdf/pdf_union.php',
                    default => '#'
                };

                echo "<tr>
                        <td>{$tipo}</td>
                        <td>{$num}</td>
                        <td>" . htmlspecialchars($nombre_decode) . "</td>
                        <td>" . htmlspecialchars($row['cedula'] ?? '—') . "</td>
                        <td>" . htmlspecialchars($row['lugar']) . "</td>
                        <td>
                            <div class='dropdown'>
                                <button class='dropbtn'>Acciones</button>
                                <div class='dropdown-content'>
                                    <a href='editar_acta.php?numero_acta=" . urlencode($num) . "'>Editar</a>
                                    <a href='{$ruta_pdf}?numero_acta=" . urlencode($num) . "' target='_blank'>Descargar</a>
                                </div>
                            </div>
                        </td>
                      </tr>";
            }
            echo "</tbody></table>";
        } else {
            echo "<p>No se encontraron actas vinculadas a esta persona.</p>";
        }
        echo "<br><a href='buscar.php' class='form__button'>Volver a la búsqueda</a>";
        $stmt->close();
    } else {
        // --- FORMULARIO DE BÚSQUEDA ---
        $columnas_permitidas = ['cedula', 'primer_nombre', 'segundo_nombre', 'primer_apellido', 'segundo_apellido'];
        $filtros_activos = [];

        foreach ($columnas_permitidas as $col) {
            if (!empty($_GET[$col])) {
                $filtros_activos[$col] = $_GET[$col];
            }
        }

        if (!empty($filtros_activos)) {
            $sql = "SELECT * FROM personas WHERE 1=1";
            $params = [];
            $types = "";

            foreach ($filtros_activos as $columna => $valor) {
                $sql .= " AND " . $columna . " LIKE ?";
                $params[] = "%$valor%";
                $types .= "s";
            }

            $stmt = $conn->prepare($sql);
            if ($types !== "") {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                echo "<h2 class='resultados__title'>Resultados de la búsqueda:</h2>";
                echo "<table class='bitacora-table'><thead><tr><th>ID</th><th>Nombre</th><th>Cédula</th><th>Nacimiento</th><th>Acción</th></tr></thead><tbody>";
                while ($row = $result->fetch_assoc()) {
                    $id_p = (int)$row['id_persona'];

                    $nombre_completo = trim("{$row['primer_nombre']} {$row['segundo_nombre']} {$row['primer_apellido']} {$row['segundo_apellido']}");
                    $nombre_c = htmlspecialchars(htmlspecialchars_decode($nombre_completo));

                    echo "<tr>
                            <td>{$id_p}</td>
                            <td>{$nombre_c}</td>
                            <td>" . htmlspecialchars($row['cedula'] ?? 'S/C') . "</td>
                            <td>" . htmlspecialchars($row['fecha_nacimiento']) . "</td>
                            <td>
                                <div class='dropdown'>
                                    <button class='dropbtn'>Acciones</button>
                                    <div class='dropdown-content'>
                                        <a href='editar_persona.php?id={$id_p}'>Actualizar</a>
                                        <a href='../expedientes/ver_expediente.php?id={$id_p}'>📂 Expediente Digital</a>
                                        <a href='buscar.php?id={$id_p}'>Ver actas</a>
                                    </div>
                                </div>
                            </td>
                          </tr>";
                }
                echo "</tbody></table>";
            } else {
                echo "<p>No se encontraron resultados.</p>";
            }
            $stmt->close();
        }
    }
    ?>
</div>

<?php
$conn->close();
?>