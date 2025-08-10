<?php
session_start();

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'ADMINISTRADOR') {
    header('Location: ../../login_registro.php');
    exit();
}

require_once '../../config.php';
$conexion = getConnection();

$error = '';
$success = '';
$ciudad = null;

// Verificar si se proporciona un ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php?error=id_invalido');
    exit();
}

$id_ciudad = $_GET['id'];

// Obtener datos de la ciudad
$get_query = "SELECT * FROM ciudad WHERE ID_CIUDAD = ?";
$get_stmt = $conexion->prepare($get_query);
$get_stmt->bind_param("i", $id_ciudad);
$get_stmt->execute();
$result = $get_stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: index.php?error=ciudad_no_encontrada');
    exit();
}

$ciudad = $result->fetch_assoc();

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    
    // Validaciones
    if (empty($nombre)) {
        $error = 'El nombre de la ciudad es obligatorio.';
    } elseif (strlen($nombre) > 45) {
        $error = 'El nombre de la ciudad no puede tener más de 45 caracteres.';
    } else {
        // Verificar si ya existe otra ciudad con ese nombre (excluyendo la actual)
        $check_query = "SELECT COUNT(*) as total FROM ciudad WHERE NOMBRE = ? AND ID_CIUDAD != ?";
        $check_stmt = $conexion->prepare($check_query);
        $check_stmt->bind_param("si", $nombre, $id_ciudad);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_row = $check_result->fetch_assoc();
        
        if ($check_row['total'] > 0) {
            $error = 'Ya existe otra ciudad con ese nombre.';
        } else {
            // Actualizar la ciudad
            $update_query = "UPDATE ciudad SET NOMBRE = ? WHERE ID_CIUDAD = ?";
            $update_stmt = $conexion->prepare($update_query);
            $update_stmt->bind_param("si", $nombre, $id_ciudad);
            
            if ($update_stmt->execute()) {
                header('Location: index.php?success=ciudad_actualizada');
                exit();
            } else {
                $error = 'Error al actualizar la ciudad.';
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
    <title>Editar Ciudad - Administrador</title>
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
        
        .ciudad-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
        }
        
        .ciudad-info strong {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <a href="index.php" class="volver-btn">← Volver a Ciudades</a>
        
        <div class="admin-header">
            <h1 class="admin-title">🏙️ Editar Ciudad</h1>
        </div>
        
        <div class="form-container">
            <div class="ciudad-info">
                <strong>ID de Ciudad:</strong> <?php echo $ciudad['ID_CIUDAD']; ?>
            </div>
            
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
                           value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : htmlspecialchars($ciudad['NOMBRE']); ?>"
                           placeholder="Ej: Lima, Arequipa, Trujillo"
                           maxlength="45"
                           required>
                    <div class="error-message">
                        Máximo 45 caracteres permitidos.
                    </div>
                </div>
                
                <div class="form-buttons">
                    <button type="submit" class="btn-primary">Actualizar Ciudad</button>
                    <a href="index.php" class="btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
