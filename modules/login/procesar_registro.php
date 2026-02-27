<?php
require_once '../../includes/db/config.php';
include_once '../../includes/db/conexion.php';
include_once '../../functions/registrar_log.php';
include '../../functions/validaciones.php';
$_POST = sanear($_POST);

header('Content-Type: text/html; charset=utf-8');

// 1. SEGURIDAD: Verificar sesión y permisos
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    die("Acceso denegado.");
}

// 2. SEGURIDAD: Verificar Token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    // Si el token no coincide, se detiene la ejecución inmediatamente
    die("Error de seguridad: Token inválido (Posible ataque CSRF). Por favor recargue el formulario.");
}

// 3. SANITIZACIÓN: Limpiar datos de entrada
$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL); // Devuelve false si no es correo válido
$password_raw = $_POST['contraseña'] ?? '';
$rol = $_POST['rol'] ?? '';

// 4. VALIDACIÓN: Verificar campos obligatorios y válidos
if (empty($nombre) || !$email || empty($password_raw) || empty($rol)) {
    echo "<script>alert('❌ Faltan campos por llenar o el correo no es válido'); window.history.back();</script>";
    exit();
}

// Validar que el rol sea uno de los permitidos
$roles_permitidos = ['admin', 'usuario'];
if (!in_array($rol, $roles_permitidos)) {
    echo "<script>alert('❌ Rol seleccionado no válido'); window.history.back();</script>";
    exit();
}

// Hashing de contraseña
$password_hash = password_hash($password_raw, PASSWORD_DEFAULT);

// 5. INSERCIÓN: Consulta preparada y manejo de errores seguro
$sql = "INSERT INTO Usuarios (nombre, email, contraseña, rol) VALUES (?, ?, ?, ?)";


try {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error en la preparación de la consulta: " . $conn->error);
    }

    $stmt->bind_param("ssss", $nombre, $email, $password_hash, $rol);

    if ($stmt->execute()) {
        $usuario_admin = $_SESSION['usuario'];
        $detalle = "Creó usuario: $email con rol: $rol";
        registrarLog($conn, $usuario_admin, "Usuarios", "Creación", $detalle);

        echo "<script>
            alert('✅ Usuario registrado correctamente');
            window.location.href = '../../public/menu_principal.php';
        </script>";
        exit();
    } else {
        throw new Exception($stmt->error, $stmt->errno);
    }

    $stmt->close();
} catch (Exception $e) {

    if ($e->getCode() == 1062) {
        echo "<script>
            alert('❌ El correo electrónico ya está registrado.');
            window.history.back();
        </script>";
    } else {
        error_log("Error DB en procesar_registro.php: " . $e->getMessage());

        echo "<script>
            alert('❌ Ocurrió un error interno al procesar la solicitud. Contacte a soporte.');
            window.history.back();
        </script>";
    }
}

$conn->close();
