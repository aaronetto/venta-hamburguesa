<?php
session_start();
require_once '../../config.php';
require_once '../../auth_functions.php';

// Verificar acceso al módulo
requerirAccesoModulo('distritos');
$conexion = getConnection();

$error = '';
$success = '';

// Obtener lista de provincias con información de ciudad para el select
$provincias_query = "SELECT p.ID_PROVINCIA, p.NOMBRE as PROVINCIA_NOMBRE, c.NOMBRE as CIUDAD_NOMBRE 
                     FROM provincia p 
                     INNER JOIN ciudad c ON p.ID_CIUDAD = c.ID_CIUDAD 
                     ORDER BY c.NOMBRE, p.NOMBRE";
$provincias_result = $conexion->query($provincias_query);

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $id_provincia = $_POST['id_provincia'];
    
    // Validaciones
    if (empty($nombre)) {
        $error = 'El nombre del distrito es obligatorio.';
    } elseif (strlen($nombre) > 45) {
        $error = 'El nombre del distrito no puede tener más de 45 caracteres.';
    } elseif (empty($id_provincia) || !is_numeric($id_provincia)) {
        $error = 'Debe seleccionar una provincia válida.';
    } else {
        // Verificar si ya existe un distrito con ese nombre en la misma provincia
        $check_query = "SELECT COUNT(*) as total FROM distrito WHERE NOMBRE = ? AND ID_PROVINCIA = ?";
        $check_stmt = $conexion->prepare($check_query);
        $check_stmt->bind_param("si", $nombre, $id_provincia);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_row = $check_result->fetch_assoc();
        
        if ($check_row['total'] > 0) {
            $error = 'Ya existe un distrito con ese nombre en la provincia seleccionada.';
        } else {
            // Insertar el nuevo distrito
            $insert_query = "INSERT INTO distrito (NOMBRE, ID_PROVINCIA) VALUES (?, ?)";
            $insert_stmt = $conexion->prepare($insert_query);
            $insert_stmt->bind_param("si", $nombre, $id_provincia);
            
            if ($insert_stmt->execute()) {
                header('Location: index.php?success=distrito_creado');
                exit();
            } else {
                $error = 'Error al registrar el distrito.';
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
    <title>Registrar Distrito - Administrador</title>
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
        
        .no-provincias {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #ffeaa7;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <a href="index.php" class="volver-btn">← Volver a Distritos</a>
        
        <div class="admin-header">
            <h1 class="admin-title">🏘️ Registrar Distrito</h1>
        </div>
        
        <div class="form-container">
            <?php if ($provincias_result->num_rows === 0): ?>
                <div class="no-provincias">
                    <strong>⚠️ No hay provincias registradas</strong><br>
                    Debe registrar al menos una provincia antes de crear distritos.
                    <br><br>
                    <a href="../provincias/registrar.php" style="color: #007bff; text-decoration: none;">
                        → Registrar Provincia
                    </a>
                </div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="error-message">❌ <?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="success-message">✅ <?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="id_provincia" class="form-label">
                            Provincia <span class="required">*</span>
                        </label>
                        <select id="id_provincia" 
                                name="id_provincia" 
                                class="form-select <?php echo $error && empty($_POST['id_provincia']) ? 'error' : ''; ?>"
                                required>
                            <option value="">Seleccione una provincia</option>
                            <?php while ($provincia = $provincias_result->fetch_assoc()): ?>
                                <option value="<?php echo $provincia['ID_PROVINCIA']; ?>"
                                        <?php echo (isset($_POST['id_provincia']) && $_POST['id_provincia'] == $provincia['ID_PROVINCIA']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($provincia['CIUDAD_NOMBRE'] . ' - ' . $provincia['PROVINCIA_NOMBRE']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="nombre" class="form-label">
                            Nombre del Distrito <span class="required">*</span>
                        </label>
                        <input type="text" 
                               id="nombre" 
                               name="nombre" 
                               class="form-input <?php echo $error ? 'error' : ''; ?>"
                               value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>"
                               placeholder="Ej: Miraflores, San Isidro, Barranco"
                               maxlength="45"
                               required>
                        <div class="error-message">
                            Máximo 45 caracteres permitidos.
                        </div>
                    </div>
                    
                    <div class="form-buttons">
                        <button type="submit" class="btn-primary">Registrar Distrito</button>
                        <a href="index.php" class="btn-secondary">Cancelar</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
