<?php
session_start();

require_once 'config.php';
$conexion = getConnection();

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$correo = trim($_POST['CORREO'] ?? '');
$clave = $_POST['CLAVE'] ?? '';

// Validaciones básicas
if (empty($correo) || empty($clave)) {
    header("Location: login_registro.php?error=campos_vacios");
    exit();
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    header("Location: login_registro.php?error=correo_invalido");
    exit();
}

// Buscar al usuario por su correo usando prepared statement
$stmt = $conexion->prepare("SELECT ID_USUARIO, NOMBRES, APELLIDOS, CORREO, CLAVE, ROL, ACTIVO FROM usuario WHERE CORREO = ?");
$stmt->bind_param("s", $correo);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows == 1) {
    $usuario = $resultado->fetch_assoc();

    // Verificar si el usuario está activo
    if ($usuario['ACTIVO'] != 1) {
        header("Location: login_registro.php?error=usuario_inactivo");
        exit();
    }

    // Validar contraseña cifrada
    if (password_verify($clave, $usuario['CLAVE'])) {
        // Guardar información del usuario en sesión
        $_SESSION['usuario'] = $usuario['NOMBRES'] . ' ' . $usuario['APELLIDOS'];
        $_SESSION['usuario_id'] = $usuario['ID_USUARIO'];
        $_SESSION['rol'] = $usuario['ROL'];
        $_SESSION['usuario_correo'] = $usuario['CORREO'];

        header("Location: plataforma.php");
        exit();
    } else {
        header("Location: login_registro.php?error=clave_incorrecta");
        exit();
    }
} else {
    header("Location: login_registro.php?error=usuario_no_existe");
    exit();
}

$stmt->close();
$conexion->close();
?>
