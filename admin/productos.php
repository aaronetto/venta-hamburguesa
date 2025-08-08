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
        case 'categoria_requerida':
            $mensaje = "❌ Error: Debe seleccionar una categoría";
            break;
    }
}

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['accion'])) {
        switch ($_POST['accion']) {
            case 'crear':
                $nombre = trim($_POST['nombre']);
                $categoria = $_POST['categoria'];
                $precio = (float)$_POST['precio'];
                
                if (!empty($nombre) && !empty($categoria) && $precio > 0) {
                    $stmt = $conexion->prepare("INSERT INTO producto (NOMB_PRODUCTO, ID_CATEGORIA, PRECIO) VALUES (?, ?, ?)");
                    $stmt->bind_param("sid", $nombre, $categoria, $precio);
                    if ($stmt->execute()) {
                        header("Location: productos.php?success=creado");
                        exit();
                    } else {
                        $mensaje = "❌ Error al crear el producto";
                    }
                    $stmt->close();
                } else {
                    $mensaje = "❌ Todos los campos son requeridos y el precio debe ser mayor a 0";
                }
                break;
                
            case 'actualizar':
                $id = $_POST['id'];
                $nombre = trim($_POST['nombre']);
                $categoria = $_POST['categoria'];
                $precio = (float)$_POST['precio'];
                
                if (!empty($nombre) && !empty($categoria) && $precio > 0) {
                    $stmt = $conexion->prepare("UPDATE producto SET NOMB_PRODUCTO = ?, ID_CATEGORIA = ?, PRECIO = ? WHERE ID_PRODUCTO = ?");
                    $stmt->bind_param("sidi", $nombre, $categoria, $precio, $id);
                    if ($stmt->execute()) {
                        header("Location: productos.php?success=actualizado");
                        exit();
                    } else {
                        $mensaje = "❌ Error al actualizar el producto";
                    }
                    $stmt->close();
                } else {
                    $mensaje = "❌ Todos los campos son requeridos y el precio debe ser mayor a 0";
                }
                break;
        }
    }
}

// Obtener categorías para el formulario
$query_categorias = "SELECT * FROM categoria ORDER BY NOMB_CATEGORIA";
$result_categorias = $conexion->query($query_categorias);

// Obtener productos con información de categoría
$query = "SELECT p.*, c.NOMB_CATEGORIA 
          FROM producto p 
          INNER JOIN categoria c ON p.ID_CATEGORIA = c.ID_CATEGORIA 
          ORDER BY p.NOMB_PRODUCTO";
$result = $conexion->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos</title>
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
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            border-radius: 10px;
            width: 80%;
            max-width: 500px;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #000;
        }
        
        .precio {
            font-weight: bold;
            color: #e53e2e;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>🍔 Gestión de Productos</h1>
            <a href="../plataforma.php" class="btn btn-secondary">← Volver al Panel</a>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="mensaje <?php echo strpos($mensaje, '✅') !== false ? 'success' : 'error'; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <!-- Formulario para crear producto -->
        <div class="form-container">
            <h3>➕ Crear Nuevo Producto</h3>
            <form method="POST">
                <input type="hidden" name="accion" value="crear">
                <div class="form-group">
                    <label for="nombre">Nombre del Producto:</label>
                    <input type="text" id="nombre" name="nombre" required maxlength="100">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="categoria">Categoría:</label>
                        <select id="categoria" name="categoria" required>
                            <option value="">-- Seleccionar Categoría --</option>
                            <?php while ($cat = $result_categorias->fetch_assoc()): ?>
                                <option value="<?php echo $cat['ID_CATEGORIA']; ?>">
                                    <?php echo htmlspecialchars($cat['NOMB_CATEGORIA']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="precio">Precio (S/):</label>
                        <input type="number" id="precio" name="precio" step="0.01" min="0" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Crear Producto</button>
            </form>
        </div>

        <!-- Tabla de productos -->
        <div class="table-container">
            <h3 style="padding: 20px; margin: 0; background: #f8f9fa; border-bottom: 1px solid #ddd;">
                📋 Lista de Productos
            </h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Precio</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['ID_PRODUCTO']; ?></td>
                                <td><?php echo htmlspecialchars($row['NOMB_PRODUCTO']); ?></td>
                                <td><?php echo htmlspecialchars($row['NOMB_CATEGORIA']); ?></td>
                                <td class="precio">S/ <?php echo number_format($row['PRECIO'], 2); ?></td>
                                <td>
                                    <button class="btn btn-warning" onclick="editarProducto(<?php echo $row['ID_PRODUCTO']; ?>, '<?php echo htmlspecialchars($row['NOMB_PRODUCTO']); ?>', <?php echo $row['ID_CATEGORIA']; ?>, <?php echo $row['PRECIO']; ?>)">
                                        ✏️ Editar
                                    </button>
                                    <button class="btn btn-danger" onclick="eliminarProducto(<?php echo $row['ID_PRODUCTO']; ?>, '<?php echo htmlspecialchars($row['NOMB_PRODUCTO']); ?>')">
                                        🗑️ Eliminar
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #666;">No hay productos registrados</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal para editar producto -->
    <div id="modalEditar" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>✏️ Editar Producto</h3>
                <span class="close" onclick="cerrarModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="accion" value="actualizar">
                <input type="hidden" id="edit_id" name="id">
                <div class="form-group">
                    <label for="edit_nombre">Nombre del Producto:</label>
                    <input type="text" id="edit_nombre" name="nombre" required maxlength="100">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_categoria">Categoría:</label>
                        <select id="edit_categoria" name="categoria" required>
                            <option value="">-- Seleccionar Categoría --</option>
                            <?php 
                            $result_categorias->data_seek(0);
                            while ($cat = $result_categorias->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $cat['ID_CATEGORIA']; ?>">
                                    <?php echo htmlspecialchars($cat['NOMB_CATEGORIA']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_precio">Precio (S/):</label>
                        <input type="number" id="edit_precio" name="precio" step="0.01" min="0" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Actualizar Producto</button>
                <button type="button" class="btn btn-secondary" onclick="cerrarModal()">Cancelar</button>
            </form>
        </div>
    </div>

    <script>
        function editarProducto(id, nombre, categoria, precio) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nombre').value = nombre;
            document.getElementById('edit_categoria').value = categoria;
            document.getElementById('edit_precio').value = precio;
            document.getElementById('modalEditar').style.display = 'block';
        }

        function cerrarModal() {
            document.getElementById('modalEditar').style.display = 'none';
        }

        function eliminarProducto(id, nombre) {
            if (confirm(`¿Estás seguro de que deseas eliminar el producto "${nombre}"?\n\nEsta acción no se puede deshacer.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'eliminar_producto.php';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'id';
                input.value = id;
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Cerrar modal al hacer clic fuera de él
        window.onclick = function(event) {
            const modal = document.getElementById('modalEditar');
            if (event.target == modal) {
                cerrarModal();
            }
        }
    </script>
</body>
</html>
