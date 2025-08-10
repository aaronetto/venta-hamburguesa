<?php
session_start();
require_once '../../config.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../login_registro.php');
    exit();
}

$conexion = getConnection();

$id_pedido = isset($_GET['id']) ? intval($_GET['id']) : 0;
$mensaje = '';
$error = '';

if ($id_pedido <= 0) {
    header('Location: index.php');
    exit();
}

// Obtener información del pedido
$query_pedido = "SELECT 
                    p.*,
                    c.NOMBRE as CLIENTE_NOMBRE,
                    c.APELLIDOS as CLIENTE_APELLIDOS,
                    c.CORREO as CLIENTE_CORREO,
                    c.TELEFONO as CLIENTE_TELEFONO,
                    u.NOMBRES as USUARIO_NOMBRES,
                    u.APELLIDOS as USUARIO_APELLIDOS
                 FROM pedido p
                 INNER JOIN cliente c ON p.ID_CLIENTE = c.ID_CLIENTE
                 INNER JOIN usuario u ON p.ID_USUARIO = u.ID_USUARIO
                 WHERE p.ID_PEDIDO = ?";

$stmt_pedido = $conexion->prepare($query_pedido);
$stmt_pedido->bind_param("i", $id_pedido);
$stmt_pedido->execute();
$result_pedido = $stmt_pedido->get_result();

if ($result_pedido->num_rows == 0) {
    header('Location: index.php');
    exit();
}

$pedido = $result_pedido->fetch_assoc();

