<?php
session_start();
require_once '../config.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    header("Location: ../login_registro.php");
    exit();
}

// Verificar si se recibió el ID
if (!isset($_POST['id']) || empty($_POST['id'])) {
    header("Location: productos.php?error=id_requerido");
    exit();
}

$conexion = getConnection();
$id = (int)$_POST['id'];

// Verificar si el producto existe
$stmt = $conexion->prepare("SELECT NOMB_PRODUCTO FROM producto WHERE ID_PRODUCTO = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: productos.php?error=producto_no_existe");
    exit();
}

$producto = $result->fetch_assoc();
$nombre_producto = $producto['NOMB_PRODUCTO'];

// Verificar si hay pedidos asociados a este producto
$stmt = $conexion->prepare("SELECT COUNT(*) as total FROM detalle_pedido WHERE ID_PRODUCTO = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$pedidos_asociados = $result->fetch_assoc()['total'];

if ($pedidos_asociados > 0) {
    header("Location: productos.php?error=producto_con_pedidos");
    exit();
}

// Eliminar el producto
$stmt = $conexion->prepare("DELETE FROM producto WHERE ID_PRODUCTO = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: productos.php?success=eliminado");
} else {
    header("Location: productos.php?error=error_eliminacion");
}

$stmt->close();
$conexion->close();
?>
