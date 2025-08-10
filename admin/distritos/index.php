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
    $id_distrito = $_GET['eliminar'];
    
    // Verificar si hay direcciones de clientes asociadas
    $check_query = "SELECT COUNT(*) as total FROM direccion_cliente WHERE ID_DISTRITO = ?";
    $check_stmt = $conexion->prepare($check_query);
    $check_stmt->bind_param("i", $id_distrito);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $check_row = $check_result->fetch_assoc();
    
    if ($check_row['total'] > 0) {
        header('Location: index.php?error=distrito_con_direcciones');
        exit();
    }
    
    // Eliminar el distrito
    $delete_query = "DELETE FROM distrito WHERE ID_DISTRITO = ?";
    $delete_stmt = $conexion->prepare($delete_query);
    $delete_stmt->bind_param("i", $id_distrito);
    
    if ($delete_stmt->execute()) {
        header('Location: index.php?success=distrito_eliminado');
    } else {
        header('Location: index.php?error=error_eliminar');
    }
    exit();
}

// Obtener distritos con información adicional
$query = "SELECT d.*, p.NOMBRE as PROVINCIA_NOMBRE, c.NOMBRE as CIUDAD_NOMBRE,
          (SELECT COUNT(*) FROM direccion_cliente WHERE ID_DISTRITO = d.ID_DISTRITO) as total_direcciones
          FROM distrito d 
          INNER JOIN provincia p ON d.ID_PROVINCIA = p.ID_PROVINCIA
          INNER JOIN ciudad c ON p.ID_CIUDAD = c.ID_CIUDAD
          ORDER BY c.NOMBRE, p.NOMBRE, d.NOMBRE";
$result = $conexion->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Distritos - Administrador</title>
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
        
        .distritos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .distrito-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .distrito-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }
        
        .distrito-nombre {
            font-size: 1.4em;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .distrito-ubicacion {
            color: #007bff;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .distrito-info {
            color: #666;
            margin-bottom: 15px;
        }
        
        .distrito-acciones {
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
            <h1 class="admin-title">🏘️ Gestión de Distritos</h1>
            <a href="registrar.php" class="btn-nuevo">+ Nuevo Distrito</a>
        </div>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="mensaje mensaje-success">
                <?php 
                switch($_GET['success']) {
                    case 'distrito_creado':
                        echo "✅ Distrito registrado exitosamente.";
                        break;
                    case 'distrito_actualizado':
                        echo "✅ Distrito actualizado exitosamente.";
                        break;
                    case 'distrito_eliminado':
                        echo "✅ Distrito eliminado exitosamente.";
                        break;
                }
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="mensaje mensaje-error">
                <?php 
                switch($_GET['error']) {
                    case 'distrito_con_direcciones':
                        echo "❌ No se puede eliminar el distrito porque tiene direcciones de clientes asociadas.";
                        break;
                    case 'error_eliminar':
                        echo "❌ Error al eliminar el distrito.";
                        break;
                }
                ?>
            </div>
        <?php endif; ?>
        
        <div class="distritos-grid">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($distrito = $result->fetch_assoc()): ?>
                    <div class="distrito-card">
                        <div class="distrito-nombre"><?php echo htmlspecialchars($distrito['NOMBRE']); ?></div>
                        <div class="distrito-ubicacion">
                            🏙️ <?php echo htmlspecialchars($distrito['CIUDAD_NOMBRE']); ?> > 
                            🏛️ <?php echo htmlspecialchars($distrito['PROVINCIA_NOMBRE']); ?>
                        </div>
                        <div class="distrito-info">
                            <strong>ID:</strong> <?php echo $distrito['ID_DISTRITO']; ?><br>
                            <strong>Direcciones:</strong> <?php echo $distrito['total_direcciones']; ?>
                        </div>
                        <div class="distrito-acciones">
                            <a href="editar.php?id=<?php echo $distrito['ID_DISTRITO']; ?>" class="btn-editar">Editar</a>
                            <a href="index.php?eliminar=<?php echo $distrito['ID_DISTRITO']; ?>" 
                               class="btn-eliminar" 
                               onclick="return confirm('¿Estás seguro de que quieres eliminar este distrito?')">Eliminar</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #666;">
                    <h3>No hay distritos registrados</h3>
                    <p>Comienza registrando el primer distrito.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
