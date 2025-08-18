<?php
session_start();
require_once 'config.php';

// Verificar si el cliente está logueado
if (!isset($_SESSION['cliente_id'])) {
    header("Location: cuenta_cliente.php");
    exit();
}

// Verificar si se recibieron los datos del formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: checkout.php");
    exit();
}

$cliente_id = $_SESSION['cliente_id'];
$conexion = getConnection();

try {
    // Iniciar transacción
    $conexion->begin_transaction();
    
    // Buscar carrito activo
    $stmt = $conexion->prepare("SELECT ID_CARRITO FROM carrito WHERE ID_CLIENTE = ? AND ESTADO = 'ACTIVO'");
    $stmt->bind_param("i", $cliente_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("No hay carrito activo");
    }
    
    $carrito = $result->fetch_assoc();
    $carrito_id = $carrito['ID_CARRITO'];
    
    // Obtener productos del carrito
    $stmt = $conexion->prepare("SELECT 
                                cd.ID_PRODUCTO,
                                cd.CANTIDAD,
                                p.NOMBRE,
                                p.PRECIO,
                                p.STOCK,
                                (cd.CANTIDAD * p.PRECIO) as SUBTOTAL
                              FROM carrito_detalle cd
                              INNER JOIN producto p ON cd.ID_PRODUCTO = p.ID_PRODUCTO
                              WHERE cd.ID_CARRITO = ? AND p.ACTIVO = 1");
    $stmt->bind_param("i", $carrito_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $productos = [];
    $total_precio = 0;
    
    while ($row = $result->fetch_assoc()) {
        $productos[] = $row;
        $total_precio += $row['SUBTOTAL'];
        
        // Verificar stock
        if ($row['STOCK'] < $row['CANTIDAD']) {
            throw new Exception("Stock insuficiente para: " . $row['NOMBRE']);
        }
    }
    
    if (empty($productos)) {
        throw new Exception("El carrito está vacío");
    }
    
         // Validar datos del formulario
     $metodo_pago = $_POST['METODO_PAGO'] ?? 'EFECTIVO';
     $observaciones = trim($_POST['OBSERVACIONES'] ?? '');
     
     // Validaciones básicas
     if (!in_array($metodo_pago, ['EFECTIVO', 'TARJETA', 'YAPE'])) {
         throw new Exception("Método de pago inválido");
     }
    
         // Crear el pedido
     $stmt = $conexion->prepare("INSERT INTO pedido (
         ID_CLIENTE, 
         FECHA_PEDIDO, 
         ESTADO, 
         TOTAL, 
         METODO_PAGO, 
         OBSERVACIONES,
         FECHA_ENTREGA,
         ID_USUARIO
     ) VALUES (?, NOW(), 'PENDIENTE', ?, ?, ?, ?, ?)");
     
     $total_con_igv = $total_precio * 1.18;
     $fecha_entrega = date('Y-m-d H:i:s', strtotime('+1 hour')); // Entrega en 1 hora
     $id_usuario = 1; // Usuario por defecto (puede ser el administrador)
     
     $stmt->bind_param("idsssi", 
         $cliente_id, 
         $total_con_igv, 
         $metodo_pago, 
         $observaciones,
         $fecha_entrega,
         $id_usuario
     );
    $stmt->execute();
    
    $pedido_id = $conexion->insert_id;
    
    // Crear detalles del pedido
    $stmt = $conexion->prepare("INSERT INTO pedido_detalle (
        ID_PEDIDO, 
        ID_PRODUCTO, 
        CANTIDAD, 
        PRECIO_UNITARIO, 
        SUBTOTAL
    ) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($productos as $producto) {
        $stmt->bind_param("iiidd", 
            $pedido_id, 
            $producto['ID_PRODUCTO'], 
            $producto['CANTIDAD'], 
            $producto['PRECIO'], 
            $producto['SUBTOTAL']
        );
        $stmt->execute();
        
        // Actualizar stock
        $nuevo_stock = $producto['STOCK'] - $producto['CANTIDAD'];
        $stmt_stock = $conexion->prepare("UPDATE producto SET STOCK = ? WHERE ID_PRODUCTO = ?");
        $stmt_stock->bind_param("ii", $nuevo_stock, $producto['ID_PRODUCTO']);
        $stmt_stock->execute();
    }
    
    // Cambiar estado del carrito a COMPRADO
    $stmt = $conexion->prepare("UPDATE carrito SET ESTADO = 'COMPRADO' WHERE ID_CARRITO = ?");
    $stmt->bind_param("i", $carrito_id);
    $stmt->execute();
    
    // Confirmar transacción
    $conexion->commit();
    
    // Limpiar carrito de la sesión
    unset($_SESSION['carrito_id']);
    
    // Redirigir a la página de confirmación
    header("Location: confirmacion_pedido.php?pedido_id=" . $pedido_id);
    exit();
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    $conexion->rollback();
    
    // Redirigir con error
    header("Location: checkout.php?error=" . urlencode($e->getMessage()));
    exit();
} finally {
    $conexion->close();
}
?>
