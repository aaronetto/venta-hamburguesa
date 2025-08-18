-- MariaDB Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema ventas_hamburguesa
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `ventas_hamburguesa` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ;
-- -----------------------------------------------------
-- Schema ventas_hamburguesa
-- -----------------------------------------------------

USE `ventas_hamburguesa` ;

-- -----------------------------------------------------
-- Table `ventas_hamburguesa`.`cliente`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ventas_hamburguesa`.`cliente` ;

CREATE TABLE IF NOT EXISTS `ventas_hamburguesa`.`cliente` (
  `ID_CLIENTE` INT NOT NULL AUTO_INCREMENT,
  `CORREO` VARCHAR(100) NOT NULL,
  `CLAVE` VARCHAR(255) NOT NULL,
  `NOMBRES` VARCHAR(100) NOT NULL,
  `APELLIDOS` VARCHAR(100) NOT NULL,
  `TELEFONO` VARCHAR(45) NULL,
  `ACTIVO` TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY (`ID_CLIENTE`),
  UNIQUE INDEX `CORREO_UNIQUE` (`CORREO` ASC))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `ventas_hamburguesa`.`ciudad`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ventas_hamburguesa`.`ciudad` ;

CREATE TABLE IF NOT EXISTS `ventas_hamburguesa`.`ciudad` (
  `ID_CIUDAD` INT NOT NULL AUTO_INCREMENT,
  `NOMBRE` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`ID_CIUDAD`),
  UNIQUE INDEX `NOMBRE_UNIQUE` (`NOMBRE` ASC))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `ventas_hamburguesa`.`provincia`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ventas_hamburguesa`.`provincia` ;

CREATE TABLE IF NOT EXISTS `ventas_hamburguesa`.`provincia` (
  `ID_PROVINCIA` INT NOT NULL AUTO_INCREMENT,
  `NOMBRE` VARCHAR(45) NOT NULL,
  `ID_CIUDAD` INT NOT NULL,
  PRIMARY KEY (`ID_PROVINCIA`),
  INDEX `fk_provincia_ciudad_idx` (`ID_CIUDAD` ASC),
  CONSTRAINT `fk_provincia_ciudad`
    FOREIGN KEY (`ID_CIUDAD`)
    REFERENCES `ventas_hamburguesa`.`ciudad` (`ID_CIUDAD`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `ventas_hamburguesa`.`distrito`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ventas_hamburguesa`.`distrito` ;

