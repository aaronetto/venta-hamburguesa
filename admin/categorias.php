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
        case 'eliminada':
            $mensaje = "✅ Categoría '" . htmlspecialchars($_GET['nombre']) . "' eliminada exitosamente";
            break;
    }
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'id_requerido':
            $mensaje = "❌ Error: ID de categoría requerido";
            break;
        case 'categoria_no_existe':
            $mensaje = "❌ Error: La categoría no existe";
            break;
        case 'categoria_con_productos':
            $mensaje = "❌ Error: No se puede eliminar la categoría porque tiene " . $_GET['productos'] . " productos asociados";
            break;
        case 'error_eliminacion':
            $mensaje = "❌ Error al eliminar la categoría";
            break;
    }
}

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['accion'])) {
        switch ($_POST['accion']) {
            case 'crear':
                $nombre = trim($_POST['nombre']);
                if (!empty($nombre)) {
                    $stmt = $conexion->prepare("INSERT INTO categoria (NOMB_CATEGORIA) VALUES (?)");
                    $stmt->bind_param("s", $nombre);
                    if ($stmt->execute()) {
                        $mensaje = "✅ Categoría creada exitosamente";
                    } else {
                        $mensaje = "❌ Error al crear la categoría";
                    }
                    $stmt->close();
                }
                break;
                
            case 'actualizar':
                $id = $_POST['id'];
                $nombre = trim($_POST['nombre']);
                if (!empty($nombre)) {
                    $stmt = $conexion->prepare("UPDATE categoria SET NOMB_CATEGORIA = ? WHERE ID_CATEGORIA = ?");
                    $stmt->bind_param("si", $nombre, $id);
                    if ($stmt->execute()) {
                        $mensaje = "✅ Categoría actualizada exitosamente";
                    } else {
                        $mensaje = "❌ Error al actualizar la categoría";
                    }
                    $stmt->close();
                }
                break;
        }
    }
}

// Obtener categorías
$query = "SELECT * FROM categoria ORDER BY NOMB_CATEGORIA";
$result = $conexion->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Categorías</title>
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
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>📂 Gestión de Categorías</h1>
            <a href="../plataforma.php" class="btn btn-secondary">← Volver al Panel</a>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="mensaje <?php echo strpos($mensaje, '✅') !== false ? 'success' : 'error'; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <!-- Formulario para crear categoría -->
        <div class="form-container">
            <h3>➕ Crear Nueva Categoría</h3>
            <form method="POST">
                <input type="hidden" name="accion" value="crear">
                <div class="form-group">
                    <label for="nombre">Nombre de la Categoría:</label>
                    <input type="text" id="nombre" name="nombre" required maxlength="50">
                </div>
                <button type="submit" class="btn btn-primary">Crear Categoría</button>
            </form>
        </div>

        <!-- Tabla de categorías -->
        <div class="table-container">
            <h3 style="padding: 20px; margin: 0; background: #f8f9fa; border-bottom: 1px solid #ddd;">
                📋 Lista de Categorías
            </h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['ID_CATEGORIA']; ?></td>
                                <td><?php echo htmlspecialchars($row['NOMB_CATEGORIA']); ?></td>
                                <td>
                                    <button class="btn btn-warning" onclick="editarCategoria(<?php echo $row['ID_CATEGORIA']; ?>, '<?php echo htmlspecialchars($row['NOMB_CATEGORIA']); ?>')">
                                        ✏️ Editar
                                    </button>
                                    <button class="btn btn-danger" onclick="eliminarCategoria(<?php echo $row['ID_CATEGORIA']; ?>, '<?php echo htmlspecialchars($row['NOMB_CATEGORIA']); ?>')">
                                        🗑️ Eliminar
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="text-align: center; color: #666;">No hay categorías registradas</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal para editar categoría -->
    <div id="modalEditar" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>✏️ Editar Categoría</h3>
                <span class="close" onclick="cerrarModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="accion" value="actualizar">
                <input type="hidden" id="edit_id" name="id">
                <div class="form-group">
                    <label for="edit_nombre">Nombre de la Categoría:</label>
                    <input type="text" id="edit_nombre" name="nombre" required maxlength="50">
                </div>
                <button type="submit" class="btn btn-primary">Actualizar Categoría</button>
                <button type="button" class="btn btn-secondary" onclick="cerrarModal()">Cancelar</button>
            </form>
        </div>
    </div>

    <script>
        function editarCategoria(id, nombre) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nombre').value = nombre;
            document.getElementById('modalEditar').style.display = 'block';
        }

        function cerrarModal() {
            document.getElementById('modalEditar').style.display = 'none';
        }

        function eliminarCategoria(id, nombre) {
            if (confirm(`¿Estás seguro de que deseas eliminar la categoría "${nombre}"?\n\nEsta acción no se puede deshacer.`)) {
                // Crear un formulario temporal para enviar la solicitud de eliminación
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'eliminar_categoria.php';
                
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
