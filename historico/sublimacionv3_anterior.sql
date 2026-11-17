-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 05-10-2026 a las 02:40:21
-- Versión del servidor: 5.7.40
-- Versión de PHP: 8.0.26

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

DROP TABLE IF EXISTS `cliente`;
CREATE TABLE IF NOT EXISTS `cliente` (
  `idCliente` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(20) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`idCliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detallepedido`
--

DROP TABLE IF EXISTS `detallepedido`;
CREATE TABLE IF NOT EXISTS `detallepedido` (
  `idDetalle` int(11) NOT NULL AUTO_INCREMENT,
  `cantidad` int(11) NOT NULL,
  `precioUnitario` decimal(10,2) NOT NULL,
  `idPedido` int(11) NOT NULL,
  `idProducto` int(11) NOT NULL,
  `idDiseño` int(11) DEFAULT NULL,
  PRIMARY KEY (`idDetalle`),
  KEY `idx_detalle_pedido` (`idPedido`),
  KEY `idx_detalle_producto` (`idProducto`),
  KEY `idx_detalle_diseño` (`idDiseño`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `diseño`
--

DROP TABLE IF EXISTS `diseño`;
CREATE TABLE IF NOT EXISTS `diseño` (
  `idDiseño` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(20) DEFAULT NULL,
  `descripcion` varchar(150) DEFAULT NULL,
  `archivo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`idDiseño`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estadopedido`
--

DROP TABLE IF EXISTS `estadopedido`;
CREATE TABLE IF NOT EXISTS `estadopedido` (
  `idEstado` int(11) NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(150) NOT NULL,
  PRIMARY KEY (`idEstado`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `estadopedido`
--

INSERT INTO `estadopedido` (`idEstado`, `descripcion`) VALUES
(1, 'Pendiente'),
(2, 'En Diseño'),
(3, 'Sublimando'),
(4, 'Terminado'),
(5, 'Entregado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mediopago`
--

DROP TABLE IF EXISTS `mediopago`;
CREATE TABLE IF NOT EXISTS `mediopago` (
  `idMedioPago` int(11) NOT NULL,
  `nombre` varchar(20) NOT NULL,
  PRIMARY KEY (`idMedioPago`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pago`
--

DROP TABLE IF EXISTS `pago`;
CREATE TABLE IF NOT EXISTS `pago` (
  `idPago` int(11) NOT NULL AUTO_INCREMENT,
  `monto` decimal(10,2) NOT NULL,
  `fechaPago` datetime DEFAULT CURRENT_TIMESTAMP,
  `idPedido` int(11) NOT NULL,
  `idMedioPago` int(11) NOT NULL,
  PRIMARY KEY (`idPago`),
  KEY `idx_pago_pedido` (`idPedido`),
  KEY `fk_pago_medio` (`idMedioPago`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedido`
--

DROP TABLE IF EXISTS `pedido`;
CREATE TABLE IF NOT EXISTS `pedido` (
  `idPedido` int(11) NOT NULL AUTO_INCREMENT,
  `fechaRegistro` datetime DEFAULT CURRENT_TIMESTAMP,
  `fechaEntrega` datetime DEFAULT CURRENT_TIMESTAMP,
  `precioTotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `idCliente` int(11) NOT NULL,
  `idEstado` int(11) NOT NULL,
  `idUsuario` int(11) NOT NULL,
  PRIMARY KEY (`idPedido`),
  KEY `idx_pedido_cliente` (`idCliente`),
  KEY `idx_pedido_estado` (`idEstado`),
  KEY `fk_pedido_usuario` (`idUsuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto`
--

DROP TABLE IF EXISTS `producto`;
CREATE TABLE IF NOT EXISTS `producto` (
  `idProducto` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(15) NOT NULL,
  `descripcion` varchar(60) DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `stock` int(11) DEFAULT NULL,
  PRIMARY KEY (`idProducto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

DROP TABLE IF EXISTS `usuario`;
CREATE TABLE IF NOT EXISTS `usuario` (
  `idUsuario` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `clave` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol` enum('ADMIN','EMPLEADO') COLLATE utf8mb4_unicode_ci DEFAULT 'EMPLEADO',
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  `fechaRegistro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tokenRecuperacion` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tokenExpiracion` datetime DEFAULT NULL,
  PRIMARY KEY (`idUsuario`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  ADD CONSTRAINT `fk_pedido_usuario` FOREIGN KEY (`idUsuario`) REFERENCES `usuario` (`idUsuario`),
  ADD CONSTRAINT `pedido_ibfk_1` FOREIGN KEY (`idUsuario`) REFERENCES `usuario` (`idUsuario`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
