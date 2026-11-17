-- Ejecutar una sola vez sobre la base de datos sublimacionv3.
ALTER TABLE pedido ADD COLUMN motivoCancelacion VARCHAR(500) NULL AFTER idEstado;
