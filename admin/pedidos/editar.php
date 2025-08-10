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
$error = '';
$success = '';

if ($id_pedido <= 0) {
    header('Location: index.php');
    exit();
}

// Obtener información del pedido
$query_pedido = "SELECT * FROM pedido WHERE ID_PEDIDO = ?";
$stmt_pedido = $conexion->prepare($query_pedido);
$stmt_pedido->bind_param("i", $id_pedido);
$stmt_pedido->execute();
$result_pedido = $stmt_pedido->get_result();

if ($result_pedido->num_rows == 0) {
    header('Location: index.php');
    exit();
}

$pedido = $result_pedido->fetch_assoc();

// Obtener clientes para el dropdown
$query_clientes = "SELECT ID_CLIENTE, NOMBRE, APELLIDOS, TELEFONO FROM cliente ORDER BY NOMBRE, APELLIDOS";
$result_clientes = $conexion->query($query_clientes);

// Obtener productos activos para el dropdown
$query_productos = "SELECT p.ID_PRODUCTO, p.CODIGO, p.NOMBRE, p.PRECIO, p.STOCK, c.NOMBRE as CATEGORIA 
                   FROM producto p 
                   INNER JOIN categoria c ON p.ID_CATEGORIA = c.ID_CATEGORIA 
                   WHERE p.ACTIVO = 1 AND c.ACTIVO = 1 
                   ORDER BY c.NOMBRE, p.NOMBRE";
