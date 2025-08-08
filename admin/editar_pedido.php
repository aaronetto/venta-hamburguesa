<?php
session_start();
require_once '../config.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    header("Location: ../login_registro.php");
    exit();
}

$conexion = getConnection();
$mensaje = '';

// Verificar si se recibió el ID del pedido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: pedidos.php?error=id_requerido");
    exit();
}

$pedido_id = (int)$_GET['id'];

// Procesar mensajes de URL
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'creado':
            $mensaje = "✅ Pedido creado exitosamente. Ahora puedes agregar productos.";
            break;
        case 'actualizado':
            $mensaje = "✅ Pedido actualizado exitosamente";
            break;
        case 'producto_agregado':
            $mensaje = "✅ Producto agregado al pedido";
            break;
        case 'producto_eliminado':
            $mensaje = "✅ Producto eliminado del pedido";
            break;
    }
}

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['accion'])) {
        switch ($_POST['accion']) {
            case 'actualizar_pedido':
                $fecha = $_POST['fecha'];
                $usuario_id = $_POST['usuario'];
                
                if (!empty($fecha) && !empty($usuario_id)) {
                    $stmt = $conexion->prepare("UPDATE pedido SET FECHA_PEDIDO = ?, ID_USUARIO = ? WHERE ID_PEDIDO = ?");
                    $stmt->bind_param("sii", $fecha, $usuario_id, $pedido_id);
                    if ($stmt->execute()) {
                        header("Location: editar_pedido.php?id=" . $pedido_id . "&success=actualizado");
                        exit();
                    } else {
                        $mensaje = "❌ Error al actualizar el pedido";
                    }
                    $stmt->close();
                }
                break;
                
            case 'agregar_producto':
                $producto_id = $_POST['producto'];
                $cantidad = (int)$_POST['cantidad'];
                
                if (!empty($producto_id) && $cantidad > 0) {
                    // Obtener precio del producto
                    $stmt = $conexion->prepare("SELECT PRECIO FROM producto WHERE ID_PRODUCTO = ?");
                    $stmt->bind_param("i", $producto_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $producto = $result->fetch_assoc();
                    
                    if ($producto) {
                        $subtotal = $producto['PRECIO'] * $cantidad;
                        
                        // Insertar detalle
                        $stmt = $conexion->prepare("INSERT INTO detalle_pedido (ID_PEDIDO, ID_PRODUCTO, CANTIDAD, SUBTOTAL) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("iiid", $pedido_id, $producto_id, $cantidad, $subtotal);
                        
                        if ($stmt->execute()) {
                            // Actualizar total del pedido
                            actualizarTotalPedido($conexion, $pedido_id);
                            header("Location: editar_pedido.php?id=" . $pedido_id . "&success=producto_agregado");
                            exit();
                        } else {
                            $mensaje = "❌ Error al agregar el producto";
                        }
                    } else {
                        $mensaje = "❌ Producto no encontrado";
                    }
                    $stmt->close();
                } else {
                    $mensaje = "❌ Producto y cantidad son requeridos";
                }
                break;
        }
    }
}

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

// Obtener información del pedido
$stmt = $conexion->prepare("SELECT p.*, u.NOMB_USUARIO 
                           FROM pedido p 
                           INNER JOIN usuario u ON p.ID_USUARIO = u.ID_USUARIO 
                           WHERE p.ID_PEDIDO = ?");
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: pedidos.php?error=pedido_no_existe");
    exit();
}

$pedido = $result->fetch_assoc();

// Obtener usuarios para el formulario
$query_usuarios = "SELECT * FROM usuario ORDER BY NOMB_USUARIO";
$result_usuarios = $conexion->query($query_usuarios);

// Obtener productos para el formulario
$query_productos = "SELECT p.*, c.NOMB_CATEGORIA 
                   FROM producto p 
                   INNER JOIN categoria c ON p.ID_CATEGORIA = c.ID_CATEGORIA 
                   ORDER BY p.NOMB_PRODUCTO";
$result_productos = $conexion->query($query_productos);

// Obtener detalles del pedido
$stmt = $conexion->prepare("SELECT dp.*, p.NOMB_PRODUCTO, p.PRECIO 
                           FROM detalle_pedido dp 
                           INNER JOIN producto p ON dp.ID_PRODUCTO = p.ID_PRODUCTO 
                           WHERE dp.ID_PEDIDO = ? 
                           ORDER BY dp.ID_DETALLE");
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$detalles = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Pedido #<?php echo $pedido_id; ?></title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .admin-header {
            background: linear-gradient(135deg, #e53e2e, #cc0000);
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
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.3s ease;
            border: none;
            cursor: pointer;
            margin-right: 5px;
        }
        
        .btn-primary {
            background: #e53e2e;
            color: white;
        }
        
        .btn-primary:hover {
            background: #cc0000;
        }
        
        .btn-secondary {
            background: #666;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #555;
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
        
        .form-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .mensaje {
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: 600;
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
        
        .total {
            font-weight: bold;
            color: #e53e2e;
            font-size: 18px;
        }
        
        .info-pedido {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .info-item {
            text-align: center;
        }
        
        .info-label {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        
        .info-value {
            color: #666;
            font-size: 16px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>✏️ Editar Pedido #<?php echo $pedido_id; ?></h1>
            <a href="pedidos.php" class="btn btn-secondary">← Volver a Pedidos</a>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="mensaje <?php echo strpos($mensaje, '✅') !== false ? 'success' : 'error'; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <!-- Información del pedido -->
        <div class="info-pedido">
            <div class="info-item">
                <div class="info-label">Cliente</div>
                <div class="info-value"><?php echo htmlspecialchars($pedido['NOMB_USUARIO']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Fecha</div>
                <div class="info-value"><?php echo date('d/m/Y', strtotime($pedido['FECHA_PEDIDO'])); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Total</div>
                <div class="info-value total">S/ <?php echo number_format($pedido['TOTAL'], 2); ?></div>
            </div>
        </div>

        <!-- Formulario para editar pedido -->
        <div class="form-container">
            <h3>📝 Editar Información del Pedido</h3>
            <form method="POST">
                <input type="hidden" name="accion" value="actualizar_pedido">
                <div class="form-row">
                    <div class="form-group">
                        <label for="usuario">Cliente:</label>
                        <select id="usuario" name="usuario" required>
                            <?php 
                            $result_usuarios->data_seek(0);
                            while ($usuario = $result_usuarios->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $usuario['ID_USUARIO']; ?>" 
                                        <?php echo ($usuario['ID_USUARIO'] == $pedido['ID_USUARIO']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($usuario['NOMB_USUARIO']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="fecha">Fecha del Pedido:</label>
                        <input type="date" id="fecha" name="fecha" value="<?php echo $pedido['FECHA_PEDIDO']; ?>" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Actualizar Pedido</button>
            </form>
        </div>

        <!-- Formulario para agregar producto -->
        <div class="form-container">
            <h3>➕ Agregar Producto al Pedido</h3>
            <form method="POST">
                <input type="hidden" name="accion" value="agregar_producto">
                <div class="form-row">
                    <div class="form-group">
                        <label for="producto">Producto:</label>
                        <select id="producto" name="producto" required>
                            <option value="">-- Seleccionar Producto --</option>
                            <?php while ($producto = $result_productos->fetch_assoc()): ?>
                                <option value="<?php echo $producto['ID_PRODUCTO']; ?>">
                                    <?php echo htmlspecialchars($producto['NOMB_PRODUCTO']); ?> 
                                    (<?php echo htmlspecialchars($producto['NOMB_CATEGORIA']); ?>) - 
                                    S/ <?php echo number_format($producto['PRECIO'], 2); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="cantidad">Cantidad:</label>
                        <input type="number" id="cantidad" name="cantidad" min="1" value="1" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-success">Agregar Producto</button>
            </form>
        </div>

        <!-- Tabla de productos del pedido -->
        <div class="table-container">
            <h3 style="padding: 20px; margin: 0; background: #f8f9fa; border-bottom: 1px solid #ddd;">
                📋 Productos del Pedido
            </h3>
            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Precio Unitario</th>
                        <th>Cantidad</th>
                        <th>Subtotal</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($detalles->num_rows > 0): ?>
                        <?php while ($detalle = $detalles->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($detalle['NOMB_PRODUCTO']); ?></td>
                                <td>S/ <?php echo number_format($detalle['PRECIO'], 2); ?></td>
                                <td><?php echo $detalle['CANTIDAD']; ?></td>
                                <td class="total">S/ <?php echo number_format($detalle['SUBTOTAL'], 2); ?></td>
                                <td>
                                    <button class="btn btn-danger" onclick="eliminarProducto(<?php echo $detalle['ID_DETALLE']; ?>)">
                                        🗑️ Eliminar
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #666;">No hay productos en este pedido</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function eliminarProducto(idDetalle) {
            if (confirm('¿Estás seguro de que deseas eliminar este producto del pedido?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'eliminar_producto_pedido.php';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'id_detalle';
                input.value = idDetalle;
                
                const inputPedido = document.createElement('input');
                inputPedido.type = 'hidden';
                inputPedido.name = 'id_pedido';
                inputPedido.value = <?php echo $pedido_id; ?>;
                
                form.appendChild(input);
                form.appendChild(inputPedido);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
