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
    header("Location: usuarios.php?error=id_requerido");
    exit();
}

$conexion = getConnection();
$id = (int)$_POST['id'];

// Verificar si el usuario existe
$stmt = $conexion->prepare("SELECT NOMB_USUARIO FROM usuario WHERE ID_USUARIO = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: usuarios.php?error=usuario_no_existe");
    exit();
}

$usuario = $result->fetch_assoc();
$nombre_usuario = $usuario['NOMB_USUARIO'];

// Verificar si hay pedidos asociados a este usuario
$stmt = $conexion->prepare("SELECT COUNT(*) as total FROM pedido WHERE ID_USUARIO = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$pedidos_asociados = $result->fetch_assoc()['total'];

if ($pedidos_asociados > 0) {
    header("Location: usuarios.php?error=usuario_con_pedidos");
    exit();
}

// Eliminar el usuario
$stmt = $conexion->prepare("DELETE FROM usuario WHERE ID_USUARIO = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: usuarios.php?success=eliminado");
} else {
    header("Location: usuarios.php?error=error_eliminacion");
}

$stmt->close();
$conexion->close();
?>
