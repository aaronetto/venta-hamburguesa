<?php
session_start();
header('Content-Type: application/json');

require_once 'config.php';

// Verificar si el cliente está logueado
if (!isset($_SESSION['cliente_id'])) {
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para ver el carrito']);
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
        // No hay carrito activo
        echo json_encode([
            'success' => true,
            'carrito' => [],
            'total_productos' => 0,
            'total_precio' => 0
        ]);
        exit();
    }
    
    $carrito = $result->fetch_assoc();
    $carrito_id = $carrito['ID_CARRITO'];
    
    // Obtener productos del carrito con información completa
    $query = "SELECT 
                cd.ID_CARRITO_DETALLE,
                cd.CANTIDAD,
                p.ID_PRODUCTO,
                p.NOMBRE,
                p.PRECIO,
                p.IMAGEN_RUTA,
                p.STOCK,
                (cd.CANTIDAD * p.PRECIO) as SUBTOTAL
              FROM carrito_detalle cd
              INNER JOIN producto p ON cd.ID_PRODUCTO = p.ID_PRODUCTO
              WHERE cd.ID_CARRITO = ? AND p.ACTIVO = 1
              ORDER BY p.NOMBRE";
    
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("i", $carrito_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $productos = [];
    $total_precio = 0;
    $total_productos = 0;
    
    while ($row = $result->fetch_assoc()) {
        $productos[] = [
            'id_detalle' => $row['ID_CARRITO_DETALLE'],
            'id_producto' => $row['ID_PRODUCTO'],
            'nombre' => $row['NOMBRE'],
            'precio' => $row['PRECIO'],
            'cantidad' => $row['CANTIDAD'],
            'subtotal' => $row['SUBTOTAL'],
            'imagen' => $row['IMAGEN_RUTA'],
            'stock' => $row['STOCK']
        ];
        
        $total_precio += $row['SUBTOTAL'];
        $total_productos += $row['CANTIDAD'];
    }
    
    echo json_encode([
        'success' => true,
        'carrito' => $productos,
        'total_productos' => $total_productos,
        'total_precio' => $total_precio
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al obtener el carrito: ' . $e->getMessage()]);
} finally {
    $conexion->close();
}
?>
