<?php
session_start();
require_once '../../config.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login_registro.php");
    exit();
}

$conexion = getConnection();
$mensaje = '';
$error = '';

// Verificar si se proporciona un ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?error=id_invalido");
    exit();
}

$id_cliente = $_GET['id'];

// Obtener datos del cliente
$stmt = $conexion->prepare("SELECT * FROM cliente WHERE ID_CLIENTE = ?");
$stmt->bind_param("i", $id_cliente);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows == 0) {
    header("Location: index.php?error=cliente_no_encontrado");
    exit();
}

$cliente = $resultado->fetch_assoc();

// Procesar mensajes de URL
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'direccion_registrada':
            $mensaje = "✅ Dirección registrada exitosamente";
            break;
        case 'direccion_actualizada':
            $mensaje = "✅ Dirección actualizada exitosamente";
            break;
        case 'direccion_eliminada':
            $mensaje = "✅ Dirección eliminada exitosamente";
            break;
    }
} elseif (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'error_eliminar_direccion':
            $error = "❌ Error al eliminar la dirección";
            break;
        case 'error_registrar_direccion':
            $error = "❌ Error al registrar la dirección";
            break;
        case 'error_actualizar_direccion':
            $error = "❌ Error al actualizar la dirección";
            break;
    }
}

// Procesar acciones de direcciones
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'eliminar_direccion':
            if (isset($_GET['direccion_id']) && is_numeric($_GET['direccion_id'])) {
                $direccion_id = $_GET['direccion_id'];
                $stmt = $conexion->prepare("DELETE FROM direccion_cliente WHERE ID_DIRECCION_CLIENTE = ? AND ID_CLIENTE = ?");
                $stmt->bind_param("ii", $direccion_id, $id_cliente);
                if ($stmt->execute()) {
                    header("Location: visualizar.php?id=$id_cliente&success=direccion_eliminada");
                } else {
                    header("Location: visualizar.php?id=$id_cliente&error=error_eliminar_direccion");
                }
                exit();
            }
            break;
    }
}

// Procesar formulario de nueva dirección
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'nueva_direccion') {
    $calle = trim($_POST['calle'] ?? '');
    $numero = trim($_POST['numero'] ?? '');
    $id_distrito = $_POST['id_distrito'] ?? '';
    
    if (empty($calle) || empty($numero) || empty($id_distrito)) {
        $error = "Todos los campos son obligatorios";
    } elseif (strlen($calle) > 45) {
        $error = "La calle no puede tener más de 45 caracteres";
    } elseif (strlen($numero) > 45) {
        $error = "El número no puede tener más de 45 caracteres";
    } elseif (!is_numeric($id_distrito)) {
        $error = "Debe seleccionar un distrito válido";
    } else {
        $stmt = $conexion->prepare("INSERT INTO direccion_cliente (CALLE, NUMERO, ID_DISTRITO, ID_CLIENTE) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssii", $calle, $numero, $id_distrito, $id_cliente);
        
        if ($stmt->execute()) {
            header("Location: visualizar.php?id=$id_cliente&success=direccion_registrada");
            exit();
        } else {
            $error = "Error al registrar la dirección: " . $conexion->error;
        }
    }
}

// Procesar formulario de editar dirección
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'editar_direccion') {
    $direccion_id = $_POST['direccion_id'] ?? '';
    $calle = trim($_POST['calle'] ?? '');
    $numero = trim($_POST['numero'] ?? '');
    $id_distrito = $_POST['id_distrito'] ?? '';
    
    if (empty($calle) || empty($numero) || empty($id_distrito) || empty($direccion_id)) {
        $error = "Todos los campos son obligatorios";
    } elseif (strlen($calle) > 45) {
        $error = "La calle no puede tener más de 45 caracteres";
    } elseif (strlen($numero) > 45) {
        $error = "El número no puede tener más de 45 caracteres";
    } elseif (!is_numeric($id_distrito) || !is_numeric($direccion_id)) {
        $error = "Datos de distrito o dirección inválidos";
    } else {
        $stmt = $conexion->prepare("UPDATE direccion_cliente SET CALLE = ?, NUMERO = ?, ID_DISTRITO = ? WHERE ID_DIRECCION_CLIENTE = ? AND ID_CLIENTE = ?");
        $stmt->bind_param("ssiii", $calle, $numero, $id_distrito, $direccion_id, $id_cliente);
        
        if ($stmt->execute()) {
            header("Location: visualizar.php?id=$id_cliente&success=direccion_actualizada");
            exit();
        } else {
            $error = "Error al actualizar la dirección: " . $conexion->error;
        }
    }
}

