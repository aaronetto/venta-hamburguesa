<?php
session_start();
require_once '../../config.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../login_registro.php");
    exit();
}

// Verificar si se recibió el ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php?error=id_requerido");
    exit();
}

$conexion = getConnection();
$id = (int)$_GET['id'];

// Verificar si el proveedor existe
$stmt = $conexion->prepare("SELECT NOMBRE FROM proveedor WHERE ID_PROVEEDOR = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php?error=proveedor_no_existe");
    exit();
}

$proveedor = $result->fetch_assoc();
$nombre_proveedor = $proveedor['NOMBRE'];
$stmt->close();

// Verificar si hay productos activos asociados a este proveedor
$stmt = $conexion->prepare("SELECT COUNT(*) as total FROM producto WHERE ID_PROVEEDOR = ? AND ACTIVO = 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$productos_activos = $result->fetch_assoc()['total'];
$stmt->close();

if ($productos_activos > 0) {
    header("Location: index.php?error=proveedor_con_productos&productos=" . $productos_activos);
    exit();
}

// Eliminar el proveedor
$stmt = $conexion->prepare("DELETE FROM proveedor WHERE ID_PROVEEDOR = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: index.php?eliminado=1");
} else {
    header("Location: index.php?error=error_eliminacion");
}

$stmt->close();
$conexion->close();
?>
