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
