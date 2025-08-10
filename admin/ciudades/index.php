<?php
session_start();

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'ADMINISTRADOR') {
    header('Location: ../../login_registro.php');
    exit();
}

require_once '../../config.php';
$conexion = getConnection();

// Procesar eliminación si se solicita
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    $id_ciudad = $_GET['eliminar'];
    
    // Verificar si hay provincias asociadas
    $check_query = "SELECT COUNT(*) as total FROM provincia WHERE ID_CIUDAD = ?";
    $check_stmt = $conexion->prepare($check_query);
    $check_stmt->bind_param("i", $id_ciudad);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $check_row = $check_result->fetch_assoc();
    
    if ($check_row['total'] > 0) {
        header('Location: index.php?error=ciudad_con_provincias');
        exit();
    }
    
    // Eliminar la ciudad
    $delete_query = "DELETE FROM ciudad WHERE ID_CIUDAD = ?";
    $delete_stmt = $conexion->prepare($delete_query);
    $delete_stmt->bind_param("i", $id_ciudad);
    
    if ($delete_stmt->execute()) {
        header('Location: index.php?success=ciudad_eliminada');
    } else {
        header('Location: index.php?error=error_eliminar');
    }
    exit();
}

// Obtener ciudades con información adicional
$query = "SELECT c.*, 
          (SELECT COUNT(*) FROM provincia WHERE ID_CIUDAD = c.ID_CIUDAD) as total_provincias
          FROM ciudad c 
          ORDER BY c.NOMBRE";
$result = $conexion->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Ciudades - Administrador</title>
    <link rel="stylesheet" href="../../style.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .admin-title {
            font-size: 2.5em;
            color: #333;
            margin: 0;
        }
        
        .btn-nuevo {
            background: #28a745;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background 0.3s;
        }
        
        .btn-nuevo:hover {
            background: #218838;
        }
        
        .ciudades-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .ciudad-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .ciudad-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }
        
        .ciudad-nombre {
            font-size: 1.4em;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .ciudad-info {
            color: #666;
            margin-bottom: 15px;
        }
        
        .ciudad-acciones {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-editar {
            background: #007bff;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.9em;
            flex: 1;
            text-align: center;
        }
        
        .btn-eliminar {
            background: #dc3545;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.9em;
            flex: 1;
            text-align: center;
        }
        
        .btn-editar:hover {
            background: #0056b3;
        }
        
        .btn-eliminar:hover {
            background: #c82333;
        }
        
        .mensaje {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .mensaje-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .mensaje-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
    </style>
</head>
<body>
    <div class="admin-container">
        <a href="../../plataforma.php" class="volver-btn">← Volver al Panel</a>
        
        <div class="admin-header">
            <h1 class="admin-title">🏙️ Gestión de Ciudades</h1>
            <a href="registrar.php" class="btn-nuevo">+ Nueva Ciudad</a>
        </div>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="mensaje mensaje-success">
                <?php 
                switch($_GET['success']) {
                    case 'ciudad_creada':
                        echo "✅ Ciudad registrada exitosamente.";
                        break;
                    case 'ciudad_actualizada':
                        echo "✅ Ciudad actualizada exitosamente.";
                        break;
                    case 'ciudad_eliminada':
                        echo "✅ Ciudad eliminada exitosamente.";
                        break;
                }
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="mensaje mensaje-error">
                <?php 
                switch($_GET['error']) {
                    case 'ciudad_con_provincias':
                        echo "❌ No se puede eliminar la ciudad porque tiene provincias asociadas.";
                        break;
                    case 'error_eliminar':
                        echo "❌ Error al eliminar la ciudad.";
                        break;
                }
                ?>
            </div>
        <?php endif; ?>
        
        <div class="ciudades-grid">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($ciudad = $result->fetch_assoc()): ?>
                    <div class="ciudad-card">
                        <div class="ciudad-nombre"><?php echo htmlspecialchars($ciudad['NOMBRE']); ?></div>
                        <div class="ciudad-info">
                            <strong>ID:</strong> <?php echo $ciudad['ID_CIUDAD']; ?><br>
                            <strong>Provincias:</strong> <?php echo $ciudad['total_provincias']; ?>
                        </div>
                        <div class="ciudad-acciones">
                            <a href="editar.php?id=<?php echo $ciudad['ID_CIUDAD']; ?>" class="btn-editar">Editar</a>
                            <a href="index.php?eliminar=<?php echo $ciudad['ID_CIUDAD']; ?>" 
                               class="btn-eliminar" 
                               onclick="return confirm('¿Estás seguro de que quieres eliminar esta ciudad?')">Eliminar</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #666;">
                    <h3>No hay ciudades registradas</h3>
                    <p>Comienza registrando la primera ciudad.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
