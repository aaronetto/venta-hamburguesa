<?php
session_start();
require_once '../../config.php';
require_once '../../auth_functions.php';

// Verificar acceso al módulo
requerirAccesoModulo('provincias');
$conexion = getConnection();

$error = '';
$success = '';
$provincia = null;

// Verificar si se proporciona un ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php?error=id_invalido');
    exit();
}

$id_provincia = $_GET['id'];

// Obtener datos de la provincia
$get_query = "SELECT p.*, c.NOMBRE as CIUDAD_NOMBRE 
              FROM provincia p 
              INNER JOIN ciudad c ON p.ID_CIUDAD = c.ID_CIUDAD 
              WHERE p.ID_PROVINCIA = ?";
$get_stmt = $conexion->prepare($get_query);
$get_stmt->bind_param("i", $id_provincia);
$get_stmt->execute();
$result = $get_stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: index.php?error=provincia_no_encontrada');
    exit();
}

$provincia = $result->fetch_assoc();

// Obtener lista de ciudades para el select
$ciudades_query = "SELECT ID_CIUDAD, NOMBRE FROM ciudad ORDER BY NOMBRE";
$ciudades_result = $conexion->query($ciudades_query);

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $id_ciudad = $_POST['id_ciudad'];
    
    // Validaciones
    if (empty($nombre)) {
        $error = 'El nombre de la provincia es obligatorio.';
    } elseif (strlen($nombre) > 45) {
        $error = 'El nombre de la provincia no puede tener más de 45 caracteres.';
    } elseif (empty($id_ciudad) || !is_numeric($id_ciudad)) {
        $error = 'Debe seleccionar una ciudad válida.';
    } else {
        // Verificar si ya existe otra provincia con ese nombre en la misma ciudad (excluyendo la actual)
        $check_query = "SELECT COUNT(*) as total FROM provincia WHERE NOMBRE = ? AND ID_CIUDAD = ? AND ID_PROVINCIA != ?";
        $check_stmt = $conexion->prepare($check_query);
        $check_stmt->bind_param("sii", $nombre, $id_ciudad, $id_provincia);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_row = $check_result->fetch_assoc();
        
        if ($check_row['total'] > 0) {
            $error = 'Ya existe otra provincia con ese nombre en la ciudad seleccionada.';
        } else {
            // Actualizar la provincia
            $update_query = "UPDATE provincia SET NOMBRE = ?, ID_CIUDAD = ? WHERE ID_PROVINCIA = ?";
            $update_stmt = $conexion->prepare($update_query);
            $update_stmt->bind_param("sii", $nombre, $id_ciudad, $id_provincia);
            
            if ($update_stmt->execute()) {
                header('Location: index.php?success=provincia_actualizada');
                exit();
            } else {
                $error = 'Error al actualizar la provincia.';
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
    <title>Editar Provincia - Administrador</title>
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
        
        .form-input, .form-select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        
        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #007bff;
        }
        
        .form-input.error, .form-select.error {
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
        
        .provincia-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
        }
        
        .provincia-info strong {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <a href="index.php" class="volver-btn">← Volver a Provincias</a>
        
        <div class="admin-header">
            <h1 class="admin-title">🏛️ Editar Provincia</h1>
        </div>
        
        <div class="form-container">
            <div class="provincia-info">
                <strong>ID de Provincia:</strong> <?php echo $provincia['ID_PROVINCIA']; ?><br>
                <strong>Ciudad Actual:</strong> <?php echo htmlspecialchars($provincia['CIUDAD_NOMBRE']); ?>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message">❌ <?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message">✅ <?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="id_ciudad" class="form-label">
                        Ciudad <span class="required">*</span>
                    </label>
                    <select id="id_ciudad" 
                            name="id_ciudad" 
                            class="form-select <?php echo $error && empty($_POST['id_ciudad']) ? 'error' : ''; ?>"
                            required>
                        <option value="">Seleccione una ciudad</option>
                        <?php 
                        // Reset the result pointer
                        $ciudades_result->data_seek(0);
                        while ($ciudad = $ciudades_result->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $ciudad['ID_CIUDAD']; ?>"
                                    <?php echo (isset($_POST['id_ciudad']) ? $_POST['id_ciudad'] : $provincia['ID_CIUDAD']) == $ciudad['ID_CIUDAD'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ciudad['NOMBRE']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="nombre" class="form-label">
                        Nombre de la Provincia <span class="required">*</span>
                    </label>
                    <input type="text" 
                           id="nombre" 
                           name="nombre" 
                           class="form-input <?php echo $error ? 'error' : ''; ?>"
                           value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : htmlspecialchars($provincia['NOMBRE']); ?>"
                           placeholder="Ej: Lima, Callao, Arequipa"
                           maxlength="45"
                           required>
                    <div class="error-message">
                        Máximo 45 caracteres permitidos.
                    </div>
                </div>
                
                <div class="form-buttons">
                    <button type="submit" class="btn-primary">Actualizar Provincia</button>
                    <a href="index.php" class="btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
