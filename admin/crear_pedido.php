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

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['accion'])) {
        switch ($_POST['accion']) {
            case 'crear_pedido':
                $usuario_id = $_POST['usuario'];
                $fecha = $_POST['fecha'];
                
                if (!empty($usuario_id) && !empty($fecha)) {
                    // Crear el pedido
                    $stmt = $conexion->prepare("INSERT INTO pedido (FECHA_PEDIDO, TOTAL, ID_USUARIO) VALUES (?, 0, ?)");
                    $stmt->bind_param("si", $fecha, $usuario_id);
                    
                    if ($stmt->execute()) {
                        $pedido_id = $conexion->insert_id;
                        header("Location: editar_pedido.php?id=" . $pedido_id . "&success=creado");
                        exit();
                    } else {
                        $mensaje = "❌ Error al crear el pedido";
                    }
                    $stmt->close();
                } else {
                    $mensaje = "❌ Todos los campos son requeridos";
                }
                break;
        }
    }
}

// Obtener usuarios para el formulario
$query_usuarios = "SELECT * FROM usuario ORDER BY NOMB_USUARIO";
$result_usuarios = $conexion->query($query_usuarios);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Pedido</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .admin-container {
            max-width: 800px;
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
        
        .info-box {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .info-box h4 {
            margin: 0 0 10px 0;
            color: #1976d2;
        }
        
        .info-box p {
            margin: 0;
            color: #424242;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>🛒 Crear Nuevo Pedido</h1>
            <a href="pedidos.php" class="btn btn-secondary">← Volver a Pedidos</a>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="mensaje <?php echo strpos($mensaje, '✅') !== false ? 'success' : 'error'; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <div class="info-box">
            <h4>📋 Información</h4>
            <p>Primero crea el pedido básico. Luego podrás agregar productos y gestionar los detalles del pedido.</p>
        </div>

        <!-- Formulario para crear pedido -->
        <div class="form-container">
            <h3>➕ Crear Pedido Básico</h3>
            <form method="POST">
                <input type="hidden" name="accion" value="crear_pedido">
                <div class="form-row">
                    <div class="form-group">
                        <label for="usuario">Cliente:</label>
                        <select id="usuario" name="usuario" required>
                            <option value="">-- Seleccionar Cliente --</option>
                            <?php while ($usuario = $result_usuarios->fetch_assoc()): ?>
                                <option value="<?php echo $usuario['ID_USUARIO']; ?>">
                                    <?php echo htmlspecialchars($usuario['NOMB_USUARIO']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="fecha">Fecha del Pedido:</label>
                        <input type="date" id="fecha" name="fecha" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Crear Pedido</button>
            </form>
        </div>
    </div>
</body>
</html>
