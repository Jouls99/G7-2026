<?php
require_once "conexion.php";

$mensaje = "";
$tipoMensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["accion"] ?? "") === "actualizar_estado") {
    $idPedidoActualizar = (int)($_POST["idPedido"] ?? 0);
    $idEstadoNuevo = (int)($_POST["idEstado"] ?? 0);

    if ($idPedidoActualizar <= 0 || $idEstadoNuevo <= 0) {
        $mensaje = "No se pudo actualizar el estado: datos inválidos.";
        $tipoMensaje = "error";
    } else {
        $stmt = $conn->prepare(
            "SELECT idEstado FROM estadopedido WHERE idEstado = ? LIMIT 1"
        );
        $stmt->bind_param("i", $idEstadoNuevo);
        $stmt->execute();
        $estadoValido = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$estadoValido) {
            $mensaje = "El estado seleccionado no existe.";
            $tipoMensaje = "error";
        } else {
            $stmt = $conn->prepare(
                "UPDATE pedido SET idEstado = ? WHERE idPedido = ?"
            );
            $stmt->bind_param("ii", $idEstadoNuevo, $idPedidoActualizar);
            $stmt->execute();

            if ($stmt->affected_rows >= 0 && $stmt->errno === 0) {
                $mensaje = "Estado del pedido actualizado correctamente.";
                $tipoMensaje = "exito";
            } else {
                $mensaje = "No se pudo actualizar el estado del pedido.";
                $tipoMensaje = "error";
            }
            $stmt->close();
        }
    }

} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre = trim($_POST["nombre"] ?? "");
    $telefono = trim($_POST["telefono"] ?? "");
    $productoId = (int)($_POST["producto"] ?? 0);
    $cantidad = (int)($_POST["cantidad"] ?? 0);
    $disenoDescripcion = trim($_POST["diseno"] ?? "");
    $fechaEntrega = $_POST["fecha_entrega"] ?? "";
    $precioUnitario = (float)($_POST["precio_unitario"] ?? 0);
    $montoPago = (float)($_POST["monto"] ?? 0);
    $medioPagoId = (int)($_POST["medio_pago"] ?? 0);

    if (
        $nombre === "" ||
        $productoId <= 0 ||
        $cantidad <= 0 ||
        $fechaEntrega === "" ||
        $precioUnitario < 0 ||
        $montoPago <= 0 ||
        $medioPagoId <= 0
    ) {
        $mensaje = "Completá todos los campos obligatorios antes de registrar el pedido.";
        $tipoMensaje = "error";
    } else {

        $conn->begin_transaction();

        try {
            // 1. Cliente
            $stmt = $conn->prepare(
                "INSERT INTO cliente (nombre, telefono) VALUES (?, ?)"
            );
            $stmt->bind_param("ss", $nombre, $telefono);
            $stmt->execute();
            $idCliente = $conn->insert_id;
            $stmt->close();

            // 2. Estado inicial: Pendiente
            $stmt = $conn->prepare(
                "SELECT idEstado FROM estadopedido
                 WHERE descripcionEstado = 'Pendiente' LIMIT 1"
            );
            $stmt->execute();
            $resultadoEstado = $stmt->get_result();
            $estado = $resultadoEstado->fetch_assoc();
            $stmt->close();

            if (!$estado) {
                throw new Exception("No existe el estado inicial 'Pendiente' en la base de datos.");
            }

            $idEstado = (int)$estado["idEstado"];

            // 3. Usuario que registra el pedido.
            // Esta version todavía no tiene login: usamos el primer usuario activo. 
            $stmt = $conn->prepare(
                "SELECT idUsuario FROM usuario
                 WHERE estado = 1
                 ORDER BY idUsuario ASC LIMIT 1"
            );
            $stmt->execute();
            $resultadoUsuario = $stmt->get_result();
            $usuario = $resultadoUsuario->fetch_assoc();
            $stmt->close();

            if (!$usuario) {
                throw new Exception("No existe un usuario activo para registrar el pedido.");
            }

            $idUsuario = (int)$usuario["idUsuario"];

            // 4. Pedido
            $precioTotal = $precioUnitario * $cantidad;

            $stmt = $conn->prepare(
                "INSERT INTO pedido
                (fechaEntrega, precioTotal, idCliente, idEstado, idUsuario)
                VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->bind_param(
                "sdiii",
                $fechaEntrega,
                $precioTotal,
                $idCliente,
                $idEstado,
                $idUsuario
            );
            $stmt->execute();
            $idPedido = $conn->insert_id;
            $stmt->close();

            // 5. Diseño opcional según el nuevo modelo (Esto hay que probarlo).
            $idDiseno = null;

            if ($disenoDescripcion !== "") {
                $disenoNombre = "Diseño personalizado";
                $stmt = $conn->prepare(
                    "INSERT INTO diseño (nombre, descripcion, archivo)
                     VALUES (?, ?, NULL)"
                );
                $stmt->bind_param("ss", $disenoNombre, $disenoDescripcion);
                $stmt->execute();
                $idDiseno = $conn->insert_id;
                $stmt->close();
            }

            // 6. Detalle del pedido
            if ($idDiseno === null) {
                $stmt = $conn->prepare(
                    "INSERT INTO detallepedido
                    (cantidad, precioUnitario, idPedido, idProducto, idDiseño)
                    VALUES (?, ?, ?, ?, NULL)"
                );
                $stmt->bind_param(
                    "idii",
                    $cantidad,
                    $precioUnitario,
                    $idPedido,
                    $productoId
                );
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO detallepedido
                    (cantidad, precioUnitario, idPedido, idProducto, idDiseño)
                    VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->bind_param(
                    "idiii",
                    $cantidad,
                    $precioUnitario,
                    $idPedido,
                    $productoId,
                    $idDiseno
                );
            }
            $stmt->execute();
            $stmt->close();

            // 7. Pago / seña inicial.
            // fechaHoraPago usa el valor DEFAULT CURRENT_TIMESTAMP de la BD.
            // El comprobante queda NULL hasta una futura version
            $stmt = $conn->prepare(
                "INSERT INTO pago
                (monto, idPedido, idMedioPago)
                VALUES (?, ?, ?)"
            );
            $stmt->bind_param("dii", $montoPago, $idPedido, $medioPagoId);
            $stmt->execute();
            $stmt->close();

            $conn->commit();

            $mensaje = "Pedido registrado correctamente. ID del pedido: #" . $idPedido;
            $tipoMensaje = "exito";

        } catch (Exception $e) {
            $conn->rollback();
            $mensaje = "No se pudo registrar el pedido: " . $e->getMessage();
            $tipoMensaje = "error";
        }
    }
}

