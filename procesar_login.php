<?php
session_start();

require_once 'config.php';
$conexion = getConnection();

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$correo = $_POST['CORREO'] ?? '';
$clave = $_POST['CLAVE'] ?? '';

// Buscar al usuario por su correo
$sql = "SELECT * FROM usuario WHERE CORREO = '$correo'";
$resultado = $conexion->query($sql);

if ($resultado->num_rows == 1) {
    $usuario = $resultado->fetch_assoc();

    // Validar contraseña cifrada
    if (password_verify($clave, $usuario['CLAVE'])) {
        $_SESSION['usuario'] = $usuario['NOMB_USUARIO']; // Se guarda el nombre

        header("Location: plataforma.php");
        exit();
    } else {
        echo "❌ Contraseña incorrecta.";
    }
} else {
    echo "❌ Usuario no encontrado.";
}

$conexion->close();
?>