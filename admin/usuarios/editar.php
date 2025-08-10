<?php
session_start();
require_once '../../config.php';
require_once '../../auth_functions.php';

// Verificar acceso al módulo
requerirAccesoModulo('usuarios');

$conexion = getConnection();
$mensaje = '';
$error = '';
$usuario = null;

// Obtener ID del usuario a editar
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php?error=id_requerido");
    exit();
}

$id = (int)$_GET['id'];

// Obtener datos del usuario
$stmt = $conexion->prepare("SELECT ID_USUARIO, NOMBRES, APELLIDOS, CORREO, ROL, ACTIVO FROM usuario WHERE ID_USUARIO = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php?error=usuario_no_existe");
    exit();
}

$usuario = $result->fetch_assoc();

// Procesar el formulario de actualización
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombres = trim($_POST['nombres']);
    $apellidos = trim($_POST['apellidos']);
    $correo = trim($_POST['correo']);
    $clave = $_POST['clave'];
    $rol = $_POST['rol'];
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // Validaciones
    if (empty($nombres) || empty($apellidos) || empty($correo)) {
        $error = "Los campos nombres, apellidos y correo son obligatorios";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = "El formato del correo electrónico no es válido";
    } elseif (!empty($clave) && strlen($clave) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres";
    } else {
        // Verificar si el correo ya existe en otro usuario
        $stmt = $conexion->prepare("SELECT ID_USUARIO FROM usuario WHERE CORREO = ? AND ID_USUARIO != ?");
        $stmt->bind_param("si", $correo, $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "El correo electrónico ya está registrado por otro usuario";
        } else {
            // Actualizar usuario
            if (!empty($clave)) {
                // Actualizar con nueva contraseña
                $clave_hash = password_hash($clave, PASSWORD_DEFAULT);
                $stmt = $conexion->prepare("UPDATE usuario SET NOMBRES = ?, APELLIDOS = ?, CORREO = ?, CLAVE = ?, ROL = ?, ACTIVO = ? WHERE ID_USUARIO = ?");
                $stmt->bind_param("sssssii", $nombres, $apellidos, $correo, $clave_hash, $rol, $activo, $id);
            } else {
                // Actualizar sin cambiar contraseña
                $stmt = $conexion->prepare("UPDATE usuario SET NOMBRES = ?, APELLIDOS = ?, CORREO = ?, ROL = ?, ACTIVO = ? WHERE ID_USUARIO = ?");
                $stmt->bind_param("ssssii", $nombres, $apellidos, $correo, $rol, $activo, $id);
            }
            
            if ($stmt->execute()) {
                $mensaje = "Usuario actualizado exitosamente";
                // Actualizar datos en la variable $usuario
                $usuario['NOMBRES'] = $nombres;
                $usuario['APELLIDOS'] = $apellidos;
                $usuario['CORREO'] = $correo;
                $usuario['ROL'] = $rol;
                $usuario['ACTIVO'] = $activo;
            } else {
                $error = "Error al actualizar el usuario: " . $conexion->error;
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
    <title>Editar Usuario - Administración</title>
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
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
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
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>✏️ Editar Usuario</h1>
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
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombres">Nombres:</label>
                        <input type="text" id="nombres" name="nombres" required maxlength="100" 
                               value="<?php echo htmlspecialchars($usuario['NOMBRES']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="apellidos">Apellidos:</label>
                        <input type="text" id="apellidos" name="apellidos" required maxlength="100"
                               value="<?php echo htmlspecialchars($usuario['APELLIDOS']); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="correo">Correo Electrónico:</label>
                    <input type="email" id="correo" name="correo" required maxlength="100"
                           value="<?php echo htmlspecialchars($usuario['CORREO']); ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="clave">Nueva Contraseña:</label>
                        <input type="password" id="clave" name="clave" minlength="6">
                        <div class="clave-info">Deja vacío para mantener la contraseña actual</div>
                    </div>
                    <div class="form-group">
                        <label for="rol">Rol:</label>
                        <select id="rol" name="rol" required>
                            <option value="ADMINISTRADOR" <?php echo ($usuario['ROL'] == 'ADMINISTRADOR') ? 'selected' : ''; ?>>Administrador</option>
                            <option value="GERENTE" <?php echo ($usuario['ROL'] == 'GERENTE') ? 'selected' : ''; ?>>Gerente</option>
                            <option value="ASISTENTE" <?php echo ($usuario['ROL'] == 'ASISTENTE') ? 'selected' : ''; ?>>Asistente</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="activo" name="activo" <?php echo ($usuario['ACTIVO'] == 1) ? 'checked' : ''; ?>>
                        <label for="activo">Usuario Activo</label>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" class="btn btn-primary">✅ Actualizar Usuario</button>
                    <a href="index.php" class="btn btn-secondary">❌ Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
