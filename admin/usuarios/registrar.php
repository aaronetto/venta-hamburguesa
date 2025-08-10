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
$error = '';

// Procesar el formulario de registro
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombres = trim($_POST['nombres']);
    $apellidos = trim($_POST['apellidos']);
    $correo = trim($_POST['correo']);
    $clave = $_POST['clave'];
    $rol = $_POST['rol'];
    
    // Validaciones
    if (empty($nombres) || empty($apellidos) || empty($correo) || empty($clave)) {
        $error = "Todos los campos son obligatorios";
    } elseif (strlen($clave) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = "El formato del correo electrónico no es válido";
    } else {
        // Verificar si el correo ya existe
        $stmt = $conexion->prepare("SELECT ID_USUARIO FROM usuario WHERE CORREO = ?");
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "El correo electrónico ya está registrado";
        } else {
            // Encriptar contraseña
            $clave_hash = password_hash($clave, PASSWORD_DEFAULT);
            
            // Insertar nuevo usuario
            $stmt = $conexion->prepare("INSERT INTO usuario (NOMBRES, APELLIDOS, CORREO, CLAVE, ROL, ACTIVO) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->bind_param("sssss", $nombres, $apellidos, $correo, $clave_hash, $rol);
            
            if ($stmt->execute()) {
                $mensaje = "Usuario registrado exitosamente";
                // Limpiar formulario
                $_POST = array();
            } else {
                $error = "Error al registrar el usuario: " . $conexion->error;
            }
        }
        $stmt->close();
    }
}

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Usuario - Administración</title>
    <link rel="stylesheet" href="../../style.css">
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
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
        }
        
        .form-row {
            display: block;
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
            <h1>👤 Registrar Nuevo Usuario</h1>
            <a href="index.php" class="btn btn-secondary">← Volver a Usuarios</a>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="mensaje success">
                ✅ <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="mensaje error">
                ❌ <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <h3>📝 Información del Usuario</h3>
            <form method="POST">
                <div class="form-group">
                    <label for="nombres">Nombres:</label>
                    <input type="text" id="nombres" name="nombres" required maxlength="100" 
                           value="<?php echo isset($_POST['nombres']) ? htmlspecialchars($_POST['nombres']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="apellidos">Apellidos:</label>
                    <input type="text" id="apellidos" name="apellidos" required maxlength="100"
                           value="<?php echo isset($_POST['apellidos']) ? htmlspecialchars($_POST['apellidos']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="correo">Correo Electrónico:</label>
                    <input type="email" id="correo" name="correo" required maxlength="100"
                           value="<?php echo isset($_POST['correo']) ? htmlspecialchars($_POST['correo']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="clave">Contraseña:</label>
                    <input type="password" id="clave" name="clave" required minlength="6">
                    <div class="clave-info">La contraseña debe tener al menos 6 caracteres</div>
                </div>
                
                <div class="form-group">
                    <label for="rol">Rol:</label>
                    <select id="rol" name="rol" required>
                        <option value="">Seleccionar rol</option>
                        <option value="ADMINISTRADOR" <?php echo (isset($_POST['rol']) && $_POST['rol'] == 'ADMINISTRADOR') ? 'selected' : ''; ?>>Administrador</option>
                        <option value="GERENTE" <?php echo (isset($_POST['rol']) && $_POST['rol'] == 'GERENTE') ? 'selected' : ''; ?>>Gerente</option>
                        <option value="ASISTENTE" <?php echo (isset($_POST['rol']) && $_POST['rol'] == 'ASISTENTE') ? 'selected' : ''; ?>>Asistente</option>
                    </select>
                </div>
                
                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" class="btn btn-primary">✅ Registrar Usuario</button>
                    <a href="index.php" class="btn btn-secondary">❌ Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
