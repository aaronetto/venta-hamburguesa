<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Cliente - Hamburguesas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <style>
        .registro-container {
            max-width: 500px;
            margin: 30px auto;
            padding: 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .registro-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .registro-header h1 {
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

        .form-row {
            display: flex;
            gap: 15px;
        }

        .form-row .form-group {
            flex: 1;
        }

        .btn-registro {
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

        .btn-registro:hover {
            background-color: #bca417;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }

        .login-link a {
            color: #d0851c;
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
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

        .requerido {
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <div class="registro-container">
        <div class="registro-header">
            <h1>Crear Cuenta</h1>
            <p>Completa tus datos para crear tu cuenta</p>
        </div>

        <?php
        // Mostrar mensajes de error
        if (isset($_GET['error'])) {
            $mensaje = '';
            switch ($_GET['error']) {
                case 'campos_vacios':
                    $mensaje = '❌ Todos los campos obligatorios deben estar completos';
                    break;
                case 'correo_invalido':
                    $mensaje = '❌ El formato del correo electrónico no es válido';
                    break;
                case 'correo_existe':
                    $mensaje = '❌ El correo electrónico ya está registrado';
                    break;
                case 'clave_corta':
                    $mensaje = '❌ La contraseña debe tener al menos 6 caracteres';
                    break;
                case 'claves_no_coinciden':
                    $mensaje = '❌ Las contraseñas no coinciden';
                    break;
                case 'error_registro':
                    $mensaje = '❌ Error al crear la cuenta. Inténtalo de nuevo.';
                    break;
                case 'faltan_datos':
                    $mensaje = '❌ Faltan datos del formulario';
                    break;
            }
            if ($mensaje) {
                echo '<div class="mensaje error">' . $mensaje . '</div>';
            }
        }
        ?>

        <form action="procesar_registro_cliente.php" method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label for="nombres">Nombres <span class="requerido">*</span></label>
                    <input type="text" id="nombres" name="NOMBRES" placeholder="Ingrese sus nombres" required maxlength="100" value="<?php echo isset($_GET['nombres']) ? htmlspecialchars($_GET['nombres']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="apellidos">Apellidos <span class="requerido">*</span></label>
                    <input type="text" id="apellidos" name="APELLIDOS" placeholder="Ingrese sus apellidos" required maxlength="100" value="<?php echo isset($_GET['apellidos']) ? htmlspecialchars($_GET['apellidos']) : ''; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="correo">Correo Electrónico <span class="requerido">*</span></label>
                <input type="email" id="correo" name="CORREO" placeholder="Ingrese su correo" required maxlength="100" value="<?php echo isset($_GET['correo']) ? htmlspecialchars($_GET['correo']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="telefono">Teléfono</label>
                <input type="tel" id="telefono" name="TELEFONO" placeholder="Ingrese su teléfono" maxlength="45" value="<?php echo isset($_GET['telefono']) ? htmlspecialchars($_GET['telefono']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="clave">Contraseña <span class="requerido">*</span></label>
                <input type="password" id="clave" name="CLAVE" placeholder="Ingrese su contraseña" required minlength="6">
            </div>
            
            <div class="form-group">
                <label for="confirmar_clave">Confirmar Contraseña <span class="requerido">*</span></label>
                <input type="password" id="confirmar_clave" name="CONFIRMAR_CLAVE" placeholder="Confirme su contraseña" required minlength="6">
            </div>
            
            <button type="submit" class="btn-registro">Crear Cuenta</button>
        </form>

        <div class="login-link">
            <p>¿Ya tienes una cuenta? <a href="cuenta_cliente.php">Inicia sesión aquí</a></p>
        </div>

        <div class="volver-inicio">
            <a href="index.php">← Volver al inicio</a>
        </div>
    </div>
</body>
</html>
