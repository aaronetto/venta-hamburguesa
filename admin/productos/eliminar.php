<?php
session_start();
require_once '../../config.php';
require_once '../../auth_functions.php';

// Verificar acceso al módulo
requerirAccesoModulo('productos');

// Verificar si se recibió el ID
if (!isset($_POST['id']) || empty($_POST['id'])) {
    header("Location: index.php?error=id_requerido");
    exit();
}

$conexion = getConnection();
$id = (int)$_POST['id'];

// Verificar si el producto existe y está activo
$stmt = $conexion->prepare("SELECT CODIGO, NOMBRE, IMAGEN_RUTA FROM producto WHERE ID_PRODUCTO = ? AND ACTIVO = 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php?error=producto_no_existe");
    exit();
}

$producto = $result->fetch_assoc();
$nombre_producto = $producto['NOMBRE'];

// Verificar si hay pedidos asociados a este producto
$stmt = $conexion->prepare("SELECT COUNT(*) as total FROM pedido_detalle WHERE ID_PRODUCTO = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$pedidos_asociados = $result->fetch_assoc()['total'];

if ($pedidos_asociados > 0) {
    header("Location: index.php?error=producto_con_pedidos");
    exit();
}

// Realizar soft delete (marcar como inactivo)
$stmt = $conexion->prepare("UPDATE producto SET ACTIVO = 0, FECHA_ACTUALIZACION = NOW() WHERE ID_PRODUCTO = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    // Opcional: Eliminar imagen física si existe
    if (!empty($producto['IMAGEN_RUTA'])) {
        $ruta_imagen = '../../' . $producto['IMAGEN_RUTA'];
        if (file_exists($ruta_imagen)) {
            unlink($ruta_imagen);
        }
    }
    
    header("Location: index.php?success=eliminado");
} else {
    header("Location: index.php?error=error_eliminacion");
}

$stmt->close();
$conexion->close();
?>
