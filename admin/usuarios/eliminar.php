<?php
session_start();
require_once '../../config.php';
require_once '../../auth_functions.php';

// Verificar acceso al módulo
requerirAccesoModulo('usuarios');

// Verificar si se recibió el ID
if (!isset($_POST['id']) || empty($_POST['id'])) {
    header("Location: index.php?error=id_requerido");
    exit();
}

$conexion = getConnection();
$id = (int)$_POST['id'];

// Verificar si el usuario existe y está activo
$stmt = $conexion->prepare("SELECT NOMBRES, APELLIDOS, CORREO FROM usuario WHERE ID_USUARIO = ? AND ACTIVO = 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php?error=usuario_no_existe");
    exit();
}

$usuario = $result->fetch_assoc();
$nombre_completo = $usuario['NOMBRES'] . ' ' . $usuario['APELLIDOS'];

// Verificar si hay pedidos asociados a este usuario
$stmt = $conexion->prepare("SELECT COUNT(*) as total FROM pedido WHERE ID_USUARIO = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$pedidos_asociados = $result->fetch_assoc()['total'];

if ($pedidos_asociados > 0) {
    header("Location: index.php?error=usuario_con_pedidos");
    exit();
}

// Realizar soft delete (marcar como inactivo)
$stmt = $conexion->prepare("UPDATE usuario SET ACTIVO = 0 WHERE ID_USUARIO = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: index.php?success=eliminado");
} else {
    header("Location: index.php?error=error_eliminacion");
}

$stmt->close();
$conexion->close();
?>
