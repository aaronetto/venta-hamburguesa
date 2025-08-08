<?php
session_start();

// Conexión
$conexion = new mysqli("localhost", "root", "", "ventas_hamburguesa");

$producto_id = $_POST['producto'] ?? null;
$cantidad = $_POST['cantidad'] ?? 1;

// Obtener nombre y precio del producto
$query = "SELECT * FROM producto WHERE ID_PRODUCTO = $producto_id";
$result = $conexion->query($query);
$producto = $result->fetch_assoc();

$nombre_producto = $producto['NOMB_PRODUCTO'];
$precio = $producto['PRECIO'];
$total = $precio * $cantidad;

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pedido Confirmado</title>
</head>
<body>
    <h2>✅ Pedido confirmado</h2>
    <p><strong>Producto:</strong> <?= $nombre_producto ?></p>
    <p><strong>Cantidad:</strong> <?= $cantidad ?></p>
    <p><strong>Total:</strong> S/ <?= $total ?></p>

    <br>
    <a href="plataforma_pedido.php">← Hacer otro pedido</a>
</body>
</html>
