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
    
    // Obtener productos del carrito con información completa
    $query = "SELECT 
                cd.ID_CARRITO_DETALLE,
                cd.CANTIDAD,
                p.ID_PRODUCTO,
                p.NOMBRE,
                p.PRECIO,
                p.IMAGEN_RUTA,
                p.STOCK,
                (cd.CANTIDAD * p.PRECIO) as SUBTOTAL
              FROM carrito_detalle cd
              INNER JOIN producto p ON cd.ID_PRODUCTO = p.ID_PRODUCTO
              WHERE cd.ID_CARRITO = ? AND p.ACTIVO = 1
              ORDER BY p.NOMBRE";
    
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("i", $carrito_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $productos = [];
    $total_precio = 0;
    $total_productos = 0;
    
    while ($row = $result->fetch_assoc()) {
        $productos[] = $row;
        $total_precio += $row['SUBTOTAL'];
        $total_productos += $row['CANTIDAD'];
    }
    
    if (empty($productos)) {
        header("Location: index.php?error=carrito_vacio");
        exit();
    }
    
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
    <title>Resumen del Carrito - Hamburguesas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .resumen-container {
            max-width: 1000px;
            margin: 50px auto;
            padding: 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .resumen-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .resumen-header h1 {
            color: #d0851c;
            margin-bottom: 10px;
        }

        .resumen-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }

        .productos-lista {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
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
            width: 80px;
            height: 80px;
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

        .producto-precio {
            color: #d0851c;
            font-weight: 600;
        }

        .producto-cantidad {
            background: #e9ecef;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 14px;
            color: #666;
        }

        .producto-subtotal {
            font-weight: 700;
            color: #d0851c;
            font-size: 18px;
        }

        .resumen-total {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .total-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
        }

        .total-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            font-weight: 700;
            font-size: 18px;
            color: #d0851c;
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

        .btn-full:last-child {
            margin-bottom: 0;
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

        @media (max-width: 768px) {
            .resumen-content {
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
        }
    </style>
</head>
<body>
    <div class="resumen-container">
        <div class="resumen-header">
            <h1>🛒 Resumen del Carrito</h1>
            <p>Revisa tu pedido antes de proceder al checkout</p>
        </div>

        <div class="pasos">
            <div class="paso">
                <div class="paso-numero">1</div>
                <div class="paso-texto">Carrito</div>
            </div>
            <div class="paso">
                <div class="paso-numero">2</div>
                <div class="paso-texto">Resumen</div>
            </div>
            <div class="paso inactivo">
                <div class="paso-numero">3</div>
                <div class="paso-texto">Checkout</div>
            </div>
            <div class="paso inactivo">
                <div class="paso-numero">4</div>
                <div class="paso-texto">Confirmación</div>
            </div>
        </div>

        <div class="resumen-content">
            <div class="productos-lista">
                <h3>Productos en tu carrito (<?php echo $total_productos; ?>)</h3>
                
                <?php foreach ($productos as $producto): ?>
                <div class="producto-item">
                    <img src="<?php echo htmlspecialchars($producto['IMAGEN_RUTA']); ?>" 
                         alt="<?php echo htmlspecialchars($producto['NOMBRE']); ?>" 
                         class="producto-img">
                    
                    <div class="producto-info">
                        <div class="producto-nombre"><?php echo htmlspecialchars($producto['NOMBRE']); ?></div>
                        <div class="producto-precio">S/ <?php echo number_format($producto['PRECIO'], 2); ?> c/u</div>
                        <div class="producto-cantidad">Cantidad: <?php echo $producto['CANTIDAD']; ?></div>
                    </div>
                    
                    <div class="producto-subtotal">
                        S/ <?php echo number_format($producto['SUBTOTAL'], 2); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="resumen-total">
                <h3>Resumen de Compra</h3>
                
                <div class="total-item">
                    <span>Subtotal:</span>
                    <span>S/ <?php echo number_format($total_precio, 2); ?></span>
                </div>
                
                <div class="total-item">
                    <span>IGV (18%):</span>
                    <span>S/ <?php echo number_format($total_precio * 0.18, 2); ?></span>
                </div>
                
                <div class="total-item">
                    <span>Total:</span>
                    <span>S/ <?php echo number_format($total_precio * 1.18, 2); ?></span>
                </div>

                <div style="margin-top: 20px;">
                    <a href="checkout.php" class="btn btn-primary btn-full">
                        <i class="fas fa-credit-card"></i> Proceder al Pagoz
                    </a>
                    <a href="index.php" class="btn btn-secondary btn-full">
                        <i class="fas fa-arrow-left"></i> Seguir comprando
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