$result_productos = $conexion->query($query_productos);

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_cliente = intval($_POST['id_cliente']);
    $metodo_pago = $_POST['metodo_pago'];
    $fecha_pedido = $_POST['fecha_pedido'];
    $fecha_entrega = $_POST['fecha_entrega'];
    $estado = $_POST['estado'];
    $observaciones = trim($_POST['observaciones']);
    
    // Validaciones
    if (empty($id_cliente)) {
        $error = "Debe seleccionar un cliente";
    } elseif (empty($fecha_pedido)) {
        $error = "Debe especificar la fecha del pedido";
    } elseif (empty($fecha_entrega)) {
        $error = "Debe especificar la fecha de entrega";
    } elseif (strtotime($fecha_entrega) < strtotime($fecha_pedido)) {
        $error = "La fecha de entrega no puede ser anterior a la fecha del pedido";
    } else {
        // Actualizar pedido
        $stmt = $conexion->prepare("UPDATE pedido SET METODO_PAGO = ?, FECHA_PEDIDO = ?, ID_CLIENTE = ?, ESTADO = ?, OBSERVACIONES = ?, FECHA_ENTREGA = ?, FECHA_ACTUALIZACION = NOW() WHERE ID_PEDIDO = ?");
        $stmt->bind_param("ssssssi", $metodo_pago, $fecha_pedido, $id_cliente, $estado, $observaciones, $fecha_entrega, $id_pedido);
        
        if ($stmt->execute()) {
            $success = "✅ Pedido actualizado correctamente";
            
            // Recargar información del pedido
            $stmt_pedido->execute();
            $pedido = $stmt_pedido->get_result()->fetch_assoc();
        } else {
            $error = "❌ Error al actualizar el pedido";
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
$query_productos_disponibles = "SELECT p.ID_PRODUCTO, p.CODIGO, p.NOMBRE, p.PRECIO, p.STOCK, c.NOMBRE as CATEGORIA 
                               FROM producto p 
                               INNER JOIN categoria c ON p.ID_CATEGORIA = c.ID_CATEGORIA 
                               WHERE p.ACTIVO = 1 AND c.ACTIVO = 1 
                               AND p.ID_PRODUCTO NOT IN (
                                   SELECT pd.ID_PRODUCTO FROM pedido_detalle pd WHERE pd.ID_PEDIDO = ?
                               )
                               ORDER BY c.NOMBRE, p.NOMBRE";

$stmt_productos_disponibles = $conexion->prepare($query_productos_disponibles);
$stmt_productos_disponibles->bind_param("i", $id_pedido);
$stmt_productos_disponibles->execute();
$result_productos_disponibles = $stmt_productos_disponibles->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Pedido - Administrador</title>
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
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover {
            background: #218838;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        .btn-warning:hover {
            background: #e0a800;
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
        }
        .form-body {
            padding: 20px;
        }
        .form-section {
            margin-bottom: 30px;
        }
        .form-section h3 {
            color: #495057;
            border-bottom: 2px solid #28a745;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
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
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        .required {
            color: #dc3545;
        }
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .productos-container {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
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
        .producto-precio {
            color: #28a745;
            font-weight: bold;
        }
        .producto-stock {
            color: #6c757d;
            font-size: 0.9em;
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
        .btn-eliminar-producto {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            cursor: pointer;
            font-size: 14px;
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
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>✏️ Editar Pedido #<?php echo $id_pedido; ?></h1>
            <div>
                <a href="visualizar.php?id=<?php echo $id_pedido; ?>" class="btn btn-success">👁️ Ver Pedido</a>
                <a href="index.php" class="btn btn-secondary">← Volver a Pedidos</a>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="mensaje error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="mensaje success">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <div class="form-header">
                <h3>📋 Información del Pedido</h3>
            </div>
            <form method="POST" action="">
                <div class="form-body">
                    <div class="form-section">
                        <h3>👤 Información del Cliente</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="id_cliente">Cliente <span class="required">*</span></label>
                                <select id="id_cliente" name="id_cliente" required>
                                    <option value="">Seleccionar cliente...</option>
                                    <?php 
                                    $result_clientes->data_seek(0);
                                    while ($cliente = $result_clientes->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $cliente['ID_CLIENTE']; ?>" <?php echo $pedido['ID_CLIENTE'] == $cliente['ID_CLIENTE'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cliente['NOMBRE'] . ' ' . $cliente['APELLIDOS'] . ' - ' . $cliente['TELEFONO']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>📅 Información del Pedido</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="fecha_pedido">Fecha del Pedido <span class="required">*</span></label>
                                <input type="date" id="fecha_pedido" name="fecha_pedido" value="<?php echo $pedido['FECHA_PEDIDO']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="fecha_entrega">Fecha de Entrega <span class="required">*</span></label>
                                <input type="datetime-local" id="fecha_entrega" name="fecha_entrega" value="<?php echo str_replace(' ', 'T', $pedido['FECHA_ENTREGA']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="metodo_pago">Método de Pago <span class="required">*</span></label>
                                <select id="metodo_pago" name="metodo_pago" required>
                                    <option value="EFECTIVO" <?php echo $pedido['METODO_PAGO'] == 'EFECTIVO' ? 'selected' : ''; ?>>💵 Efectivo</option>
                                    <option value="TARJETA" <?php echo $pedido['METODO_PAGO'] == 'TARJETA' ? 'selected' : ''; ?>>💳 Tarjeta</option>
                                    <option value="YAPE" <?php echo $pedido['METODO_PAGO'] == 'YAPE' ? 'selected' : ''; ?>>📱 Yape</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="estado">Estado <span class="required">*</span></label>
                                <select id="estado" name="estado" required>
                                    <option value="PENDIENTE" <?php echo $pedido['ESTADO'] == 'PENDIENTE' ? 'selected' : ''; ?>>⏳ Pendiente</option>
                                    <option value="LISTO" <?php echo $pedido['ESTADO'] == 'LISTO' ? 'selected' : ''; ?>>✅ Listo</option>
                                    <option value="CANCELADO" <?php echo $pedido['ESTADO'] == 'CANCELADO' ? 'selected' : ''; ?>>❌ Cancelado</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="observaciones">Observaciones</label>
                                <textarea id="observaciones" name="observaciones" placeholder="Observaciones adicionales del pedido..."><?php echo htmlspecialchars($pedido['OBSERVACIONES']); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="index.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-success">✅ Actualizar Pedido</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="productos-container">
            <div class="productos-header">
                <h3>🛒 Productos del Pedido</h3>
                <a href="visualizar.php?id=<?php echo $id_pedido; ?>" class="btn btn-primary">➕ Gestionar Productos</a>
            </div>
            
            <div class="productos-body">
                <?php if ($result_detalles->num_rows > 0): ?>
                    <?php while ($detalle = $result_detalles->fetch_assoc()): ?>
                        <div class="producto-item">
                            <div class="producto-header">
                                <div class="producto-info">
                                    <div class="producto-nombre"><?php echo htmlspecialchars($detalle['PRODUCTO_NOMBRE']); ?></div>
                                    <div class="producto-stock">Código: <?php echo htmlspecialchars($detalle['CODIGO']); ?> | Categoría: <?php echo htmlspecialchars($detalle['CATEGORIA_NOMBRE']); ?></div>
                                </div>
                                <div class="producto-precio">S/ <?php echo number_format($detalle['PRECIO_UNITARIO'], 2); ?></div>
                            </div>
                            <div class="producto-cantidad">
                                <label>Cantidad:</label>
                                <span><?php echo $detalle['CANTIDAD']; ?></span>
                                <span class="producto-subtotal">Subtotal: S/ <?php echo number_format($detalle['SUBTOTAL'], 2); ?></span>
                            </div>
                            <?php if (!empty($detalle['OBSERVACIONES'])): ?>
                                <div style="color: #6c757d; font-style: italic; margin-top: 10px;">
                                    📝 <?php echo htmlspecialchars($detalle['OBSERVACIONES']); ?>
                                </div>
                            <?php endif; ?>
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
</body>
</html>