// Procesar acciones de productos
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'agregar_producto') {
        $id_producto = intval($_POST['id_producto']);
        $cantidad = intval($_POST['cantidad']);
        $observaciones = trim($_POST['observaciones'] ?? '');
        
        // Verificar stock
        $stmt_stock = $conexion->prepare("SELECT PRECIO, STOCK FROM producto WHERE ID_PRODUCTO = ? AND ACTIVO = 1");
        $stmt_stock->bind_param("i", $id_producto);
        $stmt_stock->execute();
        $result_stock = $stmt_stock->get_result();
        $producto_stock = $result_stock->fetch_assoc();
        
        if ($producto_stock && $producto_stock['STOCK'] >= $cantidad) {
            $precio_unitario = $producto_stock['PRECIO'];
            $subtotal = $cantidad * $precio_unitario;
            
            // Insertar detalle
            $stmt_detalle = $conexion->prepare("INSERT INTO pedido_detalle (CANTIDAD, ID_PEDIDO, ID_PRODUCTO, PRECIO_UNITARIO, SUBTOTAL, OBSERVACIONES) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_detalle->bind_param("iiidds", $cantidad, $id_pedido, $id_producto, $precio_unitario, $subtotal, $observaciones);
            
            if ($stmt_detalle->execute()) {
                // Actualizar stock
                $nuevo_stock = $producto_stock['STOCK'] - $cantidad;
                $stmt_update_stock = $conexion->prepare("UPDATE producto SET STOCK = ? WHERE ID_PRODUCTO = ?");
                $stmt_update_stock->bind_param("ii", $nuevo_stock, $id_producto);
                $stmt_update_stock->execute();
                
                // Actualizar total del pedido
                $stmt_update_total = $conexion->prepare("UPDATE pedido SET TOTAL = TOTAL + ? WHERE ID_PEDIDO = ?");
                $stmt_update_total->bind_param("di", $subtotal, $id_pedido);
                $stmt_update_total->execute();
                
                $mensaje = "✅ Producto agregado correctamente al pedido";
                
                // Recargar información del pedido
                $stmt_pedido->execute();
                $pedido = $stmt_pedido->get_result()->fetch_assoc();
            } else {
                $error = "❌ Error al agregar el producto";
            }
        } else {
            $error = "❌ Stock insuficiente para el producto seleccionado";
        }
    } elseif ($action == 'eliminar_producto') {
        $id_detalle = intval($_POST['id_detalle']);
        
        // Obtener información del detalle
        $stmt_detalle = $conexion->prepare("SELECT pd.CANTIDAD, pd.ID_PRODUCTO, pd.SUBTOTAL FROM pedido_detalle pd WHERE pd.ID_PEDIDO_DETALLE = ? AND pd.ID_PEDIDO = ?");
        $stmt_detalle->bind_param("ii", $id_detalle, $id_pedido);
        $stmt_detalle->execute();
        $result_detalle = $stmt_detalle->get_result();
        
        if ($result_detalle->num_rows > 0) {
            $detalle = $result_detalle->fetch_assoc();
            
            // Eliminar detalle
            $stmt_delete = $conexion->prepare("DELETE FROM pedido_detalle WHERE ID_PEDIDO_DETALLE = ?");
            $stmt_delete->bind_param("i", $id_detalle);
            
            if ($stmt_delete->execute()) {
                // Restaurar stock
                $stmt_restore_stock = $conexion->prepare("UPDATE producto SET STOCK = STOCK + ? WHERE ID_PRODUCTO = ?");
                $stmt_restore_stock->bind_param("ii", $detalle['CANTIDAD'], $detalle['ID_PRODUCTO']);
                $stmt_restore_stock->execute();
                
                // Actualizar total del pedido
                $stmt_update_total = $conexion->prepare("UPDATE pedido SET TOTAL = TOTAL - ? WHERE ID_PEDIDO = ?");
                $stmt_update_total->bind_param("di", $detalle['SUBTOTAL'], $id_pedido);
                $stmt_update_total->execute();
                
                $mensaje = "✅ Producto eliminado correctamente del pedido";
                
                // Recargar información del pedido
                $stmt_pedido->execute();
                $pedido = $stmt_pedido->get_result()->fetch_assoc();
            } else {
                $error = "❌ Error al eliminar el producto";
            }
        } else {
            $error = "❌ Producto no encontrado en el pedido";
        }
    } elseif ($action == 'editar_producto') {
        $id_detalle = intval($_POST['id_detalle']);
        $nueva_cantidad = intval($_POST['nueva_cantidad']);
        $nuevas_observaciones = trim($_POST['nuevas_observaciones'] ?? '');
        
        // Obtener información actual del detalle
        $stmt_detalle = $conexion->prepare("SELECT pd.CANTIDAD, pd.ID_PRODUCTO, pd.PRECIO_UNITARIO FROM pedido_detalle pd WHERE pd.ID_PEDIDO_DETALLE = ? AND pd.ID_PEDIDO = ?");
        $stmt_detalle->bind_param("ii", $id_detalle, $id_pedido);
        $stmt_detalle->execute();
        $result_detalle = $stmt_detalle->get_result();
        
        if ($result_detalle->num_rows > 0) {
            $detalle_actual = $result_detalle->fetch_assoc();
            $diferencia_cantidad = $nueva_cantidad - $detalle_actual['CANTIDAD'];
            
            // Verificar stock disponible
            $stmt_stock = $conexion->prepare("SELECT STOCK FROM producto WHERE ID_PRODUCTO = ?");
            $stmt_stock->bind_param("i", $detalle_actual['ID_PRODUCTO']);
            $stmt_stock->execute();
            $result_stock = $stmt_stock->get_result();
            $producto_stock = $result_stock->fetch_assoc();
            
            if ($producto_stock && $producto_stock['STOCK'] >= $diferencia_cantidad) {
                $nuevo_subtotal = $nueva_cantidad * $detalle_actual['PRECIO_UNITARIO'];
                $diferencia_subtotal = $nuevo_subtotal - ($detalle_actual['CANTIDAD'] * $detalle_actual['PRECIO_UNITARIO']);
                
                // Actualizar detalle
                $stmt_update = $conexion->prepare("UPDATE pedido_detalle SET CANTIDAD = ?, SUBTOTAL = ?, OBSERVACIONES = ? WHERE ID_PEDIDO_DETALLE = ?");
                $stmt_update->bind_param("idsi", $nueva_cantidad, $nuevo_subtotal, $nuevas_observaciones, $id_detalle);
                
                if ($stmt_update->execute()) {
                    // Actualizar stock
                    $stmt_update_stock = $conexion->prepare("UPDATE producto SET STOCK = STOCK - ? WHERE ID_PRODUCTO = ?");
                    $stmt_update_stock->bind_param("ii", $diferencia_cantidad, $detalle_actual['ID_PRODUCTO']);
                    $stmt_update_stock->execute();
                    
                    // Actualizar total del pedido
                    $stmt_update_total = $conexion->prepare("UPDATE pedido SET TOTAL = TOTAL + ? WHERE ID_PEDIDO = ?");
                    $stmt_update_total->bind_param("di", $diferencia_subtotal, $id_pedido);
                    $stmt_update_total->execute();
                    
                    $mensaje = "✅ Producto actualizado correctamente";
                    
                    // Recargar información del pedido
                    $stmt_pedido->execute();
                    $pedido = $stmt_pedido->get_result()->fetch_assoc();
                } else {
                    $error = "❌ Error al actualizar el producto";
                }
            } else {
                $error = "❌ Stock insuficiente para la nueva cantidad";
            }
        } else {
            $error = "❌ Producto no encontrado en el pedido";
        }
    }
}

