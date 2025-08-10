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
$producto = null;

// Obtener ID del producto a editar
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php?error=id_requerido");
    exit();
}

$id = (int)$_GET['id'];

// Obtener datos del producto
$stmt = $conexion->prepare("SELECT ID_PRODUCTO, CODIGO, NOMBRE, PRECIO, DESCRIPCIOON, IMAGEN_RUTA, STOCK, ACTIVO, ID_CATEGORIA, ID_PROVEEDOR FROM producto WHERE ID_PRODUCTO = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php?error=producto_no_existe");
    exit();
}

$producto = $result->fetch_assoc();

// Obtener categorías para el formulario
$query_categorias = "SELECT ID_CATEGORIA, NOMBRE FROM categoria WHERE ACTIVO = 1 ORDER BY NOMBRE";
$result_categorias = $conexion->query($query_categorias);

// Obtener proveedores para el formulario
$query_proveedores = "SELECT ID_PROVEEDOR, NOMBRE FROM proveedor ORDER BY NOMBRE";
$result_proveedores = $conexion->query($query_proveedores);

// Procesar el formulario de actualización
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo = trim($_POST['codigo']);
    $nombre = trim($_POST['nombre']);
    $precio = (float)$_POST['precio'];
    $descripcion = trim($_POST['descripcion']);
    $stock = (int)$_POST['stock'];
    $categoria = $_POST['categoria'];
    $proveedor = $_POST['proveedor'];
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // Validaciones
    if (empty($codigo) || empty($nombre) || empty($categoria) || empty($proveedor)) {
        $error = "Los campos código, nombre, categoría y proveedor son obligatorios";
    } elseif ($precio <= 0) {
        $error = "El precio debe ser mayor a 0";
    } elseif ($stock < 0) {
        $error = "El stock no puede ser negativo";
    } else {
        // Verificar si el código ya existe en otro producto
        $stmt = $conexion->prepare("SELECT ID_PRODUCTO FROM producto WHERE CODIGO = ? AND ID_PRODUCTO != ?");
        $stmt->bind_param("si", $codigo, $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "El código de producto ya está registrado por otro producto";
        } else {
            // Verificar si el nombre ya existe en otro producto
            $stmt = $conexion->prepare("SELECT ID_PRODUCTO FROM producto WHERE NOMBRE = ? AND ID_PRODUCTO != ?");
            $stmt->bind_param("si", $nombre, $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "El nombre de producto ya está registrado por otro producto";
            } else {
                // Procesar nueva imagen si se subió
                $imagen_ruta = $producto['IMAGEN_RUTA']; // Mantener imagen actual por defecto
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
                            // Eliminar imagen anterior si existe
                            if (!empty($producto['IMAGEN_RUTA'])) {
                                $ruta_imagen_anterior = '../../' . $producto['IMAGEN_RUTA'];
                                if (file_exists($ruta_imagen_anterior)) {
                                    unlink($ruta_imagen_anterior);
                                }
                            }
                            $imagen_ruta = 'images/productos/' . $nombre_archivo;
                        } else {
                            $error = "Error al subir la imagen";
                        }
                    } else {
                        $error = "Solo se permiten archivos JPG, JPEG, PNG y GIF";
                    }
                }
                
                if (empty($error)) {
                    // Actualizar producto
                    $stmt = $conexion->prepare("UPDATE producto SET CODIGO = ?, NOMBRE = ?, PRECIO = ?, DESCRIPCIOON = ?, IMAGEN_RUTA = ?, STOCK = ?, ACTIVO = ?, FECHA_ACTUALIZACION = NOW(), ID_CATEGORIA = ?, ID_PROVEEDOR = ? WHERE ID_PRODUCTO = ?");
                    $stmt->bind_param("ssdssiiiii", $codigo, $nombre, $precio, $descripcion, $imagen_ruta, $stock, $activo, $categoria, $proveedor, $id);
                    
                    if ($stmt->execute()) {
                        $mensaje = "Producto actualizado exitosamente";
                        // Actualizar datos en la variable $producto
                        $producto['CODIGO'] = $codigo;
                        $producto['NOMBRE'] = $nombre;
                        $producto['PRECIO'] = $precio;
                        $producto['DESCRIPCIOON'] = $descripcion;
                        $producto['IMAGEN_RUTA'] = $imagen_ruta;
                        $producto['STOCK'] = $stock;
                        $producto['ACTIVO'] = $activo;
                        $producto['ID_CATEGORIA'] = $categoria;
                        $producto['ID_PROVEEDOR'] = $proveedor;
                    } else {
                        $error = "Error al actualizar el producto: " . $conexion->error;
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
    <title>Editar Producto - Administración</title>
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
        }
        
        .info-text {
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
            <h1>✏️ Editar Producto</h1>
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
                           value="<?php echo htmlspecialchars($producto['CODIGO']); ?>">
                    <div class="info-text">Código único para identificar el producto</div>
                </div>
                
                <div class="form-group">
                    <label for="nombre">Nombre del Producto:</label>
                    <input type="text" id="nombre" name="nombre" required maxlength="100"
                           value="<?php echo htmlspecialchars($producto['NOMBRE']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="descripcion">Descripción:</label>
                    <textarea id="descripcion" name="descripcion" maxlength="1000"
                              placeholder="Descripción detallada del producto..."><?php echo htmlspecialchars($producto['DESCRIPCIOON']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="precio">Precio (S/):</label>
                    <input type="number" id="precio" name="precio" step="0.01" min="0" required
                           value="<?php echo $producto['PRECIO']; ?>">
                </div>
                
                <div class="form-group">
                    <label for="stock">Stock:</label>
                    <input type="number" id="stock" name="stock" min="0" required
                           value="<?php echo $producto['STOCK']; ?>">
                    <div class="info-text">Cantidad disponible en inventario</div>
                </div>
                
                <div class="form-group">
                    <label for="categoria">Categoría:</label>
                    <select id="categoria" name="categoria" required>
                        <option value="">Seleccionar categoría</option>
                        <?php while ($cat = $result_categorias->fetch_assoc()): ?>
                            <option value="<?php echo $cat['ID_CATEGORIA']; ?>" 
                                    <?php echo ($producto['ID_CATEGORIA'] == $cat['ID_CATEGORIA']) ? 'selected' : ''; ?>>
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
                                    <?php echo ($producto['ID_PROVEEDOR'] == $prov['ID_PROVEEDOR']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($prov['NOMBRE']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="imagen">Imagen del Producto:</label>
                    <input type="file" id="imagen" name="imagen" accept="image/*" onchange="previewImage(this)">
                    <div class="info-text">Formatos permitidos: JPG, JPEG, PNG, GIF. Máximo 2MB</div>
                    <?php if (!empty($producto['IMAGEN_RUTA'])): ?>
                        <div style="margin-top: 10px;">
                            <strong>Imagen actual:</strong><br>
                            <img src="../../<?php echo htmlspecialchars($producto['IMAGEN_RUTA']); ?>" 
                                 class="imagen-preview" alt="Imagen actual del producto">
                        </div>
                    <?php endif; ?>
                    <img id="imagen-preview" class="imagen-preview" alt="Vista previa" style="display: none;">
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="activo" name="activo" <?php echo ($producto['ACTIVO'] == 1) ? 'checked' : ''; ?>>
                        <label for="activo">Producto Activo</label>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" class="btn btn-primary">✅ Actualizar Producto</button>
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