// Obtener direcciones del cliente
$query = "SELECT dc.*, d.NOMBRE as DISTRITO_NOMBRE, p.NOMBRE as PROVINCIA_NOMBRE, c.NOMBRE as CIUDAD_NOMBRE
          FROM direccion_cliente dc
          INNER JOIN distrito d ON dc.ID_DISTRITO = d.ID_DISTRITO
          INNER JOIN provincia p ON d.ID_PROVINCIA = p.ID_PROVINCIA
          INNER JOIN ciudad c ON p.ID_CIUDAD = c.ID_CIUDAD
          WHERE dc.ID_CLIENTE = ?
          ORDER BY dc.ID_DIRECCION_CLIENTE";
$stmt = $conexion->prepare($query);
$stmt->bind_param("i", $id_cliente);
$stmt->execute();
$direcciones_result = $stmt->get_result();

// Obtener distritos para el formulario
$distritos_query = "SELECT d.ID_DISTRITO, d.NOMBRE as DISTRITO_NOMBRE, p.NOMBRE as PROVINCIA_NOMBRE, c.NOMBRE as CIUDAD_NOMBRE
                    FROM distrito d
                    INNER JOIN provincia p ON d.ID_PROVINCIA = p.ID_PROVINCIA
                    INNER JOIN ciudad c ON p.ID_CIUDAD = c.ID_CIUDAD
                    ORDER BY c.NOMBRE, p.NOMBRE, d.NOMBRE";