// Catálogo real de productos
$productos = [];
$resultadoProductos = $conn->query(
    "SELECT idProducto, nombre, descripcion, precio
     FROM producto
     ORDER BY nombre ASC, idProducto ASC"
);

if ($resultadoProductos) {
    while ($producto = $resultadoProductos->fetch_assoc()) {
        $productos[] = $producto;
    }
}

// Medios de pago reales de la base de datos
$mediosPago = [];
$resultadoMedios = $conn->query(
    "SELECT idMedioPago, nombre
     FROM mediopago
     ORDER BY idMedioPago ASC"
);

if ($resultadoMedios) {
    while ($medio = $resultadoMedios->fetch_assoc()) {
        $mediosPago[] = $medio;
    }
}

// Vista solicitada desde el menú.
$vistaSolicitada = $_GET["vista"] ?? $_POST["vista"] ?? "nuevo";
$vista = $vistaSolicitada === "pedidos" ? "pedidos" : "nuevo";

// Pedidos reales con cliente, estado y productos asociados.
$pedidos = [];
$estados = [];
if ($vista === "pedidos") {
    $resultadoEstados = $conn->query(
        "SELECT idEstado, descripcionEstado FROM estadopedido ORDER BY idEstado ASC"
    );
    if ($resultadoEstados) {
        while ($estadoFila = $resultadoEstados->fetch_assoc()) {
            $estados[] = $estadoFila;
        }
    }

    $sqlPedidos = "
        SELECT
            p.idPedido,
            p.idEstado,
            p.fechaEntrega,
            p.precioTotal,
            c.nombre AS cliente,
            c.telefono,
            ep.descripcionEstado AS estado,
            GROUP_CONCAT(
                CONCAT(pr.nombre, ' (', dp.cantidad, ')')
                ORDER BY pr.nombre SEPARATOR '||'
            ) AS productos,
            GROUP_CONCAT(
                CONCAT(
                    pr.nombre, '||',
                    dp.cantidad, '||',
                    dp.precioUnitario, '||',
                    COALESCE(d.descripcion, '')
                )
                ORDER BY pr.nombre SEPARATOR '##'
            ) AS detalle_productos
        FROM pedido p
        INNER JOIN cliente c ON c.idCliente = p.idCliente
        LEFT JOIN estadopedido ep ON ep.idEstado = p.idEstado
        LEFT JOIN detallepedido dp ON dp.idPedido = p.idPedido
        LEFT JOIN producto pr ON pr.idProducto = dp.idProducto
        LEFT JOIN diseño d ON d.idDiseño = dp.idDiseño
        GROUP BY p.idPedido, p.idEstado, p.fechaEntrega, p.precioTotal,
                 c.nombre, c.telefono, ep.descripcionEstado
        ORDER BY p.idPedido DESC
    ";
    $resultadoPedidos = $conn->query($sqlPedidos);
    if ($resultadoPedidos) {
        while ($pedido = $resultadoPedidos->fetch_assoc()) {
            $pedidos[] = $pedido;
        }
    }
}
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

    <aside class="sidebar">
        <div class="logo">
            <strong>Kapykuá</strong>
            <span>Librería Gráfica</span>
        </div>

        <nav class="menu">
            <a href="?vista=nuevo" class="menu-item <?php echo $vista === "nuevo" ? "activo" : ""; ?>" id="menuNuevoPedido">
                <span class="icono">＋</span>
                <span>Nuevo<br>Pedido</span>
            </a>

            <a href="?vista=pedidos" class="menu-item <?php echo $vista === "pedidos" ? "activo" : ""; ?>" id="menuPedidos">
                <span class="icono">□</span>
                <span>Pedidos</span>
            </a>

            <a href="#" class="menu-item" aria-disabled="true" title="Sección pendiente de desarrollo">
                <span class="icono">♧</span>
                <span>Notificar</span>
            </a>
        </nav>
    </aside>

    <div class="principal">

        <header class="barra-superior">
            <span class="ajustes">⚙</span>
        </header>

        <main class="contenido">

            <?php if ($mensaje !== ""): ?>
                <div class="mensaje <?php echo $tipoMensaje; ?>">
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php endif; ?>

            <?php if ($vista === "nuevo"): ?>
            <form method="POST" id="pedidoForm">

                <div class="pasos">

                    <!-- PASO 1 -->
                    <section class="paso abierto" data-paso="1">
                        <div class="titulo-paso">
                            <span class="numero">1</span>
                            <h2>DATOS DEL CLIENTE</h2>
                            <button type="button" class="flecha" data-abrir="1">&gt;</button>
                        </div>

                        <div class="contenido-oculto">
                            <div class="campo">
                                <label for="nombre">Nombre *</label>
                                <input type="text" id="nombre" name="nombre" maxlength="20" required>
                            </div>

                            <div class="campo">
                                <label for="telefono">Teléfono</label>
                                <input type="tel" id="telefono" name="telefono" maxlength="20">
                            </div>

                            <div class="botonera">
                                <button type="button" class="btn-siguiente" data-siguiente="2">
                                    Siguiente &gt;
                                </button>
                            </div>
                        </div>
                    </section>

                    <!-- PASO 2 -->
                    <section class="paso cerrado" data-paso="2">
                        <div class="titulo-paso">
                            <span class="numero">2</span>
                            <h2>INFORMACIÓN DEL PEDIDO</h2>
                            <button type="button" class="flecha" data-abrir="2">&gt;</button>
                        </div>

                        <div class="contenido-oculto">

                            <div class="campo">
                                <label for="producto">Producto *</label>
                                <select id="producto" name="producto" required>
                                    <option value="">Seleccione un producto</option>

                                    <?php foreach ($productos as $producto): ?>
                                        <option
                                            value="<?php echo (int)$producto["idProducto"]; ?>"
                                            data-precio="<?php echo htmlspecialchars($producto["precio"] ?? ""); ?>"
                                        >
                                            <?php echo htmlspecialchars($producto["nombre"]); ?>
                                            <?php if (!empty($producto["descripcion"])): ?>
                                                - <?php echo htmlspecialchars($producto["descripcion"]); ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>

                                </select>
                            </div>

                            <div class="fila">
                                <div class="campo">
                                    <label for="cantidad">Cantidad *</label>
                                    <input
                                        type="number"
                                        id="cantidad"
                                        name="cantidad"
                                        min="1"
                                        value="1"
                                        required
                                    >
                                </div>

                                <div class="campo">
                                    <label for="precio_unitario">Precio unitario *</label>
                                    <input
                                        type="number"
                                        id="precio_unitario"
                                        name="precio_unitario"
                                        min="0"
                                        step="0.01"
                                        placeholder="0.00"
                                        required
                                    >
                                </div>
                            </div>

                            <div class="campo">
                                <label for="diseno">Diseño solicitado</label>
                                <textarea id="diseno" name="diseno" rows="4" maxlength="150" placeholder="Describí el diseño que solicita el cliente..."></textarea>
                            </div>

                            <div class="nota-beta">
                                La funcion para subir el archivo del diseño y observaciones especiales lo tengo que implementar.
                            </div>

                            <div class="botonera">
                                <button type="button" class="btn-atras" data-anterior="1">
                                    &lt; Atrás
                                </button>
                                <button type="button" class="btn-siguiente" data-siguiente="3">
                                    Siguiente &gt;
                                </button>
                            </div>
                        </div>
                    </section>

                    <!-- PASO 3 -->
                    <section class="paso cerrado" data-paso="3">
                        <div class="titulo-paso">
                            <span class="numero">3</span>
                            <h2>FECHA DE ENTREGA</h2>
                            <button type="button" class="flecha" data-abrir="3">&gt;</button>
                        </div>

                        <div class="contenido-oculto">
                            <div class="campo">
                                <label for="fecha_entrega">Fecha *</label>
                                <input type="date" id="fecha_entrega" name="fecha_entrega" required>
                            </div>

                            <div class="botonera">
                                <button type="button" class="btn-atras" data-anterior="2">
                                    &lt; Atrás
                                </button>
                                <button type="button" class="btn-siguiente" data-siguiente="4">
                                    Siguiente &gt;
                                </button>
                            </div>
                        </div>
                    </section>

                    <!-- PASO 4 -->
                    <section class="paso cerrado" data-paso="4">
                        <div class="titulo-paso">
                            <span class="numero">4</span>
                            <h2>PAGO</h2>
                            <button type="button" class="flecha" data-abrir="4">&gt;</button>
                        </div>

                        <div class="contenido-oculto">

                            <div class="resumen">
                                <div>
                                    <span>Precio total</span>
                                    <strong id="totalVisual">$ 0,00</strong>
                                </div>
                            </div>

                            <div class="fila">
                                <div class="campo">
                                    <label for="monto">Monto de la seña *</label>
                                    <input
                                        type="number"
                                        id="monto"
                                        name="monto"
                                        min="0.01"
                                        step="0.01"
                                        placeholder="0.00"
                                        required
                                    >
                                </div>

                                <div class="campo">
                                    <label for="medio_pago">Medio de pago *</label>
                                    <select id="medio_pago" name="medio_pago" required>
                                        <option value="">Seleccione un medio</option>
                                        <?php foreach ($mediosPago as $medio): ?>
                                            <option value="<?php echo (int)$medio["idMedioPago"]; ?>">
                                                <?php echo htmlspecialchars($medio["nombre"]); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="botonera">
                                <button type="button" class="btn-atras" data-anterior="3">
                                    &lt; Atrás
                                </button>
                                <button type="submit" class="btn-registrar">
                                    Registrar pedido
                                </button>
                            </div>

                        </div>
                    </section>

                </div>

            </form>
            <?php else: ?>

            <section class="seguimiento">
                <h1>Seguimiento de Pedidos</h1>

                <?php if (count($pedidos) === 0): ?>
                    <div class="sin-pedidos">
                        <h2>Todavía no hay pedidos registrados</h2>
                        <p>Cuando registres un pedido desde «Nuevo Pedido», aparecerá en esta sección.</p>
                        <a class="btn-registrar enlace-boton" href="?vista=nuevo">Registrar un pedido</a>
                    </div>
                <?php else: ?>
                    <div class="resumen-pedidos">
                        <span><?php echo count($pedidos); ?> pedido(s) registrado(s)</span>
                        <a href="?vista=nuevo" class="btn-registrar enlace-boton">＋ Nuevo pedido</a>
                    </div>

                    <div class="grilla-pedidos">
                        <?php foreach ($pedidos as $pedido): ?>
                            <?php
                                $estadoTexto = $pedido["estado"] ?: "Sin estado";
                                $estadoClase = strtolower(trim($estadoTexto));
                                $estadoClase = strtr($estadoClase, [
                                    'á' => 'a', 'é' => 'e', 'í' => 'i',
                                    'ó' => 'o', 'ú' => 'u', 'ñ' => 'n'
                                ]);
                                $estadoClase = preg_replace('/[^a-z0-9]+/i', '-', $estadoClase);
                                $productosLista = $pedido["productos"] ? explode("||", $pedido["productos"]) : [];
                                $detallesLista = $pedido["detalle_productos"] ? explode("##", $pedido["detalle_productos"]) : [];
                            ?>
                            <article class="tarjeta-pedido">
                                <div class="cabecera-tarjeta">
                                    <h2>#PED-<?php echo str_pad((string)$pedido["idPedido"], 5, "0", STR_PAD_LEFT); ?></h2>
                                    <span class="estado estado-<?php echo htmlspecialchars($estadoClase); ?>">
                                        <?php echo htmlspecialchars($estadoTexto); ?>
                                    </span>
                                </div>

                                <form method="POST" class="form-estado">
                                    <input type="hidden" name="accion" value="actualizar_estado">
                                    <input type="hidden" name="vista" value="pedidos">
                                    <input type="hidden" name="idPedido" value="<?php echo (int)$pedido["idPedido"]; ?>">
                                    <label for="estado-<?php echo (int)$pedido["idPedido"]; ?>">Cambiar estado:</label>
                                    <select
                                        id="estado-<?php echo (int)$pedido["idPedido"]; ?>"
                                        name="idEstado"
                                        aria-label="Nuevo estado del pedido"
                                    >
                                        <?php foreach ($estados as $estadoOpcion): ?>
                                            <option
                                                value="<?php echo (int)$estadoOpcion["idEstado"]; ?>"
                                                <?php echo (int)$estadoOpcion["idEstado"] === (int)$pedido["idEstado"] ? "selected" : ""; ?>
                                            >
                                                <?php echo htmlspecialchars($estadoOpcion["descripcionEstado"]); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn-guardar-estado">Guardar</button>
                                </form>

                                <p class="cliente-pedido">Cliente: <strong><?php echo htmlspecialchars($pedido["cliente"]); ?></strong></p>
                                <p><strong>Fecha de entrega:</strong>
                                    <?php echo $pedido["fechaEntrega"] ? date("d/m/Y", strtotime($pedido["fechaEntrega"])) : "No indicada"; ?>
                                </p>

                                <div class="productos-pedido">
                                    <strong>Productos:</strong>
                                    <?php if ($productosLista): ?>
                                        <ul>
                                            <?php foreach ($productosLista as $productoTexto): ?>
                                                <li><?php echo htmlspecialchars($productoTexto); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <span>Sin productos asociados</span>
                                    <?php endif; ?>
                                </div>

                                <div class="pie-tarjeta">
                                    <span class="total-pedido">Total: $ <?php echo number_format((float)$pedido["precioTotal"], 2, ",", "."); ?></span>
                                    <button type="button" class="btn-detalles" aria-expanded="false">Ver Detalles</button>
                                </div>

                                <div class="detalle-expandido" hidden>
                                    <p><strong>Número de pedido:</strong> #PED-<?php echo str_pad((string)$pedido["idPedido"], 5, "0", STR_PAD_LEFT); ?></p>
                                    <p><strong>Cliente:</strong> <?php echo htmlspecialchars($pedido["cliente"]); ?></p>
                                    <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($pedido["telefono"] ?: "No informado"); ?></p>
                                    <p><strong>Estado:</strong> <?php echo htmlspecialchars($estadoTexto); ?></p>
                                    <p><strong>Total:</strong> $ <?php echo number_format((float)$pedido["precioTotal"], 2, ",", "."); ?></p>
                                    <strong>Detalle:</strong>
                                    <?php foreach ($detallesLista as $detalleTexto): ?>
                                        <?php $partes = explode("||", $detalleTexto, 4); ?>
                                        <div class="linea-detalle">
                                            <strong><?php echo htmlspecialchars($partes[0] ?? "Producto"); ?></strong>
                                            <span>Cantidad: <?php echo htmlspecialchars($partes[1] ?? "0"); ?></span>
                                            <span>Precio unitario: $ <?php echo number_format((float)($partes[2] ?? 0), 2, ",", "."); ?></span>
                                            <?php if (!empty($partes[3])): ?>
                                                <span>Diseño: <?php echo htmlspecialchars($partes[3]); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <?php endif; ?>

        </main>
    </div>
