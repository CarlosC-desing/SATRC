<?php
// Mover includes al principio para asegurar que la sesión esté disponible antes de usarla
require_once '../../includes/db/config.php';

// Iniciar sesión si no está iniciada (aunque config.php suele hacerlo)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generación de Token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Verificación de permisos de Admin
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ' . BASE_URL . 'public/index.php');
    exit();
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Registro Civil de Peña - Registrar Usuario</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/index.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700&family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
</head>

<body>
    <main>
        <div class="fondo">
            <div class="azul"></div>
            <div class="blanco"></div>
        </div>
        <div class="bandera">
            <img class="flag" id="bandera" src="<?php echo BASE_URL; ?>assets/img/SVG/BANDERA.svg" alt="Bandera">
            <img class="escudo" id="bandera" src="<?php echo BASE_URL; ?>assets/img/SVG/escudo.svg" alt="Bandera">
        </div>
        <div class="all">
            <section class="info">
                <h1>Panel de Control</h1>
                <h2>Registro de Usuarios</h2>
                <p>Gestión administrativa para:</p>
                <ul>
                    <li>Nuevos Operadores</li>
                    <li>Administradores</li>
                </ul>
            </section>
            <section class="form">
                <form class="formulario" action="procesar_registro.php" method="POST">
                    <h3>Crear Cuenta</h3>

                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                    <div class="input-group">
                        <input type="text" id="nombre" name="nombre" placeholder="Nombre Completo" required autocomplete="off">
                    </div>

                    <div class="input-group">
                        <input type="email" id="email" name="email" placeholder="Correo Electrónico" required autocomplete="off">
                    </div>

                    <div class="input-group">
                        <input type="password" id="contraseña" name="contraseña" placeholder="Contraseña" required autocomplete="new-password">
                    </div>

                    <div class="input-group">
                        <select id="rol" name="rol" required style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
                            <option value="">Selecciona el rol</option>
                            <option value="usuario">Usuario (Operador)</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>

                    <button type="submit">Registrar</button>
                </form>
            </section>
        </div>
    </main>

    <footer class="footer">
        <div class="footer__logos">
            <img class="logos__yari" src="<?php echo BASE_URL; ?>assets/img/SVG/ESCUDO_YARI.svg" alt="Yari">
        </div>
        <div class="footer__info">
            <p class="info__registro">Registro Civil</p>
            <p class="info__direccion">Dirección: carrera 7 entre calles 17 y 18.</p>
        </div>
        <div class="footer__yo">
            <p class="yo__info">Desarrollado por: Carlos Canelón.</p>
        </div>
    </footer>
</body>

</html>