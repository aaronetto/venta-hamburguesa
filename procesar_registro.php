<?php
session_start();

require_once 'config.php';
$conexion = getConnection();

if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}

// Verificar si todos los datos llegaron del formulario
if (isset($_POST['NOMBRES']) && isset($_POST['APELLIDOS']) && isset($_POST['CORREO']) && isset($_POST['CLAVE']) && isset($_POST['ROL'])) {
    
    $nombres = trim($_POST['NOMBRES']);
    $apellidos = trim($_POST['APELLIDOS']);
    $correo = trim($_POST['CORREO']);
    $clave = $_POST['CLAVE'];
    $rol = $_POST['ROL'];
    
    // Validaciones
    if (empty($nombres) || empty($apellidos) || empty($correo) || empty($clave) || empty($rol)) {
        header("Location: login_registro.php?error=campos_vacios");
        exit();
    }
    
    if (strlen($clave) < 6) {
        header("Location: login_registro.php?error=clave_corta");
        exit();
    }
    
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        header("Location: login_registro.php?error=correo_invalido");
        exit();
    }
    
    // Verificar si el correo ya existe
    $stmt = $conexion->prepare("SELECT ID_USUARIO FROM usuario WHERE CORREO = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        header("Location: login_registro.php?error=correo_existe");
        exit();
    }
    $stmt->close();
    
    // Cifrar la contraseña
    $clave_hash = password_hash($clave, PASSWORD_DEFAULT);
    
    // Insertar nuevo usuario usando prepared statement
    $stmt = $conexion->prepare("INSERT INTO usuario (NOMBRES, APELLIDOS, CORREO, CLAVE, ROL, ACTIVO) VALUES (?, ?, ?, ?, ?, 1)");
    $stmt->bind_param("sssss", $nombres, $apellidos, $correo, $clave_hash, $rol);
    
    if ($stmt->execute()) {
        // Obtener el ID del usuario recién creado
        $usuario_id = $conexion->insert_id;
        
        // Guardar información del usuario en sesión
        $_SESSION['usuario'] = $nombres . ' ' . $apellidos;
        $_SESSION['usuario_id'] = $usuario_id;
        $_SESSION['usuario_rol'] = $rol;
        $_SESSION['usuario_correo'] = $correo;
        
        header("Location: plataforma.php");
        exit();
    } else {
        header("Location: login_registro.php?error=error_registro");
        exit();
    }
    
    $stmt->close();
} else {
    header("Location: login_registro.php?error=faltan_datos");
    exit();
}

$conexion->close();
?>