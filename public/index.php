<?php
session_start();
require_once '../includes/db/config.php';
require_once ROOT_PATH . 'includes/db/conexion.php';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro Civil de Peña</title>

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/index.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bitcount+Prop+Double+Ink:wght@100..900&family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&family=Oswald:wght@200..700&family=Pacifico&family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Roboto:ital,wght@0,100..900;1,100..900&family=UnifrakturMaguntia&display=swap" rel="stylesheet">
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
                <h1>¡Bienvenido!</h1>
                <h2>Registro Civil de Peña</h2>
                <p>Automatización de actas de:</p>
                <ul>
                    <li>Nacimiento</li>
                    <li>Unión estable de hecho</li>
                    <li>Matrimonio</li>
                    <li>Defunción</li>
                </ul>
            </section>

            <section class="form">
                <?php if (isset($_GET['error'])): ?>
                    <p style="color:red; font-weight:bold;">
                        <?php
                        // Manejo de errores básicos vía URL si vienen de validar_login.php
                        echo htmlspecialchars($_GET['error']);
                        ?>
                    </p>
                <?php endif; ?>

                <form class="formulario" action="<?php echo BASE_URL; ?>modules/login/validar_login.php" method="POST">
                    <h3>Inicio de sesión</h3>

                    <div class="input-group">
                        <div class="icon-container">
                            <img src="<?php echo BASE_URL; ?>assets/img/usuario.png" alt="User">
                        </div>
                        <input type="email" id="email" name="email" placeholder="Usuario" required>
                    </div>

                    <div class="input-group">
                        <div class="icon-container">
                            <img src="<?php echo BASE_URL; ?>assets/img/clave.png" alt="Candado">
                        </div>
                        <input type="password" id="contraseña" name="contraseña" placeholder="Clave" required>
                    </div>

                    <button type="submit">Inicio</button>
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
            <p class="info__tlf">Teléfono: +58 0412-9999999</p>
            <p class="info__email">Email: contacto@civilpeña.com</p>
        </div>
        <div class="footer__yo">
            <a class="yo__link" href="#">
                <p class="yo__info">Desarrollado por: Carlos Canelón.</p>
            </a>
        </div>
    </footer>
</body>

</html>