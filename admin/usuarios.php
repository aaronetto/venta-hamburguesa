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
            $mensaje = "✅ Usuario creado exitosamente";
            break;
        case 'actualizado':
            $mensaje = "✅ Usuario actualizado exitosamente";
            break;
        case 'eliminado':
            $mensaje = "✅ Usuario eliminado exitosamente";
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
        case 'email_duplicado':
            $mensaje = "❌ Error: El correo electrónico ya está registrado";
            break;
    }
}

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['accion'])) {
        switch ($_POST['accion']) {
            case 'crear':
                $nombre = trim($_POST['nombre']);
                $correo = trim($_POST['correo']);
                $clave = $_POST['clave'];
                
                if (!empty($nombre) && !empty($correo) && !empty($clave)) {
                    // Verificar si el correo ya existe
                    $stmt = $conexion->prepare("SELECT ID_USUARIO FROM usuario WHERE CORREO = ?");
                    $stmt->bind_param("s", $correo);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $mensaje = "❌ El correo electrónico ya está registrado";
                    } else {
                        // Encriptar contraseña
                        $clave_hash = password_hash($clave, PASSWORD_DEFAULT);
                        
                        $stmt = $conexion->prepare("INSERT INTO usuario (NOMB_USUARIO, CORREO, CLAVE) VALUES (?, ?, ?)");
                        $stmt->bind_param("sss", $nombre, $correo, $clave_hash);
                        if ($stmt->execute()) {
                            header("Location: usuarios.php?success=creado");
                            exit();
                        } else {
                            $mensaje = "❌ Error al crear el usuario";
                        }
                    }
                    $stmt->close();
                } else {
                    $mensaje = "❌ Todos los campos son requeridos";
                }
                break;
                
            case 'actualizar':
                $id = $_POST['id'];
                $nombre = trim($_POST['nombre']);
                $correo = trim($_POST['correo']);
                $clave = $_POST['clave'];
                
                if (!empty($nombre) && !empty($correo)) {
                    // Verificar si el correo ya existe en otro usuario
                    $stmt = $conexion->prepare("SELECT ID_USUARIO FROM usuario WHERE CORREO = ? AND ID_USUARIO != ?");
                    $stmt->bind_param("si", $correo, $id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $mensaje = "❌ El correo electrónico ya está registrado por otro usuario";
                    } else {
                        if (!empty($clave)) {
                            // Actualizar con nueva contraseña
                            $clave_hash = password_hash($clave, PASSWORD_DEFAULT);
                            $stmt = $conexion->prepare("UPDATE usuario SET NOMB_USUARIO = ?, CORREO = ?, CLAVE = ? WHERE ID_USUARIO = ?");
                            $stmt->bind_param("sssi", $nombre, $correo, $clave_hash, $id);
                        } else {
                            // Actualizar sin cambiar contraseña
                            $stmt = $conexion->prepare("UPDATE usuario SET NOMB_USUARIO = ?, CORREO = ? WHERE ID_USUARIO = ?");
                            $stmt->bind_param("ssi", $nombre, $correo, $id);
                        }
                        
                        if ($stmt->execute()) {
                            header("Location: usuarios.php?success=actualizado");
                            exit();
                        } else {
                            $mensaje = "❌ Error al actualizar el usuario";
                        }
                    }
                    $stmt->close();
                } else {
                    $mensaje = "❌ Nombre y correo son requeridos";
                }
                break;
        }
    }
}

// Obtener usuarios
$query = "SELECT * FROM usuario ORDER BY NOMB_USUARIO";
$result = $conexion->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios</title>
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
        
        .form-group input {
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
        
        .clave-info {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>👥 Gestión de Usuarios</h1>
            <a href="../plataforma.php" class="btn btn-secondary">← Volver al Panel</a>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="mensaje <?php echo strpos($mensaje, '✅') !== false ? 'success' : 'error'; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <!-- Formulario para crear usuario -->
        <div class="form-container">
            <h3>➕ Crear Nuevo Usuario</h3>
            <form method="POST">
                <input type="hidden" name="accion" value="crear">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre">Nombre Completo:</label>
                        <input type="text" id="nombre" name="nombre" required maxlength="100">
                    </div>
                    <div class="form-group">
                        <label for="correo">Correo Electrónico:</label>
                        <input type="email" id="correo" name="correo" required maxlength="100">
                    </div>
                </div>
                <div class="form-group">
                    <label for="clave">Contraseña:</label>
                    <input type="password" id="clave" name="clave" required minlength="6">
                    <div class="clave-info">La contraseña debe tener al menos 6 caracteres</div>
                </div>
                <button type="submit" class="btn btn-primary">Crear Usuario</button>
            </form>
        </div>

        <!-- Tabla de usuarios -->
        <div class="table-container">
            <h3 style="padding: 20px; margin: 0; background: #f8f9fa; border-bottom: 1px solid #ddd;">
                📋 Lista de Usuarios
            </h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['ID_USUARIO']; ?></td>
                                <td><?php echo htmlspecialchars($row['NOMB_USUARIO']); ?></td>
                                <td><?php echo htmlspecialchars($row['CORREO']); ?></td>
                                <td>
                                    <button class="btn btn-warning" onclick="editarUsuario(<?php echo $row['ID_USUARIO']; ?>, '<?php echo htmlspecialchars($row['NOMB_USUARIO']); ?>', '<?php echo htmlspecialchars($row['CORREO']); ?>')">
                                        ✏️ Editar
                                    </button>
                                    <button class="btn btn-danger" onclick="eliminarUsuario(<?php echo $row['ID_USUARIO']; ?>, '<?php echo htmlspecialchars($row['NOMB_USUARIO']); ?>')">
                                        🗑️ Eliminar
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #666;">No hay usuarios registrados</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal para editar usuario -->
    <div id="modalEditar" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>✏️ Editar Usuario</h3>
                <span class="close" onclick="cerrarModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="accion" value="actualizar">
                <input type="hidden" id="edit_id" name="id">
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_nombre">Nombre Completo:</label>
                        <input type="text" id="edit_nombre" name="nombre" required maxlength="100">
                    </div>
                    <div class="form-group">
                        <label for="edit_correo">Correo Electrónico:</label>
                        <input type="email" id="edit_correo" name="correo" required maxlength="100">
                    </div>
                </div>
                <div class="form-group">
                    <label for="edit_clave">Nueva Contraseña (dejar vacío para no cambiar):</label>
                    <input type="password" id="edit_clave" name="clave" minlength="6">
                    <div class="clave-info">Deja vacío para mantener la contraseña actual</div>
                </div>
                <button type="submit" class="btn btn-primary">Actualizar Usuario</button>
                <button type="button" class="btn btn-secondary" onclick="cerrarModal()">Cancelar</button>
            </form>
        </div>
    </div>

    <script>
        function editarUsuario(id, nombre, correo) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nombre').value = nombre;
            document.getElementById('edit_correo').value = correo;
            document.getElementById('edit_clave').value = '';
            document.getElementById('modalEditar').style.display = 'block';
        }

        function cerrarModal() {
            document.getElementById('modalEditar').style.display = 'none';
        }

        function eliminarUsuario(id, nombre) {
            if (confirm(`¿Estás seguro de que deseas eliminar el usuario "${nombre}"?\n\nEsta acción no se puede deshacer.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'eliminar_usuario.php';
                
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
