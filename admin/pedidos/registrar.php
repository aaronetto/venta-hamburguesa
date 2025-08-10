<?php
session_start();
require_once '../../config.php';
require_once '../../auth_functions.php';

// Verificar acceso al módulo
requerirAccesoModulo('pedidos');

$conexion = getConnection();

$error = '';
$success = '';

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
        // Iniciar transacción
        $conexion->begin_transaction();
        
        try {
            // Insertar pedido
            $stmt = $conexion->prepare("INSERT INTO pedido (METODO_PAGO, FECHA_PEDIDO, TOTAL, ID_CLIENTE, ESTADO, OBSERVACIONES, FECHA_ENTREGA, FECHA_CREACION, ID_USUARIO) VALUES (?, ?, 0, ?, ?, ?, ?, NOW(), ?)");
            $stmt->bind_param("ssssssi", $metodo_pago, $fecha_pedido, $id_cliente, $estado, $observaciones, $fecha_entrega, $_SESSION['usuario_id']);
            
            if ($stmt->execute()) {
                $id_pedido = $conexion->insert_id;
                $total_pedido = 0;
                
                // Procesar productos del pedido
                if (isset($_POST['productos']) && is_array($_POST['productos'])) {
                    foreach ($_POST['productos'] as $index => $producto) {
                        if (!empty($producto['id_producto']) && !empty($producto['cantidad']) && $producto['cantidad'] > 0) {
                            $id_producto = intval($producto['id_producto']);
                            $cantidad = intval($producto['cantidad']);
                            $precio_unitario = floatval($producto['precio_unitario']);
                            $subtotal = $cantidad * $precio_unitario;
                            $observaciones_producto = trim($producto['observaciones'] ?? '');
                            
                            // Verificar stock
                            $stmt_stock = $conexion->prepare("SELECT STOCK FROM producto WHERE ID_PRODUCTO = ?");
                            $stmt_stock->bind_param("i", $id_producto);
                            $stmt_stock->execute();
                            $result_stock = $stmt_stock->get_result();
                            $producto_stock = $result_stock->fetch_assoc();
                            
                            if ($producto_stock && $producto_stock['STOCK'] >= $cantidad) {
                                // Insertar detalle del pedido
                                $stmt_detalle = $conexion->prepare("INSERT INTO pedido_detalle (CANTIDAD, ID_PEDIDO, ID_PRODUCTO, PRECIO_UNITARIO, SUBTOTAL, OBSERVACIONES) VALUES (?, ?, ?, ?, ?, ?)");
                                $stmt_detalle->bind_param("iiidds", $cantidad, $id_pedido, $id_producto, $precio_unitario, $subtotal, $observaciones_producto);
                                $stmt_detalle->execute();
                                
                                // Actualizar stock
                                $nuevo_stock = $producto_stock['STOCK'] - $cantidad;
                                $stmt_update_stock = $conexion->prepare("UPDATE producto SET STOCK = ? WHERE ID_PRODUCTO = ?");
                                $stmt_update_stock->bind_param("ii", $nuevo_stock, $id_producto);
                                $stmt_update_stock->execute();
                                
                                $total_pedido += $subtotal;
                            } else {
                                throw new Exception("Stock insuficiente para el producto seleccionado");
                            }
                        }
                    }
                }
                
                // Actualizar total del pedido
                $stmt_update_total = $conexion->prepare("UPDATE pedido SET TOTAL = ? WHERE ID_PEDIDO = ?");
                $stmt_update_total->bind_param("di", $total_pedido, $id_pedido);
                $stmt_update_total->execute();
                
                $conexion->commit();
                $success = "✅ Pedido registrado correctamente con ID: #$id_pedido";
                
                // Limpiar formulario
                $_POST = array();
            } else {
                throw new Exception("Error al crear el pedido");
            }
        } catch (Exception $e) {
            $conexion->rollback();
            $error = "❌ Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Pedido - Administrador</title>
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
        .form-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
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
            justify-content: between;
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
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>📝 Registrar Nuevo Pedido</h1>
            <div>
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
            <form method="POST" action="" id="pedidoForm">
                <div class="form-body">
                    <div class="form-section">
                        <h3>👤 Información del Cliente</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="id_cliente">Cliente <span class="required">*</span></label>
                                <select id="id_cliente" name="id_cliente" required>
                                    <option value="">Seleccionar cliente...</option>
                                    <?php while ($cliente = $result_clientes->fetch_assoc()): ?>
                                        <option value="<?php echo $cliente['ID_CLIENTE']; ?>" <?php echo (isset($_POST['id_cliente']) && $_POST['id_cliente'] == $cliente['ID_CLIENTE']) ? 'selected' : ''; ?>>
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
                                <input type="date" id="fecha_pedido" name="fecha_pedido" value="<?php echo $_POST['fecha_pedido'] ?? date('Y-m-d'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="fecha_entrega">Fecha de Entrega <span class="required">*</span></label>
                                <input type="datetime-local" id="fecha_entrega" name="fecha_entrega" value="<?php echo $_POST['fecha_entrega'] ?? ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="metodo_pago">Método de Pago <span class="required">*</span></label>
                                <select id="metodo_pago" name="metodo_pago" required>
                                    <option value="EFECTIVO" <?php echo (isset($_POST['metodo_pago']) && $_POST['metodo_pago'] == 'EFECTIVO') ? 'selected' : ''; ?>>💵 Efectivo</option>
                                    <option value="TARJETA" <?php echo (isset($_POST['metodo_pago']) && $_POST['metodo_pago'] == 'TARJETA') ? 'selected' : ''; ?>>💳 Tarjeta</option>
                                    <option value="YAPE" <?php echo (isset($_POST['metodo_pago']) && $_POST['metodo_pago'] == 'YAPE') ? 'selected' : ''; ?>>📱 Yape</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="estado">Estado <span class="required">*</span></label>
                                <select id="estado" name="estado" required>
                                    <option value="PENDIENTE" <?php echo (isset($_POST['estado']) && $_POST['estado'] == 'PENDIENTE') ? 'selected' : ''; ?>>⏳ Pendiente</option>
                                    <option value="LISTO" <?php echo (isset($_POST['estado']) && $_POST['estado'] == 'LISTO') ? 'selected' : ''; ?>>✅ Listo</option>
                                    <option value="CANCELADO" <?php echo (isset($_POST['estado']) && $_POST['estado'] == 'CANCELADO') ? 'selected' : ''; ?>>❌ Cancelado</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="observaciones">Observaciones</label>
                                <textarea id="observaciones" name="observaciones" placeholder="Observaciones adicionales del pedido..."><?php echo htmlspecialchars($_POST['observaciones'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>🛒 Productos del Pedido</h3>
                        <div class="productos-container">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="nuevo_producto">Agregar Producto</label>
                                    <select id="nuevo_producto">
                                        <option value="">Seleccionar producto...</option>
                                        <?php 
                                        $result_productos->data_seek(0);
                                        while ($producto = $result_productos->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo $producto['ID_PRODUCTO']; ?>" 
                                                    data-precio="<?php echo $producto['PRECIO']; ?>"
                                                    data-stock="<?php echo $producto['STOCK']; ?>"
                                                    data-nombre="<?php echo htmlspecialchars($producto['NOMBRE']); ?>"
                                                    data-categoria="<?php echo htmlspecialchars($producto['CATEGORIA']); ?>">
                                                <?php echo htmlspecialchars($producto['CODIGO'] . ' - ' . $producto['NOMBRE'] . ' (' . $producto['CATEGORIA'] . ') - S/ ' . $producto['PRECIO']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="nueva_cantidad">Cantidad</label>
                                    <input type="number" id="nueva_cantidad" min="1" value="1">
                                </div>
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="button" class="btn btn-primary" onclick="agregarProducto()">➕ Agregar</button>
                                </div>
                            </div>
                            
                            <div id="productos-lista">
                                <div class="productos-vacios">
                                    No hay productos agregados al pedido
                                </div>
                            </div>
                        </div>
                        
                        <div id="total-pedido" class="total-pedido" style="display: none;">
                            Total del Pedido: S/ <span id="total-valor">0.00</span>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="index.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-success">✅ Registrar Pedido</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        let productos = [];
        let contadorProductos = 0;

        function agregarProducto() {
            const select = document.getElementById('nuevo_producto');
            const cantidad = document.getElementById('nueva_cantidad');
            
            if (!select.value) {
                alert('Debe seleccionar un producto');
                return;
            }
            
            if (!cantidad.value || cantidad.value < 1) {
                alert('Debe especificar una cantidad válida');
                return;
            }
            
            const option = select.options[select.selectedIndex];
            const idProducto = select.value;
            const precio = parseFloat(option.dataset.precio);
            const stock = parseInt(option.dataset.stock);
            const nombre = option.dataset.nombre;
            const categoria = option.dataset.categoria;
            const cantidadVal = parseInt(cantidad.value);
            
            if (cantidadVal > stock) {
                alert('La cantidad excede el stock disponible (' + stock + ')');
                return;
            }
            
            // Verificar si el producto ya está agregado
            if (productos.find(p => p.id_producto == idProducto)) {
                alert('Este producto ya está agregado al pedido');
                return;
            }
            
            const producto = {
                id: contadorProductos++,
                id_producto: idProducto,
                nombre: nombre,
                categoria: categoria,
                precio: precio,
                stock: stock,
                cantidad: cantidadVal,
                subtotal: precio * cantidadVal
            };
            
            productos.push(producto);
            actualizarListaProductos();
            actualizarTotal();
            
            // Limpiar campos
            select.value = '';
            cantidad.value = 1;
        }

        function eliminarProducto(id) {
            productos = productos.filter(p => p.id !== id);
            actualizarListaProductos();
            actualizarTotal();
        }

        function actualizarCantidad(id, nuevaCantidad) {
            const producto = productos.find(p => p.id === id);
            if (producto) {
                if (nuevaCantidad > producto.stock) {
                    alert('La cantidad excede el stock disponible (' + producto.stock + ')');
                    return;
                }
                producto.cantidad = nuevaCantidad;
                producto.subtotal = producto.precio * nuevaCantidad;
                actualizarListaProductos();
                actualizarTotal();
            }
        }

        function actualizarListaProductos() {
            const container = document.getElementById('productos-lista');
            
            if (productos.length === 0) {
                container.innerHTML = '<div class="productos-vacios">No hay productos agregados al pedido</div>';
                return;
            }
            
            let html = '';
            productos.forEach(producto => {
                html += `
                    <div class="producto-item">
                        <button type="button" class="btn-eliminar-producto" onclick="eliminarProducto(${producto.id})">×</button>
                        <div class="producto-header">
                            <div class="producto-info">
                                <div class="producto-nombre">${producto.nombre}</div>
                                <div class="producto-precio">S/ ${producto.precio.toFixed(2)}</div>
                                <div class="producto-stock">Stock: ${producto.stock} | Categoría: ${producto.categoria}</div>
                            </div>
                        </div>
                        <div class="producto-cantidad">
                            <label>Cantidad:</label>
                            <input type="number" min="1" max="${producto.stock}" value="${producto.cantidad}" 
                                   onchange="actualizarCantidad(${producto.id}, this.value)">
                            <span class="producto-subtotal">Subtotal: S/ ${producto.subtotal.toFixed(2)}</span>
                        </div>
                        <div class="form-group">
                            <label>Observaciones del producto:</label>
                            <textarea name="productos[${producto.id}][observaciones]" placeholder="Observaciones específicas para este producto..."></textarea>
                        </div>
                        <input type="hidden" name="productos[${producto.id}][id_producto]" value="${producto.id_producto}">
                        <input type="hidden" name="productos[${producto.id}][cantidad]" value="${producto.cantidad}">
                        <input type="hidden" name="productos[${producto.id}][precio_unitario]" value="${producto.precio}">
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        function actualizarTotal() {
            const total = productos.reduce((sum, producto) => sum + producto.subtotal, 0);
            const totalElement = document.getElementById('total-valor');
            const totalContainer = document.getElementById('total-pedido');
            
            totalElement.textContent = total.toFixed(2);
            totalContainer.style.display = total > 0 ? 'block' : 'none';
        }

        // Validar formulario antes de enviar
        document.getElementById('pedidoForm').addEventListener('submit', function(e) {
            if (productos.length === 0) {
                e.preventDefault();
                alert('Debe agregar al menos un producto al pedido');
                return false;
            }
        });
    </script>
</body>
</html>
