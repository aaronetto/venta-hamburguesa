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
    header("Location: categorias.php?error=id_requerido");
    exit();
}

$conexion = getConnection();
$id = (int)$_POST['id'];

// Verificar si la categoría existe
$stmt = $conexion->prepare("SELECT NOMB_CATEGORIA FROM categoria WHERE ID_CATEGORIA = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: categorias.php?error=categoria_no_existe");
    exit();
}

$categoria = $result->fetch_assoc();
$nombre_categoria = $categoria['NOMB_CATEGORIA'];

// Verificar si hay productos asociados a esta categoría
$stmt = $conexion->prepare("SELECT COUNT(*) as total FROM producto WHERE ID_CATEGORIA = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$productos_asociados = $result->fetch_assoc()['total'];

if ($productos_asociados > 0) {
    header("Location: categorias.php?error=categoria_con_productos&productos=" . $productos_asociados);
    exit();
}

// Eliminar la categoría
$stmt = $conexion->prepare("DELETE FROM categoria WHERE ID_CATEGORIA = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: categorias.php?success=eliminada&nombre=" . urlencode($nombre_categoria));
} else {
    header("Location: categorias.php?error=error_eliminacion");
}

$stmt->close();
$conexion->close();
?>