// Obtener productos del pedido
$query_detalles = "SELECT 
                     pd.ID_PEDIDO_DETALLE,
                     pd.CANTIDAD,
                     pd.PRECIO_UNITARIO,
                     pd.SUBTOTAL,
                     pd.OBSERVACIONES,
                     p.CODIGO,
                     p.NOMBRE as PRODUCTO_NOMBRE,
                     c.NOMBRE as CATEGORIA_NOMBRE
                   FROM pedido_detalle pd
                   INNER JOIN producto p ON pd.ID_PRODUCTO = p.ID_PRODUCTO
                   INNER JOIN categoria c ON p.ID_CATEGORIA = c.ID_CATEGORIA
                   WHERE pd.ID_PEDIDO = ?
                   ORDER BY pd.ID_PEDIDO_DETALLE";

$stmt_detalles = $conexion->prepare($query_detalles);
$stmt_detalles->bind_param("i", $id_pedido);
$stmt_detalles->execute();
$result_detalles = $stmt_detalles->get_result();

// Obtener productos disponibles para agregar
$query_productos = "SELECT p.ID_PRODUCTO, p.CODIGO, p.NOMBRE, p.PRECIO, p.STOCK, c.NOMBRE as CATEGORIA 
                   FROM producto p 
                   INNER JOIN categoria c ON p.ID_CATEGORIA = c.ID_CATEGORIA 
                   WHERE p.ACTIVO = 1 AND c.ACTIVO = 1 
                   AND p.ID_PRODUCTO NOT IN (
                       SELECT pd.ID_PRODUCTO FROM pedido_detalle pd WHERE pd.ID_PEDIDO = ?
                   )
                   ORDER BY c.NOMBRE, p.NOMBRE";

