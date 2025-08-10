<?php
session_start();
require_once '../../config.php';
require_once '../../auth_functions.php';

// Verificar acceso al módulo
requerirAccesoModulo('provincias');
$conexion = getConnection();

// Procesar eliminación si se solicita
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    $id_provincia = $_GET['eliminar'];
    
    // Verificar si hay distritos asociados
    $check_query = "SELECT COUNT(*) as total FROM distrito WHERE ID_PROVINCIA = ?";
    $check_stmt = $conexion->prepare($check_query);
    $check_stmt->bind_param("i", $id_provincia);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $check_row = $check_result->fetch_assoc();
    
    if ($check_row['total'] > 0) {
        header('Location: index.php?error=provincia_con_distritos');
        exit();
    }
    
    // Eliminar la provincia
    $delete_query = "DELETE FROM provincia WHERE ID_PROVINCIA = ?";
    $delete_stmt = $conexion->prepare($delete_query);
    $delete_stmt->bind_param("i", $id_provincia);
    
    if ($delete_stmt->execute()) {
        header('Location: index.php?success=provincia_eliminada');
    } else {
        header('Location: index.php?error=error_eliminar');
    }
    exit();
}

// Obtener provincias con información adicional
$query = "SELECT p.*, c.NOMBRE as CIUDAD_NOMBRE,
          (SELECT COUNT(*) FROM distrito WHERE ID_PROVINCIA = p.ID_PROVINCIA) as total_distritos
          FROM provincia p 
          INNER JOIN ciudad c ON p.ID_CIUDAD = c.ID_CIUDAD
          ORDER BY c.NOMBRE, p.NOMBRE";
$result = $conexion->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Provincias - Administrador</title>
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
        
        .provincias-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .provincia-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .provincia-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }
        
        .provincia-nombre {
            font-size: 1.4em;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .provincia-ciudad {
            color: #007bff;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .provincia-info {
            color: #666;
            margin-bottom: 15px;
        }
        
        .provincia-acciones {
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
            <h1 class="admin-title">🏛️ Gestión de Provincias</h1>
            <a href="registrar.php" class="btn-nuevo">+ Nueva Provincia</a>
        </div>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="mensaje mensaje-success">
                <?php 
                switch($_GET['success']) {
                    case 'provincia_creada':
                        echo "✅ Provincia registrada exitosamente.";
                        break;
                    case 'provincia_actualizada':
                        echo "✅ Provincia actualizada exitosamente.";
                        break;
                    case 'provincia_eliminada':
                        echo "✅ Provincia eliminada exitosamente.";
                        break;
                }
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="mensaje mensaje-error">
                <?php 
                switch($_GET['error']) {
                    case 'provincia_con_distritos':
                        echo "❌ No se puede eliminar la provincia porque tiene distritos asociados.";
                        break;
                    case 'error_eliminar':
                        echo "❌ Error al eliminar la provincia.";
                        break;
                }
                ?>
            </div>
        <?php endif; ?>
        
        <div class="provincias-grid">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($provincia = $result->fetch_assoc()): ?>
                    <div class="provincia-card">
                        <div class="provincia-nombre"><?php echo htmlspecialchars($provincia['NOMBRE']); ?></div>
                        <div class="provincia-ciudad">🏙️ <?php echo htmlspecialchars($provincia['CIUDAD_NOMBRE']); ?></div>
                        <div class="provincia-info">
                            <strong>ID:</strong> <?php echo $provincia['ID_PROVINCIA']; ?><br>
                            <strong>Distritos:</strong> <?php echo $provincia['total_distritos']; ?>
                        </div>
                        <div class="provincia-acciones">
                            <a href="editar.php?id=<?php echo $provincia['ID_PROVINCIA']; ?>" class="btn-editar">Editar</a>
                            <a href="index.php?eliminar=<?php echo $provincia['ID_PROVINCIA']; ?>" 
                               class="btn-eliminar" 
                               onclick="return confirm('¿Estás seguro de que quieres eliminar esta provincia?')">Eliminar</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #666;">
                    <h3>No hay provincias registradas</h3>
                    <p>Comienza registrando la primera provincia.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
