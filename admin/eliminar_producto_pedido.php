<?php
session_start();
require_once '../config.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    header("Location: ../login_registro.php");
    exit();
}

// Verificar si se recibieron los parámetros
if (!isset($_POST['id_detalle']) || !isset($_POST['id_pedido'])) {
    header("Location: pedidos.php?error=parametros_requeridos");
    exit();
}

$conexion = getConnection();
$id_detalle = (int)$_POST['id_detalle'];
$id_pedido = (int)$_POST['id_pedido'];

// Verificar si el detalle existe
$stmt = $conexion->prepare("SELECT ID_DETALLE FROM detalle_pedido WHERE ID_DETALLE = ? AND ID_PEDIDO = ?");
$stmt->bind_param("ii", $id_detalle, $id_pedido);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: editar_pedido.php?id=" . $id_pedido . "&error=detalle_no_existe");
    exit();
}

// Eliminar el detalle
$stmt = $conexion->prepare("DELETE FROM detalle_pedido WHERE ID_DETALLE = ?");
$stmt->bind_param("i", $id_detalle);

if ($stmt->execute()) {
    // Actualizar total del pedido
    actualizarTotalPedido($conexion, $id_pedido);
    header("Location: editar_pedido.php?id=" . $id_pedido . "&success=producto_eliminado");
} else {
    header("Location: editar_pedido.php?id=" . $id_pedido . "&error=error_eliminacion");
}

$stmt->close();
$conexion->close();

// Función para actualizar el total del pedido
function actualizarTotalPedido($conexion, $pedido_id) {
    $stmt = $conexion->prepare("SELECT SUM(SUBTOTAL) as total FROM detalle_pedido WHERE ID_PEDIDO = ?");
    $stmt->bind_param("i", $pedido_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $total = $result->fetch_assoc()['total'] ?? 0;
    
    $stmt = $conexion->prepare("UPDATE pedido SET TOTAL = ? WHERE ID_PEDIDO = ?");
    $stmt->bind_param("di", $total, $pedido_id);
    $stmt->execute();
    $stmt->close();
}
?>
