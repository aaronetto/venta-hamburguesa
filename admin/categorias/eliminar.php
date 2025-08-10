<?php
session_start();
require_once '../../config.php';
require_once '../../auth_functions.php';

// Verificar acceso al módulo
requerirAccesoModulo('categorias');

// Verificar si se recibió el ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php?error=id_requerido");
    exit();
}

$conexion = getConnection();
$id = (int)$_GET['id'];

// Verificar si la categoría existe y está activa
$stmt = $conexion->prepare("SELECT NOMBRE FROM categoria WHERE ID_CATEGORIA = ? AND ACTIVO = 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php?error=categoria_no_existe");
    exit();
}

$categoria = $result->fetch_assoc();
$nombre_categoria = $categoria['NOMBRE'];
$stmt->close();

// Verificar si hay productos activos asociados a esta categoría
$stmt = $conexion->prepare("SELECT COUNT(*) as total FROM producto WHERE ID_CATEGORIA = ? AND ACTIVO = 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$productos_activos = $result->fetch_assoc()['total'];
$stmt->close();

if ($productos_activos > 0) {
    header("Location: index.php?error=categoria_con_productos&productos=" . $productos_activos);
    exit();
}

// Realizar soft delete de la categoría
$stmt = $conexion->prepare("UPDATE categoria SET ACTIVO = 0, FECHA_ACTUALIZACION = NOW() WHERE ID_CATEGORIA = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: index.php?eliminado=1");
} else {
    header("Location: index.php?error=error_eliminacion");
}

$stmt->close();
$conexion->close();
?>
