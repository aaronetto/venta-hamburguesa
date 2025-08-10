<?php
session_start();
require_once '../../config.php';
require_once '../../auth_functions.php';

// Verificar acceso al módulo
requerirAccesoModulo('clientes');

$conexion = getConnection();

// Procesar mensajes de URL
$mensaje = '';
if (isset($_GET['creado'])) {
    $mensaje = "✅ Cliente creado exitosamente";
} elseif (isset($_GET['actualizado'])) {
    $mensaje = "✅ Cliente actualizado exitosamente";
} elseif (isset($_GET['eliminado'])) {
    $mensaje = "✅ Cliente eliminado exitosamente";
} elseif (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'campos_vacios':
            $mensaje = "❌ Error: Los campos obligatorios no pueden estar vacíos";
            break;
        case 'correo_duplicado':
            $mensaje = "❌ Error: Ya existe un cliente con ese correo electrónico";
            break;
        case 'cliente_con_pedidos':
            $mensaje = "❌ Error: No se puede eliminar el cliente porque tiene pedidos asociados";
            break;
        case 'error_eliminacion':
            $mensaje = "❌ Error al eliminar el cliente";
            break;
    }
}

// Procesar eliminación si se solicita
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    $id_cliente = $_GET['eliminar'];
    
    // Verificar si el cliente tiene pedidos asociados
    $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM pedido WHERE ID_CLIENTE = ?");
    $stmt->bind_param("i", $id_cliente);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $pedidos = $resultado->fetch_assoc();
    
    if ($pedidos['total'] > 0) {
        header("Location: index.php?error=cliente_con_pedidos");
        exit();
    }
    
    // Verificar si el cliente tiene direcciones asociadas
    $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM direccion_cliente WHERE ID_CLIENTE = ?");
    $stmt->bind_param("i", $id_cliente);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $direcciones = $resultado->fetch_assoc();
    
    if ($direcciones['total'] > 0) {
        // Eliminar direcciones del cliente primero
        $stmt = $conexion->prepare("DELETE FROM direccion_cliente WHERE ID_CLIENTE = ?");
        $stmt->bind_param("i", $id_cliente);
        $stmt->execute();
    }
    
    // Eliminar el cliente
    $stmt = $conexion->prepare("DELETE FROM cliente WHERE ID_CLIENTE = ?");
    $stmt->bind_param("i", $id_cliente);
    
    if ($stmt->execute()) {
        header("Location: index.php?eliminado=1");
    } else {
        header("Location: index.php?error=error_eliminacion");
    }
    exit();
}

// Obtener lista de clientes con información adicional
$query = "SELECT c.*, 
          (SELECT COUNT(*) FROM pedido WHERE ID_CLIENTE = c.ID_CLIENTE) as total_pedidos,
          (SELECT COUNT(*) FROM direccion_cliente WHERE ID_CLIENTE = c.ID_CLIENTE) as total_direcciones
          FROM cliente c 
          ORDER BY c.NOMBRE, c.APELLIDOS";
$result = $conexion->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Clientes</title>
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
        
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        
        .btn-info:hover {
            background: #138496;
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
        
        .client-name {
            font-weight: 600;
            color: #495057;
        }
        
        .client-email {
            color: #17a2b8;
            font-size: 14px;
        }
        
        .client-phone {
            color: #6c757d;
            font-size: 14px;
        }
        
        .stats-info {
            font-size: 12px;
            color: #6c757d;
        }
        
        .stats-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin: 1px;
        }
        
        .stats-pedidos {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .stats-direcciones {
            background: #f3e5f5;
            color: #7b1fa2;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>👥 Gestión de Clientes</h1>
            <div>
                <a href="registrar.php" class="btn btn-primary">➕ Nuevo Cliente</a>
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
                📋 Lista de Clientes
            </h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre Completo</th>
                        <th>Correo</th>
                        <th>Teléfono</th>
                        <th>Dirección Principal</th>
                        <th>Estadísticas</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['ID_CLIENTE']; ?></td>
                                <td>
                                    <div class="client-name">
                                        <?php echo htmlspecialchars($row['NOMBRE'] . ' ' . $row['APELLIDOS']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="client-email">
                                        <?php echo htmlspecialchars($row['CORREO']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="client-phone">
                                        <?php echo htmlspecialchars($row['TELEFONO']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="stats-info">
                                        <?php echo htmlspecialchars($row['DIRECCION']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="stats-info">
                                        <span class="stats-badge stats-pedidos">
                                            📦 <?php echo $row['total_pedidos']; ?> pedidos
                                        </span>
                                        <span class="stats-badge stats-direcciones">
                                            📍 <?php echo $row['total_direcciones']; ?> direcciones
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a href="visualizar.php?id=<?php echo $row['ID_CLIENTE']; ?>" class="btn btn-info">
                                            👁️ Ver
                                        </a>
                                        <a href="editar.php?id=<?php echo $row['ID_CLIENTE']; ?>" class="btn btn-warning">
                                            ✏️ Editar
                                        </a>
                                        <button class="btn btn-danger" onclick="eliminarCliente(<?php echo $row['ID_CLIENTE']; ?>, '<?php echo htmlspecialchars($row['NOMBRE'] . ' ' . $row['APELLIDOS']); ?>')">
                                            🗑️ Eliminar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: #666; padding: 40px;">
                                No hay clientes registrados
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function eliminarCliente(id, nombre) {
            if (confirm(`¿Estás seguro de que quieres eliminar el cliente "${nombre}"?`)) {
                window.location.href = `index.php?eliminar=${id}`;
            }
        }
    </script>
</body>
</html>

<?php
$conexion->close();
?>
