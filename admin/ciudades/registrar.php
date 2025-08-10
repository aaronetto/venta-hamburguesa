<?php
session_start();
require_once '../../config.php';
require_once '../../auth_functions.php';

// Verificar acceso al módulo
requerirAccesoModulo('ciudades');
$conexion = getConnection();

$error = '';
$success = '';

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    
    // Validaciones
    if (empty($nombre)) {
        $error = 'El nombre de la ciudad es obligatorio.';
    } elseif (strlen($nombre) > 45) {
        $error = 'El nombre de la ciudad no puede tener más de 45 caracteres.';
    } else {
        // Verificar si ya existe una ciudad con ese nombre
        $check_query = "SELECT COUNT(*) as total FROM ciudad WHERE NOMBRE = ?";
        $check_stmt = $conexion->prepare($check_query);
        $check_stmt->bind_param("s", $nombre);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_row = $check_result->fetch_assoc();
        
        if ($check_row['total'] > 0) {
            $error = 'Ya existe una ciudad con ese nombre.';
        } else {
            // Insertar la nueva ciudad
            $insert_query = "INSERT INTO ciudad (NOMBRE) VALUES (?)";
            $insert_stmt = $conexion->prepare($insert_query);
            $insert_stmt->bind_param("s", $nombre);
            
            if ($insert_stmt->execute()) {
                header('Location: index.php?success=ciudad_creada');
                exit();
            } else {
                $error = 'Error al registrar la ciudad.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Ciudad - Administrador</title>
    <link rel="stylesheet" href="../../style.css">
    <style>
        .admin-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .admin-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .admin-title {
            font-size: 2.5em;
            color: #333;
            margin: 0;
        }
        
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        
        .form-input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #007bff;
        }
        
        .form-input.error {
            border-color: #dc3545;
        }
        
        .error-message {
            color: #dc3545;
            font-size: 0.9em;
            margin-top: 5px;
        }
        
        .success-message {
            color: #28a745;
            font-size: 0.9em;
            margin-top: 5px;
        }
        
        .form-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            flex: 1;
            transition: background 0.3s;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            flex: 1;
            transition: background 0.3s;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .volver-btn {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 20px;
            display: inline-block;
        }
        
        .volver-btn:hover {
            background: #545b62;
        }
        
        .required {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <a href="index.php" class="volver-btn">← Volver a Ciudades</a>
        
        <div class="admin-header">
            <h1 class="admin-title">🏙️ Registrar Ciudad</h1>
        </div>
        
        <div class="form-container">
            <?php if ($error): ?>
                <div class="error-message">❌ <?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message">✅ <?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="nombre" class="form-label">
                        Nombre de la Ciudad <span class="required">*</span>
                    </label>
                    <input type="text" 
                           id="nombre" 
                           name="nombre" 
                           class="form-input <?php echo $error ? 'error' : ''; ?>"
                           value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>"
                           placeholder="Ej: Lima, Arequipa, Trujillo"
                           maxlength="45"
                           required>
                    <div class="error-message">
                        Máximo 45 caracteres permitidos.
                    </div>
                </div>
                
                <div class="form-buttons">
                    <button type="submit" class="btn-primary">Registrar Ciudad</button>
                    <a href="index.php" class="btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
