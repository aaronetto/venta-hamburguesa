<?php
session_start();

// Limpiar todas las variables de sesión específicas
unset($_SESSION['usuario']);
unset($_SESSION['usuario_id']);
unset($_SESSION['rol']);
unset($_SESSION['usuario_correo']);

// Destruir la sesión completamente
session_destroy();

// Redirigir al login
header("Location: login_registro.php");
exit();
?>
