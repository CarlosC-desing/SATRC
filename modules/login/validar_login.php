<?php
require_once '../../includes/db/config.php';
session_start();
require_once ROOT_PATH . 'includes/db/conexion.php';
include '../../functions/validaciones.php';
$_POST = sanear($_POST);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $contraseña = $_POST['contraseña'];
    $max_intentos = 5;
    $minutos_bloqueo = 15;

    // 1. Verificar si el usuario está bloqueado temporalmente
    $sql_check = "SELECT intentos, ultimo_intento FROM login_attempts WHERE email = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();

    if ($attempt = $res_check->fetch_assoc()) {
        $tiempo_pasado = (time() - strtotime($attempt['ultimo_intento'])) / 60;
        if ($attempt['intentos'] >= $max_intentos && $tiempo_pasado < $minutos_bloqueo) {
            $espera = ceil($minutos_bloqueo - $tiempo_pasado);
            header("Location: " . BASE_URL . "public/index.php?error=Demasiados intentos. Intente en $espera minutos.");
            exit();
        }
    }

    // 2. Intentar autenticar al usuario
    $sql = "SELECT id, nombre, email, contraseña, rol FROM Usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($contraseña, $row['contraseña'])) {
            // ÉXITO: Limpiar intentos fallidos y configurar sesión
            $stmt_reset = $conn->prepare("DELETE FROM login_attempts WHERE email = ?");
            $stmt_reset->bind_param("s", $email);
            $stmt_reset->execute();

            session_regenerate_id(true);
            $_SESSION['usuario'] = $row['nombre'];
            $_SESSION['id_usuario'] = $row['id'];
            $_SESSION['rol'] = $row['rol'];

            if (empty($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }

            header("Location: " . BASE_URL . "public/menu_principal.php");
            exit();
        }
    }

    // FALLO: Registrar o incrementar intento fallido
    $stmt_fail = $conn->prepare("INSERT INTO login_attempts (email, intentos) VALUES (?, 1) 
                                 ON DUPLICATE KEY UPDATE intentos = intentos + 1, ultimo_intento = CURRENT_TIMESTAMP");
    $stmt_fail->bind_param("s", $email);
    $stmt_fail->execute();

    header("Location: " . BASE_URL . "public/index.php?error=Credenciales incorrectas");
    exit();
}
$conn->close();
