<?php
session_start();

// Destruir todas las variables de sesión del cliente
unset($_SESSION['cliente_id']);
unset($_SESSION['cliente_nombre']);
unset($_SESSION['cliente_correo']);
unset($_SESSION['cliente_telefono']);
unset($_SESSION['tipo_usuario']);
unset($_SESSION['carrito_id']);

// Destruir la sesión
session_destroy();

// Redirigir al inicio
header("Location: index.php");
exit();
?>
