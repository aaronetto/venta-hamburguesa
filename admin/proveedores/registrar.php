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
        // Verificar si ya existe un proveedor con ese número de documento
        $stmt = $conexion->prepare("SELECT ID_PROVEEDOR FROM proveedor WHERE NUMERO_DOCUMENTO = ?");
        $stmt->bind_param("s", $numero_documento);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Ya existe un proveedor con ese número de documento";
        } else {
            // Verificar si ya existe un proveedor con ese correo
            $stmt = $conexion->prepare("SELECT ID_PROVEEDOR FROM proveedor WHERE CORREO = ?");
            $stmt->bind_param("s", $correo);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Ya existe un proveedor con ese correo electrónico";
            } else {
                // Insertar el nuevo proveedor
                $stmt = $conexion->prepare("INSERT INTO proveedor (NOMBRE, RAZON_SOCIAL, NUMERO_DOCUMENTO, DIRECCION, TELEFONO, CORREO, SITIO_WEB, CONTACTO_NOMBRES, CONTACTO_APELLIDOS) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssssss", $nombre, $razon_social, $numero_documento, $direccion, $telefono, $correo, $sitio_web, $contacto_nombres, $contacto_apellidos);
                
                if ($stmt->execute()) {
                    header("Location: index.php?creado=1");
                    exit();
                } else {
                    $error = "Error al crear el proveedor: " . $conexion->error;
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
    <title>Registrar Proveedor</title>
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
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>➕ Registrar Nuevo Proveedor</h1>
            <a href="index.php" class="btn btn-secondary">← Volver a Proveedores</a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="mensaje error">
                ❌ <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST">
                <div class="form-section">
                    <div class="section-title">📋 Información General</div>
                    
                    <div class="form-group">
                        <label for="nombre" class="required">Nombre del Proveedor:</label>
                        <input type="text" id="nombre" name="nombre" required maxlength="100"
                               value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>">
                        <div class="info-text">Nombre comercial del proveedor</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="razon_social" class="required">Razón Social:</label>
                        <input type="text" id="razon_social" name="razon_social" required maxlength="100"
                               value="<?php echo isset($_POST['razon_social']) ? htmlspecialchars($_POST['razon_social']) : ''; ?>">
                        <div class="info-text">Razón social legal de la empresa</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="numero_documento" class="required">Número de Documento:</label>
                        <input type="text" id="numero_documento" name="numero_documento" required maxlength="11"
                               value="<?php echo isset($_POST['numero_documento']) ? htmlspecialchars($_POST['numero_documento']) : ''; ?>">
                        <div class="info-text">RUC o DNI del proveedor (máximo 11 dígitos)</div>
                    </div>
                </div>
                
                <div class="form-section">
                    <div class="section-title">📍 Información de Contacto</div>
                    
                    <div class="form-group">
                        <label for="direccion" class="required">Dirección:</label>
                        <textarea id="direccion" name="direccion" required maxlength="255"
                                  placeholder="Dirección completa del proveedor..."><?php echo isset($_POST['direccion']) ? htmlspecialchars($_POST['direccion']) : ''; ?></textarea>
                        <div class="info-text">Dirección física completa</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="telefono" class="required">Teléfono:</label>
                        <input type="tel" id="telefono" name="telefono" required maxlength="255"
                               value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : ''; ?>">
                        <div class="info-text">Número de teléfono principal</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="correo" class="required">Correo Electrónico:</label>
                        <input type="email" id="correo" name="correo" required maxlength="255"
                               value="<?php echo isset($_POST['correo']) ? htmlspecialchars($_POST['correo']) : ''; ?>">
                        <div class="info-text">Correo electrónico de contacto</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="sitio_web">Sitio Web:</label>
                        <input type="url" id="sitio_web" name="sitio_web" maxlength="255"
                               placeholder="https://www.ejemplo.com"
                               value="<?php echo isset($_POST['sitio_web']) ? htmlspecialchars($_POST['sitio_web']) : ''; ?>">
                        <div class="info-text">Sitio web del proveedor (opcional)</div>
                    </div>
                </div>
                
                <div class="form-section">
                    <div class="section-title">👤 Persona de Contacto</div>
                    
                    <div class="form-group">
                        <label for="contacto_nombres" class="required">Nombres del Contacto:</label>
                        <input type="text" id="contacto_nombres" name="contacto_nombres" required maxlength="100"
                               value="<?php echo isset($_POST['contacto_nombres']) ? htmlspecialchars($_POST['contacto_nombres']) : ''; ?>">
                        <div class="info-text">Nombres de la persona de contacto principal</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="contacto_apellidos">Apellidos del Contacto:</label>
                        <input type="text" id="contacto_apellidos" name="contacto_apellidos" maxlength="100"
                               value="<?php echo isset($_POST['contacto_apellidos']) ? htmlspecialchars($_POST['contacto_apellidos']) : ''; ?>">
                        <div class="info-text">Apellidos de la persona de contacto (opcional)</div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">✅ Crear Proveedor</button>
                    <a href="index.php" class="btn btn-secondary">❌ Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
