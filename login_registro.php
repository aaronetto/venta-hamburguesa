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
            width: 350px;
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

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 6px;
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
        }

        .btn:hover {
            background-color: #e73370;
        }

        .form-box p {
            text-align: center;
            margin-top: 10px;
            font-size: 14px;
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
       <form action="procesar_login.php" method="POST">
    <input type="text" name="CORREO" placeholder="Correo" required>
    <input type="password" name="CLAVE" placeholder="Clave" required>
    <button type="submit">Iniciar sesión</button>
</form>
</div>

        <!-- FORMULARIO DE REGISTRO -->
        <div id="registro" class="form-box">
            <form action="procesar_registro.php" method="post">
                 <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="NOMB_USUARIO" placeholder="Nombre completo" required>
                 <label for="correo">Correo</label>
                <input type="email" id="correo"name="CORREO" placeholder="Correo" required>
                 <label for="clave">Contraseña</label>
                <input type="password" id="contraseña" name="CLAVE" placeholder="Contraseña" required>
                 
                <input type="submit" class="btn" value="Registrarse">
            </form>
             <p class="Volver al Inicio"></p>
              ¿Desea volver al Inicio? <a href="index.html">Haz click Aqui</a>
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
 