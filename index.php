<?php
require_once "conexion.php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kapykuá - Nuevo Pedido</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="app">

    <!-- BARRA LATERAL -->
    <aside class="sidebar">
        <div class="logo">
            <strong>Kapykuá</strong>
            <span>Librería Gráfica</span>
        </div>

        <nav class="menu">
            <a href="#" class="menu-item activo">
                <span class="icono">＋</span>
                <span>Nuevo<br>Pedido</span>
            </a>

            <a href="#" class="menu-item">
                <span class="icono">□</span>
                <span>Pedidos</span>
            </a>

            <a href="#" class="menu-item">
                <span class="icono">♧</span>
                <span>Notificar</span>
            </a>
        </nav>
    </aside>

    <!-- ÁREA PRINCIPAL -->
    <div class="principal">

        <header class="barra-superior">
            <span class="ajustes">⚙</span>
        </header>

        <main class="contenido">

            <div class="pasos">

                <!-- PASO 1 ABIERTO -->
                <section class="paso abierto">
                    <div class="titulo-paso">
                        <span class="numero">1</span>
                        <h2>DATOS DEL CLIENTE</h2>
                    </div>

                    <div class="formulario">

                        <div class="campo">
                            <label for="nombre">Nombre</label>
                            <input type="text" id="nombre" name="nombre">
                        </div>

                        <div class="campo">
                            <label for="telefono">Teléfono</label>
                            <input type="tel" id="telefono" name="telefono">
                        </div>

                        <div class="botonera">
                            <button type="button" class="btn-siguiente">Siguiente &gt;</button>
                        </div>

                    </div>
                </section>

                <!-- PASO 2 -->
                <section class="paso cerrado">
                    <div class="titulo-paso">
                        <span class="numero">2</span>
                        <h2>INFORMACIÓN DEL PEDIDO</h2>
                        <span class="flecha">&gt;</span>
                    </div>

                    <div class="contenido-oculto">
                        <div class="campo">
                            <label for="producto">Producto</label>
                            <select id="producto" name="producto">
                                <option value="">Seleccione un producto</option>
                                <option>Taza</option>
                                <option>Mate</option>
                                <option>Mouse Pad</option>
                                <option>Botella</option>
                                <option>Gorra</option>
                            </select>
                        </div>

                        <div class="fila">
                            <div class="campo">
                                <label for="cantidad">Cantidad</label>
                                <input type="number" id="cantidad" min="1">
                            </div>

                            <div class="campo">
                                <label for="diseno">Diseño</label>
                                <input type="text" id="diseno">
                            </div>
                        </div>

                        <div class="campo">
                            <label for="descripcion">Descripción del diseño</label>
                            <textarea id="descripcion" rows="3"></textarea>
                        </div>

                        <div class="campo">
                            <label for="observaciones">Observaciones</label>
                            <textarea id="observaciones" rows="3"></textarea>
                        </div>

                        <div class="campo">
                            <label for="archivo">Archivo del diseño</label>
                            <input type="file" id="archivo">
                        </div>

                        <div class="botonera">
                            <button type="button" class="btn-atras">&lt; Atrás</button>
                            <button type="button" class="btn-siguiente">Siguiente &gt;</button>
                        </div>
                    </div>
                </section>

                <!-- PASO 3 -->
                <section class="paso cerrado">
                    <div class="titulo-paso">
                        <span class="numero">3</span>
                        <h2>FECHA DE ENTREGA</h2>
                        <span class="flecha">&gt;</span>
                    </div>

                    <div class="contenido-oculto">
                        <div class="campo">
                            <label for="fecha_entrega">Fecha</label>
                            <input type="date" id="fecha_entrega">
                        </div>

                        <div class="botonera">
                            <button type="button" class="btn-atras">&lt; Atrás</button>
                            <button type="button" class="btn-siguiente">Siguiente &gt;</button>
                        </div>
                    </div>
                </section>

                <!-- PASO 4 -->
                <section class="paso cerrado">
                    <div class="titulo-paso">
                        <span class="numero">4</span>
                        <h2>PAGO</h2>
                        <span class="flecha">&gt;</span>
                    </div>

                    <div class="contenido-oculto">
                        <div class="fila">
                            <div class="campo">
                                <label for="monto">Monto de la seña</label>
                                <input type="number" id="monto" min="0" step="0.01">
                            </div>

                            <div class="campo">
                                <label for="medio_pago">Medio de pago</label>
                                <select id="medio_pago">
                                    <option value="">Seleccione un medio</option>
                                    <option>Efectivo</option>
                                    <option>Transferencia</option>
                                    <option>Débito</option>
                                    <option>Crédito</option>
                                </select>
                            </div>
                        </div>

                        <div class="botonera">
                            <button type="button" class="btn-atras">&lt; Atrás</button>
                            <button type="button" class="btn-registrar">Registrar pedido</button>
                        </div>
                    </div>
                </section>

            </div>

        </main>
    </div>
</div>

</body>
</html>
