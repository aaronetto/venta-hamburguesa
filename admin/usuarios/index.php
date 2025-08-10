<?php
session_start();
require_once '../../config.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../login_registro.php");
    exit();
}

$conexion = getConnection();
$mensaje = '';

// Procesar mensajes de URL
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'creado':
            $mensaje = "✅ Usuario creado exitosamente";
            break;
        case 'actualizado':
            $mensaje = "✅ Usuario actualizado exitosamente";
            break;
        case 'eliminado':
            $mensaje = "✅ Usuario eliminado exitosamente";
            break;
        case 'activado':
            $mensaje = "✅ Usuario activado exitosamente";
            break;
    }
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'id_requerido':
            $mensaje = "❌ Error: ID de usuario requerido";
            break;
        case 'usuario_no_existe':
            $mensaje = "❌ Error: El usuario no existe";
            break;
        case 'usuario_con_pedidos':
            $mensaje = "❌ Error: No se puede eliminar el usuario porque tiene pedidos asociados";
            break;
        case 'error_eliminacion':
            $mensaje = "❌ Error al eliminar el usuario";
            break;
        case 'error_activacion':
            $mensaje = "❌ Error al activar el usuario";
            break;
        case 'email_duplicado':
            $mensaje = "❌ Error: El correo electrónico ya está registrado";
            break;
    }
}

// Obtener usuarios activos
$query = "SELECT ID_USUARIO, NOMBRES, APELLIDOS, CORREO, ROL, ACTIVO FROM usuario ORDER BY NOMBRES, APELLIDOS";
$result = $conexion->query($query);

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Administración</title>
    <link rel="stylesheet" href="../../style.css">
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
            margin-right: 10px;
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
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .rol-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .rol-administrador {
            background: #e53e2e;
            color: white;
        }
        
        .rol-gerente {
            background: #ffc107;
            color: #212529;
        }
        
        .rol-asistente {
            background: #17a2b8;
            color: white;
        }
        
        .actions {
            display: flex;
            gap: 5px;
        }
        
        .table-header {
            padding: 20px;
            margin: 0;
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>👥 Gestión de Usuarios</h1>
            <a href="../../plataforma.php" class="btn btn-secondary">← Volver al Panel</a>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="mensaje <?php echo strpos($mensaje, '✅') !== false ? 'success' : 'error'; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <!-- Tabla de usuarios -->
        <div class="table-container">
            <div class="table-header">
                <h3 style="margin: 0;">📋 Lista de Usuarios</h3>
                <a href="registrar.php" class="btn btn-primary">➕ Nuevo Usuario</a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombres</th>
                        <th>Apellidos</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['ID_USUARIO']; ?></td>
                                <td><?php echo htmlspecialchars($row['NOMBRES']); ?></td>
                                <td><?php echo htmlspecialchars($row['APELLIDOS']); ?></td>
                                <td><?php echo htmlspecialchars($row['CORREO']); ?></td>
                                <td>
                                    <span class="rol-badge rol-<?php echo strtolower($row['ROL']); ?>">
                                        <?php echo $row['ROL']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['ACTIVO'] == 1): ?>
                                        <span class="status-badge status-active">Activo</span>
                                    <?php else: ?>
                                        <span class="status-badge status-inactive">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a href="editar.php?id=<?php echo $row['ID_USUARIO']; ?>" class="btn btn-warning">
                                            ✏️ Editar
                                        </a>
                                        <?php if ($row['ACTIVO'] == 1): ?>
                                            <button class="btn btn-danger" onclick="eliminarUsuario(<?php echo $row['ID_USUARIO']; ?>, '<?php echo htmlspecialchars($row['NOMBRES'] . ' ' . $row['APELLIDOS']); ?>')">
                                                🗑️ Eliminar
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-success" onclick="activarUsuario(<?php echo $row['ID_USUARIO']; ?>, '<?php echo htmlspecialchars($row['NOMBRES'] . ' ' . $row['APELLIDOS']); ?>')">
                                                ✅ Activar
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: #666;">No hay usuarios registrados</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function eliminarUsuario(id, nombre) {
            if (confirm(`¿Estás seguro de que deseas eliminar el usuario "${nombre}"?\n\nEsta acción marcará al usuario como inactivo.`)) {
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

        function activarUsuario(id, nombre) {
            if (confirm(`¿Estás seguro de que deseas activar el usuario "${nombre}"?`)) {
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
