<?php
session_start();
require_once '../../config.php';
require_once '../../auth_functions.php';

// Verificar acceso al módulo
requerirAccesoModulo('pedidos');

$conexion = getConnection();

$mensaje = '';

// Procesar eliminación de pedido
if (isset($_GET['action']) && $_GET['action'] == 'eliminar' && isset($_GET['id'])) {
    $id_pedido = intval($_GET['id']);
    
    // Verificar si el pedido existe
    $stmt = $conexion->prepare("SELECT ID_PEDIDO FROM pedido WHERE ID_PEDIDO = ?");
    $stmt->bind_param("i", $id_pedido);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Eliminar detalles del pedido primero
        $stmt_detalle = $conexion->prepare("DELETE FROM pedido_detalle WHERE ID_PEDIDO = ?");
        $stmt_detalle->bind_param("i", $id_pedido);
        $stmt_detalle->execute();
        
        // Eliminar el pedido
        $stmt_pedido = $conexion->prepare("DELETE FROM pedido WHERE ID_PEDIDO = ?");
        $stmt_pedido->bind_param("i", $id_pedido);
        
        if ($stmt_pedido->execute()) {
            $mensaje = "✅ Pedido eliminado correctamente";
        } else {
            $mensaje = "❌ Error al eliminar el pedido";
        }
    } else {
        $mensaje = "❌ Pedido no encontrado";
    }
}

// Obtener lista de pedidos
$query = "SELECT 
            p.ID_PEDIDO,
            p.METODO_PAGO,
            p.FECHA_PEDIDO,
            p.TOTAL,
            p.ESTADO,
            p.FECHA_ENTREGA,
            p.OBSERVACIONES,
            c.NOMBRE as CLIENTE_NOMBRE,
            c.APELLIDOS as CLIENTE_APELLIDOS,
            c.TELEFONO as CLIENTE_TELEFONO,
            u.NOMBRES as USUARIO_NOMBRES,
            u.APELLIDOS as USUARIO_APELLIDOS,
            (SELECT COUNT(*) FROM pedido_detalle pd WHERE pd.ID_PEDIDO = p.ID_PEDIDO) as TOTAL_PRODUCTOS
          FROM pedido p
          INNER JOIN cliente c ON p.ID_CLIENTE = c.ID_CLIENTE
          INNER JOIN usuario u ON p.ID_USUARIO = u.ID_USUARIO
          ORDER BY p.FECHA_PEDIDO DESC, p.ID_PEDIDO DESC";

$result = $conexion->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pedidos - Administrador</title>
    <link rel="stylesheet" href="../../style.css">
    <style>
        .admin-container {
            max-width: 1400px;
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
            font-weight: bold;
            color: #495057;
        }
        tr:hover {
            background: #f8f9fa;
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
        .total-pedido {
            font-weight: bold;
            color: #28a745;
        }
        .cliente-info {
            font-weight: bold;
            color: #495057;
        }
        .fecha-info {
            color: #6c757d;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>📋 Gestión de Pedidos</h1>
            <div>
                <a href="registrar.php" class="btn btn-primary">➕ Nuevo Pedido</a>
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
                📋 Lista de Pedidos
            </h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Fecha Pedido</th>
                        <th>Entrega</th>
                        <th>Método Pago</th>
                        <th>Estado</th>
                        <th>Total</th>
                        <th>Productos</th>
                        <th>Usuario</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($pedido = $result->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?php echo $pedido['ID_PEDIDO']; ?></strong></td>
                                <td>
                                    <div class="cliente-info">
                                        <?php echo htmlspecialchars($pedido['CLIENTE_NOMBRE'] . ' ' . $pedido['CLIENTE_APELLIDOS']); ?>
                                    </div>
                                    <div class="fecha-info">
                                        📞 <?php echo htmlspecialchars($pedido['CLIENTE_TELEFONO']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="fecha-info">
                                        📅 <?php echo date('d/m/Y', strtotime($pedido['FECHA_PEDIDO'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="fecha-info">
                                        🕒 <?php echo date('d/m/Y H:i', strtotime($pedido['FECHA_ENTREGA'])); ?>
                                    </div>
                                </td>
                                <td>
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
                                </td>
                                <td>
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
                                </td>
                                <td>
                                    <span class="total-pedido">
                                        S/ <?php echo number_format($pedido['TOTAL'], 2); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="fecha-info">
                                        📦 <?php echo $pedido['TOTAL_PRODUCTOS']; ?> productos
                                    </span>
                                </td>
                                <td>
                                    <div class="fecha-info">
                                        👤 <?php echo htmlspecialchars($pedido['USUARIO_NOMBRES'] . ' ' . $pedido['USUARIO_APELLIDOS']); ?>
                                    </div>
                                </td>
                                <td>
                                    <a href="visualizar.php?id=<?php echo $pedido['ID_PEDIDO']; ?>" class="btn btn-success">👁️ Ver</a>
                                    <a href="editar.php?id=<?php echo $pedido['ID_PEDIDO']; ?>" class="btn btn-warning">✏️ Editar</a>
                                    <a href="index.php?action=eliminar&id=<?php echo $pedido['ID_PEDIDO']; ?>" 
                                       class="btn btn-danger" 
                                       onclick="return confirm('¿Estás seguro de que quieres eliminar este pedido? Esta acción no se puede deshacer.')">
                                        🗑️ Eliminar
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 40px; color: #666;">
                                <p>No hay pedidos registrados.</p>
                                <p>Haz clic en "Nuevo Pedido" para crear el primero.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