$distritos_result = $conexion->query($distritos_query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Cliente</title>
    <link rel="stylesheet" href="../../style.css">
    <style>
        .admin-container {
            max-width: 1200px;
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
        
        .info-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .info-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .info-body {
            padding: 30px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .info-value {
            color: #212529;
            font-size: 16px;
        }
        
        .direcciones-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .direcciones-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .direcciones-body {
            padding: 30px;
        }
        
        .direccion-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #17a2b8;
        }
        
        .direccion-info {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 15px;
            align-items: center;
        }
        
        .direccion-text {
            font-weight: 600;
            color: #495057;
        }
        
        .direccion-location {
            color: #6c757d;
            font-size: 14px;
        }
        
        .direccion-actions {
            display: flex;
            gap: 10px;
        }
        
        .form-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .form-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #ddd;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .form-body {
            padding: 30px;
            display: none;
        }
        
        .form-body.active {
            display: block;
        }
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .form-section h3 {
            color: #495057;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #17a2b8;
            box-shadow: 0 0 0 2px rgba(23, 162, 184, 0.25);
        }
        
        .required {
            color: #dc3545;
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
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .form-row, .info-grid, .direccion-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>👁️ Visualizar Cliente</h1>
            <div>
                <a href="editar.php?id=<?php echo $id_cliente; ?>" class="btn btn-warning">✏️ Editar Cliente</a>
                <a href="index.php" class="btn btn-secondary">← Volver a Clientes</a>
            </div>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="mensaje success">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="mensaje error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Información del Cliente -->
        <div class="info-container">
            <div class="info-header">
                <h3>👤 Información del Cliente</h3>
            </div>
            <div class="info-body">
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Nombre Completo</span>
                        <span class="info-value"><?php echo htmlspecialchars($cliente['NOMBRE'] . ' ' . $cliente['APELLIDOS']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Correo Electrónico</span>
                        <span class="info-value"><?php echo htmlspecialchars($cliente['CORREO']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Teléfono</span>
                        <span class="info-value"><?php echo htmlspecialchars($cliente['TELEFONO']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Dirección Principal</span>
                        <span class="info-value"><?php echo htmlspecialchars($cliente['DIRECCION'] ?: 'No especificada'); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gestión de Direcciones -->
        <div class="direcciones-container">
            <div class="direcciones-header">
                <h3>📍 Direcciones del Cliente</h3>
                <button class="btn btn-primary" onclick="toggleForm()">➕ Nueva Dirección</button>
            </div>
            
            <!-- Formulario para nueva dirección -->
            <div class="form-container">
                <div class="form-header" onclick="toggleForm()">
                    <h3>📝 Agregar Nueva Dirección</h3>
                    <span id="toggle-icon">▼</span>
                </div>
                <div class="form-body" id="nueva-direccion-form">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="nueva_direccion">
                        
                        <div class="form-section">
                            <h3>📍 Información de la Dirección</h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="calle">Calle <span class="required">*</span></label>
                                    <input type="text" id="calle" name="calle" 
                                           value="<?php echo htmlspecialchars($_POST['calle'] ?? ''); ?>" 
                                           maxlength="45" required>
                                </div>

                                <div class="form-group">
                                    <label for="numero">Número <span class="required">*</span></label>
                                    <input type="text" id="numero" name="numero" 
                                           value="<?php echo htmlspecialchars($_POST['numero'] ?? ''); ?>" 
                                           maxlength="45" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="id_distrito">Distrito <span class="required">*</span></label>
                                <select id="id_distrito" name="id_distrito" required>
                                    <option value="">Seleccione un distrito</option>
                                    <?php 
                                    $distritos_result->data_seek(0);
                                    while ($distrito = $distritos_result->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $distrito['ID_DISTRITO']; ?>" 
                                                <?php echo ($_POST['id_distrito'] ?? '') == $distrito['ID_DISTRITO'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($distrito['CIUDAD_NOMBRE'] . ' > ' . $distrito['PROVINCIA_NOMBRE'] . ' > ' . $distrito['DISTRITO_NOMBRE']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" onclick="toggleForm()">Cancelar</button>
                            <button type="submit" class="btn btn-primary">✅ Registrar Dirección</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="direcciones-body">
                <?php if ($direcciones_result->num_rows > 0): ?>
                    <?php while ($direccion = $direcciones_result->fetch_assoc()): ?>
                        <div class="direccion-card">
                            <div class="direccion-info">
                                <div>
                                    <div class="direccion-text">
                                        <?php echo htmlspecialchars($direccion['CALLE'] . ' ' . $direccion['NUMERO']); ?>
                                    </div>
                                </div>
                                <div class="direccion-location">
                                    📍 <?php echo htmlspecialchars($direccion['CIUDAD_NOMBRE'] . ' > ' . $direccion['PROVINCIA_NOMBRE'] . ' > ' . $direccion['DISTRITO_NOMBRE']); ?>
                                </div>
                                <div class="direccion-actions">
                                    <button class="btn btn-warning" onclick="editarDireccion(<?php echo $direccion['ID_DIRECCION_CLIENTE']; ?>, '<?php echo htmlspecialchars($direccion['CALLE']); ?>', '<?php echo htmlspecialchars($direccion['NUMERO']); ?>', <?php echo $direccion['ID_DISTRITO']; ?>)">
                                        ✏️ Editar
                                    </button>
                                    <a href="visualizar.php?id=<?php echo $id_cliente; ?>&action=eliminar_direccion&direccion_id=<?php echo $direccion['ID_DIRECCION_CLIENTE']; ?>" 
                                       class="btn btn-danger" 
                                       onclick="return confirm('¿Estás seguro de que quieres eliminar esta dirección?')">
                                        🗑️ Eliminar
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align: center; color: #666; padding: 40px;">
                        <p>No hay direcciones registradas para este cliente.</p>
                        <p>Haz clic en "Nueva Dirección" para agregar la primera.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleForm() {
            const form = document.getElementById('nueva-direccion-form');
            const icon = document.getElementById('toggle-icon');
            
            if (form.classList.contains('active')) {
                form.classList.remove('active');
                icon.textContent = '▼';
            } else {
                form.classList.add('active');
                icon.textContent = '▲';
            }
        }

        function editarDireccion(id, calle, numero, distritoId) {
            const form = document.getElementById('nueva-direccion-form');
            const icon = document.getElementById('toggle-icon');
            
            // Cambiar el título del formulario
            document.querySelector('.form-header h3').textContent = '✏️ Editar Dirección';
            
            // Cambiar la acción del formulario
            const actionInput = form.querySelector('input[name="action"]');
            actionInput.value = 'editar_direccion';
            
            // Agregar campo oculto para el ID de la dirección
            let hiddenInput = form.querySelector('input[name="direccion_id"]');
            if (!hiddenInput) {
                hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'direccion_id';
                form.querySelector('form').appendChild(hiddenInput);
            }
            hiddenInput.value = id;
            
            // Llenar los campos con los datos actuales
            document.getElementById('calle').value = calle;
            document.getElementById('numero').value = numero;
            document.getElementById('id_distrito').value = distritoId;
            
            // Cambiar el texto del botón
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.textContent = '✅ Actualizar Dirección';
            
            // Mostrar el formulario
            form.classList.add('active');
            icon.textContent = '▲';
            
            // Scroll al formulario
            form.scrollIntoView({ behavior: 'smooth' });
        }
    </script>
</body>
</html>

<?php
$conexion->close();
?>
