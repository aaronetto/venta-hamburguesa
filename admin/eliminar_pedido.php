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
    header("Location: pedidos.php?error=id_requerido");
    exit();
}

$conexion = getConnection();
$id = (int)$_POST['id'];

// Verificar si el pedido existe
$stmt = $conexion->prepare("SELECT ID_PEDIDO FROM pedido WHERE ID_PEDIDO = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: pedidos.php?error=pedido_no_existe");
    exit();
}

// Iniciar transacción
$conexion->begin_transaction();

try {
    // Eliminar detalles del pedido primero
    $stmt = $conexion->prepare("DELETE FROM detalle_pedido WHERE ID_PEDIDO = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    // Eliminar el pedido
    $stmt = $conexion->prepare("DELETE FROM pedido WHERE ID_PEDIDO = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    // Confirmar transacción
    $conexion->commit();
    
    header("Location: pedidos.php?success=eliminado");
} catch (Exception $e) {
    // Revertir transacción en caso de error
    $conexion->rollback();
    header("Location: pedidos.php?error=error_eliminacion");
}

$stmt->close();
$conexion->close();
?>
