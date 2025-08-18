<?php
session_start();
require_once 'config.php';

// Verificar si el cliente está logueado
if (!isset($_SESSION['cliente_id'])) {
    header("Location: cuenta_cliente.php");
    exit();
}

$cliente_id = $_SESSION['cliente_id'];
$conexion = getConnection();

try {
    // Buscar carrito activo
    $stmt = $conexion->prepare("SELECT ID_CARRITO FROM carrito WHERE ID_CLIENTE = ? AND ESTADO = 'ACTIVO'");
    $stmt->bind_param("i", $cliente_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header("Location: index.php?error=carrito_vacio");
        exit();
    }
    
    $carrito = $result->fetch_assoc();
    $carrito_id = $carrito['ID_CARRITO'];
    
    // Obtener productos del carrito para mostrar resumen
    $query = "SELECT 
                cd.CANTIDAD,
                p.NOMBRE,
                p.PRECIO,
                (cd.CANTIDAD * p.PRECIO) as SUBTOTAL
              FROM carrito_detalle cd
              INNER JOIN producto p ON cd.ID_PRODUCTO = p.ID_PRODUCTO
              WHERE cd.ID_CARRITO = ? AND p.ACTIVO = 1";
    
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("i", $carrito_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $productos = [];
    $total_precio = 0;
    
    while ($row = $result->fetch_assoc()) {
        $productos[] = $row;
        $total_precio += $row['SUBTOTAL'];
    }
    
    if (empty($productos)) {
        header("Location: index.php?error=carrito_vacio");
        exit();
    }
    
    // Obtener información del cliente
    $stmt = $conexion->prepare("SELECT NOMBRES, APELLIDOS, CORREO, TELEFONO FROM cliente WHERE ID_CLIENTE = ?");
    $stmt->bind_param("i", $cliente_id);
    $stmt->execute();
    $cliente_info = $stmt->get_result()->fetch_assoc();
    
} catch (Exception $e) {
    header("Location: index.php?error=error_carrito");
    exit();
} finally {
    $conexion->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Checkout - Hamburguesas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .checkout-container {
            max-width: 1200px;
            margin: 50px auto;
            padding: 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .checkout-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .checkout-header h1 {
            color: #d0851c;
            margin-bottom: 10px;
        }

        .checkout-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }

        .formulario-checkout {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 30px;
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

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #d0851c;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .resumen-checkout {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .resumen-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
        }

        .resumen-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            font-weight: 700;
            font-size: 18px;
            color: #d0851c;
        }

        .producto-resumen {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
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

        .btn-full {
            width: 100%;
            margin-bottom: 10px;
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
            background: #d0851c;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 10px;
        }

        .paso-texto {
            color: #d0851c;
            font-weight: 600;
        }

        .paso.inactivo .paso-numero {
            background: #e9ecef;
            color: #666;
        }

        .paso.inactivo .paso-texto {
            color: #666;
        }

        .metodo-pago {
            margin-top: 20px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
        }

        .metodo-pago h4 {
            margin-bottom: 15px;
            color: #333;
        }

        .opcion-pago {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .opcion-pago:hover {
            border-color: #d0851c;
        }

        .opcion-pago input[type="radio"] {
            margin-right: 10px;
        }

        .opcion-pago label {
            margin: 0;
            cursor: pointer;
            flex: 1;
        }

        @media (max-width: 768px) {
            .checkout-content {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .pasos {
                flex-direction: column;
                align-items: center;
            }

            .paso {
                margin: 5px 0;
            }
        }
    </style>
</head>
<body>
    <div class="checkout-container">
        <div class="checkout-header">
            <h1>💳 Checkout</h1>
            <p>Completa tus datos para finalizar el pedido</p>
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
            <div class="paso">
                <div class="paso-numero">3</div>
                <div class="paso-texto">Checkout</div>
            </div>
            <div class="paso inactivo">
                <div class="paso-numero">4</div>
                <div class="paso-texto">Confirmación</div>
            </div>
        </div>

        <form action="procesar_pedido.php" method="POST">
            <div class="checkout-content">
                <div class="formulario-checkout">
                    <h3>📋 Información de Entrega</h3>
                    
                                         <div class="form-group">
                         <label>Información del Cliente</label>
                         <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                             <p><strong>Nombre:</strong> <?php echo htmlspecialchars($cliente_info['NOMBRES'] . ' ' . $cliente_info['APELLIDOS']); ?></p>
                             <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($cliente_info['TELEFONO']); ?></p>
                             <p><strong>Email:</strong> <?php echo htmlspecialchars($cliente_info['CORREO']); ?></p>
                         </div>
                     </div>

                                         <div class="form-group">
                         <label for="observaciones">Observaciones de Entrega</label>
                         <textarea id="observaciones" name="OBSERVACIONES" rows="3" placeholder="Instrucciones especiales para la entrega, dirección específica, referencias..."></textarea>
                     </div>

                    <div class="metodo-pago">
                        <h4>💳 Método de Pago</h4>
                        
                        <div class="opcion-pago">
                            <input type="radio" id="efectivo" name="METODO_PAGO" value="EFECTIVO" checked>
                            <label for="efectivo">💵 Efectivo al momento de la entrega</label>
                        </div>
                        
                        <div class="opcion-pago">
                            <input type="radio" id="tarjeta" name="METODO_PAGO" value="TARJETA">
                            <label for="tarjeta">💳 Tarjeta de crédito/débito</label>
                        </div>
                        
                                                 <div class="opcion-pago">
                             <input type="radio" id="yape" name="METODO_PAGO" value="YAPE">
                             <label for="yape">📱 Yape</label>
                         </div>
                    </div>

                    <div class="form-group">
                        <label for="observaciones">Observaciones</label>
                        <textarea id="observaciones" name="OBSERVACIONES" rows="3" placeholder="Instrucciones especiales para la entrega..."></textarea>
                    </div>
                </div>

                <div class="resumen-checkout">
                    <h3>📦 Resumen del Pedido</h3>
                    
                    <?php foreach ($productos as $producto): ?>
                    <div class="producto-resumen">
                        <span><?php echo htmlspecialchars($producto['NOMBRE']); ?> x<?php echo $producto['CANTIDAD']; ?></span>
                        <span>S/ <?php echo number_format($producto['SUBTOTAL'], 2); ?></span>
                    </div>
                    <?php endforeach; ?>
                    
                    <hr style="margin: 15px 0;">
                    
                    <div class="resumen-item">
                        <span>Subtotal:</span>
                        <span>S/ <?php echo number_format($total_precio, 2); ?></span>
                    </div>
                    
                    <div class="resumen-item">
                        <span>IGV (18%):</span>
                        <span>S/ <?php echo number_format($total_precio * 0.18, 2); ?></span>
                    </div>
                    
                    <div class="resumen-item">
                        <span>Total:</span>
                        <span>S/ <?php echo number_format($total_precio * 1.18, 2); ?></span>
                    </div>

                    <div style="margin-top: 20px;">
                        <button type="submit" class="btn btn-primary btn-full">
                            <i class="fas fa-check"></i> Confirmar Pedido
                        </button>
                        <a href="resumen_carrito.php" class="btn btn-secondary btn-full">
                            <i class="fas fa-arrow-left"></i> Volver al Resumen
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</body>
</html>
