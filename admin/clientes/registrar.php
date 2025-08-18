<?php
session_start();
require_once '../../config.php';
require_once '../../auth_functions.php';

// Verificar acceso al módulo
requerirAccesoModulo('clientes');

$conexion = getConnection();
$mensaje = '';
$error = '';

// Procesar el formulario si se envía
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    
    // Validaciones
    if (empty($nombre) || empty($apellidos) || empty($correo) || empty($telefono)) {
        $error = "Los campos marcados con * son obligatorios";
    } elseif (strlen($nombre) > 45) {
        $error = "El nombre no puede tener más de 45 caracteres";
    } elseif (strlen($apellidos) > 45) {
        $error = "Los apellidos no pueden tener más de 45 caracteres";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = "El formato del correo electrónico no es válido";
    } elseif (strlen($correo) > 45) {
        $error = "El correo no puede tener más de 45 caracteres";
    } elseif (strlen($telefono) > 45) {
        $error = "El teléfono no puede tener más de 45 caracteres";
    } elseif (strlen($direccion) > 45) {
        $error = "La dirección no puede tener más de 45 caracteres";
    } else {
        // Verificar si el correo ya existe
        $stmt = $conexion->prepare("SELECT ID_CLIENTE FROM cliente WHERE CORREO = ?");
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Ya existe un cliente con ese correo electrónico";
        } else {
            // Insertar el nuevo cliente
            $stmt = $conexion->prepare("INSERT INTO cliente (NOMBRES, APELLIDOS, CORREO, TELEFONO, DIRECCION) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $nombre, $apellidos, $correo, $telefono, $direccion);
            
            if ($stmt->execute()) {
                header("Location: index.php?creado=1");
                exit();
            } else {
                $error = "Error al crear el cliente: " . $conexion->error;
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Cliente</title>
    <link rel="stylesheet" href="../../style.css">
    <style>
        .admin-container {
            max-width: 800px;
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
            padding: 12px 24px;
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
        
        .form-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .form-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .form-body {
            padding: 30px;
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
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #17a2b8;
            box-shadow: 0 0 0 2px rgba(23, 162, 184, 0.25);
        }
        
        .required {
            color: #dc3545;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
            font-weight: 600;
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
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>👤 Registrar Cliente</h1>
            <div>
                <a href="index.php" class="btn btn-secondary">← Volver a Clientes</a>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <div class="form-header">
                <h3>📝 Información del Cliente</h3>
            </div>
            
            <form method="POST" action="">
                <div class="form-body">
                    <div class="form-section">
                        <h3>👤 Datos Personales</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nombre">Nombre <span class="required">*</span></label>
                                <input type="text" id="nombre" name="nombre" 
                                       value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>" 
                                       maxlength="45" required>
                            </div>

                            <div class="form-group">
                                <label for="apellidos">Apellidos <span class="required">*</span></label>
                                <input type="text" id="apellidos" name="apellidos" 
                                       value="<?php echo htmlspecialchars($_POST['apellidos'] ?? ''); ?>" 
                                       maxlength="45" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>📞 Información de Contacto</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="correo">Correo Electrónico <span class="required">*</span></label>
                                <input type="email" id="correo" name="correo" 
                                       value="<?php echo htmlspecialchars($_POST['correo'] ?? ''); ?>" 
                                       maxlength="45" required>
                            </div>

                            <div class="form-group">
                                <label for="telefono">Teléfono <span class="required">*</span></label>
                                <input type="tel" id="telefono" name="telefono" 
                                       value="<?php echo htmlspecialchars($_POST['telefono'] ?? ''); ?>" 
                                       maxlength="45" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="direccion">Dirección Principal</label>
                            <input type="text" id="direccion" name="direccion" 
                                   value="<?php echo htmlspecialchars($_POST['direccion'] ?? ''); ?>" 
                                   maxlength="45" placeholder="Dirección principal del cliente">
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="index.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">✅ Registrar Cliente</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

<?php
$conexion->close();
?>