</div>

<script>
    document.querySelectorAll(".btn-detalles").forEach(function(boton) {
        boton.addEventListener("click", function() {
            const detalle = boton.closest(".tarjeta-pedido").querySelector(".detalle-expandido");
            const estaAbierto = !detalle.hidden;
            detalle.hidden = estaAbierto;
            boton.textContent = estaAbierto ? "Ver Detalles" : "Ocultar Detalles";
            boton.setAttribute("aria-expanded", String(!estaAbierto));
        });
    });

    function mostrarPaso(numeroPaso) {
        document.querySelectorAll(".paso").forEach(function(paso) {
            const seleccionado = paso.dataset.paso === String(numeroPaso);

            paso.classList.toggle("abierto", seleccionado);
            paso.classList.toggle("cerrado", !seleccionado);
        });

        const seleccionado = document.querySelector(
            '.paso[data-paso="' + numeroPaso + '"]'
        );

        if (seleccionado) {
            seleccionado.scrollIntoView({
                behavior: "smooth",
                block: "start"
            });
        }
    }

    document.querySelectorAll(".btn-siguiente").forEach(function(boton) {
        boton.addEventListener("click", function() {
            const actual = boton.closest(".paso");
            const campos = actual.querySelectorAll("input, select, textarea");

            for (const campo of campos) {
                if (!campo.checkValidity()) {
                    campo.reportValidity();
                    return;
                }
            }

            mostrarPaso(boton.dataset.siguiente);
        });
    });

    document.querySelectorAll(".btn-atras").forEach(function(boton) {
        boton.addEventListener("click", function() {
            mostrarPaso(boton.dataset.anterior);
        });
    });

    document.querySelectorAll(".flecha").forEach(function(boton) {
        boton.addEventListener("click", function() {
            mostrarPaso(boton.dataset.abrir);
        });
    });

    const menuNuevoPedido = document.getElementById("menuNuevoPedido");
    if (menuNuevoPedido) {
        menuNuevoPedido.addEventListener("click", function(evento) {
            evento.preventDefault();
            mostrarPaso(1);
        });
    }

    const producto = document.getElementById("producto");
    const cantidad = document.getElementById("cantidad");
    const precioUnitario = document.getElementById("precio_unitario");
    const totalVisual = document.getElementById("totalVisual");

    function actualizarTotal() {
        const cantidadValor = parseFloat(cantidad.value) || 0;
        const precioValor = parseFloat(precioUnitario.value) || 0;
        const total = cantidadValor * precioValor;

        totalVisual.textContent = "$ " + total.toLocaleString("es-AR", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    if (producto && cantidad && precioUnitario && totalVisual) {
    producto.addEventListener("change", function() {
        const opcion = producto.options[producto.selectedIndex];
        const precio = opcion.dataset.precio || "";

        if (precio !== "") {
            precioUnitario.value = precio;
        }

        actualizarTotal();
    });

    cantidad.addEventListener("input", actualizarTotal);
    precioUnitario.addEventListener("input", actualizarTotal);

    document.getElementById("pedidoForm").addEventListener("submit", function(evento) {
        const monto = parseFloat(document.getElementById("monto").value) || 0;
        const total = (parseFloat(cantidad.value) || 0) *
                      (parseFloat(precioUnitario.value) || 0);

        if (monto > total) {
            evento.preventDefault();
            alert("La seña no puede ser mayor que el precio total del pedido.");
        }
    });
    }
</script>

</body>
</html>
