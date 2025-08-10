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

// Verificar si el usuario existe
$stmt = $conexion->prepare("SELECT NOMBRES, APELLIDOS FROM usuario WHERE ID_USUARIO = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php?error=usuario_no_existe");
    exit();
}

// Activar el usuario
$stmt = $conexion->prepare("UPDATE usuario SET ACTIVO = 1 WHERE ID_USUARIO = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: index.php?success=activado");
} else {
    header("Location: index.php?error=error_activacion");
}

$stmt->close();
$conexion->close();
?>
