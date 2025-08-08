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
            $mensaje = "✅ Pedido creado exitosamente";
            break;
        case 'actualizado':
            $mensaje = "✅ Pedido actualizado exitosamente";
            break;
        case 'eliminado':
            $mensaje = "✅ Pedido eliminado exitosamente";
            break;
    }
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'id_requerido':
            $mensaje = "❌ Error: ID de pedido requerido";
            break;
        case 'pedido_no_existe':
            $mensaje = "❌ Error: El pedido no existe";
            break;
        case 'error_eliminacion':
            $mensaje = "❌ Error al eliminar el pedido";
            break;
    }
}

// Obtener pedidos con información de usuario
$query = "SELECT p.*, u.NOMB_USUARIO 
          FROM pedido p 
          INNER JOIN usuario u ON p.ID_USUARIO = u.ID_USUARIO 
          ORDER BY p.FECHA_PEDIDO DESC, p.ID_PEDIDO DESC";
$result = $conexion->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pedidos</title>
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
        }
        
        .fecha {
            color: #666;
            font-size: 14px;
        }
        
        .usuario {
            font-weight: 600;
            color: #333;
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
            margin: 5% auto;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
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
        
        .detalle-table {
            width: 100%;
            margin-top: 15px;
        }
        
        .detalle-table th {
            background: #f8f9fa;
            padding: 10px;
            font-size: 14px;
        }
        
        .detalle-table td {
            padding: 10px;
            font-size: 14px;
        }
        
        .info-pedido {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .info-label {
            font-weight: 600;
            color: #333;
        }
        
        .info-value {
            color: #666;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>🛒 Gestión de Pedidos</h1>
            <div>
                <a href="crear_pedido.php" class="btn btn-primary">➕ Crear Pedido</a>
                <a href="../plataforma.php" class="btn btn-secondary">← Volver al Panel</a>
            </div>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="mensaje <?php echo strpos($mensaje, '✅') !== false ? 'success' : 'error'; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <!-- Tabla de pedidos -->
        <div class="table-container">
            <h3 style="padding: 20px; margin: 0; background: #f8f9fa; border-bottom: 1px solid #ddd;">
                📋 Lista de Pedidos
            </h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Total</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['ID_PEDIDO']; ?></td>
                                <td class="fecha"><?php echo date('d/m/Y', strtotime($row['FECHA_PEDIDO'])); ?></td>
                                <td class="usuario"><?php echo htmlspecialchars($row['NOMB_USUARIO']); ?></td>
                                <td class="total">S/ <?php echo number_format($row['TOTAL'], 2); ?></td>
                                <td>
                                    <button class="btn btn-info" onclick="verDetalle(<?php echo $row['ID_PEDIDO']; ?>)">
                                        👁️ Ver Detalle
                                    </button>
                                    <a href="editar_pedido.php?id=<?php echo $row['ID_PEDIDO']; ?>" class="btn btn-warning">
                                        ✏️ Editar
                                    </a>
                                    <button class="btn btn-danger" onclick="eliminarPedido(<?php echo $row['ID_PEDIDO']; ?>)">
                                        🗑️ Eliminar
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #666;">No hay pedidos registrados</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal para ver detalle del pedido -->
    <div id="modalDetalle" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>📋 Detalle del Pedido</h3>
                <span class="close" onclick="cerrarModal()">&times;</span>
            </div>
            <div id="detalleContenido">
                <!-- El contenido se cargará dinámicamente -->
            </div>
        </div>
    </div>

    <script>
        function verDetalle(idPedido) {
            // Cargar el detalle del pedido mediante AJAX
            fetch('obtener_detalle_pedido.php?id=' + idPedido)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('detalleContenido').innerHTML = data;
                    document.getElementById('modalDetalle').style.display = 'block';
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar el detalle del pedido');
                });
        }

        function cerrarModal() {
            document.getElementById('modalDetalle').style.display = 'none';
        }

        function eliminarPedido(id) {
            if (confirm('¿Estás seguro de que deseas eliminar este pedido?\n\nEsta acción no se puede deshacer.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'eliminar_pedido.php';
                
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
            const modal = document.getElementById('modalDetalle');
            if (event.target == modal) {
                cerrarModal();
            }
        }
    </script>
</body>
</html>
