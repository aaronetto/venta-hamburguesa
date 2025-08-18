<?php
session_start();
require_once '../../config.php';
require_once '../../auth_functions.php';

// Verificar acceso al módulo
requerirAccesoModulo('productos');

$conexion = getConnection();
$mensaje = '';
$error = '';

// Obtener categorías para el formulario
$query_categorias = "SELECT ID_CATEGORIA, NOMBRE FROM categoria WHERE ACTIVO = 1 ORDER BY NOMBRE";
$result_categorias = $conexion->query($query_categorias);

// Obtener proveedores para el formulario
$query_proveedores = "SELECT ID_PROVEEDOR, NOMBRE FROM proveedor ORDER BY NOMBRE";
$result_proveedores = $conexion->query($query_proveedores);

// Procesar el formulario de registro
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo = trim($_POST['codigo']);
    $nombre = trim($_POST['nombre']);
    $precio = (float)$_POST['precio'];
    $descripcion = trim($_POST['descripcion']);
    $stock = (int)$_POST['stock'];
    $categoria = $_POST['categoria'];
    $proveedor = $_POST['proveedor'];
    
    // Validaciones
    if (empty($codigo) || empty($nombre) || empty($categoria) || empty($proveedor)) {
        $error = "Los campos código, nombre, categoría y proveedor son obligatorios";
    } elseif ($precio <= 0) {
        $error = "El precio debe ser mayor a 0";
    } elseif ($stock < 0) {
        $error = "El stock no puede ser negativo";
    } else {
        // Verificar si el código ya existe
        $stmt = $conexion->prepare("SELECT ID_PRODUCTO FROM producto WHERE CODIGO = ?");
        $stmt->bind_param("s", $codigo);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "El código de producto ya está registrado";
        } else {
            // Verificar si el nombre ya existe
            $stmt = $conexion->prepare("SELECT ID_PRODUCTO FROM producto WHERE NOMBRE = ?");
            $stmt->bind_param("s", $nombre);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "El nombre de producto ya está registrado";
            } else {
                // Procesar imagen si se subió
                $imagen_ruta = null;
                if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
                    $imagen = $_FILES['imagen'];
                    $extension = strtolower(pathinfo($imagen['name'], PATHINFO_EXTENSION));
                    $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    if (in_array($extension, $extensiones_permitidas)) {
                        $nombre_archivo = uniqid() . '.' . $extension;
                        $ruta_destino = '../../images/productos/' . $nombre_archivo;
                        
                        // Crear directorio si no existe
                        if (!is_dir('../../images/productos/')) {
                            mkdir('../../images/productos/', 0777, true);
                        }
                        
                        if (move_uploaded_file($imagen['tmp_name'], $ruta_destino)) {
                            $imagen_ruta = 'images/productos/' . $nombre_archivo;
                        } else {
                            $error = "Error al subir la imagen";
                        }
                    } else {
                        $error = "Solo se permiten archivos JPG, JPEG, PNG y GIF";
                    }
                }
                
                if (empty($error)) {
                    // Insertar nuevo producto
                    $stmt = $conexion->prepare("INSERT INTO producto (CODIGO, NOMBRE, PRECIO, DESCRIPCION, IMAGEN_RUTA, STOCK, ACTIVO, FECHA_CREACION, ID_CATEGORIA, ID_PROVEEDOR) VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), ?, ?)");
                    $stmt->bind_param("ssdssiii", $codigo, $nombre, $precio, $descripcion, $imagen_ruta, $stock, $categoria, $proveedor);
                    
                    if ($stmt->execute()) {
                        $mensaje = "Producto registrado exitosamente";
                        // Limpiar formulario
                        $_POST = array();
                    } else {
                        $error = "Error al registrar el producto: " . $conexion->error;
                    }
                }
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
    <title>Registrar Producto - Administración</title>
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
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
        }
        
        .form-group textarea {
            height: 100px;
            resize: vertical;
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
        
        .imagen-preview {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
            border-radius: 5px;
            display: none;
        }
        
        .info-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>🍔 Registrar Nuevo Producto</h1>
            <a href="index.php" class="btn btn-secondary">← Volver a Productos</a>
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
            <h3>📝 Información del Producto</h3>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="codigo">Código del Producto:</label>
                    <input type="text" id="codigo" name="codigo" required maxlength="100" 
                           value="<?php echo isset($_POST['codigo']) ? htmlspecialchars($_POST['codigo']) : ''; ?>">
                    <div class="info-text">Código único para identificar el producto</div>
                </div>
                
                <div class="form-group">
                    <label for="nombre">Nombre del Producto:</label>
                    <input type="text" id="nombre" name="nombre" required maxlength="100"
                           value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="descripcion">Descripción:</label>
                    <textarea id="descripcion" name="descripcion" maxlength="1000"
                              placeholder="Descripción detallada del producto..."><?php echo isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="precio">Precio (S/):</label>
                    <input type="number" id="precio" name="precio" step="0.01" min="0" required
                           value="<?php echo isset($_POST['precio']) ? $_POST['precio'] : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="stock">Stock:</label>
                    <input type="number" id="stock" name="stock" min="0" required
                           value="<?php echo isset($_POST['stock']) ? $_POST['stock'] : '0'; ?>">
                    <div class="info-text">Cantidad disponible en inventario</div>
                </div>
                
                <div class="form-group">
                    <label for="categoria">Categoría:</label>
                    <select id="categoria" name="categoria" required>
                        <option value="">Seleccionar categoría</option>
                        <?php while ($cat = $result_categorias->fetch_assoc()): ?>
                            <option value="<?php echo $cat['ID_CATEGORIA']; ?>" 
                                    <?php echo (isset($_POST['categoria']) && $_POST['categoria'] == $cat['ID_CATEGORIA']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['NOMBRE']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="proveedor">Proveedor:</label>
                    <select id="proveedor" name="proveedor" required>
                        <option value="">Seleccionar proveedor</option>
                        <?php while ($prov = $result_proveedores->fetch_assoc()): ?>
                            <option value="<?php echo $prov['ID_PROVEEDOR']; ?>"
                                    <?php echo (isset($_POST['proveedor']) && $_POST['proveedor'] == $prov['ID_PROVEEDOR']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($prov['NOMBRE']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="imagen">Imagen del Producto:</label>
                    <input type="file" id="imagen" name="imagen" accept="image/*" onchange="previewImage(this)">
                    <div class="info-text">Formatos permitidos: JPG, JPEG, PNG, GIF. Máximo 2MB</div>
                    <img id="imagen-preview" class="imagen-preview" alt="Vista previa">
                </div>
                
                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" class="btn btn-primary">✅ Registrar Producto</button>
                    <a href="index.php" class="btn btn-secondary">❌ Cancelar</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function previewImage(input) {
            const preview = document.getElementById('imagen-preview');
            const file = input.files[0];
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        }
    </script>
</body>
</html>
