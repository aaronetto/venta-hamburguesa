<?php
session_start();
require_once 'config.php';

// Verificar si el cliente está logueado
if (!isset($_SESSION['cliente_id'])) {
    header("Location: cuenta_cliente.php");
    exit();
}

// Verificar si se recibió el ID del pedido
if (!isset($_GET['pedido_id'])) {
    header("Location: index.php");
    exit();
}

$pedido_id = (int)$_GET['pedido_id'];
$cliente_id = $_SESSION['cliente_id'];
$conexion = getConnection();

try {
         // Obtener información del pedido
     $stmt = $conexion->prepare("SELECT 
                                 p.*,
                                 c.NOMBRES as CLIENTE_NOMBRES,
                                 c.APELLIDOS as CLIENTE_APELLIDOS,
                                 c.TELEFONO as CLIENTE_TELEFONO,
                                 c.CORREO as CLIENTE_CORREO
                               FROM pedido p
                               INNER JOIN cliente c ON p.ID_CLIENTE = c.ID_CLIENTE
                               WHERE p.ID_PEDIDO = ? AND p.ID_CLIENTE = ?");
    $stmt->bind_param("ii", $pedido_id, $cliente_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header("Location: index.php?error=pedido_no_encontrado");
        exit();
    }
    
    $pedido = $result->fetch_assoc();
    
    // Obtener detalles del pedido
    $stmt = $conexion->prepare("SELECT 
                                pd.*,
                                p.NOMBRE as PRODUCTO_NOMBRE,
                                p.IMAGEN_RUTA
                              FROM pedido_detalle pd
                              INNER JOIN producto p ON pd.ID_PRODUCTO = p.ID_PRODUCTO
                              WHERE pd.ID_PEDIDO = ?
                              ORDER BY p.NOMBRE");
    $stmt->bind_param("i", $pedido_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $detalles = [];
    while ($row = $result->fetch_assoc()) {
        $detalles[] = $row;
    }
    
} catch (Exception $e) {
    header("Location: index.php?error=error_pedido");
    exit();
} finally {
    $conexion->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Confirmación del Pedido - Hamburguesas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .confirmacion-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .confirmacion-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .confirmacion-header h1 {
            color: #28a745;
            margin-bottom: 10px;
        }

        .success-icon {
            font-size: 64px;
            color: #28a745;
            margin-bottom: 20px;
        }

        .pedido-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .pedido-numero {
            font-size: 24px;
            font-weight: 700;
            color: #d0851c;
            margin-bottom: 15px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-weight: 600;
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .info-value {
            color: #333;
            font-size: 16px;
        }

        .productos-lista {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .producto-item {
            display: flex;
            align-items: center;
            padding: 15px;
            margin-bottom: 15px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .producto-item:last-child {
            margin-bottom: 0;
        }

        .producto-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 15px;
        }

        .producto-info {
            flex: 1;
        }

        .producto-nombre {
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }

        .producto-detalles {
            color: #666;
            font-size: 14px;
        }

        .producto-subtotal {
            font-weight: 700;
            color: #d0851c;
            font-size: 16px;
        }

        .total-section {
            background: #d0851c;
            color: white;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin-bottom: 30px;
        }

        .total-section h3 {
            margin: 0 0 10px 0;
            font-size: 20px;
        }

        .total-amount {
            font-size: 32px;
            font-weight: 700;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            text-align: center;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 16px;
            margin: 0 10px;
        }

        .btn-primary {
            background: #d0851c;
            color: white;
        }

        .btn-primary:hover {
            background: #bca417;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .pasos {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }

        .paso {
            display: flex;
            align-items: center;
            margin: 0 15px;
        }

        .paso-numero {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #28a745;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 10px;
        }

        .paso-texto {
            color: #28a745;
            font-weight: 600;
        }

        .paso.inactivo .paso-numero {
            background: #e9ecef;
            color: #666;
        }

        .paso.inactivo .paso-texto {
            color: #666;
        }

        .estado-pedido {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
        }

        .estado-pendiente {
            background: #fff3cd;
            color: #856404;
        }

        .estado-preparando {
            background: #cce5ff;
            color: #004085;
        }

        .estado-enviado {
            background: #d4edda;
            color: #155724;
        }

        .estado-entregado {
            background: #d1e7dd;
            color: #0f5132;
        }

        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }

            .producto-item {
                flex-direction: column;
                text-align: center;
            }

            .producto-img {
                margin-right: 0;
                margin-bottom: 10px;
            }

            .pasos {
                flex-direction: column;
                align-items: center;
            }

            .paso {
                margin: 5px 0;
            }

            .btn {
                display: block;
                margin: 10px 0;
            }
        }
    </style>
</head>
<body>
    <div class="confirmacion-container">
        <div class="confirmacion-header">
            <div class="success-icon">✅</div>
            <h1>¡Pedido Confirmado!</h1>
            <p>Tu pedido ha sido procesado exitosamente</p>
        </div>

        <div class="pasos">
            <div class="paso inactivo">
                <div class="paso-numero">1</div>
                <div class="paso-texto">Carrito</div>
            </div>
            <div class="paso inactivo">
                <div class="paso-numero">2</div>
                <div class="paso-texto">Resumen</div>
            </div>
            <div class="paso inactivo">
                <div class="paso-numero">3</div>
                <div class="paso-texto">Checkout</div>
            </div>
            <div class="paso">
                <div class="paso-numero">4</div>
                <div class="paso-texto">Confirmación</div>
            </div>
        </div>

        <div class="pedido-info">
            <div class="pedido-numero">
                Pedido #<?php echo str_pad($pedido['ID_PEDIDO'], 6, '0', STR_PAD_LEFT); ?>
            </div>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Fecha del Pedido</div>
                    <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($pedido['FECHA_PEDIDO'])); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Estado</div>
                    <div class="info-value">
                        <span class="estado-pedido estado-pendiente"><?php echo $pedido['ESTADO']; ?></span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Método de Pago</div>
                    <div class="info-value"><?php echo $pedido['METODO_PAGO']; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Total</div>
                    <div class="info-value">S/ <?php echo number_format($pedido['TOTAL'], 2); ?></div>
                </div>
            </div>

                         <div class="info-item">
                 <div class="info-label">Cliente</div>
                 <div class="info-value">
                     <?php echo htmlspecialchars($pedido['CLIENTE_NOMBRES'] . ' ' . $pedido['CLIENTE_APELLIDOS']); ?><br>
                     Tel: <?php echo htmlspecialchars($pedido['CLIENTE_TELEFONO']); ?><br>
                     Email: <?php echo htmlspecialchars($pedido['CLIENTE_CORREO']); ?>
                 </div>
             </div>

            <?php if (!empty($pedido['OBSERVACIONES'])): ?>
            <div class="info-item" style="margin-top: 15px;">
                <div class="info-label">Observaciones</div>
                <div class="info-value"><?php echo htmlspecialchars($pedido['OBSERVACIONES']); ?></div>
            </div>
            <?php endif; ?>
        </div>

        <div class="productos-lista">
            <h3>Productos del Pedido</h3>
            
            <?php foreach ($detalles as $detalle): ?>
            <div class="producto-item">
                <img src="<?php echo htmlspecialchars($detalle['IMAGEN_RUTA']); ?>" 
                     alt="<?php echo htmlspecialchars($detalle['PRODUCTO_NOMBRE']); ?>" 
                     class="producto-img">
                
                <div class="producto-info">
                    <div class="producto-nombre"><?php echo htmlspecialchars($detalle['PRODUCTO_NOMBRE']); ?></div>
                    <div class="producto-detalles">
                        Cantidad: <?php echo $detalle['CANTIDAD']; ?> | 
                        Precio: S/ <?php echo number_format($detalle['PRECIO_UNITARIO'], 2); ?>
                    </div>
                </div>
                
                <div class="producto-subtotal">
                    S/ <?php echo number_format($detalle['SUBTOTAL'], 2); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="total-section">
            <h3>Total del Pedido</h3>
            <div class="total-amount">S/ <?php echo number_format($pedido['TOTAL'], 2); ?></div>
        </div>

        <div style="text-align: center;">
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Volver al Inicio
            </a>
            <a href="mi_cuenta.php" class="btn btn-secondary">
                <i class="fas fa-user"></i> Mi Cuenta
            </a>
        </div>

        <div style="margin-top: 30px; padding: 20px; background: #e9ecef; border-radius: 8px; text-align: center;">
            <h4>📞 ¿Necesitas ayuda?</h4>
            <p>Si tienes alguna pregunta sobre tu pedido, contáctanos:</p>
            <p><strong>WhatsApp:</strong> +51 987 654 321</p>
            <p><strong>Email:</strong> contacto@hamburguesasbuenaventura.com</p>
        </div>
    </div>
</body>
</html>
