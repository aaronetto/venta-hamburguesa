<?php
session_start();
require_once '../../config.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../login_registro.php");
    exit();
}

$conexion = getConnection();

// Procesar mensajes de URL
$mensaje = '';
if (isset($_GET['creado'])) {
    $mensaje = "✅ Categoría creada exitosamente";
} elseif (isset($_GET['actualizado'])) {
    $mensaje = "✅ Categoría actualizada exitosamente";
} elseif (isset($_GET['eliminado'])) {
    $mensaje = "✅ Categoría eliminada exitosamente";
} elseif (isset($_GET['activado'])) {
    $mensaje = "✅ Categoría activada exitosamente";
} elseif (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'campos_vacios':
            $mensaje = "❌ Error: Todos los campos son obligatorios";
            break;
        case 'nombre_duplicado':
            $mensaje = "❌ Error: Ya existe una categoría con ese nombre";
            break;
        case 'categoria_con_productos':
            $mensaje = "❌ Error: No se puede eliminar la categoría porque tiene productos asociados";
            break;
        case 'error_eliminacion':
            $mensaje = "❌ Error al eliminar la categoría";
            break;
        case 'error_activacion':
            $mensaje = "❌ Error al activar la categoría";
            break;
    }
}

// Obtener categorías con información adicional
$query = "SELECT c.*, 
          (SELECT COUNT(*) FROM producto WHERE ID_CATEGORIA = c.ID_CATEGORIA AND ACTIVO = 1) as productos_activos,
          (SELECT COUNT(*) FROM producto WHERE ID_CATEGORIA = c.ID_CATEGORIA) as total_productos
          FROM categoria c 
          ORDER BY c.NOMBRE";
$result = $conexion->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Categorías</title>
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
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.3s ease;
            border: none;
            cursor: pointer;
            margin: 2px;
        }
        
        .btn-primary {
            background: #28a745;
            color: white;
        }
        
        .btn-primary:hover {
            background: #218838;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
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
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-activo {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactivo {
            background: #f8d7da;
            color: #721c24;
        }
        
        .productos-count {
            font-size: 12px;
            color: #6c757d;
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
        
        .actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .description-cell {
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
            <h1>📂 Gestión de Categorías</h1>
            <div>
                <a href="registrar.php" class="btn btn-primary">➕ Nueva Categoría</a>
                <a href="../../plataforma.php" class="btn btn-secondary">← Volver al Panel</a>
            </div>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="mensaje <?php echo strpos($mensaje, '✅') !== false ? 'success' : 'error'; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <div class="table-container">
            <h3 style="padding: 20px; margin: 0; background: #f8f9fa; border-bottom: 1px solid #ddd;">
                📋 Lista de Categorías
            </h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Productos</th>
                        <th>Estado</th>
                        <th>Fecha Creación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['ID_CATEGORIA']; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['NOMBRE']); ?></strong></td>
                                <td class="description-cell">
                                    <?php echo !empty($row['DESCRIPCION']) ? htmlspecialchars($row['DESCRIPCION']) : '<em>Sin descripción</em>'; ?>
                                </td>
                                <td>
                                    <span class="productos-count">
                                        <?php echo $row['productos_activos']; ?> activos
                                        <?php if ($row['total_productos'] > $row['productos_activos']): ?>
                                            / <?php echo $row['total_productos']; ?> total
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $row['ACTIVO'] ? 'status-activo' : 'status-inactivo'; ?>">
                                        <?php echo $row['ACTIVO'] ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['FECHA_CREACION'])); ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="editar.php?id=<?php echo $row['ID_CATEGORIA']; ?>" class="btn btn-warning">
                                            ✏️ Editar
                                        </a>
                                        <?php if ($row['ACTIVO']): ?>
                                            <button class="btn btn-danger" onclick="eliminarCategoria(<?php echo $row['ID_CATEGORIA']; ?>, '<?php echo htmlspecialchars($row['NOMBRE']); ?>')">
                                                🗑️ Eliminar
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-success" onclick="activarCategoria(<?php echo $row['ID_CATEGORIA']; ?>, '<?php echo htmlspecialchars($row['NOMBRE']); ?>')">
                                                ✅ Activar
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: #666; padding: 40px;">
                                No hay categorías registradas
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function eliminarCategoria(id, nombre) {
            if (confirm(`¿Estás seguro de que quieres eliminar la categoría "${nombre}"?`)) {
                window.location.href = `eliminar.php?id=${id}`;
            }
        }
        
        function activarCategoria(id, nombre) {
            if (confirm(`¿Estás seguro de que quieres activar la categoría "${nombre}"?`)) {
                window.location.href = `activar.php?id=${id}`;
            }
        }
    </script>
</body>
</html>
