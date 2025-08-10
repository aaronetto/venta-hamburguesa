<?php
session_start();
require_once '../../config.php';
require_once '../../auth_functions.php';

// Verificar acceso al módulo
requerirAccesoModulo('proveedores');

$conexion = getConnection();
$mensaje = '';
$error = '';
$proveedor = null;

// Verificar si se proporcionó un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php?error=id_requerido");
    exit();
}

$id = (int)$_GET['id'];

// Obtener datos del proveedor
$stmt = $conexion->prepare("SELECT * FROM proveedor WHERE ID_PROVEEDOR = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php?error=proveedor_no_existe");
    exit();
}

$proveedor = $result->fetch_assoc();
$stmt->close();

// Procesar el formulario de actualización
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $razon_social = trim($_POST['razon_social']);
    $numero_documento = trim($_POST['numero_documento']);
    $direccion = trim($_POST['direccion']);
    $telefono = trim($_POST['telefono']);
    $correo = trim($_POST['correo']);
    $sitio_web = trim($_POST['sitio_web']);
    $contacto_nombres = trim($_POST['contacto_nombres']);
    $contacto_apellidos = trim($_POST['contacto_apellidos']);
    
    // Validaciones
    if (empty($nombre) || empty($razon_social) || empty($numero_documento) || 
        empty($direccion) || empty($telefono) || empty($correo) || empty($contacto_nombres)) {
        $error = "Los campos marcados con * son obligatorios";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = "El formato del correo electrónico no es válido";
    } elseif (!empty($sitio_web) && !filter_var($sitio_web, FILTER_VALIDATE_URL)) {
        $error = "El formato del sitio web no es válido";
    } else {
        // Verificar si ya existe otro proveedor con ese número de documento (excluyendo el actual)
        $stmt = $conexion->prepare("SELECT ID_PROVEEDOR FROM proveedor WHERE NUMERO_DOCUMENTO = ? AND ID_PROVEEDOR != ?");
        $stmt->bind_param("si", $numero_documento, $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Ya existe otro proveedor con ese número de documento";
        } else {
            // Verificar si ya existe otro proveedor con ese correo (excluyendo el actual)
            $stmt = $conexion->prepare("SELECT ID_PROVEEDOR FROM proveedor WHERE CORREO = ? AND ID_PROVEEDOR != ?");
            $stmt->bind_param("si", $correo, $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Ya existe otro proveedor con ese correo electrónico";
            } else {
                // Actualizar el proveedor
                $stmt = $conexion->prepare("UPDATE proveedor SET NOMBRE = ?, RAZON_SOCIAL = ?, NUMERO_DOCUMENTO = ?, DIRECCION = ?, TELEFONO = ?, CORREO = ?, SITIO_WEB = ?, CONTACTO_NOMBRES = ?, CONTACTO_APELLIDOS = ? WHERE ID_PROVEEDOR = ?");
                $stmt->bind_param("sssssssssi", $nombre, $razon_social, $numero_documento, $direccion, $telefono, $correo, $sitio_web, $contacto_nombres, $contacto_apellidos, $id);
                
                if ($stmt->execute()) {
                    header("Location: index.php?actualizado=1");
                    exit();
                } else {
                    $error = "Error al actualizar el proveedor: " . $conexion->error;
                }
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
    <title>Editar Proveedor</title>
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
            margin: 5px;
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
        
        .form-group label.required::after {
            content: " *";
            color: #dc3545;
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
            border-color: #17a2b8;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
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
        
        .form-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .form-section:last-child {
            border-bottom: none;
        }
        
        .section-title {
            color: #17a2b8;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .proveedor-info {
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
            <h1>✏️ Editar Proveedor</h1>
            <a href="index.php" class="btn btn-secondary">← Volver a Proveedores</a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="mensaje error">
                ❌ <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <div class="proveedor-info">
                <strong>ID:</strong> <?php echo $proveedor['ID_PROVEEDOR']; ?>
            </div>

            <form method="POST">
                <div class="form-section">
                    <div class="section-title">📋 Información General</div>
                    
                    <div class="form-group">
                        <label for="nombre" class="required">Nombre del Proveedor:</label>
                        <input type="text" id="nombre" name="nombre" required maxlength="100"
                               value="<?php echo htmlspecialchars($proveedor['NOMBRE']); ?>">
                        <div class="info-text">Nombre comercial del proveedor</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="razon_social" class="required">Razón Social:</label>
                        <input type="text" id="razon_social" name="razon_social" required maxlength="100"
                               value="<?php echo htmlspecialchars($proveedor['RAZON_SOCIAL']); ?>">
                        <div class="info-text">Razón social legal de la empresa</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="numero_documento" class="required">Número de Documento:</label>
                        <input type="text" id="numero_documento" name="numero_documento" required maxlength="11"
                               value="<?php echo htmlspecialchars($proveedor['NUMERO_DOCUMENTO']); ?>">
                        <div class="info-text">RUC o DNI del proveedor (máximo 11 dígitos)</div>
                    </div>
                </div>
                
                <div class="form-section">
                    <div class="section-title">📍 Información de Contacto</div>
                    
                    <div class="form-group">
                        <label for="direccion" class="required">Dirección:</label>
                        <textarea id="direccion" name="direccion" required maxlength="255"
                                  placeholder="Dirección completa del proveedor..."><?php echo htmlspecialchars($proveedor['DIRECCION']); ?></textarea>
                        <div class="info-text">Dirección física completa</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="telefono" class="required">Teléfono:</label>
                        <input type="tel" id="telefono" name="telefono" required maxlength="255"
                               value="<?php echo htmlspecialchars($proveedor['TELEFONO']); ?>">
                        <div class="info-text">Número de teléfono principal</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="correo" class="required">Correo Electrónico:</label>
                        <input type="email" id="correo" name="correo" required maxlength="255"
                               value="<?php echo htmlspecialchars($proveedor['CORREO']); ?>">
                        <div class="info-text">Correo electrónico de contacto</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="sitio_web">Sitio Web:</label>
                        <input type="url" id="sitio_web" name="sitio_web" maxlength="255"
                               placeholder="https://www.ejemplo.com"
                               value="<?php echo htmlspecialchars($proveedor['SITIO_WEB']); ?>">
                        <div class="info-text">Sitio web del proveedor (opcional)</div>
                    </div>
                </div>
                
                <div class="form-section">
                    <div class="section-title">👤 Persona de Contacto</div>
                    
                    <div class="form-group">
                        <label for="contacto_nombres" class="required">Nombres del Contacto:</label>
                        <input type="text" id="contacto_nombres" name="contacto_nombres" required maxlength="100"
                               value="<?php echo htmlspecialchars($proveedor['CONTACTO_NOMBRES']); ?>">
                        <div class="info-text">Nombres de la persona de contacto principal</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="contacto_apellidos">Apellidos del Contacto:</label>
                        <input type="text" id="contacto_apellidos" name="contacto_apellidos" maxlength="100"
                               value="<?php echo htmlspecialchars($proveedor['CONTACTO_APELLIDOS']); ?>">
                        <div class="info-text">Apellidos de la persona de contacto (opcional)</div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">✅ Actualizar Proveedor</button>
                    <a href="index.php" class="btn btn-secondary">❌ Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
