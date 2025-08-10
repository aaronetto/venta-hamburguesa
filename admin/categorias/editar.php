<?php
session_start();
require_once '../../config.php';
require_once '../../auth_functions.php';

// Verificar acceso al módulo
requerirAccesoModulo('categorias');

$conexion = getConnection();
$mensaje = '';
$error = '';
$categoria = null;

// Verificar si se proporcionó un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php?error=id_requerido");
    exit();
}

$id = (int)$_GET['id'];

// Obtener datos de la categoría
$stmt = $conexion->prepare("SELECT * FROM categoria WHERE ID_CATEGORIA = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php?error=categoria_no_existe");
    exit();
}

$categoria = $result->fetch_assoc();
$stmt->close();

// Procesar el formulario de actualización
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // Validaciones
    if (empty($nombre)) {
        $error = "El nombre de la categoría es obligatorio";
    } else {
        // Verificar si ya existe otra categoría con ese nombre (excluyendo la actual)
        $stmt = $conexion->prepare("SELECT ID_CATEGORIA FROM categoria WHERE NOMBRE = ? AND ID_CATEGORIA != ?");
        $stmt->bind_param("si", $nombre, $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Ya existe otra categoría con ese nombre";
        } else {
            // Actualizar la categoría
            $stmt = $conexion->prepare("UPDATE categoria SET NOMBRE = ?, DESCRIPCION = ?, ACTIVO = ?, FECHA_ACTUALIZACION = NOW() WHERE ID_CATEGORIA = ?");
            $stmt->bind_param("ssii", $nombre, $descripcion, $activo, $id);
            
            if ($stmt->execute()) {
                header("Location: index.php?actualizado=1");
                exit();
            } else {
                $error = "Error al actualizar la categoría: " . $conexion->error;
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
    <title>Editar Categoría</title>
    <link rel="stylesheet" href="../../style.css">
    <style>
        .admin-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .admin-header {
            background: linear-gradient(135deg, #28a745, #20c997);
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
            margin: 5px;
        }
        
        .btn-primary {
            background: #28a745;
            color: white;
        }
        
        .btn-primary:hover {
            background: #218838;
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
            color: #495057;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #28a745;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-group input[type="checkbox"] {
            width: auto;
            margin-right: 10px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            border: 1px solid #e9ecef;
        }
        
        .info-text {
            font-size: 14px;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .mensaje {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .mensaje.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-actions {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        
        .categoria-info {
            background: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #495057;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>✏️ Editar Categoría</h1>
            <a href="index.php" class="btn btn-secondary">← Volver a Categorías</a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="mensaje error">
                ❌ <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <div class="categoria-info">
                <strong>ID:</strong> <?php echo $categoria['ID_CATEGORIA']; ?> | 
                <strong>Fecha de Creación:</strong> <?php echo date('d/m/Y H:i', strtotime($categoria['FECHA_CREACION'])); ?>
                <?php if ($categoria['FECHA_ACTUALIZACION']): ?>
                    | <strong>Última Actualización:</strong> <?php echo date('d/m/Y H:i', strtotime($categoria['FECHA_ACTUALIZACION'])); ?>
                <?php endif; ?>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label for="nombre">Nombre de la Categoría:</label>
                    <input type="text" id="nombre" name="nombre" required maxlength="100"
                           value="<?php echo htmlspecialchars($categoria['NOMBRE']); ?>">
                    <div class="info-text">Nombre único para identificar la categoría</div>
                </div>
                
                <div class="form-group">
                    <label for="descripcion">Descripción:</label>
                    <textarea id="descripcion" name="descripcion" maxlength="500"
                              placeholder="Describe brevemente qué tipo de productos pertenecen a esta categoría..."><?php echo htmlspecialchars($categoria['DESCRIPCION']); ?></textarea>
                    <div class="info-text">Descripción opcional para explicar el propósito de la categoría</div>
                </div>
                
                <div class="form-group">
                    <label>Estado:</label>
                    <div class="checkbox-group">
                        <input type="checkbox" id="activo" name="activo" 
                               <?php echo $categoria['ACTIVO'] ? 'checked' : ''; ?>>
                        <label for="activo" style="margin: 0; font-weight: normal;">
                            Categoría activa (visible para productos)
                        </label>
                    </div>
                    <div class="info-text">Desmarca esta opción para desactivar la categoría</div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">✅ Actualizar Categoría</button>
                    <a href="index.php" class="btn btn-secondary">❌ Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
