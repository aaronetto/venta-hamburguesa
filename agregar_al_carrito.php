<?php
session_start();
header('Content-Type: application/json');

require_once 'config.php';

// Verificar si el cliente está logueado
if (!isset($_SESSION['cliente_id'])) {
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para agregar productos al carrito']);
    exit();
}

// Verificar si se recibieron los datos necesarios
if (!isset($_POST['producto_id']) || !isset($_POST['cantidad'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit();
}

$producto_id = (int)$_POST['producto_id'];
$cantidad = (int)$_POST['cantidad'];
$cliente_id = $_SESSION['cliente_id'];

// Validaciones
if ($producto_id <= 0 || $cantidad <= 0) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit();
}

$conexion = getConnection();

try {
    // Verificar que el producto existe y está activo
    $stmt = $conexion->prepare("SELECT ID_PRODUCTO, NOMBRE, PRECIO, STOCK FROM producto WHERE ID_PRODUCTO = ? AND ACTIVO = 1");
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado o no disponible']);
        exit();
    }
    
    $producto = $result->fetch_assoc();
    
    // Verificar stock
    if ($producto['STOCK'] < $cantidad) {
        echo json_encode(['success' => false, 'message' => 'Stock insuficiente. Disponible: ' . $producto['STOCK']]);
        exit();
    }
    
    // Obtener o crear carrito activo
    $carrito_id = null;
    
    // Buscar carrito activo existente
    $stmt = $conexion->prepare("SELECT ID_CARRITO FROM carrito WHERE ID_CLIENTE = ? AND ESTADO = 'ACTIVO'");
    $stmt->bind_param("i", $cliente_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $carrito = $result->fetch_assoc();
        $carrito_id = $carrito['ID_CARRITO'];
    } else {
        // Crear nuevo carrito
        $stmt = $conexion->prepare("INSERT INTO carrito (ESTADO, ID_CLIENTE) VALUES ('ACTIVO', ?)");
        $stmt->bind_param("i", $cliente_id);
        $stmt->execute();
        $carrito_id = $conexion->insert_id;
        
        // Actualizar la sesión con el nuevo carrito
        $_SESSION['carrito_id'] = $carrito_id;
    }
    
    // Verificar si el producto ya está en el carrito
    $stmt = $conexion->prepare("SELECT ID_CARRITO_DETALLE, CANTIDAD FROM carrito_detalle WHERE ID_CARRITO = ? AND ID_PRODUCTO = ?");
    $stmt->bind_param("ii", $carrito_id, $producto_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Actualizar cantidad existente
        $detalle = $result->fetch_assoc();
        $nueva_cantidad = $detalle['CANTIDAD'] + $cantidad;
        
        // Verificar stock nuevamente con la cantidad total
        if ($producto['STOCK'] < $nueva_cantidad) {
            echo json_encode(['success' => false, 'message' => 'Stock insuficiente para la cantidad solicitada']);
            exit();
        }
        
        $stmt = $conexion->prepare("UPDATE carrito_detalle SET CANTIDAD = ? WHERE ID_CARRITO_DETALLE = ?");
        $stmt->bind_param("ii", $nueva_cantidad, $detalle['ID_CARRITO_DETALLE']);
        $stmt->execute();
    } else {
        // Agregar nuevo producto al carrito
        $stmt = $conexion->prepare("INSERT INTO carrito_detalle (ID_CARRITO, ID_PRODUCTO, CANTIDAD) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $carrito_id, $producto_id, $cantidad);
        $stmt->execute();
    }
    
    // Obtener el total de productos en el carrito
    $stmt = $conexion->prepare("SELECT SUM(CANTIDAD) as total FROM carrito_detalle WHERE ID_CARRITO = ?");
    $stmt->bind_param("i", $carrito_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $total = $result->fetch_assoc()['total'];
    
    echo json_encode([
        'success' => true, 
        'message' => 'Producto agregado al carrito',
        'producto' => $producto['NOMBRE'],
        'cantidad' => $cantidad,
        'total_carrito' => $total
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al agregar al carrito: ' . $e->getMessage()]);
} finally {
    $conexion->close();
}
?>
