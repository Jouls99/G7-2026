-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 13-11-2026 a las 22:13:04
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sublimacionv3`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cliente`
--

CREATE TABLE `cliente` (
  `idCliente` int(11) NOT NULL,
  `nombre` varchar(20) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cliente`
--

INSERT INTO `cliente` (`idCliente`, `nombre`, `telefono`) VALUES
(1, 'Juan Carlos', '3794121212'),
(2, 'Pedro Prueba', '3794000000'),
(3, 'Gabriel Prueba', '3794123456');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detallepedido`
--

CREATE TABLE `detallepedido` (
  `idDetalle` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precioUnitario` decimal(10,2) NOT NULL,
  `idPedido` int(11) NOT NULL,
  `idProducto` int(11) NOT NULL,
  `idDiseño` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detallepedido`
--

INSERT INTO `detallepedido` (`idDetalle`, `cantidad`, `precioUnitario`, `idPedido`, `idProducto`, `idDiseño`) VALUES
(1, 1, 10.00, 1, 87, 1),
(2, 2, 10000.00, 2, 1, 2),
(3, 3, 2000.00, 3, 131, 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `diseño`
--

CREATE TABLE `diseño` (
  `idDiseño` int(11) NOT NULL,
  `nombre` varchar(20) DEFAULT NULL,
  `descripcion` varchar(150) DEFAULT NULL,
  `archivo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `diseño`
--

INSERT INTO `diseño` (`idDiseño`, `nombre`, `descripcion`, `archivo`) VALUES
(1, 'Messi', 'Un vaso Térmico sublimado con una foto de Messi y el Mundial.', NULL),
(2, 'Diseño personalizado', 'Tazas de Messi y Maradona', NULL),
(3, 'Diseño personalizado', '3 pines, uno de Dragon Ball, otro de Naruto, otro de Caballeros del Zodiaco', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estadopedido`
--

CREATE TABLE `estadopedido` (
  `idEstado` int(11) NOT NULL,
  `descripcionEstado` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `estadopedido`
--

INSERT INTO `estadopedido` (`idEstado`, `descripcionEstado`) VALUES
(1, 'Pendiente'),
(2, 'En Diseño'),
(3, 'Sublimando'),
(4, 'Terminado'),
(5, 'Entregado'),
(6, 'Cancelado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mediopago`
--

CREATE TABLE `mediopago` (
  `idMedioPago` int(11) NOT NULL,
  `nombre` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `mediopago`
--

INSERT INTO `mediopago` (`idMedioPago`, `nombre`) VALUES
(1, 'Efectivo'),
(2, 'Transferencia'),
(3, 'Debito'),
(4, 'Credito');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pago`
--

CREATE TABLE `pago` (
  `idPago` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fechaHoraPago` datetime DEFAULT current_timestamp(),
  `comprobante` varchar(255) DEFAULT NULL,
  `idPedido` int(11) NOT NULL,
  `idMedioPago` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pago`
--

INSERT INTO `pago` (`idPago`, `monto`, `fechaHoraPago`, `comprobante`, `idPedido`, `idMedioPago`) VALUES
(1, 5.00, '2026-10-05 15:30:50', NULL, 1, 2),
(2, 5000.00, '2026-10-05 16:08:39', NULL, 2, 2),
(3, 3000.00, '2026-10-05 16:13:24', NULL, 3, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedido`
--

CREATE TABLE `pedido` (
  `idPedido` int(11) NOT NULL,
  `fechaRegistro` datetime DEFAULT current_timestamp(),
  `fechaEntrega` date NOT NULL,
  `precioTotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `idCliente` int(11) NOT NULL,
  `idEstado` int(11) NOT NULL,
  `idUsuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedido`
--

INSERT INTO `pedido` (`idPedido`, `fechaRegistro`, `fechaEntrega`, `precioTotal`, `idCliente`, `idEstado`, `idUsuario`) VALUES
(1, '2026-10-05 15:30:50', '2026-10-31', 10.00, 1, 1, 1),
(2, '2026-10-05 16:08:39', '2026-10-05', 20000.00, 2, 1, 1),
(3, '2026-10-05 16:13:24', '2026-10-19', 6000.00, 3, 1, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto`
--

CREATE TABLE `producto` (
  `idProducto` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` varchar(60) DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `stock` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `producto`
--

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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `idUsuario` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `passwordHash` varchar(255) NOT NULL,
  `rol` enum('ADMIN','EMPLEADO') DEFAULT 'EMPLEADO',
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `fechaRegistro` datetime NOT NULL DEFAULT current_timestamp(),
  `tokenRecuperacion` varchar(64) DEFAULT NULL,
  `tokenExpiracion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`idUsuario`, `nombre`, `telefono`, `email`, `passwordHash`, `rol`, `estado`, `fechaRegistro`, `tokenRecuperacion`, `tokenExpiracion`) VALUES
(1, 'Administrador', NULL, 'admin@kapykua.local', '$2y$12$ACxhFoKtRkd.S7GiWZHww.3y3R345ZYJz9gPe1OBivhHCraGJfejq', 'ADMIN', 1, '2026-10-05 15:08:28', NULL, NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `cliente`
--
ALTER TABLE `cliente`
  ADD PRIMARY KEY (`idCliente`);

--
-- Indices de la tabla `detallepedido`
--
ALTER TABLE `detallepedido`
  ADD PRIMARY KEY (`idDetalle`),
  ADD KEY `idx_detalle_pedido` (`idPedido`),
  ADD KEY `idx_detalle_producto` (`idProducto`),
  ADD KEY `idx_detalle_diseño` (`idDiseño`);

--
-- Indices de la tabla `diseño`
--
ALTER TABLE `diseño`
  ADD PRIMARY KEY (`idDiseño`);

--
-- Indices de la tabla `estadopedido`
--
ALTER TABLE `estadopedido`
  ADD PRIMARY KEY (`idEstado`);

--
-- Indices de la tabla `mediopago`
--
ALTER TABLE `mediopago`
  ADD PRIMARY KEY (`idMedioPago`);

--
-- Indices de la tabla `pago`
--
ALTER TABLE `pago`
  ADD PRIMARY KEY (`idPago`),
  ADD KEY `idx_pago_pedido` (`idPedido`),
  ADD KEY `idx_pago_medio` (`idMedioPago`);

--
-- Indices de la tabla `pedido`
--
ALTER TABLE `pedido`
  ADD PRIMARY KEY (`idPedido`),
  ADD KEY `idx_pedido_cliente` (`idCliente`),
  ADD KEY `idx_pedido_estado` (`idEstado`),
  ADD KEY `idx_pedido_usuario` (`idUsuario`);

--
-- Indices de la tabla `producto`
--
ALTER TABLE `producto`
  ADD PRIMARY KEY (`idProducto`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`idUsuario`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `cliente`
--
ALTER TABLE `cliente`
  MODIFY `idCliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `detallepedido`
--
ALTER TABLE `detallepedido`
  MODIFY `idDetalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `diseño`
--
ALTER TABLE `diseño`
  MODIFY `idDiseño` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `estadopedido`
--
ALTER TABLE `estadopedido`
  MODIFY `idEstado` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `mediopago`
--
ALTER TABLE `mediopago`
  MODIFY `idMedioPago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `pago`
--
ALTER TABLE `pago`
  MODIFY `idPago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `pedido`
--
ALTER TABLE `pedido`
  MODIFY `idPedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `producto`
--
ALTER TABLE `producto`
  MODIFY `idProducto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=179;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `idUsuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `detallepedido`
--
ALTER TABLE `detallepedido`
  ADD CONSTRAINT `fk_detalle_diseño` FOREIGN KEY (`idDiseño`) REFERENCES `diseño` (`idDiseño`),
  ADD CONSTRAINT `fk_detalle_pedido` FOREIGN KEY (`idPedido`) REFERENCES `pedido` (`idPedido`),
  ADD CONSTRAINT `fk_detalle_producto` FOREIGN KEY (`idProducto`) REFERENCES `producto` (`idProducto`);

--
-- Filtros para la tabla `pago`
--
ALTER TABLE `pago`
  ADD CONSTRAINT `fk_pago_medio` FOREIGN KEY (`idMedioPago`) REFERENCES `mediopago` (`idMedioPago`),
  ADD CONSTRAINT `fk_pago_pedido` FOREIGN KEY (`idPedido`) REFERENCES `pedido` (`idPedido`);

--
-- Filtros para la tabla `pedido`
--
ALTER TABLE `pedido`
  ADD CONSTRAINT `fk_pedido_cliente` FOREIGN KEY (`idCliente`) REFERENCES `cliente` (`idCliente`),
  ADD CONSTRAINT `fk_pedido_estado` FOREIGN KEY (`idEstado`) REFERENCES `estadopedido` (`idEstado`),
  ADD CONSTRAINT `fk_pedido_usuario` FOREIGN KEY (`idUsuario`) REFERENCES `usuario` (`idUsuario`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