CREATE TABLE IF NOT EXISTS `ventas_hamburguesa`.`distrito` (
  `ID_DISTRITO` INT NOT NULL AUTO_INCREMENT,
  `NOMBRE` VARCHAR(45) NOT NULL,
  `ID_PROVINCIA` INT NOT NULL,
  PRIMARY KEY (`ID_DISTRITO`),
  INDEX `fk_distrito_provincia1_idx` (`ID_PROVINCIA` ASC),
  CONSTRAINT `fk_distrito_provincia1`
    FOREIGN KEY (`ID_PROVINCIA`)
    REFERENCES `ventas_hamburguesa`.`provincia` (`ID_PROVINCIA`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `ventas_hamburguesa`.`direccion_cliente`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ventas_hamburguesa`.`direccion_cliente` ;

CREATE TABLE IF NOT EXISTS `ventas_hamburguesa`.`direccion_cliente` (
  `ID_DIRECCION_CLIENTE` INT NOT NULL AUTO_INCREMENT,
  `CALLE` VARCHAR(45) NOT NULL,
  `NUMERO` VARCHAR(45) NOT NULL,
  `ID_DISTRITO` INT NOT NULL,
  `ID_CLIENTE` INT NOT NULL,
  PRIMARY KEY (`ID_DIRECCION_CLIENTE`, `ID_DISTRITO`),
  INDEX `fk_direccion_cliente_distrito1_idx` (`ID_DISTRITO` ASC),
  INDEX `fk_direccion_cliente_cliente1_idx` (`ID_CLIENTE` ASC),
  CONSTRAINT `fk_direccion_cliente_distrito1`
    FOREIGN KEY (`ID_DISTRITO`)
    REFERENCES `ventas_hamburguesa`.`distrito` (`ID_DISTRITO`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_direccion_cliente_cliente1`
    FOREIGN KEY (`ID_CLIENTE`)
    REFERENCES `ventas_hamburguesa`.`cliente` (`ID_CLIENTE`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `ventas_hamburguesa`.`proveedor`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ventas_hamburguesa`.`proveedor` ;

CREATE TABLE IF NOT EXISTS `ventas_hamburguesa`.`proveedor` (
  `ID_PROVEEDOR` INT NOT NULL AUTO_INCREMENT,
  `NOMBRE` VARCHAR(100) NOT NULL,
  `RAZON_SOCIAL` VARCHAR(100) NOT NULL,
  `NUMERO_DOCUMENTO` VARCHAR(11) NOT NULL,
  `DIRECCION` VARCHAR(255) NOT NULL,
  `TELEFONO` VARCHAR(255) NOT NULL,
  `CORREO` VARCHAR(255) NOT NULL,
  `SITIO_WEB` VARCHAR(255) NULL,
  `CONTACTO_NOMBRES` VARCHAR(100) NOT NULL,
  `CONTACTO_APELLIDOS` VARCHAR(100) NULL,
  PRIMARY KEY (`ID_PROVEEDOR`),
  UNIQUE INDEX `NUMERO_DOCUMENTO_UNIQUE` (`NUMERO_DOCUMENTO` ASC))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `ventas_hamburguesa`.`carrito`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ventas_hamburguesa`.`carrito` ;

CREATE TABLE IF NOT EXISTS `ventas_hamburguesa`.`carrito` (
  `ID_CARRITO` INT NOT NULL AUTO_INCREMENT,
  `ESTADO` ENUM('ACTIVO', 'COMPRADO', 'ABANDONADO') NOT NULL DEFAULT 'ABANDONADO',
  `ID_CLIENTE` INT NULL,
  PRIMARY KEY (`ID_CARRITO`),
  INDEX `fk_carrito_cliente1_idx` (`ID_CLIENTE` ASC),
  CONSTRAINT `fk_carrito_cliente1`
    FOREIGN KEY (`ID_CLIENTE`)
    REFERENCES `ventas_hamburguesa`.`cliente` (`ID_CLIENTE`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `ventas_hamburguesa`.`categoria`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ventas_hamburguesa`.`categoria` ;

CREATE TABLE IF NOT EXISTS `ventas_hamburguesa`.`categoria` (
  `ID_CATEGORIA` INT NOT NULL AUTO_INCREMENT,
  `NOMBRE` VARCHAR(100) NOT NULL,
  `DESCRIPCION` TEXT NULL,
  `ACTIVO` TINYINT NOT NULL DEFAULT 1,
  `FECHA_CREACION` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `FECHA_ACTUALIZACION` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID_CATEGORIA`),
  UNIQUE INDEX `NOMBRE_UNIQUE` (`NOMBRE` ASC))
ENGINE = InnoDB
AUTO_INCREMENT = 6
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `ventas_hamburguesa`.`producto`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ventas_hamburguesa`.`producto` ;

CREATE TABLE IF NOT EXISTS `ventas_hamburguesa`.`producto` (
  `ID_PRODUCTO` INT NOT NULL AUTO_INCREMENT,
  `CODIGO` VARCHAR(100) NOT NULL,
  `NOMBRE` VARCHAR(100) NOT NULL,
  `PRECIO` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  `DESCRIPCION` TEXT NULL,
  `IMAGEN_RUTA` VARCHAR(255) NULL,
  `STOCK` INT NOT NULL DEFAULT 0,
  `ACTIVO` TINYINT NOT NULL DEFAULT 1,
  `FECHA_CREACION` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `FECHA_ACTUALIZACION` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  `ID_CATEGORIA` INT NOT NULL,
  `ID_PROVEEDOR` INT NOT NULL,
  PRIMARY KEY (`ID_PRODUCTO`),
  UNIQUE INDEX `NOMBRE_UNIQUE` (`NOMBRE` ASC),
  INDEX `fk_producto_categoria_idx` (`ID_CATEGORIA` ASC),
  INDEX `fk_producto_proveedor1_idx` (`ID_PROVEEDOR` ASC),
  CONSTRAINT `fk_producto_categoria`
    FOREIGN KEY (`ID_CATEGORIA`)
    REFERENCES `ventas_hamburguesa`.`categoria` (`ID_CATEGORIA`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_producto_proveedor1`
    FOREIGN KEY (`ID_PROVEEDOR`)
    REFERENCES `ventas_hamburguesa`.`proveedor` (`ID_PROVEEDOR`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 11
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `ventas_hamburguesa`.`carrito_detalle`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ventas_hamburguesa`.`carrito_detalle` ;

CREATE TABLE IF NOT EXISTS `ventas_hamburguesa`.`carrito_detalle` (
  `ID_CARRITO_DETALLE` INT NOT NULL AUTO_INCREMENT,
  `ID_CARRITO` INT NOT NULL,
  `ID_PRODUCTO` INT NOT NULL,
  `CANTIDAD` INT NOT NULL DEFAULT 1,
  PRIMARY KEY (`ID_CARRITO_DETALLE`),
  INDEX `fk_carrito_detalle_carrito1_idx` (`ID_CARRITO` ASC),
  INDEX `fk_carrito_detalle_producto1_idx` (`ID_PRODUCTO` ASC),
  CONSTRAINT `fk_carrito_detalle_carrito1`
    FOREIGN KEY (`ID_CARRITO`)
    REFERENCES `ventas_hamburguesa`.`carrito` (`ID_CARRITO`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_carrito_detalle_producto1`
    FOREIGN KEY (`ID_PRODUCTO`)
    REFERENCES `ventas_hamburguesa`.`producto` (`ID_PRODUCTO`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

USE `ventas_hamburguesa` ;

-- -----------------------------------------------------
-- Table `ventas_hamburguesa`.`usuario`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ventas_hamburguesa`.`usuario` ;

CREATE TABLE IF NOT EXISTS `ventas_hamburguesa`.`usuario` (
  `ID_USUARIO` INT NOT NULL AUTO_INCREMENT,
  `CORREO` VARCHAR(100) NOT NULL,
  `CLAVE` VARCHAR(255) NOT NULL,
  `NOMBRES` VARCHAR(100) NOT NULL,
  `APELLIDOS` VARCHAR(100) NOT NULL,
  `ROL` ENUM('ADMINISTRADOR', 'GERENTE', 'ASISTENTE') NOT NULL,
  `ACTIVO` TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY (`ID_USUARIO`),
  UNIQUE INDEX `CORREO_UNIQUE` (`CORREO` ASC))
ENGINE = InnoDB
AUTO_INCREMENT = 6
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `ventas_hamburguesa`.`pedido`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ventas_hamburguesa`.`pedido` ;

CREATE TABLE IF NOT EXISTS `ventas_hamburguesa`.`pedido` (
  `ID_PEDIDO` INT NOT NULL AUTO_INCREMENT,
  `METODO_PAGO` ENUM('EFECTIVO', 'TARJETA', 'YAPE') NOT NULL DEFAULT 'EFECTIVO',
  `FECHA_PEDIDO` DATE NOT NULL,
  `ID_CLIENTE` INT NOT NULL,
  `ESTADO` ENUM('PENDIENTE', 'LISTO', 'CANCELADO') NOT NULL DEFAULT 'PENDIENTE',
  `TOTAL` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `OBSERVACIONES` TEXT NULL,
  `FECHA_ENTREGA` DATETIME NOT NULL,
  `FECHA_CREACION` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `FECHA_ACTUALIZACION` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  `ID_USUARIO` INT NOT NULL,
  PRIMARY KEY (`ID_PEDIDO`),
  INDEX `fk_pedido_cliente1_idx` (`ID_CLIENTE` ASC),
  INDEX `fk_pedido_usuario1_idx` (`ID_USUARIO` ASC),
  CONSTRAINT `fk_pedido_cliente1`
    FOREIGN KEY (`ID_CLIENTE`)
    REFERENCES `ventas_hamburguesa`.`cliente` (`ID_CLIENTE`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_pedido_usuario1`
    FOREIGN KEY (`ID_USUARIO`)
    REFERENCES `ventas_hamburguesa`.`usuario` (`ID_USUARIO`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 7
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `ventas_hamburguesa`.`pedido_detalle`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ventas_hamburguesa`.`pedido_detalle` ;

CREATE TABLE IF NOT EXISTS `ventas_hamburguesa`.`pedido_detalle` (
  `ID_PEDIDO_DETALLE` INT NOT NULL AUTO_INCREMENT,
  `ID_PEDIDO` INT NOT NULL,
  `ID_PRODUCTO` INT NOT NULL,
  `CANTIDAD` INT NOT NULL,
  `PRECIO_UNITARIO` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  `SUBTOTAL` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `OBSERVACIONES` TEXT NULL,
  PRIMARY KEY (`ID_PEDIDO_DETALLE`),
  INDEX `fk_pedido_detalle_pedido1_idx` (`ID_PEDIDO` ASC),
  INDEX `fk_pedido_detalle_producto1_idx` (`ID_PRODUCTO` ASC),
  CONSTRAINT `fk_pedido_detalle_pedido1`
    FOREIGN KEY (`ID_PEDIDO`)
    REFERENCES `ventas_hamburguesa`.`pedido` (`ID_PEDIDO`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_pedido_detalle_producto1`
    FOREIGN KEY (`ID_PRODUCTO`)
    REFERENCES `ventas_hamburguesa`.`producto` (`ID_PRODUCTO`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 10
DEFAULT CHARACTER SET = utf8mb4;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

-- -----------------------------------------------------
-- DATOS INSERT IMPORTADOS DESDE ventas_hamburguesas.sql
-- -----------------------------------------------------

USE `ventas_hamburguesa`;

-- -----------------------------------------------------
-- Datos para tabla `ventas_hamburguesa`.`ciudad`
-- -----------------------------------------------------
INSERT INTO `ventas_hamburguesa`.`ciudad` (`ID_CIUDAD`, `NOMBRE`) VALUES
(1, 'Lima'),
(2, 'Arequipa'),
(3, 'Trujillo'),
(4, 'Chiclayo'),
(5, 'Piura');

-- -----------------------------------------------------
-- Datos para tabla `ventas_hamburguesa`.`provincia`
-- -----------------------------------------------------
INSERT INTO `ventas_hamburguesa`.`provincia` (`ID_PROVINCIA`, `NOMBRE`, `ID_CIUDAD`) VALUES
(1, 'Lima', 1),
(2, 'Callao', 1),
(3, 'Arequipa', 2),
(4, 'Trujillo', 3),
(5, 'Chiclayo', 4);

-- -----------------------------------------------------
-- Datos para tabla `ventas_hamburguesa`.`distrito`
-- -----------------------------------------------------
INSERT INTO `ventas_hamburguesa`.`distrito` (`ID_DISTRITO`, `NOMBRE`, `ID_PROVINCIA`) VALUES
(1, 'Miraflores', 1),
(2, 'San Isidro', 1),
(3, 'Barranco', 1),
(4, 'Callao', 2),
(5, 'Bellavista', 2);

-- -----------------------------------------------------
-- Datos para tabla `ventas_hamburguesa`.`cliente`
-- -----------------------------------------------------
INSERT INTO `ventas_hamburguesa`.`cliente` (`ID_CLIENTE`, `NOMBRES`, `APELLIDOS`, `CORREO`, `CLAVE`, `TELEFONO`, `ACTIVO`) VALUES
(1, 'Carlos', 'Pérez Gomez', 'carlos@gmail.com', '$2y$10$yvY3RVvLugOzV5BGGbY6ve2VoaXdhyZz0mKTQivVT2SFjGlCwEn6q', '999888777', 1),
(2, 'Lucía', 'Ramos Zevallos', 'lucia.ramos@hotmail.com', '$2y$10$yvY3RVvLugOzV5BGGbY6ve2VoaXdhyZz0mKTQivVT2SFjGlCwEn6q', '999888776', 1),
(3, 'Diego', 'Torres Buenaventura', 'diego.torres@outlook.com', '$2y$10$yvY3RVvLugOzV5BGGbY6ve2VoaXdhyZz0mKTQivVT2SFjGlCwEn6q', '999888775', 1),
(4, 'María', 'González Molina', 'maria.g@gmail.com', '$2y$10$yvY3RVvLugOzV5BGGbY6ve2VoaXdhyZz0mKTQivVT2SFjGlCwEn6q', '999888774', 1),
(5, 'Pedro', 'Salas Vargas', 'pedro.salas@yahoo.com', '$2y$10$yvY3RVvLugOzV5BGGbY6ve2VoaXdhyZz0mKTQivVT2SFjGlCwEn6q', '999888773', 1);

-- -----------------------------------------------------
-- Datos para tabla `ventas_hamburguesa`.`direccion_cliente`
-- -----------------------------------------------------
INSERT INTO `ventas_hamburguesa`.`direccion_cliente` (`ID_DIRECCION_CLIENTE`, `CALLE`, `NUMERO`, `ID_DISTRITO`, `ID_CLIENTE`) VALUES
(1, 'Av. Larco', '123', 1, 1),
(2, 'Av. Arequipa', '456', 2, 2),
(3, 'Av. Tacna', '789', 1, 3),
(4, 'Av. Grau', '321', 3, 4),
(5, 'Av. Abancay', '654', 2, 5);

-- -----------------------------------------------------
-- Datos para tabla `ventas_hamburguesa`.`proveedor`
-- -----------------------------------------------------
INSERT INTO `ventas_hamburguesa`.`proveedor` (`ID_PROVEEDOR`, `NOMBRE`, `RAZON_SOCIAL`, `NUMERO_DOCUMENTO`, `DIRECCION`, `TELEFONO`, `CORREO`, `SITIO_WEB`, `CONTACTO_NOMBRES`, `CONTACTO_APELLIDOS`) VALUES
(1, 'Carnes Premium S.A.', 'Carnes Premium S.A.C.', '20123456789', 'Av. Industrial 123, Lima', '01-4567890', 'ventas@carnespremium.com', 'www.carnespremium.com', 'Juan', 'García'),
(2, 'Panadería El Buen Pan', 'Panadería El Buen Pan E.I.R.L.', '20123456788', 'Jr. Panaderos 456, Lima', '01-4567891', 'ventas@elbuenpan.com', 'www.elbuenpan.com', 'María', 'López'),
(3, 'Bebidas Refrescantes', 'Bebidas Refrescantes S.A.C.', '20123456787', 'Av. Bebidas 789, Lima', '01-4567892', 'ventas@bebidasrefrescantes.com', 'www.bebidasrefrescantes.com', 'Carlos', 'Rodríguez'),
(4, 'Verduras Frescas', 'Verduras Frescas E.I.R.L.', '20123456786', 'Mercado Central 321, Lima', '01-4567893', 'ventas@verdurasfrescas.com', NULL, 'Ana', 'Martínez'),
(5, 'Quesos Artesanales', 'Quesos Artesanales S.A.C.', '20123456785', 'Av. Lácteos 654, Lima', '01-4567894', 'ventas@quesosartesanales.com', 'www.quesosartesanales.com', 'Luis', 'Fernández');

USE `ventas_hamburguesa`;

-- -----------------------------------------------------
-- Datos para tabla `ventas_hamburguesa`.`categoria`
-- -----------------------------------------------------
INSERT INTO `ventas_hamburguesa`.`categoria` (`ID_CATEGORIA`, `NOMBRE`, `DESCRIPCION`, `ACTIVO`, `FECHA_CREACION`, `FECHA_ACTUALIZACION`) VALUES
(1, 'Hamburguesas', 'Hamburguesas de carne, pollo y vegetarianas', 1, NOW(), NULL),
(2, 'Combos', 'Combos que incluyen hamburguesa, papas y bebida', 1, NOW(), NULL),
(3, 'Bebidas', 'Bebidas gaseosas, jugos y agua', 1, NOW(), NULL),
(4, 'Acompañamientos', 'Papas fritas, ensaladas y otros acompañamientos', 1, NOW(), NULL),
(5, 'Postres', 'Helados, tortas y otros postres', 1, NOW(), NULL);

-- -----------------------------------------------------
-- Datos para tabla `ventas_hamburguesa`.`usuario`
-- -----------------------------------------------------
INSERT INTO `ventas_hamburguesa`.`usuario` (`ID_USUARIO`, `NOMBRES`, `APELLIDOS`, `CORREO`, `CLAVE`, `ROL`, `ACTIVO`) VALUES
(1, 'Carlos', 'Pérez Gomez', 'carlos@gmail.com', '$2y$10$yvY3RVvLugOzV5BGGbY6ve2VoaXdhyZz0mKTQivVT2SFjGlCwEn6q', 'ADMINISTRADOR', 1),
(2, 'Lucía', 'Ramos Zevallos', 'lucia.ramos@hotmail.com', '$2y$10$yvY3RVvLugOzV5BGGbY6ve2VoaXdhyZz0mKTQivVT2SFjGlCwEn6q', 'GERENTE', 1),
(3, 'Diego', 'Torres Buenaventura', 'diego.torres@outlook.com', '$2y$10$yvY3RVvLugOzV5BGGbY6ve2VoaXdhyZz0mKTQivVT2SFjGlCwEn6q', 'ASISTENTE', 1),
(4, 'María', 'González Molina', 'maria.g@gmail.com', '$2y$10$yvY3RVvLugOzV5BGGbY6ve2VoaXdhyZz0mKTQivVT2SFjGlCwEn6q', 'ASISTENTE', 1),
(5, 'Pedro', 'Salas Vargas', 'pedro.salas@yahoo.com', '$2y$10$yvY3RVvLugOzV5BGGbY6ve2VoaXdhyZz0mKTQivVT2SFjGlCwEn6q', 'ASISTENTE', 1);

-- -----------------------------------------------------
-- Datos para tabla `ventas_hamburguesa`.`producto`
-- -----------------------------------------------------
INSERT INTO `ventas_hamburguesa`.`producto` (`ID_PRODUCTO`, `CODIGO`, `NOMBRE`, `PRECIO`, `DESCRIPCION`, `IMAGEN_RUTA`, `STOCK`, `ACTIVO`, `FECHA_CREACION`, `FECHA_ACTUALIZACION`, `ID_CATEGORIA`, `ID_PROVEEDOR`) VALUES
(1, 'HAM001', 'Hamburguesa Clásica', 15.90, 'Hamburguesa con carne, lechuga, tomate y mayonesa', 'images/pr1.png', 50, 1, NOW(), NULL, 1, 1),
(2, 'HAM002', 'Hamburguesa con Queso', 22.50, 'Hamburguesa con carne, queso cheddar, lechuga y tomate', 'images/pr2.png', 45, 1, NOW(), NULL, 1, 1),
(3, 'HAM003', 'Hamburguesa Doble Carne', 15.00, 'Hamburguesa con doble carne, lechuga y tomate', 'images/pr3.png', 40, 1, NOW(), NULL, 1, 1),
(4, 'COM001', 'Combo Clásico', 25.00, 'Hamburguesa clásica con papas fritas y bebida', 'images/pr4.png', 30, 1, NOW(), NULL, 2, 1),
(5, 'COM002', 'Combo Doble', 32.00, 'Hamburguesa doble carne con papas fritas y bebida', 'images/pr5.png', 25, 1, NOW(), NULL, 2, 1),
(6, 'COM003', 'Combo Familiar', 45.00, '2 hamburguesas, papas fritas grandes y 2 bebidas', 'images/pr6.png', 20, 1, NOW(), NULL, 2, 1),
(7, 'BEB001', 'Pepsi Jumbo', 8.00, 'Pepsi 500ml', 'images/bebida1.png', 100, 1, NOW(), NULL, 3, 3),
(8, 'BEB002', 'Inca Kola 500ml', 5.00, 'Inca Kola 500ml', 'images/bebida2.png', 80, 1, NOW(), NULL, 3, 3),
(9, 'BEB003', 'Agua mineral sin gas 500ml', 3.00, 'Agua mineral sin gas 500ml', 'images/bebida3.png', 120, 1, NOW(), NULL, 3, 3),
(10, 'ACO001', 'Papas Fritas', 8.50, 'Papas fritas crujientes', 'images/pr7.png', 60, 1, NOW(), NULL, 4, 2);

-- -----------------------------------------------------
-- Datos para tabla `ventas_hamburguesa`.`pedido`
-- -----------------------------------------------------
INSERT INTO `ventas_hamburguesa`.`pedido` (`ID_PEDIDO`, `METODO_PAGO`, `FECHA_PEDIDO`, `TOTAL`, `ID_CLIENTE`, `ESTADO`, `OBSERVACIONES`, `FECHA_ENTREGA`, `FECHA_CREACION`, `FECHA_ACTUALIZACION`, `ID_USUARIO`) VALUES
(1, 'EFECTIVO', '2025-08-01', 23.90, 1, 'LISTO', 'Sin cebolla', '2025-08-01 14:30:00', NOW(), NULL, 1),
(2, 'TARJETA', '2025-07-20', 30.50, 2, 'LISTO', 'Extra queso', '2025-07-20 19:15:00', NOW(), NULL, 2),
(3, 'YAPE', '2025-07-31', 15.00, 3, 'LISTO', NULL, '2025-07-31 12:45:00', NOW(), NULL, 3),
(4, 'EFECTIVO', '2025-08-03', 45.75, 1, 'PENDIENTE', 'Para llevar', '2025-08-03 20:00:00', NOW(), NULL, 1),
(5, 'TARJETA', '2025-08-04', 18.00, 4, 'PENDIENTE', NULL, '2025-08-04 18:30:00', NOW(), NULL, 4),
(6, 'YAPE', '2025-08-04', 32.00, 5, 'PENDIENTE', 'Sin papas', '2025-08-04 21:00:00', NOW(), NULL, 5);

-- -----------------------------------------------------
-- Datos para tabla `ventas_hamburguesa`.`pedido_detalle`
-- -----------------------------------------------------
INSERT INTO `ventas_hamburguesa`.`pedido_detalle` (`ID_PEDIDO_DETALLE`, `CANTIDAD`, `ID_PEDIDO`, `ID_PRODUCTO`, `PRECIO_UNITARIO`, `SUBTOTAL`, `OBSERVACIONES`) VALUES
(1, 1, 1, 1, 15.90, 15.90, 'Sin cebolla'),
(2, 1, 1, 7, 8.00, 8.00, NULL),
(3, 1, 2, 2, 22.50, 22.50, 'Extra queso'),
(4, 1, 2, 7, 8.00, 8.00, NULL),
(5, 1, 3, 3, 15.00, 15.00, NULL),
(6, 2, 4, 1, 15.90, 31.80, NULL),
(7, 1, 4, 2, 13.95, 13.95, NULL),
(8, 2, 5, 9, 3.00, 6.00, NULL),
(9, 1, 6, 5, 32.00, 32.00, 'Sin papas');

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
