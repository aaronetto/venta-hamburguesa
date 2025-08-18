<?php
session_start();
header('Content-Type: application/json');

require_once 'config.php';

// Verificar si el cliente está logueado
if (!isset($_SESSION['cliente_id'])) {
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para vaciar el carrito']);
    exit();
}

$cliente_id = $_SESSION['cliente_id'];
$conexion = getConnection();

try {
    // Buscar carrito activo
    $stmt = $conexion->prepare("SELECT ID_CARRITO FROM carrito WHERE ID_CLIENTE = ? AND ESTADO = 'ACTIVO'");
    $stmt->bind_param("i", $cliente_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'No hay carrito activo']);
        exit();
    }
    
    $carrito = $result->fetch_assoc();
    $carrito_id = $carrito['ID_CARRITO'];
    
    // Eliminar todos los productos del carrito
    $stmt = $conexion->prepare("DELETE FROM carrito_detalle WHERE ID_CARRITO = ?");
    $stmt->bind_param("i", $carrito_id);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Carrito vaciado exitosamente'
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al vaciar el carrito: ' . $e->getMessage()]);
} finally {
    $conexion->close();
}
?>
