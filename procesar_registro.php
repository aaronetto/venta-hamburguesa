<?php
session_start(); // Inicia sesión

require_once 'config.php';
$conexion = getConnection();

if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}

// Verifica si todos los datos llegaron del formulario
if (isset($_POST['NOMB_USUARIO']) && isset($_POST['CORREO']) && isset($_POST['CLAVE'])) {
    $NOMB_USUARIO = $_POST['NOMB_USUARIO'];
    $CORREO = $_POST['CORREO'];
    $CLAVE = password_hash($_POST['CLAVE'], PASSWORD_DEFAULT); // Se cifra la clave

    // NO insertes ID_USUARIO si es AUTO_INCREMENT
    $sql = "INSERT INTO usuario (NOMB_USUARIO, CORREO, CLAVE)
            VALUES ('$NOMB_USUARIO', '$CORREO','$CLAVE')";

    if ($conexion->query($sql) === TRUE) {
        $_SESSION['usuario'] = $NOMB_USUARIO; // Guardamos el nombre del usuario en sesión
        header("Location: plataforma.php"); // Redirige al usuario a la plataforma
        exit();
    } else {
        echo "Error: " . $conexion->error;
    }
} else {
    echo "Faltan datos del formulario.";
}

$conexion->close();
?>