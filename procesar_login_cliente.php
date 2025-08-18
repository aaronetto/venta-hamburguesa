<?php
session_start();

require_once 'config.php';
$conexion = getConnection();

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$correo = trim($_POST['CORREO'] ?? '');
$clave = $_POST['CLAVE'] ?? '';

// Validaciones básicas
if (empty($correo) || empty($clave)) {
    header("Location: cuenta_cliente.php?error=campos_vacios");
    exit();
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    header("Location: cuenta_cliente.php?error=correo_invalido");
    exit();
}

// Buscar al cliente por su correo usando prepared statement
$stmt = $conexion->prepare("SELECT ID_CLIENTE, NOMBRES, APELLIDOS, CORREO, CLAVE, TELEFONO, ACTIVO FROM cliente WHERE CORREO = ?");
$stmt->bind_param("s", $correo);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows == 1) {
    $cliente = $resultado->fetch_assoc();

    // Verificar si el cliente está activo
    if ($cliente['ACTIVO'] != 1) {
        header("Location: cuenta_cliente.php?error=cliente_inactivo");
        exit();
    }

    // Validar contraseña cifrada
    if (password_verify($clave, $cliente['CLAVE'])) {
        // Guardar información del cliente en sesión
        $_SESSION['cliente_id'] = $cliente['ID_CLIENTE'];
        $_SESSION['cliente_nombre'] = $cliente['NOMBRES'] . ' ' . $cliente['APELLIDOS'];
        $_SESSION['cliente_correo'] = $cliente['CORREO'];
        $_SESSION['cliente_telefono'] = $cliente['TELEFONO'];
        $_SESSION['tipo_usuario'] = 'cliente';

        // Crear o recuperar carrito activo para el cliente
        $carrito_stmt = $conexion->prepare("SELECT ID_CARRITO FROM carrito WHERE ID_CLIENTE = ? AND ESTADO = 'ACTIVO'");
        $carrito_stmt->bind_param("i", $cliente['ID_CLIENTE']);
        $carrito_stmt->execute();
        $carrito_result = $carrito_stmt->get_result();

        if ($carrito_result->num_rows == 0) {
            // Crear nuevo carrito activo
            $crear_carrito_stmt = $conexion->prepare("INSERT INTO carrito (ESTADO, ID_CLIENTE) VALUES ('ACTIVO', ?)");
            $crear_carrito_stmt->bind_param("i", $cliente['ID_CLIENTE']);
            $crear_carrito_stmt->execute();
            $_SESSION['carrito_id'] = $conexion->insert_id;
        } else {
            // Usar carrito existente
            $carrito = $carrito_result->fetch_assoc();
            $_SESSION['carrito_id'] = $carrito['ID_CARRITO'];
        }

        $carrito_stmt->close();

        header("Location: index.php");
        exit();
    } else {
        header("Location: cuenta_cliente.php?error=clave_incorrecta");
        exit();
    }
} else {
    header("Location: cuenta_cliente.php?error=cliente_no_existe");
    exit();
}

$stmt->close();
$conexion->close();
?>
