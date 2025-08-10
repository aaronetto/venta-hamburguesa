<?php
session_start();
require_once '../../config.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../login_registro.php");
    exit();
}

// Verificar si se recibió el ID
if (!isset($_POST['id']) || empty($_POST['id'])) {
    header("Location: index.php?error=id_requerido");
    exit();
}

$conexion = getConnection();
$id = (int)$_POST['id'];

// Verificar si el producto existe
$stmt = $conexion->prepare("SELECT NOMBRE FROM producto WHERE ID_PRODUCTO = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php?error=producto_no_existe");
    exit();
}

// Activar el producto
$stmt = $conexion->prepare("UPDATE producto SET ACTIVO = 1, FECHA_ACTUALIZACION = NOW() WHERE ID_PRODUCTO = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: index.php?success=activado");
} else {
    header("Location: index.php?error=error_activacion");
}

$stmt->close();
$conexion->close();
?>
