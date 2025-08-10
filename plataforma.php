<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login_registro.php");
    exit();
}

$nombre = $_SESSION['usuario'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plataforma Administrativa</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .admin-header {
            background: linear-gradient(135deg, #e53e2e, #cc0000);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .admin-menu {
            display: flex;
            flex-direction: column;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .admin-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            border: 1px solid #e9ecef;
        }
        
        .section-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e53e2e;
        }
        
        .section-title {
            color: #e53e2e;
            font-size: 24px;
            font-weight: bold;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-description {
            color: #666;
            font-size: 14px;
            margin: 5px 0 0 0;
        }
        
        .section-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .admin-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            border-left: 4px solid #e53e2e;
        }
        
        .admin-card:hover {
            transform: translateY(-5px);
        }
        
        .admin-card h3 {
            color: #e53e2e;
            margin-bottom: 15px;
            font-size: 20px;
        }
        
        .admin-card p {
            color: #666;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .admin-btn {
            display: inline-block;
            background: #e53e2e;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.3s ease;
        }
        
        .admin-btn:hover {
            background: #cc0000;
        }
        
        .logout-btn {
            background: #333;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.3s ease;
        }
        
        .logout-btn:hover {
            background: #555;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #e53e2e;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>🍔 Panel de Administración</h1>
            <p>Bienvenido, <?php echo htmlspecialchars($nombre); ?> 👋</p>
        </div>

        <?php
        // Obtener estadísticas básicas
        require_once 'config.php';
        $conexion = getConnection();
        
        // Contar categorías
        $query_cat = "SELECT COUNT(*) as total FROM categoria";
        $result_cat = $conexion->query($query_cat);
        $total_categorias = $result_cat->fetch_assoc()['total'];
        
        // Contar productos
        $query_prod = "SELECT COUNT(*) as total FROM producto";
        $result_prod = $conexion->query($query_prod);
        $total_productos = $result_prod->fetch_assoc()['total'];
        
        // Contar pedidos
        $query_ped = "SELECT COUNT(*) as total FROM pedido";
        $result_ped = $conexion->query($query_ped);
        $total_pedidos = $result_ped->fetch_assoc()['total'];
        
        // Contar usuarios
        $query_usu = "SELECT COUNT(*) as total FROM usuario";
        $result_usu = $conexion->query($query_usu);
        $total_usuarios = $result_usu->fetch_assoc()['total'];
        
        // Contar clientes
        $query_cli = "SELECT COUNT(*) as total FROM cliente";
        $result_cli = $conexion->query($query_cli);
        $total_clientes = $result_cli->fetch_assoc()['total'];
        ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_categorias; ?></div>
                <div class="stat-label">Categorías</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_productos; ?></div>
                <div class="stat-label">Productos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_pedidos; ?></div>
                <div class="stat-label">Pedidos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_usuarios; ?></div>
                <div class="stat-label">Usuarios</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_clientes; ?></div>
                <div class="stat-label">Clientes</div>
            </div>
        </div>

        <div class="admin-menu">
            <!-- Sección Productos -->
            <div class="admin-section">
                <div class="section-header">
                    <h2 class="section-title">🍔 Sección Productos</h2>
                    <p class="section-description">Administra todo lo relacionado con productos, categorías y proveedores</p>
                </div>
                <div class="section-cards">
                    <div class="admin-card">
                        <h3>📂 Gestión de Categorías</h3>
                        <p>Administra las categorías de productos del sistema</p>
                        <a href="admin/categorias/" class="admin-btn">Gestionar Categorías</a>
                    </div>
                    <div class="admin-card">
                        <h3>🍔 Gestión de Productos</h3>
                        <p>Administra los productos y sus características</p>
                        <a href="admin/productos/" class="admin-btn">Gestionar Productos</a>
                    </div>
                    <div class="admin-card">
                        <h3>🏢 Gestión de Proveedores</h3>
                        <p>Administra los proveedores del sistema</p>
                        <a href="admin/proveedores/" class="admin-btn">Gestionar Proveedores</a>
                    </div>
                </div>
            </div>

            <!-- Sección Clientes -->
            <div class="admin-section">
                <div class="section-header">
                    <h2 class="section-title">👤 Sección Clientes</h2>
                    <p class="section-description">Administra la información de los clientes</p>
                </div>
                <div class="section-cards">
                    <div class="admin-card">
                        <h3>👤 Gestión de Clientes</h3>
                        <p>Administra los clientes y sus direcciones</p>
                        <a href="admin/clientes/" class="admin-btn">Gestionar Clientes</a>
                    </div>
                </div>
            </div>

            <!-- Sección Pedidos -->
            <div class="admin-section">
                <div class="section-header">
                    <h2 class="section-title">🛒 Sección Pedidos</h2>
                    <p class="section-description">Administra los pedidos y sus detalles</p>
                </div>
                <div class="section-cards">
                    <div class="admin-card">
                        <h3>🛒 Gestión de Pedidos</h3>
                        <p>Administra los pedidos y sus detalles</p>
                        <a href="admin/pedidos/" class="admin-btn">Gestionar Pedidos</a>
                    </div>
                </div>
            </div>

            <!-- Sección Usuarios -->
            <div class="admin-section">
                <div class="section-header">
                    <h2 class="section-title">👥 Sección Usuarios</h2>
                    <p class="section-description">Administra los usuarios del sistema</p>
                </div>
                <div class="section-cards">
                    <div class="admin-card">
                        <h3>👥 Gestión de Usuarios</h3>
                        <p>Administra los usuarios del sistema</p>
                        <a href="admin/usuarios/" class="admin-btn">Gestionar Usuarios</a>
                    </div>
                </div>
            </div>

            <!-- Sección Mantenimiento -->
            <div class="admin-section">
                <div class="section-header">
                    <h2 class="section-title">🔧 Sección Mantenimiento</h2>
                    <p class="section-description">Administra las configuraciones geográficas del sistema</p>
                </div>
                <div class="section-cards">
                    <div class="admin-card">
                        <h3>🏙️ Gestión de Ciudades</h3>
                        <p>Administra las ciudades del sistema</p>
                        <a href="admin/ciudades/" class="admin-btn">Gestionar Ciudades</a>
                    </div>
                    <div class="admin-card">
                        <h3>🏛️ Gestión de Provincias</h3>
                        <p>Administra las provincias del sistema</p>
                        <a href="admin/provincias/" class="admin-btn">Gestionar Provincias</a>
                    </div>
                    <div class="admin-card">
                        <h3>🏘️ Gestión de Distritos</h3>
                        <p>Administra los distritos del sistema</p>
                        <a href="admin/distritos/" class="admin-btn">Gestionar Distritos</a>
                    </div>
                </div>
            </div>

        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="logout.php" class="logout-btn">🔓 Cerrar Sesión</a>
        </div>
    </div>
</body>
</html>