<?php
session_start();
require_once 'config.php';

// Obtener productos activos de la base de datos
$conexion = getConnection();
$query = "SELECT p.*, c.NOMBRE as CATEGORIA_NOMBRE 
          FROM producto p 
          INNER JOIN categoria c ON p.ID_CATEGORIA = c.ID_CATEGORIA 
          WHERE p.ACTIVO = 1 AND c.ACTIVO = 1 
          ORDER BY c.NOMBRE, p.NOMBRE";
$result = $conexion->query($query);

$productos = [];
$categorias = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categoria = $row['CATEGORIA_NOMBRE'];
        if (!isset($categorias[$categoria])) {
            $categorias[$categoria] = [];
        }
        $categorias[$categoria][] = $row;
        $productos[] = $row;
    }
}

$conexion->close();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hamburguesas Buenaventura</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .cliente-info {
            background: rgba(255, 255, 255, 0.95);
            padding: 10px 20px;
            border-radius: 25px;
            margin-left: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: #333;
        }

        .cliente-info .nombre {
            font-weight: 600;
            color: #d0851c;
        }

        .cliente-info .logout {
            background: #e74c3c;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            text-decoration: none;
            font-size: 12px;
            transition: background 0.3s;
        }

        .cliente-info .logout:hover {
            background: #c0392b;
        }

        .menu .navbar ul {
            display: flex;
            align-items: center;
        }

        .carrito-icon {
            position: relative;
            cursor: pointer;
            padding: 10px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 50%;
            margin-left: 15px;
            transition: all 0.3s;
        }

        .carrito-icon:hover {
            background: #d0851c;
            color: white;
        }

        .carrito-icon i {
            font-size: 20px;
        }

        .carrito-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }

        /* Sidebar del carrito */
        .carrito-sidebar {
            position: fixed;
            top: 0;
            right: -400px;
            width: 400px;
            height: 100vh;
            background: white;
            box-shadow: -5px 0 15px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            transition: right 0.3s ease;
            overflow-y: auto;
        }

        .carrito-sidebar.active {
            right: 0;
        }

        .carrito-header {
            background: #d0851c;
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .carrito-header h3 {
            margin: 0;
            font-size: 18px;
        }

        .carrito-close {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
        }

        .carrito-content {
            padding: 20px;
        }

        .carrito-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .carrito-item:last-child {
            border-bottom: none;
        }

        .carrito-item-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 15px;
        }

        .carrito-item-info {
            flex: 1;
        }

        .carrito-item-nombre {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .carrito-item-precio {
            color: #d0851c;
            font-weight: 600;
        }

        .carrito-item-cantidad {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .cantidad-btn {
            background: #f0f0f0;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            font-weight: bold;
        }

        .cantidad-btn:hover {
            background: #d0851c;
            color: white;
        }

        .carrito-total {
            position: sticky;
            bottom: 0;
            background: white;
            padding: 20px;
            border-top: 2px solid #f0f0f0;
            text-align: center;
        }

        .carrito-total h4 {
            margin: 0 0 10px 0;
            color: #333;
        }

        .carrito-vaciar {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 10px;
        }

        .carrito-checkout {
            background: #d0851c;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            width: 100%;
        }

        .carrito-vacio {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }

        /* Overlay */
        .carrito-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }

        .carrito-overlay.active {
            display: block;
        }

        /* Productos cards */
        .product-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .product-card-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .product-card-content {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .product-card-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }

        .product-card-description {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
            flex: 1;
        }

        .product-card-price {
            font-size: 20px;
            font-weight: 700;
            color: #d0851c;
            margin-bottom: 15px;
        }

        .product-card-btn {
            background: #d0851c;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            width: 100%;
        }

        .product-card-btn:hover {
            background: #bca417;
        }

        .product-card-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        @media (max-width: 768px) {
            .cliente-info {
                margin: 10px 0;
                flex-direction: column;
                text-align: center;
            }

            .carrito-sidebar {
                width: 100%;
                right: -100%;
            }

            .carrito-icon {
                margin-left: 10px;
            }
        }
    </style>
</head>

<body>

    <header class="header">

        <div class="menu container">

            <a href="#" class="logo">
                <img src="images/logo_negocio.png" alt="Logo de la empresa">
            </a>
            <input type="checkbox" id="menu">
            <label for="menu">
                <img src="images/menu.png" class="menu-icono" alt="Menú">
            </label>
            
            <nav class="navbar">
                <ul>
                    <li><a href="#">Inicio</a></li>
                    <li><a href="#servicios">Servicios</a></li>
                    <?php if (isset($_SESSION['cliente_id'])): ?>
                        <li><a href="mi_cuenta.php">Mi Cuenta</a></li>
                    <?php else: ?>
                        <li><a href="cuenta_cliente.php">Cuenta</a></li>
                    <?php endif; ?>
                    <li><a href="#Pedidos">Pedido</a></li>
                    <li><a href="#Productos">Productos</a></li>
                    <li><a href="#contacto">Contacto</a></li>
                                         <?php if (isset($_SESSION['cliente_id'])): ?>
                         <li>
                             <div class="carrito-icon" id="carrito-icon">
                                 <span class="carrito-badge" id="carrito-badge">0</span>
                                 <i class="fas fa-shopping-cart"></i>
                             </div>
                         </li>
                         <li>
                             <div class="cliente-info">
                                 <span>👤 <span class="nombre"><?php echo htmlspecialchars($_SESSION['cliente_nombre']); ?></span></span>
                                 <a href="logout_cliente.php" class="logout">Cerrar Sesión</a>
                             </div>
                         </li>
                     <?php endif; ?>
                </ul>
            </nav>

        </div>

        <div class="header-content-container">
            <div class="header-txt">
                <span class="oferta">Ofertas</span>
                <h1>Hamburguesa Premium</h1>
                <a href="#" class="btn-1">Información</a>
            </div>
        </div>

    </header>

    <!-- Sidebar del Carrito -->
    <div class="carrito-overlay" id="carrito-overlay"></div>
    <div class="carrito-sidebar" id="carrito-sidebar">
        <div class="carrito-header">
            <h3>🛒 Mi Carrito</h3>
            <button class="carrito-close" id="carrito-close">&times;</button>
        </div>
        <div class="carrito-content" id="carrito-content">
            <div class="carrito-vacio" id="carrito-vacio">
                <i class="fas fa-shopping-cart" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                <p>Tu carrito está vacío</p>
                <p>¡Agrega algunos productos deliciosos!</p>
            </div>
        </div>
        <div class="carrito-total" id="carrito-total" style="display: none;">
            <h4>Total: S/ <span id="carrito-total-precio">0.00</span></h4>
            <button class="carrito-vaciar" id="carrito-vaciar">Vaciar Carrito</button>
            <button class="carrito-checkout" id="carrito-checkout" onclick="window.location.href='resumen_carrito.php'">Proceder al Pago</button>
        </div>
    </div>

    <!-- Sección de Servicios -->
    <section id="servicios" class="services container">
        <h2>Servicios</h2>
        <p>Ofrecemos productos de alta calidad, combos especiales y atención personalizada.</p>

        <div class="services-list">
            <div class="service-item">
                <h3>Delivery rápido</h3>
                <p>Entregamos tus hamburguesas calientes y a tiempo.</p>
            </div>
            <div class="service-item">
                <h3>Personalización</h3>
                <p>Arma tu combo como tú quieras: ingredientes, tamaño, bebida y más.</p>
            </div>
            <div class="service-item">
                <h3>Atención al cliente</h3>
                <p>Soporte por WhatsApp y correo 24/7 para resolver tus dudas.</p>
            </div>
        </div>
    </section>

    <section class="about container">
        <div class="about-img">
            <img src="images/left.png" alt="">
        </div>

        <div class="about-txt">
            <span class="oferta">Ofertas</span>
            <h2>100% ingredientes naturales frescos</h2>
            <p>
                Lorem ipsum dolor sit amet consectetur adipisicing elit. 
                Deserunt, exercitationem ad! Velit corporis minima maxime 
                quas vero praesentium, doloribus, magnam quod quisquam voluptatum 
                vel porro libero, natus ipsam tenetur tempora.
            </p>
            <p>
                Lorem ipsum dolor sit amet consectetur adipisicing elit. 
                Deserunt, exercitationem ad! Velit corporis minima maxime 
                quas vero praesentium, doloribus, magnam quod quisquam voluptatum 
                vel porro libero, natus ipsam tenetur tempora.
            </p>
            <a href="#" class="btn-1">Información</a>
        </div>
    </section>

    <section class="Ofert-carousel container">
        <div class="carousel-wrapper">
            <a href="#slide3" class="carousel-arrow left">&#10094;</a>
            <div class="carousel-track" id="carousel">
                <div class="carousel-slide" id="slide1">
                    <img src="images/ofert-1.jpg" alt="Oferta 1">
                </div>
                <div class="carousel-slide" id="slide2">
                    <img src="images/ofert-2.jpg" alt="Oferta 2">
                </div>
                <div class="carousel-slide" id="slide3">
                    <img src="images/ofert-3.jpg" alt="Oferta 3">
                </div>  
            </div>
            <a href="#slide2" class="carousel-arrow right">&#10095;</a>
        </div> 
    </section>

    <main class="Product container">
        <span class="oferta">Nuestros Productos</span>
        <h2>Deliciosas Hamburguesas</h2>
        <p>
            Descubre nuestra amplia variedad de hamburguesas preparadas con ingredientes frescos y de la mejor calidad.
        </p>

        <?php foreach ($categorias as $categoria => $productos_categoria): ?>
        <div class="categoria-seccion">
            <h3 style="text-align: center; margin: 40px 0 20px 0; color: #d0851c; font-size: 24px;"><?php echo htmlspecialchars($categoria); ?></h3>
            <div class="product-content" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px;">
                <?php foreach ($productos_categoria as $producto): ?>
                <div class="product-card">
                    <img src="<?php echo htmlspecialchars($producto['IMAGEN_RUTA']); ?>" alt="<?php echo htmlspecialchars($producto['NOMBRE']); ?>" class="product-card-img">
                    <div class="product-card-content">
                        <h3 class="product-card-title"><?php echo htmlspecialchars($producto['NOMBRE']); ?></h3>
                        <p class="product-card-description"><?php echo htmlspecialchars($producto['DESCRIPCION'] ?: 'Sin descripción disponible'); ?></p>
                        <div class="product-card-price">S/ <?php echo number_format($producto['PRECIO'], 2); ?></div>
                        <?php if (isset($_SESSION['cliente_id'])): ?>
                            <button class="product-card-btn" onclick="agregarAlCarrito(<?php echo $producto['ID_PRODUCTO']; ?>)">
                                <i class="fas fa-plus"></i> Agregar al Carrito
                            </button>
                        <?php else: ?>
                            <button class="product-card-btn" onclick="mostrarLoginRequerido()" style="background: #6c757d;">
                                <i class="fas fa-sign-in-alt"></i> Inicia Sesión
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </main>

    <section class="news">
        <div class="news-content-container" style="display: flex; justify-content: space-between; align-items: center;">
            <div class="news-1">
                <h3>Subscribete a las noticias</h3>
                <p>
                    ¡No te pierdas ni una mordida!
                    Suscríbete y recibe en tu correo lo mejor 
                    del mundo hamburguesero: eventos, secretos 
                    de cocina y ofertas exclusivas.
                </p>
            </div>

            <div class="news-2">
                <form>
                    <input type="email" placeholder="Email">
                    <input type="submit" class="btn-2" value="Enviar">
                </form>
            </div>
        </div>
    </section>

    <section class="maps">
        <iframe class="map" src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d31219.602638789413!2d-76.90319456364944!3d-12.012487534592912!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x9105c72728f1e9c3%3A0xe561ad9645daa02c!2sLa%20Molina!5e0!3m2!1ses!2spe!4v1752387877396!5m2!1ses!2spe" width="100%" height="500" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
    </section>

    <footer class="footer" id="contacto">
        <div class="footer-content container">
            <div class="link">
                <h3>Contacto</h3>
                <ul>
                    <li><a href="tel:+51987654321">📞 +51 987 654 321</a></li>
                    <li><a href="mailto:contacto@hamburguesasbuenaventura.com">✉ contacto@hamburguesasbuenaventura.com</a></li>
                    <li><a href="#">📍 Av. Principal 123, La Molina, Lima</a></li>
                </ul>
            </div>

            <div class="link">
                <h3>Síguenos</h3>
                <ul>
                    <li><a href="https://facebook.com" target="_blank">📘 Facebook</a></li>
                    <li><a href="https://instagram.com" target="_blank">📸 Instagram</a></li>
                    <li><a href="https://wa.me/51987654321" target="_blank">📱 WhatsApp</a></li>
                </ul>
            </div>

            <div class="link">
                <h3>Información</h3>
                <ul>
                    <li><a href="#">Términos y condiciones</a></li>
                    <li><a href="#">Política de privacidad</a></li>
                    <li><a href="#">Preguntas frecuentes</a></li>
                </ul>
            </div>

            <div class="link">
                <h3>Secciones</h3>
                <ul>
                    <li><a href="#">Inicio</a></li>
                    <li><a href="#">Servicios</a></li>
                    <li><a href="#">Productos</a></li>
                    <li><a href="#">Contacto</a></li>
                </ul>
            </div>
        </div>
    </footer>

    <script>
        // Variables globales
        let carritoAbierto = false;

        // Elementos del DOM
        const carritoIcon = document.getElementById('carrito-icon');
        const carritoSidebar = document.getElementById('carrito-sidebar');
        const carritoOverlay = document.getElementById('carrito-overlay');
        const carritoClose = document.getElementById('carrito-close');
        const carritoContent = document.getElementById('carrito-content');
        const carritoVacio = document.getElementById('carrito-vacio');
        const carritoTotal = document.getElementById('carrito-total');
        const carritoBadge = document.getElementById('carrito-badge');

        // Event listeners
        if (carritoIcon) {
            carritoIcon.addEventListener('click', abrirCarrito);
        }
        if (carritoClose) {
            carritoClose.addEventListener('click', cerrarCarrito);
        }
        if (carritoOverlay) {
            carritoOverlay.addEventListener('click', cerrarCarrito);
        }

        // Cargar carrito al iniciar
        document.addEventListener('DOMContentLoaded', function() {
            cargarCarrito();
            
            // Event listener para vaciar carrito
            const carritoVaciar = document.getElementById('carrito-vaciar');
            if (carritoVaciar) {
                carritoVaciar.addEventListener('click', vaciarCarrito);
            }
        });

        // Funciones del carrito
        function abrirCarrito() {
            carritoSidebar.classList.add('active');
            carritoOverlay.classList.add('active');
            carritoAbierto = true;
            document.body.style.overflow = 'hidden';
        }

        function cerrarCarrito() {
            carritoSidebar.classList.remove('active');
            carritoOverlay.classList.remove('active');
            carritoAbierto = false;
            document.body.style.overflow = 'auto';
        }

        function cargarCarrito() {
            fetch('obtener_carrito.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        actualizarCarrito(data);
                    }
                })
                .catch(error => {
                    console.error('Error al cargar el carrito:', error);
                });
        }

        function actualizarCarrito(data) {
            const carrito = data.carrito;
            const totalProductos = data.total_productos;
            const totalPrecio = data.total_precio;

            // Actualizar badge
            if (carritoBadge) {
                carritoBadge.textContent = totalProductos;
                carritoBadge.style.display = totalProductos > 0 ? 'flex' : 'none';
            }

            // Actualizar contenido del carrito
            if (carrito.length === 0) {
                carritoVacio.style.display = 'block';
                carritoTotal.style.display = 'none';
                carritoContent.innerHTML = carritoVacio.outerHTML;
            } else {
                carritoVacio.style.display = 'none';
                carritoTotal.style.display = 'block';
                
                let html = '';
                carrito.forEach(item => {
                    html += `
                        <div class="carrito-item" data-detalle-id="${item.id_detalle}">
                            <img src="${item.imagen}" alt="${item.nombre}" class="carrito-item-img">
                            <div class="carrito-item-info">
                                <div class="carrito-item-nombre">${item.nombre}</div>
                                <div class="carrito-item-precio">S/ ${parseFloat(item.precio).toFixed(2)}</div>
                                <div class="carrito-item-cantidad">
                                    <button class="cantidad-btn" onclick="actualizarCantidad(${item.id_detalle}, 'decrementar')">-</button>
                                    <span>${item.cantidad}</span>
                                    <button class="cantidad-btn" onclick="actualizarCantidad(${item.id_detalle}, 'incrementar')">+</button>
                                    <button class="cantidad-btn" onclick="actualizarCantidad(${item.id_detalle}, 'eliminar')" style="background: #e74c3c; color: white;">🗑️</button>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                carritoContent.innerHTML = html;
            }

            // Actualizar total
            document.getElementById('carrito-total-precio').textContent = totalPrecio.toFixed(2);
        }

        function agregarAlCarrito(productoId) {
            const formData = new FormData();
            formData.append('producto_id', productoId);
            formData.append('cantidad', 1);

            fetch('agregar_al_carrito.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarNotificacion('✅ ' + data.message, 'success');
                    cargarCarrito();
                } else {
                    mostrarNotificacion('❌ ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarNotificacion('❌ Error al agregar al carrito', 'error');
            });
        }

        function actualizarCantidad(detalleId, accion) {
            const formData = new FormData();
            formData.append('detalle_id', detalleId);
            formData.append('accion', accion);

            fetch('actualizar_carrito.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarNotificacion('✅ ' + data.message, 'success');
                    cargarCarrito();
                } else {
                    mostrarNotificacion('❌ ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarNotificacion('❌ Error al actualizar el carrito', 'error');
            });
        }

        function vaciarCarrito() {
            if (confirm('¿Estás seguro de que quieres vaciar el carrito?')) {
                fetch('vaciar_carrito.php', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        mostrarNotificacion('✅ ' + data.message, 'success');
                        cargarCarrito();
                    } else {
                        mostrarNotificacion('❌ ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    mostrarNotificacion('❌ Error al vaciar el carrito', 'error');
                });
            }
        }

        function mostrarLoginRequerido() {
            mostrarNotificacion('🔐 Debes iniciar sesión para agregar productos al carrito', 'warning');
            setTimeout(() => {
                window.location.href = 'cuenta_cliente.php';
            }, 2000);
        }

        function mostrarNotificacion(mensaje, tipo) {
            // Crear notificación
            const notificacion = document.createElement('div');
            notificacion.className = `notificacion ${tipo}`;
            notificacion.textContent = mensaje;
            notificacion.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 8px;
                color: white;
                font-weight: 600;
                z-index: 10000;
                animation: slideIn 0.3s ease;
                max-width: 300px;
            `;

            // Estilos según tipo
            if (tipo === 'success') {
                notificacion.style.background = '#28a745';
            } else if (tipo === 'error') {
                notificacion.style.background = '#dc3545';
            } else if (tipo === 'warning') {
                notificacion.style.background = '#ffc107';
                notificacion.style.color = '#333';
            }

            // Agregar al DOM
            document.body.appendChild(notificacion);

            // Remover después de 3 segundos
            setTimeout(() => {
                notificacion.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => {
                    document.body.removeChild(notificacion);
                }, 300);
            }, 3000);
        }

        // Estilos CSS para animaciones
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    </script>

</body>

</html>
