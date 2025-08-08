<?php
session_start();
require_once '../config.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    exit('Acceso denegado');
}

// Verificar si se recibió el ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    exit('ID de pedido requerido');
}

$conexion = getConnection();
$id = (int)$_GET['id'];

// Obtener información del pedido
$stmt = $conexion->prepare("SELECT p.*, u.NOMB_USUARIO 
                           FROM pedido p 
                           INNER JOIN usuario u ON p.ID_USUARIO = u.ID_USUARIO 
                           WHERE p.ID_PEDIDO = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    exit('Pedido no encontrado');
}

$pedido = $result->fetch_assoc();

// Obtener detalles del pedido
$stmt = $conexion->prepare("SELECT dp.*, p.NOMB_PRODUCTO, p.PRECIO 
                           FROM detalle_pedido dp 
                           INNER JOIN producto p ON dp.ID_PRODUCTO = p.ID_PRODUCTO 
                           WHERE dp.ID_PEDIDO = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$detalles = $stmt->get_result();
?>

<div class="info-pedido">
    <div class="info-item">
        <span class="info-label">ID del Pedido:</span>
        <span class="info-value">#<?php echo $pedido['ID_PEDIDO']; ?></span>
    </div>
    <div class="info-item">
        <span class="info-label">Fecha:</span>
        <span class="info-value"><?php echo date('d/m/Y', strtotime($pedido['FECHA_PEDIDO'])); ?></span>
    </div>
    <div class="info-item">
        <span class="info-label">Cliente:</span>
        <span class="info-value"><?php echo htmlspecialchars($pedido['NOMB_USUARIO']); ?></span>
    </div>
    <div class="info-item">
        <span class="info-label">Total:</span>
        <span class="info-value total">S/ <?php echo number_format($pedido['TOTAL'], 2); ?></span>
    </div>
</div>

<h4>Productos del Pedido:</h4>
<?php if ($detalles->num_rows > 0): ?>
    <table class="detalle-table">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Precio Unitario</th>
                <th>Cantidad</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($detalle = $detalles->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($detalle['NOMB_PRODUCTO']); ?></td>
                    <td>S/ <?php echo number_format($detalle['PRECIO'], 2); ?></td>
                    <td><?php echo $detalle['CANTIDAD']; ?></td>
                    <td class="total">S/ <?php echo number_format($detalle['SUBTOTAL'], 2); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p style="text-align: center; color: #666; padding: 20px;">No hay productos en este pedido</p>
<?php endif; ?>

<style>
    .total {
        font-weight: bold;
        color: #e53e2e;
    }
    
    .info-pedido {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 5px;
    }
    
    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .info-label {
        font-weight: 600;
        color: #333;
    }
    
    .info-value {
        color: #666;
    }
    
    .detalle-table {
        width: 100%;
        margin-top: 15px;
        border-collapse: collapse;
    }
    
    .detalle-table th {
        background: #f8f9fa;
        padding: 10px;
        font-size: 14px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }
    
    .detalle-table td {
        padding: 10px;
        font-size: 14px;
        border-bottom: 1px solid #ddd;
    }
</style>
