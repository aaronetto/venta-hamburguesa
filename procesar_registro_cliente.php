<?php
session_start();

require_once 'config.php';
$conexion = getConnection();

if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}

// Verificar si todos los datos obligatorios llegaron del formulario
if (isset($_POST['NOMBRES']) && isset($_POST['APELLIDOS']) && isset($_POST['CORREO']) && isset($_POST['CLAVE']) && isset($_POST['CONFIRMAR_CLAVE'])) {
    
    $nombres = trim($_POST['NOMBRES']);
    $apellidos = trim($_POST['APELLIDOS']);
    $correo = trim($_POST['CORREO']);
    $clave = $_POST['CLAVE'];
    $confirmar_clave = $_POST['CONFIRMAR_CLAVE'];
    $telefono = trim($_POST['TELEFONO'] ?? '');
    
    // Validaciones
    if (empty($nombres) || empty($apellidos) || empty($correo) || empty($clave) || empty($confirmar_clave)) {
        header("Location: registro_cliente.php?error=campos_vacios&nombres=" . urlencode($nombres) . "&apellidos=" . urlencode($apellidos) . "&correo=" . urlencode($correo) . "&telefono=" . urlencode($telefono));
        exit();
    }
    
    if (strlen($clave) < 6) {
        header("Location: registro_cliente.php?error=clave_corta&nombres=" . urlencode($nombres) . "&apellidos=" . urlencode($apellidos) . "&correo=" . urlencode($correo) . "&telefono=" . urlencode($telefono));
        exit();
    }
    
    if ($clave !== $confirmar_clave) {
        header("Location: registro_cliente.php?error=claves_no_coinciden&nombres=" . urlencode($nombres) . "&apellidos=" . urlencode($apellidos) . "&correo=" . urlencode($correo) . "&telefono=" . urlencode($telefono));
        exit();
    }
    
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        header("Location: registro_cliente.php?error=correo_invalido&nombres=" . urlencode($nombres) . "&apellidos=" . urlencode($apellidos) . "&correo=" . urlencode($correo) . "&telefono=" . urlencode($telefono));
        exit();
    }
    
    // Verificar si el correo ya existe
    $stmt = $conexion->prepare("SELECT ID_CLIENTE FROM cliente WHERE CORREO = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        header("Location: registro_cliente.php?error=correo_existe&nombres=" . urlencode($nombres) . "&apellidos=" . urlencode($apellidos) . "&correo=" . urlencode($correo) . "&telefono=" . urlencode($telefono));
        exit();
    }
    $stmt->close();
    
    // Cifrar la contraseña
    $clave_hash = password_hash($clave, PASSWORD_DEFAULT);
    
    // Insertar nuevo cliente usando prepared statement
    $stmt = $conexion->prepare("INSERT INTO cliente (NOMBRES, APELLIDOS, CORREO, CLAVE, TELEFONO, ACTIVO) VALUES (?, ?, ?, ?, ?, 1)");
    $stmt->bind_param("sssss", $nombres, $apellidos, $correo, $clave_hash, $telefono);
    
    if ($stmt->execute()) {
        // Obtener el ID del cliente recién creado
        $cliente_id = $conexion->insert_id;
        
        // Crear carrito activo para el nuevo cliente
        $carrito_stmt = $conexion->prepare("INSERT INTO carrito (ESTADO, ID_CLIENTE) VALUES ('ACTIVO', ?)");
        $carrito_stmt->bind_param("i", $cliente_id);
        $carrito_stmt->execute();
        $carrito_id = $conexion->insert_id;
        
        // Guardar información del cliente en sesión
        $_SESSION['cliente_id'] = $cliente_id;
        $_SESSION['cliente_nombre'] = $nombres . ' ' . $apellidos;
        $_SESSION['cliente_correo'] = $correo;
        $_SESSION['cliente_telefono'] = $telefono;
        $_SESSION['tipo_usuario'] = 'cliente';
        $_SESSION['carrito_id'] = $carrito_id;
        
        $stmt->close();
        $carrito_stmt->close();
        
        header("Location: cuenta_cliente.php?success=registro_exitoso");
        exit();
    } else {
        header("Location: registro_cliente.php?error=error_registro&nombres=" . urlencode($nombres) . "&apellidos=" . urlencode($apellidos) . "&correo=" . urlencode($correo) . "&telefono=" . urlencode($telefono));
        exit();
    }
    
    $stmt->close();
} else {
    header("Location: registro_cliente.php?error=faltan_datos");
    exit();
}

$conexion->close();
?>
