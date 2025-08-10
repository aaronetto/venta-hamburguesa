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
    $mensaje = "✅ Proveedor creado exitosamente";
} elseif (isset($_GET['actualizado'])) {
    $mensaje = "✅ Proveedor actualizado exitosamente";
} elseif (isset($_GET['eliminado'])) {
    $mensaje = "✅ Proveedor eliminado exitosamente";
} elseif (isset($_GET['activado'])) {
    $mensaje = "✅ Proveedor activado exitosamente";
} elseif (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'campos_vacios':
            $mensaje = "❌ Error: Los campos obligatorios no pueden estar vacíos";
            break;
        case 'documento_duplicado':
            $mensaje = "❌ Error: Ya existe un proveedor con ese número de documento";
            break;
        case 'correo_duplicado':
            $mensaje = "❌ Error: Ya existe un proveedor con ese correo electrónico";
            break;
        case 'proveedor_con_productos':
            $mensaje = "❌ Error: No se puede eliminar el proveedor porque tiene productos asociados";
            break;
        case 'error_eliminacion':
            $mensaje = "❌ Error al eliminar el proveedor";
            break;
        case 'error_activacion':
            $mensaje = "❌ Error al activar el proveedor";
            break;
    }
}

// Obtener proveedores con información adicional
$query = "SELECT p.*, 
          (SELECT COUNT(*) FROM producto WHERE ID_PROVEEDOR = p.ID_PROVEEDOR AND ACTIVO = 1) as productos_activos,
          (SELECT COUNT(*) FROM producto WHERE ID_PROVEEDOR = p.ID_PROVEEDOR) as total_productos
          FROM proveedor p 
          ORDER BY p.NOMBRE";
$result = $conexion->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Proveedores</title>
    <link rel="stylesheet" href="../../style.css">
    <style>
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .admin-header {
            background: linear-gradient(135deg, #17a2b8, #138496);
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
            background: #17a2b8;
            color: white;
        }
        
        .btn-primary:hover {
            background: #138496;
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
            padding: 12px;
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
        
        .contact-info {
            font-size: 12px;
            color: #6c757d;
        }
        
        .contact-name {
            font-weight: 600;
            color: #495057;
        }
        
        .documento {
            font-family: monospace;
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
        }
        
        .website-link {
            color: #17a2b8;
            text-decoration: none;
        }
        
        .website-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>🏢 Gestión de Proveedores</h1>
            <div>
                <a href="registrar.php" class="btn btn-primary">➕ Nuevo Proveedor</a>
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
                📋 Lista de Proveedores
            </h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Razón Social</th>
                        <th>Documento</th>
                        <th>Contacto</th>
                        <th>Teléfono</th>
                        <th>Correo</th>
                        <th>Productos</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['ID_PROVEEDOR']; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['NOMBRE']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['RAZON_SOCIAL']); ?></td>
                                <td><span class="documento"><?php echo htmlspecialchars($row['NUMERO_DOCUMENTO']); ?></span></td>
                                <td>
                                    <div class="contact-info">
                                        <div class="contact-name">
                                            <?php echo htmlspecialchars($row['CONTACTO_NOMBRES']); ?>
                                            <?php if (!empty($row['CONTACTO_APELLIDOS'])): ?>
                                                <?php echo htmlspecialchars($row['CONTACTO_APELLIDOS']); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($row['TELEFONO']); ?></td>
                                <td><?php echo htmlspecialchars($row['CORREO']); ?></td>
                                <td>
                                    <span class="productos-count">
                                        <?php echo $row['productos_activos']; ?> activos
                                        <?php if ($row['total_productos'] > $row['productos_activos']): ?>
                                            / <?php echo $row['total_productos']; ?> total
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a href="editar.php?id=<?php echo $row['ID_PROVEEDOR']; ?>" class="btn btn-warning">
                                            ✏️ Editar
                                        </a>
                                        <button class="btn btn-danger" onclick="eliminarProveedor(<?php echo $row['ID_PROVEEDOR']; ?>, '<?php echo htmlspecialchars($row['NOMBRE']); ?>')">
                                            🗑️ Eliminar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" style="text-align: center; color: #666; padding: 40px;">
                                No hay proveedores registrados
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function eliminarProveedor(id, nombre) {
            if (confirm(`¿Estás seguro de que quieres eliminar el proveedor "${nombre}"?`)) {
                window.location.href = `eliminar.php?id=${id}`;
            }
        }
    </script>
</body>
</html>
