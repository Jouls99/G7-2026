-- Base de datos Kapykuá - Parte 2
-- Versión corregida según el diagrama de clases actualizado
-- Motor previsto: MariaDB / MySQL (XAMPP)

CREATE DATABASE IF NOT EXISTS sublimacionv2
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

USE sublimacionv2;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS pago;
DROP TABLE IF EXISTS detallepedido;
DROP TABLE IF EXISTS pedido;
DROP TABLE IF EXISTS diseño;
DROP TABLE IF EXISTS producto;
DROP TABLE IF EXISTS estadopedido;
DROP TABLE IF EXISTS cliente;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- CLIENTE
-- Un cliente puede tener 0..* pedidos.
-- ============================================
CREATE TABLE cliente (
    idCliente INT NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(20) NOT NULL,
    telefono VARCHAR(20),
    PRIMARY KEY (idCliente)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;

-- ============================================
-- PRODUCTO
-- Un producto puede aparecer en 1..* detalles.
-- ============================================
CREATE TABLE producto (
    idProducto INT NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(150) NOT NULL,
    descripcion VARCHAR(60),
    precio DECIMAL(10,2),
    stock INT,
    PRIMARY KEY (idProducto),
    CONSTRAINT chk_producto_precio CHECK (precio IS NULL OR precio >= 0),
    CONSTRAINT chk_producto_stock CHECK (stock IS NULL OR stock >= 0)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;

-- ============================================
-- DISEÑO
-- Cada detalle de pedido referencia un diseño.
-- ============================================
CREATE TABLE diseño (
    idDiseño INT NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(20),
    descripcion VARCHAR(150),
    archivo VARCHAR(255),
    PRIMARY KEY (idDiseño)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;

-- ============================================
-- ESTADO DEL PEDIDO
-- Un estado puede corresponder a 0..* pedidos.
-- ============================================
CREATE TABLE estadopedido (
    idEstado INT NOT NULL AUTO_INCREMENT,
    descripcion VARCHAR(150) NOT NULL,
    PRIMARY KEY (idEstado)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;

-- ============================================
-- PEDIDO
-- Cada pedido pertenece a un cliente y tiene
-- un único estado vigente.
-- ============================================
CREATE TABLE pedido (
    idPedido INT NOT NULL AUTO_INCREMENT,
    fechaRegistro DATE NOT NULL,
    fechaEntrega DATE NOT NULL,
    precioTotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    idCliente INT NOT NULL,
    idEstado INT NOT NULL,
    PRIMARY KEY (idPedido),
    KEY idx_pedido_cliente (idCliente),
    KEY idx_pedido_estado (idEstado),
    CONSTRAINT fk_pedido_cliente
        FOREIGN KEY (idCliente)
        REFERENCES cliente (idCliente),
    CONSTRAINT fk_pedido_estado
        FOREIGN KEY (idEstado)
        REFERENCES estadopedido (idEstado),
    CONSTRAINT chk_pedido_precio CHECK (precioTotal >= 0)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;

-- ============================================
-- DETALLE DEL PEDIDO
-- Un pedido tiene 1..* detalles.
-- Cada detalle pertenece a un pedido, un producto
-- y un diseño.
-- ============================================
CREATE TABLE detallepedido (
    idDetalle INT NOT NULL AUTO_INCREMENT,
    cantidad INT NOT NULL,
    precioUnitario DECIMAL(10,2) NOT NULL,
    idPedido INT NOT NULL,
    idProducto INT NOT NULL,
    idDiseño INT NOT NULL,
    PRIMARY KEY (idDetalle),
    KEY idx_detalle_pedido (idPedido),
    KEY idx_detalle_producto (idProducto),
    KEY idx_detalle_diseño (idDiseño),
    CONSTRAINT fk_detalle_pedido
        FOREIGN KEY (idPedido)
        REFERENCES pedido (idPedido),
    CONSTRAINT fk_detalle_producto
        FOREIGN KEY (idProducto)
        REFERENCES producto (idProducto),
    CONSTRAINT fk_detalle_diseño
        FOREIGN KEY (idDiseño)
        REFERENCES diseño (idDiseño),
    CONSTRAINT chk_detalle_cantidad CHECK (cantidad > 0),
    CONSTRAINT chk_detalle_precio CHECK (precioUnitario >= 0)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;

-- ============================================
-- PAGO
-- Un pedido puede tener 0..* pagos.
-- Cada pago pertenece a un pedido.
-- ============================================
CREATE TABLE pago (
    idPago INT NOT NULL AUTO_INCREMENT,
    monto DECIMAL(10,2) NOT NULL,
    fechaPago DATE NOT NULL,
    metodoPago VARCHAR(13),
    idPedido INT NOT NULL,
    PRIMARY KEY (idPago),
    KEY idx_pago_pedido (idPedido),
    CONSTRAINT fk_pago_pedido
        FOREIGN KEY (idPedido)
        REFERENCES pedido (idPedido),
    CONSTRAINT chk_pago_monto CHECK (monto > 0)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;

-- ============================================
-- DATOS INICIALES MÍNIMOS PARA LOS ESTADOS
-- ============================================
INSERT INTO estadopedido (descripcion) VALUES
('Pendiente'),
('En Diseño'),
('Sublimando'),
('Terminado'),
('Entregado');
INSERT INTO `producto` (`idProducto`, `nombre`, `descripcion`, `precio`, `stock`) VALUES
(1, 'Taza', 'Taza para sublimación', NULL, NULL),
(2, 'Mate', 'Mate para sublimación', NULL, NULL),
(3, 'Mouse Pad', 'Mouse Pad para sublimación', NULL, NULL),
(4, 'Botella', 'Botella para sublimación', NULL, NULL),
(5, 'Gorra', 'Gorra para sublimación', NULL, NULL),
(6, 'Almanaques carpita para sublimar (19 x13 cm)', NULL, NULL, NULL),
(7, 'Azucarera/Yerbera Ceramica', NULL, NULL, NULL),
(8, 'Azucarera/yerbera polimero', NULL, NULL, NULL),
(9, 'Baberos y Bandanas', NULL, NULL, NULL),
(10, 'Billeteras', NULL, NULL, NULL),
(11, 'Bolsita para auto', NULL, NULL, NULL),
(12, 'Bolsitas golosineras (20 x 30 cm)', NULL, NULL, NULL),
(13, 'Bolso Playero', NULL, NULL, NULL),
(14, 'Botella Aluminio sublimable 750 ml', NULL, NULL, NULL),
(15, 'Botineros', NULL, NULL, NULL),
(16, 'Bufandas Polar sublimable', NULL, NULL, NULL),
(17, 'Cajita feliz sublimable 14 x 10 x 10cm', NULL, NULL, NULL),
(18, 'Cantimplora polimero', NULL, NULL, NULL),
(19, 'Cantimplora polimero osito', NULL, NULL, NULL),
(20, 'Cartucheras chicas', NULL, NULL, NULL),
(21, 'Cartuchera grande 22 x 10 x 7 cm', NULL, NULL, NULL),
(22, 'Chopp Ceramica', NULL, NULL, NULL),
(23, 'Chopp Polimero', NULL, NULL, NULL),
(24, 'Conjunto AJUAR bebé', NULL, NULL, NULL),
(25, 'Conjunto SET DE BAÑO bebé', NULL, NULL, NULL),
(26, 'Copas Sublimables 23 cm x 8 cm', NULL, NULL, NULL),
(27, 'Credenciales polimero', NULL, NULL, NULL),
(28, 'Credenciales polimero brillante con ganchito', NULL, NULL, NULL),
(29, 'Cubiertos Polimero blancos', NULL, NULL, NULL),
(30, 'Cubiertos polimero colores pastel', NULL, NULL, NULL),
(31, 'Delantales', NULL, NULL, NULL),
(32, 'Fundas Almohadon 30 x 40 cm y 20 x 40 cm', NULL, NULL, NULL),
(33, 'Fundas Almohadon 40 x 40 cm', NULL, NULL, NULL),
(34, 'Fundas Almohadon Matero 33 x 43 x 15 cm', NULL, NULL, NULL),
(35, 'Hoppy polimero', NULL, NULL, NULL),
(36, 'Jarritos de café de colores para vinilo', NULL, NULL, NULL),
(37, 'Jarro Termico sublimable', NULL, NULL, NULL),
(38, 'Llaveros destapador polimero', NULL, NULL, NULL),
(39, 'Llaveros polimero blancos', NULL, NULL, NULL),
(40, 'Llaveros polimero brillantes', NULL, NULL, NULL),
(41, 'Macetas polimero', NULL, NULL, NULL),
(42, 'Maletin Pizarra', NULL, NULL, NULL),
(43, 'Mate Ceramica', NULL, NULL, NULL),
(44, 'Mate Listo', NULL, NULL, NULL),
(45, 'Mate polimero', NULL, NULL, NULL),
(46, 'Mate polimero antivuelco', NULL, NULL, NULL),
(47, 'Mate polimero completo', NULL, NULL, NULL),
(48, 'Matera de neoprene', NULL, NULL, NULL),
(49, 'Medallas polimero', NULL, NULL, NULL),
(50, 'Medias sublimables Futbol/Jokey', NULL, NULL, NULL),
(51, 'Monederitos', NULL, NULL, NULL),
(52, 'Morral chico', NULL, NULL, NULL),
(53, 'Morrales grandes', NULL, NULL, NULL),
(54, 'Morrales medianos', NULL, NULL, NULL),
(55, 'Mouse Pad cuadrado y redondo 20 x 20 cm', NULL, NULL, NULL),
(56, 'Mouse Pad grande 40 cm x 30 cm', NULL, NULL, NULL),
(57, 'Mouse Pad gamer 70 x 30', NULL, NULL, NULL),
(58, 'Neceser 18 x 14 cm', NULL, NULL, NULL),
(59, 'Neceser holográficos 18 x 14 cm', NULL, NULL, NULL),
(60, 'Neceser grande 13 x 23 cm', NULL, NULL, NULL),
(61, 'Pines Polimero Sublimables', NULL, NULL, NULL),
(62, 'Platos Polimero 15 cm', NULL, NULL, NULL),
(63, 'Plato Polimero 20 cm', NULL, NULL, NULL),
(64, 'Pochoclero carton sublimable 7,5 x 9,5 cm', NULL, NULL, NULL),
(65, 'Porta lapices acrilico', NULL, NULL, NULL),
(66, 'Porta lapices ceramica', NULL, NULL, NULL),
(67, 'Porta lapices polimero', NULL, NULL, NULL),
(68, 'Porta netbook 27 x 32 cm', NULL, NULL, NULL),
(69, 'Porta notebook 31 x 40 cm', NULL, NULL, NULL),
(70, 'Porta tablet neoprene 20 x 15 cm', NULL, NULL, NULL),
(71, 'Posa vaso neoprene antideslizante', NULL, NULL, NULL),
(72, 'Posavasos polimero', NULL, NULL, NULL),
(73, 'Posavasos polimero redondo/pua', NULL, NULL, NULL),
(74, 'Pua polimero', NULL, NULL, NULL),
(75, 'Riñoneras', NULL, NULL, NULL),
(76, 'Set asado', NULL, NULL, NULL),
(77, 'Set Higiene', NULL, NULL, NULL),
(78, 'Set de Jardin', NULL, NULL, NULL),
(79, 'Set manopla y agarraderas (3 piezas)', NULL, NULL, NULL),
(80, 'Set Mochila jardin, lunchera, cartuchera', NULL, NULL, NULL),
(81, 'Set Yerbera/Azucarera Neoprene (conjunto)', NULL, NULL, NULL),
(82, 'Tazas Ceramica importada premiun AAA', NULL, NULL, NULL),
(83, 'Tazas polimero apilables WORKAT', NULL, NULL, NULL),
(84, 'Toallitas', NULL, NULL, NULL),
(85, 'Toallón para bebé 50 x 50 cm con capucha sublimable', NULL, NULL, NULL),
(86, 'Vaso polimero', NULL, NULL, NULL),
(87, 'Vaso Termico sublimable', NULL, NULL, NULL),
(88, 'Vinilo reflectivo sublimable', NULL, NULL, NULL),
(89, 'Papel Fotografico Adhesivo brillante y mate', NULL, NULL, NULL),
(90, 'Papel Fotografico Brillante 140 grs', NULL, NULL, NULL),
(91, 'Papel Fotografico Brillante 200 grs', NULL, NULL, NULL),
(92, 'Papel Fotografico Brillante 230 grs', NULL, NULL, NULL),
(93, 'Papel HOLOFAN adhesivo', NULL, NULL, NULL),
(94, 'Papel KRAFT adhesivo', NULL, NULL, NULL),
(95, 'Papel TATUFAN', NULL, NULL, NULL),
(96, 'Papel Transfer ropa Clara', NULL, NULL, NULL),
(97, 'Papel Transfer Ropa Oscura', NULL, NULL, NULL),
(98, 'Papel Winky blanco', NULL, NULL, NULL),
(99, 'Papel Winky transparente', NULL, NULL, NULL),
(100, 'Parches textiles sublimables A4', NULL, NULL, NULL),
(101, 'Matelina Simple faz 200 grs', NULL, NULL, NULL),
(102, 'Matelina Doble Faz 220 grs', NULL, NULL, NULL),
(103, 'Filmilo Acetato adhesivo', NULL, NULL, NULL),
(104, 'Filmilo Blanco adhesivo', NULL, NULL, NULL),
(105, 'Filmilo oro lujoso adhesivo', NULL, NULL, NULL),
(106, 'AGENDA PERPETUA', NULL, NULL, NULL),
(107, 'ALCANCIA 10 X 10 CM', NULL, NULL, NULL),
(108, 'ALHAJERO 8,8 X 4,5 CM', NULL, NULL, NULL),
(109, 'ARBOLITO DE NAVIDAD (22cm x 16cm)', NULL, NULL, NULL),
(110, 'ARBOLITO DE NAVIDAD TABLITA (29 cm x 19 cm)', NULL, NULL, NULL),
(111, 'ARBOLITO DE NAVIDAD MINI (10 cm x 8 cm)', NULL, NULL, NULL),
(112, 'BANDEJA FC A3', NULL, NULL, NULL),
(113, 'BANDEJA FC A4', NULL, NULL, NULL),
(114, 'BANDERITA CON MASTIL (13 X 19 CM)', NULL, NULL, NULL),
(115, 'CAJA BOTELLA DE CERVEZA', NULL, NULL, NULL),
(116, 'CAJA DE VINO (10cm x 10cm x 40,5cm)', NULL, NULL, NULL),
(117, 'CAJA DE TE', NULL, NULL, NULL),
(118, 'DESTAPADOR DE PARED 24 X 10 CM', NULL, NULL, NULL),
(119, 'DOMINO 28 PIEZAS', NULL, NULL, NULL),
(120, 'LLAVERO CAMISETA (5cm x 5cm + ARGOLLA)', NULL, NULL, NULL),
(121, 'LLAVERO CHOPP (4cm x 6cm + ARGOLLA)', NULL, NULL, NULL),
(122, 'LLAVERO CIRCULAR (5cm diametro + ARGOLLA)', NULL, NULL, NULL),
(123, 'LLAVERO CORAZON DUO', NULL, NULL, NULL),
(124, 'LLAVERO CRUZ (4,5cm x 6cm + ARGOLLA)', NULL, NULL, NULL),
(125, 'LLAVERO CUADRADO (5cm x 5cm + ARGOLLA)', NULL, NULL, NULL),
(126, 'LLAVERO DESTAPADOR', NULL, NULL, NULL),
(127, 'LLAVERO FORMA MATE (4 X 7 CM)', NULL, NULL, NULL),
(128, 'LLAVERO I LOVE (6,5cm x 4,5cm + ARGOLLA)', NULL, NULL, NULL),
(129, 'MEDALLA', NULL, NULL, NULL),
(130, 'MEMOTEST 10 PIEZAS 6 X 6 CM', NULL, NULL, NULL),
(131, 'PINES', NULL, NULL, NULL),
(132, 'PLACA SUBLIMABLE 14 X 35 CM', NULL, NULL, NULL),
(133, 'PLACA SUBLIMABLE 15 X 15 CM', NULL, NULL, NULL),
(134, 'PLACA SUBLIMABLE 20 X 29 CM', NULL, NULL, NULL),
(135, 'PLACA SUBLIMABLE 26 X 35 CM', NULL, NULL, NULL),
(136, 'PORTA CEL DE ESCRITORIO (7,5cm x 16cm)', NULL, NULL, NULL),
(137, 'PORTA LLAVES 3 GANCHOS (20cm x 10,5cm)', NULL, NULL, NULL),
(138, 'PORTA RETRATO (10cm x 8cm)', NULL, NULL, NULL),
(139, 'PORTA RETRATO (13cm x 18cm)', NULL, NULL, NULL),
(140, 'PORTARETRATO TRIPTICO GIRATORIO', NULL, NULL, NULL),
(141, 'POSA VASO CIRCULAR (10cm diámetro)', NULL, NULL, NULL),
(142, 'POSA VASO CUADRADO (10cm x 10cm)', NULL, NULL, NULL),
(143, 'RELOJ CIRCULAR (20cm diametro + MAQUINA y CAJA SUBLIMABLE)', NULL, NULL, NULL),
(144, 'RELOJ CUADRADO (20cm x 20cm + MAQUINA y CAJA SUBLIMABLE)', NULL, NULL, NULL),
(145, 'ROMPECABEZAS 12 PIEZAS (14cm x 20cm)', NULL, NULL, NULL),
(146, 'ROMPECABEZAS 30 PIEZAS (29cm x 20cm)', NULL, NULL, NULL),
(147, 'ROMPECABEZAS 4 PIEZAS (14cm x 14cm)', NULL, NULL, NULL),
(148, 'ROMPECABEZAS 6 PIEZAS (14cm x 20cm)', NULL, NULL, NULL),
(149, 'Spum niños blanco talle 4 al 14', NULL, NULL, NULL),
(150, 'Spum niños gris talle 4 al 14', NULL, NULL, NULL),
(151, 'Algodón niños colores varios talle 4 al 14', NULL, NULL, NULL),
(152, 'Musculosas niños colores fluo', NULL, NULL, NULL),
(153, 'Bodies talles 1 al 6', NULL, NULL, NULL),
(154, 'Sudaderas spum talle unico varios colores', NULL, NULL, NULL),
(155, 'Zumberas set deportivo colores fluo', NULL, NULL, NULL),
(156, 'Remeras corte dama talle S al XXl blancas', NULL, NULL, NULL),
(157, 'Remeras corte dama talle S al XXl colores gris/rosa', NULL, NULL, NULL),
(158, 'Remera corte dama Talle especial blanca', NULL, NULL, NULL),
(159, 'Remera corte dama Talle especial colores', NULL, NULL, NULL),
(160, 'Puperas Talle Unico', NULL, NULL, NULL),
(161, 'Spum Blanco talle S al XXL', NULL, NULL, NULL),
(162, 'Spum Gris talle S al XXL', NULL, NULL, NULL),
(163, 'Spum Blanco Talle Especial', NULL, NULL, NULL),
(164, 'Spum Gris Talle Especial', NULL, NULL, NULL),
(165, 'Algodón Colores varios Talle S al XXL', NULL, NULL, NULL),
(166, 'Algodón Talles Especiales', NULL, NULL, NULL),
(167, 'Algodón Gris', NULL, NULL, NULL),
(168, 'Algodón Gris Talle Especial', NULL, NULL, NULL),
(169, 'Set Deportivo Talles S al XXL', NULL, NULL, NULL),
(170, 'Musculosas Set Deportivo Talle S al XXL', NULL, NULL, NULL),
(171, 'Chombas Algodón Talle S al XXL', NULL, NULL, NULL),
(172, 'Chombas Algodón Talle Especial', NULL, NULL, NULL),
(173, 'Algodón Peinado', NULL, NULL, NULL),
(174, 'Algodón Peinado Talle especial', NULL, NULL, NULL),
(175, 'Pecheras Adultos Set Deportivo', NULL, NULL, NULL),
(176, 'Pecheras Niños Set Deportivo', NULL, NULL, NULL),
(177, 'Puperas Algodón', NULL, NULL, NULL),
(178, 'Pijamas Verano Niños del 4 al 14', NULL, NULL, NULL);

