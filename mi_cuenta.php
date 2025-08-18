<?php
session_start();

// Verificar si el cliente está logueado
if (!isset($_SESSION['cliente_id'])) {
    header("Location: cuenta_cliente.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Cuenta - Hamburguesas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <style>
        .mi-cuenta-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .cuenta-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .cuenta-header h1 {
            color: #d0851c;
            margin-bottom: 10px;
        }

        .info-section {
            margin-bottom: 30px;
        }

        .info-section h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 20px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .info-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #d0851c;
        }

        .info-item label {
            display: block;
            font-weight: 600;
            color: #666;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .info-item .value {
            font-size: 16px;
            color: #333;
            font-weight: 500;
        }

        .actions-section {
            text-align: center;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #f0f0f0;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 0 10px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary {
            background-color: #d0851c;
            color: white;
        }

        .btn-primary:hover {
            background-color: #bca417;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background-color: #c0392b;
        }

        .volver-inicio {
            text-align: center;
            margin-top: 20px;
        }

        .volver-inicio a {
            color: #666;
            text-decoration: none;
        }

        .volver-inicio a:hover {
            color: #d0851c;
        }

        .mensaje {
            padding: 12px;
            border-radius: 8px;
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
    </style>
</head>
<body>
    <div class="mi-cuenta-container">
        <div class="cuenta-header">
            <h1>Mi Cuenta</h1>
            <p>Bienvenido, <?php echo htmlspecialchars($_SESSION['cliente_nombre']); ?></p>
        </div>

        <?php
        // Mostrar mensajes de éxito o error
        if (isset($_GET['success'])) {
            $mensaje = '';
            switch ($_GET['success']) {
                case 'datos_actualizados':
                    $mensaje = '✅ Tus datos han sido actualizados correctamente.';
                    break;
            }
            if ($mensaje) {
                echo '<div class="mensaje success">' . $mensaje . '</div>';
            }
        }

        if (isset($_GET['error'])) {
            $mensaje = '';
            switch ($_GET['error']) {
                case 'error_actualizacion':
                    $mensaje = '❌ Error al actualizar los datos. Inténtalo de nuevo.';
                    break;
            }
            if ($mensaje) {
                echo '<div class="mensaje error">' . $mensaje . '</div>';
            }
        }
        ?>

        <div class="info-section">
            <h2>Información Personal</h2>
            <div class="info-grid">
                <div class="info-item">
                    <label>Nombres Completos</label>
                    <div class="value"><?php echo htmlspecialchars($_SESSION['cliente_nombre']); ?></div>
                </div>
                <div class="info-item">
                    <label>Correo Electrónico</label>
                    <div class="value"><?php echo htmlspecialchars($_SESSION['cliente_correo']); ?></div>
                </div>
                <div class="info-item">
                    <label>Teléfono</label>
                    <div class="value"><?php echo htmlspecialchars($_SESSION['cliente_telefono'] ?? 'No registrado'); ?></div>
                </div>
                <div class="info-item">
                    <label>ID de Cliente</label>
                    <div class="value">#<?php echo htmlspecialchars($_SESSION['cliente_id']); ?></div>
                </div>
            </div>
        </div>

        <!-- <div class="info-section">
            <h2>Carrito de Compras</h2>
            <div class="info-grid">
                <div class="info-item">
                    <label>ID del Carrito</label>
                    <div class="value">#<?php echo htmlspecialchars($_SESSION['carrito_id'] ?? 'No disponible'); ?></div>
                </div>
                <div class="info-item">
                    <label>Estado del Carrito</label>
                    <div class="value">Activo</div>
                </div>
            </div>
        </div> -->

        <div class="actions-section">
            <a href="index.php" class="btn btn-primary">Volver al Inicio</a>
            <!-- <a href="editar_perfil.php" class="btn btn-secondary">Editar Perfil</a> -->
            <a href="logout_cliente.php" class="btn btn-danger">Cerrar Sesión</a>
        </div>

        <!-- <div class="volver-inicio">
            <a href="index.php">← Volver al inicio</a>
        </div> -->
    </div>
</body>
</html>
