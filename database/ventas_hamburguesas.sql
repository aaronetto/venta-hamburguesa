-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 04-08-2025 a las 03:41:16
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `ventas_hamburguesa`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categoria`
--

CREATE TABLE `categoria` (
  `ID_CATEGORIA` int(11) NOT NULL,
  `NOMB_CATEGORIA` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categoria`
--

INSERT INTO `categoria` (`ID_CATEGORIA`, `NOMB_CATEGORIA`) VALUES
(1, 'Hamburguesas'),
(2, 'Combos'),
(3, 'Bebidas');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_pedido`
--

CREATE TABLE `detalle_pedido` (
  `ID_DETALLE` int(11) NOT NULL,
  `ID_PEDIDO` int(11) NOT NULL,
  `ID_PRODUCTO` int(11) NOT NULL,
  `CANTIDAD` int(11) NOT NULL,
  `SUBTOTAL` decimal(6,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_pedido`
--

INSERT INTO `detalle_pedido` (`ID_DETALLE`, `ID_PEDIDO`, `ID_PRODUCTO`, `CANTIDAD`, `SUBTOTAL`) VALUES
(1, 1, 1, 1, 15.90),
(2, 1, 3, 1, 8.00),
(3, 2, 2, 1, 22.50),
(4, 2, 3, 1, 8.00),
(5, 3, 1, 1, 15.00),
(6, 4, 1, 2, 31.80),
(7, 4, 2, 1, 13.95),
(8, 5, 3, 2, 10.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedido`
--

CREATE TABLE `pedido` (
  `ID_PEDIDO` int(11) NOT NULL,
  `FECHA_PEDIDO` date NOT NULL,
  `TOTAL` decimal(6,2) NOT NULL,
  `ID_USUARIO` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedido`
--

INSERT INTO `pedido` (`ID_PEDIDO`, `FECHA_PEDIDO`, `TOTAL`, `ID_USUARIO`) VALUES
(1, '2025-08-01', 23.90, 1),
(2, '2025-07-20', 30.50, 2),
(3, '2025-07-31', 15.00, 3),
(4, '2025-08-03', 45.75, 1),
(5, '2025-08-04', 18.00, 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto`
--

CREATE TABLE `producto` (
  `ID_PRODUCTO` int(11) NOT NULL,
  `NOMB_PRODUCTO` varchar(100) NOT NULL,
  `ID_CATEGORIA` int(11) NOT NULL,
  `PRECIO` decimal(6,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `producto`
--

INSERT INTO `producto` (`ID_PRODUCTO`, `NOMB_PRODUCTO`, `ID_CATEGORIA`, `PRECIO`) VALUES
(1, 'Hamburguesa Clásica', 1, 15.90),
(2, 'Hamburguesa con Queso', 1, 22.50),
(3, 'Hamburguesa Doble Carne', 1, 15.00),
(4, 'Combo Clásico', 2, 25.00),
(7, 'Pepsi Jumbo', 3, 8.00),
(8, 'Inca Kola 500ml', 3, 5.00),
(9, 'Agua mineral sin gas 500ml', 3, 3.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `ID_USUARIO` int(11) NOT NULL,
  `NOMB_USUARIO` varchar(100) DEFAULT NULL,
  `CORREO` varchar(100) DEFAULT NULL,
  `CLAVE` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`ID_USUARIO`, `NOMB_USUARIO`, `CORREO`, `CLAVE`) VALUES
(1, 'Carlos Pérez Gomez', 'carlos@gmail.com', '$2y$10$yvY3RVvLugOzV5BGGbY6ve2VoaXdhyZz0mKTQivVT2SFjGlCwEn6q'),
(2, 'Lucía Ramos Zevallos', 'lucia.ramos@hotmail.com', '$2y$10$yvY3RVvLugOzV5BGGbY6ve2VoaXdhyZz0mKTQivVT2SFjGlCwEn6q'),
(3, 'Diego Torres Buenaventura', 'diego.torres@outlook.com', '$2y$10$yvY3RVvLugOzV5BGGbY6ve2VoaXdhyZz0mKTQivVT2SFjGlCwEn6q'),
(4, 'María González Molina', 'maria.g@gmail.com', '$2y$10$yvY3RVvLugOzV5BGGbY6ve2VoaXdhyZz0mKTQivVT2SFjGlCwEn6q'),
(5, 'Pedro Salas Vargas', 'pedro.salas@yahoo.com', '$2y$10$yvY3RVvLugOzV5BGGbY6ve2VoaXdhyZz0mKTQivVT2SFjGlCwEn6q');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categoria`
--
ALTER TABLE `categoria`
  ADD PRIMARY KEY (`ID_CATEGORIA`);

--
-- Indices de la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  ADD PRIMARY KEY (`ID_DETALLE`),
  ADD KEY `ID_PEDIDO` (`ID_PEDIDO`),
  ADD KEY `ID_PRODUCTO` (`ID_PRODUCTO`);

--
-- Indices de la tabla `pedido`
--
ALTER TABLE `pedido`
  ADD PRIMARY KEY (`ID_PEDIDO`),
  ADD KEY `ID_USUARIO` (`ID_USUARIO`);

--
-- Indices de la tabla `producto`
--
ALTER TABLE `producto`
  ADD PRIMARY KEY (`ID_PRODUCTO`),
  ADD KEY `ID_CATEGORIA` (`ID_CATEGORIA`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`ID_USUARIO`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categoria`
--
ALTER TABLE `categoria`
  MODIFY `ID_CATEGORIA` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  MODIFY `ID_DETALLE` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `pedido`
--
ALTER TABLE `pedido`
  MODIFY `ID_PEDIDO` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `producto`
--
ALTER TABLE `producto`
  MODIFY `ID_PRODUCTO` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
COMMIT;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `ID_USUARIO` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
