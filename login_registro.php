<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login y Registro</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: #d0851cff;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 400px;
            overflow: hidden;
        }

        .tabs {
            display: flex;
            justify-content: space-around;
            background-color: #bca417ff;
        }

        .tabs button {
            flex: 1;
            padding: 15px;
            color: white;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s;
        }

        .tabs button.active {
            background-color: #e7c933ff;
        }

        .form-box {
            padding: 25px;
            display: none;
        }

        .form-box.active {
            display: block;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background-color: #120e0bff;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn:hover {
            background-color: #e73370;
        }

        .form-box p {
            text-align: center;
            margin-top: 10px;
            font-size: 14px;
        }

        .mensaje {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
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

    </style>
</head>
<body>
    <div class="container">
        <div class="tabs">
            <button class="tab-button active" onclick="showForm('login')">Iniciar Sesión</button>
            <button class="tab-button" onclick="showForm('registro')">Registrarse</button>
        </div>

        <!-- FORMULARIO DE LOGIN -->
        <div id="login" class="form-box active">
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
                    case 'usuario_no_existe':
                        $mensaje = '❌ Usuario no encontrado';
                        break;
                    case 'clave_incorrecta':
                        $mensaje = '❌ Contraseña incorrecta';
                        break;
                    case 'usuario_inactivo':
                        $mensaje = '❌ Usuario inactivo. Contacte al administrador';
                        break;
                    case 'correo_existe':
                        $mensaje = '❌ El correo electrónico ya está registrado';
                        break;
                    case 'clave_corta':
                        $mensaje = '❌ La contraseña debe tener al menos 6 caracteres';
                        break;
                    case 'error_registro':
                        $mensaje = '❌ Error al registrar el usuario';
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
            <form action="procesar_login.php" method="POST">
                <div class="form-group">
                    <label for="correo_login">Correo Electrónico</label>
                    <input type="email" id="correo_login" name="CORREO" placeholder="Ingrese su correo" required>
                </div>
                <div class="form-group">
                    <label for="clave_login">Contraseña</label>
                    <input type="password" id="clave_login" name="CLAVE" placeholder="Ingrese su contraseña" required>
                </div>
                <button type="submit" class="btn">Iniciar Sesión</button>
            </form>
        </div>

        <!-- FORMULARIO DE REGISTRO -->
        <div id="registro" class="form-box">
            <form action="procesar_registro.php" method="post">
                <div class="form-group">
                    <label for="nombres">Nombres</label>
                    <input type="text" id="nombres" name="NOMBRES" placeholder="Ingrese sus nombres" required maxlength="100">
                </div>
                <div class="form-group">
                    <label for="apellidos">Apellidos</label>
                    <input type="text" id="apellidos" name="APELLIDOS" placeholder="Ingrese sus apellidos" required maxlength="100">
                </div>
                <div class="form-group">
                    <label for="correo">Correo Electrónico</label>
                    <input type="email" id="correo" name="CORREO" placeholder="Ingrese su correo" required maxlength="100">
                </div>
                <div class="form-group">
                    <label for="clave">Contraseña</label>
                    <input type="password" id="clave" name="CLAVE" placeholder="Ingrese su contraseña" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="rol">Rol</label>
                    <select id="rol" name="ROL" required>
                        <option value="">Seleccionar rol</option>
                        <option value="ADMINISTRADOR">Administrador</option>
                        <option value="GERENTE">Gerente</option>
                        <option value="ASISTENTE">Asistente</option>
                    </select>
                </div>
                <button type="submit" class="btn">Registrarse</button>
            </form>
            <p>¿Desea volver al Inicio? <a href="/">Haz click Aquí</a></p>
        </div>
    </div>

    <script>
        function showForm(formId) {
            const tabs = document.querySelectorAll('.tab-button');
            const forms = document.querySelectorAll('.form-box');

            tabs.forEach(tab => tab.classList.remove('active'));
            forms.forEach(form => form.classList.remove('active'));

            document.getElementById(formId).classList.add('active');

            if (formId === 'login') {
                tabs[0].classList.add('active');
            } else {
                tabs[1].classList.add('active');
            }
        }
    </script>
</body>
</html>
 