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

// Verificar si la categoría existe y está inactiva
$stmt = $conexion->prepare("SELECT NOMBRE FROM categoria WHERE ID_CATEGORIA = ? AND ACTIVO = 0");
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

// Activar la categoría
$stmt = $conexion->prepare("UPDATE categoria SET ACTIVO = 1, FECHA_ACTUALIZACION = NOW() WHERE ID_CATEGORIA = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: index.php?activado=1");
} else {
    header("Location: index.php?error=error_activacion");
}

$stmt->close();
$conexion->close();
?>
