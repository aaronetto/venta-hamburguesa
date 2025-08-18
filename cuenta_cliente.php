<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Cuenta - Hamburguesas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <style>
        .cuenta-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .cuenta-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .cuenta-header h1 {
            color: #d0851c;
            margin-bottom: 10px;
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

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #d0851c;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background-color: #d0851c;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-login:hover {
            background-color: #bca417;
        }

        .registro-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }

        .registro-link a {
            color: #d0851c;
            text-decoration: none;
            font-weight: 600;
        }

        .registro-link a:hover {
            text-decoration: underline;
        }

        .mensaje {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .mensaje.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .mensaje.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
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
    </style>
</head>
<body>
    <div class="cuenta-container">
        <div class="cuenta-header">
            <h1>Mi Cuenta</h1>
            <p>Inicia sesión para acceder a tu cuenta</p>
        </div>

        <?php
        // Mostrar mensajes de error
        if (isset($_GET['error'])) {
            $mensaje = '';
            switch ($_GET['error']) {
                case 'campos_vacios':
                    $mensaje = '❌ Todos los campos son obligatorios';
                    break;
                case 'correo_invalido':
                    $mensaje = '❌ El formato del correo electrónico no es válido';
                    break;
                case 'cliente_no_existe':
                    $mensaje = '❌ Cliente no encontrado';
                    break;
                case 'clave_incorrecta':
                    $mensaje = '❌ Contraseña incorrecta';
                    break;
                case 'cliente_inactivo':
                    $mensaje = '❌ Cuenta inactiva. Contacte al administrador';
                    break;
            }
            if ($mensaje) {
                echo '<div class="mensaje error">' . $mensaje . '</div>';
            }
        }

        // Mostrar mensajes de éxito
        if (isset($_GET['success'])) {
            $mensaje = '';
            switch ($_GET['success']) {
                case 'registro_exitoso':
                    $mensaje = '✅ Cuenta creada exitosamente. Ya puedes iniciar sesión.';
                    break;
            }
            if ($mensaje) {
                echo '<div class="mensaje success">' . $mensaje . '</div>';
            }
        }
        ?>

        <form action="procesar_login_cliente.php" method="POST">
            <div class="form-group">
                <label for="correo">Correo Electrónico</label>
                <input type="email" id="correo" name="CORREO" placeholder="Ingrese su correo" required>
            </div>
            <div class="form-group">
                <label for="clave">Contraseña</label>
                <input type="password" id="clave" name="CLAVE" placeholder="Ingrese su contraseña" required>
            </div>
            <button type="submit" class="btn-login">Iniciar Sesión</button>
        </form>

        <div class="registro-link">
            <p>¿No tienes una cuenta? <a href="registro_cliente.php">Regístrate aquí</a></p>
        </div>

        <div class="volver-inicio">
            <a href="index.php">← Volver al inicio</a>
        </div>
    </div>
</body>
</html>
