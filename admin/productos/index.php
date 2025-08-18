<?php
session_start();
require_once '../../config.php';
require_once '../../auth_functions.php';

// Verificar acceso al módulo
requerirAccesoModulo('productos');

$conexion = getConnection();
$mensaje = '';

// Procesar mensajes de URL
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'creado':
            $mensaje = "✅ Producto creado exitosamente";
            break;
        case 'actualizado':
            $mensaje = "✅ Producto actualizado exitosamente";
            break;
        case 'eliminado':
            $mensaje = "✅ Producto eliminado exitosamente";
            break;
        case 'activado':
            $mensaje = "✅ Producto activado exitosamente";
            break;
    }
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'id_requerido':
            $mensaje = "❌ Error: ID de producto requerido";
            break;
        case 'producto_no_existe':
            $mensaje = "❌ Error: El producto no existe";
            break;
        case 'producto_con_pedidos':
            $mensaje = "❌ Error: No se puede eliminar el producto porque tiene pedidos asociados";
            break;
        case 'error_eliminacion':
            $mensaje = "❌ Error al eliminar el producto";
            break;
        case 'error_activacion':
            $mensaje = "❌ Error al activar el producto";
            break;
    }
}

// Obtener productos con información de categoría y proveedor
$query = "SELECT p.*, c.NOMBRE as CATEGORIA_NOMBRE, pr.NOMBRE as PROVEEDOR_NOMBRE 
          FROM producto p 
          INNER JOIN categoria c ON p.ID_CATEGORIA = c.ID_CATEGORIA 
          INNER JOIN proveedor pr ON p.ID_PROVEEDOR = pr.ID_PROVEEDOR 
          ORDER BY p.NOMBRE";
$result = $conexion->query($query);

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos - Administración</title>
    <link rel="stylesheet" href="../../style.css">
    <style>
        .admin-container {
            max-width: 1400px;
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
            margin: 2px;
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
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-warning:hover {
            background: #e0a800;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
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
            padding: 15px;
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
        
        .precio {
            font-weight: bold;
            color: #e53e2e;
        }
        
        .stock {
            font-weight: bold;
        }
        
        .stock.bajo {
            color: #dc3545;
        }
        
        .stock.medio {
            color: #ffc107;
        }
        
        .stock.alto {
            color: #28a745;
        }
        
        .estado {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .estado.activo {
            background: #d4edda;
            color: #155724;
        }
        
        .estado.inactivo {
            background: #f8d7da;
            color: #721c24;
        }
        
        .producto-imagen {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }
        
        .sin-imagen {
            width: 50px;
            height: 50px;
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            font-size: 12px;
        }
        
        .acciones {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .descripcion {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>🍔 Gestión de Productos</h1>
            <div>
                <a href="registrar.php" class="btn btn-primary">➕ Nuevo Producto</a>
                <a href="../../plataforma.php" class="btn btn-secondary">← Volver al Panel</a>
            </div>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="mensaje <?php echo strpos($mensaje, '✅') !== false ? 'success' : 'error'; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <!-- Tabla de productos -->
        <div class="table-container">
            <h3 style="padding: 20px; margin: 0; background: #f8f9fa; border-bottom: 1px solid #ddd;">
                📋 Lista de Productos
            </h3>
            <table>
                <thead>
                    <tr>
                        <th>Imagen</th>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Categoría</th>
                        <th>Proveedor</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php 
                            // Determinar clase de stock
                            $stock_class = '';
                            if ($row['STOCK'] <= 5) {
                                $stock_class = 'bajo';
                            } elseif ($row['STOCK'] <= 15) {
                                $stock_class = 'medio';
                            } else {
                                $stock_class = 'alto';
                            }
                            ?>
                            <tr>
                                <td>
                                    <?php if (!empty($row['IMAGEN_RUTA'])): ?>
                                        <img src="../../<?php echo htmlspecialchars($row['IMAGEN_RUTA']); ?>" 
                                             class="producto-imagen" alt="Imagen del producto">
                                    <?php else: ?>
                                        <div class="sin-imagen">Sin img</div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($row['CODIGO']); ?></strong></td>
                                <td><strong><?php echo htmlspecialchars($row['NOMBRE']); ?></strong></td>
                                <td class="descripcion" title="<?php echo htmlspecialchars($row['DESCRIPCION']); ?>">
                                    <?php echo htmlspecialchars($row['DESCRIPCION'] ?: 'Sin descripción'); ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['CATEGORIA_NOMBRE']); ?></td>
                                <td><?php echo htmlspecialchars($row['PROVEEDOR_NOMBRE']); ?></td>
                                <td class="precio">S/ <?php echo number_format($row['PRECIO'], 2); ?></td>
                                <td class="stock <?php echo $stock_class; ?>"><?php echo $row['STOCK']; ?></td>
                                <td>
                                    <span class="estado <?php echo $row['ACTIVO'] == 1 ? 'activo' : 'inactivo'; ?>">
                                        <?php echo $row['ACTIVO'] == 1 ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="acciones">
                                        <a href="editar.php?id=<?php echo $row['ID_PRODUCTO']; ?>" 
                                           class="btn btn-warning">✏️ Editar</a>
                                        
                                        <?php if ($row['ACTIVO'] == 1): ?>
                                            <button class="btn btn-danger" 
                                                    onclick="eliminarProducto(<?php echo $row['ID_PRODUCTO']; ?>, '<?php echo htmlspecialchars($row['NOMBRE']); ?>')">
                                                🗑️ Eliminar
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-success" 
                                                    onclick="activarProducto(<?php echo $row['ID_PRODUCTO']; ?>, '<?php echo htmlspecialchars($row['NOMBRE']); ?>')">
                                                ✅ Activar
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" style="text-align: center; color: #666; padding: 40px;">
                                No hay productos registrados
                                <br><br>
                                <a href="registrar.php" class="btn btn-primary">➕ Crear Primer Producto</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function eliminarProducto(id, nombre) {
            if (confirm(`¿Estás seguro de que deseas eliminar el producto "${nombre}"?\n\nEsta acción no se puede deshacer.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'eliminar.php';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'id';
                input.value = id;
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function activarProducto(id, nombre) {
            if (confirm(`¿Estás seguro de que deseas activar el producto "${nombre}"?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'activar.php';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'id';
                input.value = id;
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
