<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login_registro.php");
    exit();
}

$nombre = $_SESSION['usuario'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Plataforma</title>
</head>
<body>
    <h1>Bienvenido, <?php echo htmlspecialchars($nombre); ?> 👋</h1>

    <ul>
        <li><a href="pedido.php">🛒 Hacer pedido</a></li>
        <li><a href="logout.php">🔓 Cerrar sesión</a></li>
    </ul>
</body>
</html>