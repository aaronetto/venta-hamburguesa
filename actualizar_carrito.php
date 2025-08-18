<?php
session_start();
header('Content-Type: application/json');

require_once 'config.php';

// Verificar si el cliente está logueado
if (!isset($_SESSION['cliente_id'])) {
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para modificar el carrito']);
    exit();
}

// Verificar si se recibieron los datos necesarios
if (!isset($_POST['detalle_id']) || !isset($_POST['accion'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit();
}

$detalle_id = (int)$_POST['detalle_id'];
$accion = $_POST['accion']; // 'incrementar', 'decrementar', 'eliminar'
$cliente_id = $_SESSION['cliente_id'];

// Validaciones
if ($detalle_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de detalle inválido']);
    exit();
}

$conexion = getConnection();

try {
    // Verificar que el detalle pertenece al carrito del cliente
    $stmt = $conexion->prepare("
        SELECT cd.ID_CARRITO_DETALLE, cd.CANTIDAD, p.ID_PRODUCTO, p.NOMBRE, p.PRECIO, p.STOCK
        FROM carrito_detalle cd
        INNER JOIN carrito c ON cd.ID_CARRITO = c.ID_CARRITO
        INNER JOIN producto p ON cd.ID_PRODUCTO = p.ID_PRODUCTO
        WHERE cd.ID_CARRITO_DETALLE = ? AND c.ID_CLIENTE = ? AND c.ESTADO = 'ACTIVO'
    ");
    $stmt->bind_param("ii", $detalle_id, $cliente_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado en el carrito']);
        exit();
    }
    
    $detalle = $result->fetch_assoc();
    $cantidad_actual = $detalle['CANTIDAD'];
    $stock_disponible = $detalle['STOCK'];
    
    switch ($accion) {
        case 'incrementar':
            if ($cantidad_actual >= $stock_disponible) {
                echo json_encode(['success' => false, 'message' => 'No hay más stock disponible']);
                exit();
            }
            $nueva_cantidad = $cantidad_actual + 1;
            break;
            
        case 'decrementar':
            if ($cantidad_actual <= 1) {
                // Si la cantidad es 1, eliminar el producto
                $stmt = $conexion->prepare("DELETE FROM carrito_detalle WHERE ID_CARRITO_DETALLE = ?");
                $stmt->bind_param("i", $detalle_id);
                $stmt->execute();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Producto eliminado del carrito',
                    'eliminado' => true
                ]);
                exit();
            }
            $nueva_cantidad = $cantidad_actual - 1;
            break;
            
        case 'eliminar':
            $stmt = $conexion->prepare("DELETE FROM carrito_detalle WHERE ID_CARRITO_DETALLE = ?");
            $stmt->bind_param("i", $detalle_id);
            $stmt->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'Producto eliminado del carrito',
                'eliminado' => true
            ]);
            exit();
            
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            exit();
    }
    
    // Actualizar cantidad
    $stmt = $conexion->prepare("UPDATE carrito_detalle SET CANTIDAD = ? WHERE ID_CARRITO_DETALLE = ?");
    $stmt->bind_param("ii", $nueva_cantidad, $detalle_id);
    $stmt->execute();
    
    // Calcular nuevo subtotal
    $nuevo_subtotal = $nueva_cantidad * $detalle['PRECIO'];
    
    echo json_encode([
        'success' => true,
        'message' => 'Carrito actualizado',
        'nueva_cantidad' => $nueva_cantidad,
        'nuevo_subtotal' => $nuevo_subtotal,
        'eliminado' => false
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar el carrito: ' . $e->getMessage()]);
} finally {
    $conexion->close();
}
?>