$stmt_productos = $conexion->prepare($query_productos);
$stmt_productos->bind_param("i", $id_pedido);
$stmt_productos->execute();
$result_productos = $stmt_productos->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Pedido - Administrador</title>
    <link rel="stylesheet" href="../../style.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .admin-header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #545b62;
        }
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        .btn-warning:hover {
            background: #e0a800;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover {
            background: #218838;
        }
        .info-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 20px;
        }
        .info-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #ddd;
        }
        .info-body {
            padding: 20px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .info-item {
            display: flex;
            flex-direction: column;
        }
        .info-label {
            font-weight: bold;
            color: #495057;
            margin-bottom: 5px;
        }
        .info-value {
            color: #6c757d;
        }
        .estado-pendiente {
            background: #fff3cd;
            color: #856404;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9em;
            font-weight: bold;
        }
        .estado-listo {
            background: #d4edda;
            color: #155724;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9em;
            font-weight: bold;
        }
        .estado-cancelado {
            background: #f8d7da;
            color: #721c24;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9em;
            font-weight: bold;
        }
        .metodo-pago {
            background: #e9ecef;
            color: #495057;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9em;
            font-weight: bold;
        }
        .productos-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 20px;
        }
        .productos-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .productos-body {
            padding: 20px;
        }
        .producto-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            position: relative;
        }
        .producto-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .producto-info {
            flex: 1;
        }
        .producto-nombre {
            font-weight: bold;
            color: #495057;
        }
        .producto-codigo {
            color: #6c757d;
            font-size: 0.9em;
        }
        .producto-categoria {
            color: #6c757d;
            font-size: 0.9em;
        }
        .producto-precio {
            color: #28a745;
            font-weight: bold;
        }
        .producto-cantidad {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        .producto-cantidad input {
            width: 80px;
            text-align: center;
        }
        .producto-subtotal {
            font-weight: bold;
            color: #28a745;
            font-size: 1.1em;
        }
        .producto-observaciones {
            color: #6c757d;
            font-style: italic;
            margin-top: 10px;
        }
        .form-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 20px;
        }
        .form-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #ddd;
            cursor: pointer;
        }
        .form-body {
            padding: 20px;
            display: none;
        }
        .form-body.active {
            display: block;
        }
        .form-section {
            margin-bottom: 20px;
        }
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            margin-bottom: 5px;
            font-weight: bold;
            color: #495057;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 60px;
        }
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
        }
        .total-pedido {
            background: #28a745;
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            font-size: 1.2em;
            font-weight: bold;
            margin-top: 20px;
        }
        .mensaje {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: bold;
        }
        .mensaje.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .mensaje.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .productos-vacios {
            text-align: center;
            color: #6c757d;
            padding: 40px;
            font-style: italic;
        }
        .toggle-icon {
            transition: transform 0.3s ease;
        }
        .toggle-icon.rotated {
            transform: rotate(180deg);
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>👁️ Visualizar Pedido #<?php echo $id_pedido; ?></h1>
            <div>
                <a href="editar.php?id=<?php echo $id_pedido; ?>" class="btn btn-warning">✏️ Editar Pedido</a>
                <a href="index.php" class="btn btn-secondary">← Volver a Pedidos</a>
            </div>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="mensaje success">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="mensaje error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="info-container">
            <div class="info-header">
                <h3>📋 Información del Pedido</h3>
            </div>
            <div class="info-body">
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Cliente</span>
                        <span class="info-value"><?php echo htmlspecialchars($pedido['CLIENTE_NOMBRE'] . ' ' . $pedido['CLIENTE_APELLIDOS']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Teléfono</span>
                        <span class="info-value">📞 <?php echo htmlspecialchars($pedido['CLIENTE_TELEFONO']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Correo</span>
                        <span class="info-value">📧 <?php echo htmlspecialchars($pedido['CLIENTE_CORREO']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Fecha del Pedido</span>
                        <span class="info-value">📅 <?php echo date('d/m/Y', strtotime($pedido['FECHA_PEDIDO'])); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Fecha de Entrega</span>
                        <span class="info-value">🕒 <?php echo date('d/m/Y H:i', strtotime($pedido['FECHA_ENTREGA'])); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Método de Pago</span>
                        <span class="metodo-pago">
                            <?php 
                            switch($pedido['METODO_PAGO']) {
                                case 'EFECTIVO': echo '💵 Efectivo'; break;
                                case 'TARJETA': echo '💳 Tarjeta'; break;
                                case 'YAPE': echo '📱 Yape'; break;
                                default: echo $pedido['METODO_PAGO'];
                            }
                            ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Estado</span>
                        <?php 
                        $estado_class = '';
                        $estado_icon = '';
                        switch($pedido['ESTADO']) {
                            case 'PENDIENTE':
                                $estado_class = 'estado-pendiente';
                                $estado_icon = '⏳';
                                break;
                            case 'LISTO':
                                $estado_class = 'estado-listo';
                                $estado_icon = '✅';
                                break;
                            case 'CANCELADO':
                                $estado_class = 'estado-cancelado';
                                $estado_icon = '❌';
                                break;
                        }
                        ?>
                        <span class="<?php echo $estado_class; ?>">
                            <?php echo $estado_icon . ' ' . $pedido['ESTADO']; ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Usuario Responsable</span>
                        <span class="info-value">👤 <?php echo htmlspecialchars($pedido['USUARIO_NOMBRES'] . ' ' . $pedido['USUARIO_APELLIDOS']); ?></span>
                    </div>
                    <?php if (!empty($pedido['OBSERVACIONES'])): ?>
                        <div class="info-item" style="grid-column: 1 / -1;">
                            <span class="info-label">Observaciones</span>
                            <span class="info-value"><?php echo htmlspecialchars($pedido['OBSERVACIONES']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="productos-container">
            <div class="productos-header">
                <h3>🛒 Productos del Pedido</h3>
                <button class="btn btn-primary" onclick="toggleForm()">➕ Agregar Producto</button>
            </div>
            
            <div class="form-container">
                <div class="form-header" onclick="toggleForm()">
                    <h3>📝 Agregar Nuevo Producto</h3>
                    <span class="toggle-icon" id="toggle-icon">▼</span>
                </div>
                <div class="form-body" id="nuevo-producto-form">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="agregar_producto">
                        <div class="form-section">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="id_producto">Producto <span style="color: #dc3545;">*</span></label>
                                    <select id="id_producto" name="id_producto" required>
                                        <option value="">Seleccionar producto...</option>
                                        <?php while ($producto = $result_productos->fetch_assoc()): ?>
                                            <option value="<?php echo $producto['ID_PRODUCTO']; ?>" 
                                                    data-precio="<?php echo $producto['PRECIO']; ?>"
                                                    data-stock="<?php echo $producto['STOCK']; ?>">
                                                <?php echo htmlspecialchars($producto['CODIGO'] . ' - ' . $producto['NOMBRE'] . ' (' . $producto['CATEGORIA'] . ') - S/ ' . $producto['PRECIO'] . ' - Stock: ' . $producto['STOCK']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="cantidad">Cantidad <span style="color: #dc3545;">*</span></label>
                                    <input type="number" id="cantidad" name="cantidad" min="1" value="1" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="observaciones">Observaciones</label>
                                    <textarea id="observaciones" name="observaciones" placeholder="Observaciones específicas para este producto..."></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" onclick="toggleForm()">Cancelar</button>
                            <button type="submit" class="btn btn-success">✅ Agregar Producto</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="productos-body">
                <?php if ($result_detalles->num_rows > 0): ?>
                    <?php while ($detalle = $result_detalles->fetch_assoc()): ?>
                        <div class="producto-item">
                            <div class="producto-header">
                                <div class="producto-info">
                                    <div class="producto-nombre"><?php echo htmlspecialchars($detalle['PRODUCTO_NOMBRE']); ?></div>
                                    <div class="producto-codigo">Código: <?php echo htmlspecialchars($detalle['CODIGO']); ?></div>
                                    <div class="producto-categoria">Categoría: <?php echo htmlspecialchars($detalle['CATEGORIA_NOMBRE']); ?></div>
                                </div>
                                <div class="producto-precio">S/ <?php echo number_format($detalle['PRECIO_UNITARIO'], 2); ?></div>
                            </div>
                            <div class="producto-cantidad">
                                <label>Cantidad:</label>
                                <input type="number" min="1" value="<?php echo $detalle['CANTIDAD']; ?>" 
                                       onchange="editarProducto(<?php echo $detalle['ID_PEDIDO_DETALLE']; ?>, this.value)">
                                <span class="producto-subtotal">Subtotal: S/ <?php echo number_format($detalle['SUBTOTAL'], 2); ?></span>
                            </div>
                            <?php if (!empty($detalle['OBSERVACIONES'])): ?>
                                <div class="producto-observaciones">
                                    📝 <?php echo htmlspecialchars($detalle['OBSERVACIONES']); ?>
                                </div>
                            <?php endif; ?>
                            <div style="margin-top: 10px;">
                                <button class="btn btn-warning" onclick="editarProductoForm(<?php echo $detalle['ID_PEDIDO_DETALLE']; ?>, '<?php echo htmlspecialchars($detalle['OBSERVACIONES']); ?>')">
                                    ✏️ Editar
                                </button>
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="action" value="eliminar_producto">
                                    <input type="hidden" name="id_detalle" value="<?php echo $detalle['ID_PEDIDO_DETALLE']; ?>">
                                    <button type="submit" class="btn btn-danger" 
                                            onclick="return confirm('¿Estás seguro de que quieres eliminar este producto del pedido?')">
                                        🗑️ Eliminar
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="productos-vacios">
                        No hay productos en este pedido
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="total-pedido">
            Total del Pedido: S/ <?php echo number_format($pedido['TOTAL'], 2); ?>
        </div>
    </div>

    <script>
        function toggleForm() {
            const form = document.getElementById('nuevo-producto-form');
            const icon = document.getElementById('toggle-icon');
            
            if (form.classList.contains('active')) {
                form.classList.remove('active');
                icon.textContent = '▼';
                icon.classList.remove('rotated');
            } else {
                form.classList.add('active');
                icon.textContent = '▲';
                icon.classList.add('rotated');
            }
        }

        function editarProducto(idDetalle, nuevaCantidad) {
            if (confirm('¿Deseas actualizar la cantidad de este producto?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="editar_producto">
                    <input type="hidden" name="id_detalle" value="${idDetalle}">
                    <input type="hidden" name="nueva_cantidad" value="${nuevaCantidad}">
                    <input type="hidden" name="nuevas_observaciones" value="">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function editarProductoForm(idDetalle, observaciones) {
            const nuevasObservaciones = prompt('Ingresa las nuevas observaciones para este producto:', observaciones);
            if (nuevasObservaciones !== null) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="editar_producto">
                    <input type="hidden" name="id_detalle" value="${idDetalle}">
                    <input type="hidden" name="nueva_cantidad" value="">
                    <input type="hidden" name="nuevas_observaciones" value="${nuevasObservaciones}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
