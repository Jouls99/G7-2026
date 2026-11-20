<?php
session_start();
require_once "conexion.php";
if (empty($_SESSION["idUsuario"])) {
    // Primera ejecución: si todavía no existe ningún ADMIN, iniciar el asistente local.
    $estadoAdmin = $conn->query("SELECT COUNT(*) AS total FROM usuario WHERE rol='ADMIN'");
    $totalAdmin = $estadoAdmin ? (int)$estadoAdmin->fetch_assoc()["total"] : 0;
    if ($totalAdmin === 0) {
        header("Location: configurar_admin.php");
        exit;
    }
    $errorLogin = "";
    if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["accion"] ?? "") === "login") {
        $email = trim($_POST["email"] ?? ""); $clave = $_POST["clave"] ?? "";
        $st = $conn->prepare("SELECT idUsuario,nombre,email,passwordHash,rol,estado FROM usuario WHERE email=? LIMIT 1");
        $st->bind_param("s", $email); $st->execute(); $u = $st->get_result()->fetch_assoc(); $st->close();
        if ($u && (int)$u["estado"] === 1 && password_verify($clave, $u["passwordHash"])) {
            session_regenerate_id(true); $_SESSION["idUsuario"]=(int)$u["idUsuario"]; $_SESSION["nombre"]=$u["nombre"]; $_SESSION["rol"]=$u["rol"];
            header("Location: index.php"); exit;
        } else { $errorLogin = "Correo o contraseña incorrectos, o cuenta inactiva."; }
    }
    ?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Acceso a la Cuenta - Kapykuá</title><link rel="stylesheet" href="css/style.css"></head><body class="auth-page"><header class="auth-banner">Acceso a la Cuenta</header><form class="auth-card" method="post"><h1>Iniciar Sesión</h1><p class="auth-intro">Ingresá tus datos para continuar</p><?php if($errorLogin): ?><p class="auth-error"><?php echo htmlspecialchars($errorLogin); ?></p><?php endif; ?><input type="hidden" name="accion" value="login"><label>Correo Electrónico de Usuario</label><input type="email" name="email" required autocomplete="username"><label>Contraseña:</label><input type="password" name="clave" required autocomplete="current-password"><div class="auth-links"><a href="recuperar.php">¿Olvidaste tu contraseña?</a></div><button class="auth-submit">Ingresar</button></form></body></html><?php exit;
}
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["accion"] ?? "") === "logout") { $_SESSION=[]; session_destroy(); header("Location: index.php"); exit; }


$mensaje = "";
$tipoMensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["accion"] ?? "") === "actualizar_estado") {
    $idPedidoActualizar = (int)($_POST["idPedido"] ?? 0);
    $idEstadoNuevo = (int)($_POST["idEstado"] ?? 0);
    $motivoCancelacion = trim($_POST["motivoCancelacion"] ?? "");
    if ($idPedidoActualizar <= 0 || !in_array($idEstadoNuevo, [1, 2, 3, 4, 6], true) || ($_POST["vista"] ?? "") !== "pedidos") {
        $mensaje = "No se pudo actualizar el estado: datos inválidos o acción fuera de la pantalla Pedidos."; $tipoMensaje = "error";
    } elseif ($idEstadoNuevo === 6 && $motivoCancelacion === "") {
        $mensaje = "Para cancelar un pedido, ingresá el motivo."; $tipoMensaje = "error";
    } else {
        $stmt = $conn->prepare("UPDATE pedido SET idEstado = ?, motivoCancelacion = CASE WHEN ? = 6 THEN ? ELSE motivoCancelacion END WHERE idPedido = ?");
        $stmt->bind_param("iisi", $idEstadoNuevo, $idEstadoNuevo, $motivoCancelacion, $idPedidoActualizar);
        if ($stmt->execute()) { $mensaje = "Estado del pedido actualizado correctamente."; $tipoMensaje = "exito"; }
        else { $mensaje = "No se pudo actualizar el estado del pedido: " . $stmt->error; $tipoMensaje = "error"; }
        $stmt->close();
    }

 } elseif ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["accion"] ?? "") === "subir_archivo_pedido") {
    $idPedidoArchivo = (int)($_POST["idPedido"] ?? 0);
    $idDetalleArchivo = (int)($_POST["idDetalle"] ?? 0);
    try {
        if ($idPedidoArchivo <= 0 || $idDetalleArchivo <= 0 || empty($_FILES["archivo_pedido"])) {
            throw new Exception("Seleccioná un archivo válido para el producto.");
        }
        $stmt = $conn->prepare("SELECT ep.descripcionEstado AS estado FROM pedido p INNER JOIN estadopedido ep ON ep.idEstado = p.idEstado INNER JOIN detallepedido dp ON dp.idPedido = p.idPedido WHERE p.idPedido = ? AND dp.idDetalle = ? LIMIT 1");
        $stmt->bind_param("ii", $idPedidoArchivo, $idDetalleArchivo);
        $stmt->execute();
        $pedidoArchivo = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$pedidoArchivo) throw new Exception("El producto no pertenece al pedido indicado.");
        if (in_array(mb_strtolower($pedidoArchivo["estado"], "UTF-8"), ["entregado", "cancelado"], true)) {
            throw new Exception("No se pueden modificar los archivos de pedidos entregados o cancelados.");
        }
        $archivo = $_FILES["archivo_pedido"];
        if ($archivo["error"] !== UPLOAD_ERR_OK) throw new Exception("No se pudo recibir el archivo.");
        $nombreOriginal = basename($archivo["name"]);
        $tam = (int)$archivo["size"];
        $ext = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        $permitidas = ["jpg", "jpeg", "png", "webp", "pdf"];
        if (!in_array($ext, $permitidas, true) || $tam <= 0 || $tam > 8 * 1024 * 1024) {
            throw new Exception("El archivo debe ser JPG, PNG, WEBP o PDF y no superar 8 MB.");
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($archivo["tmp_name"]);
        $mimes = ["image/jpeg", "image/png", "image/webp", "application/pdf"];
        if (!in_array($mime, $mimes, true)) throw new Exception("El tipo real del archivo no es válido.");
        $directorio = __DIR__ . "/uploads/disenos/PED-" . $idPedidoArchivo . "/detalle-" . $idDetalleArchivo;
        if (!is_dir($directorio) && !mkdir($directorio, 0755, true)) throw new Exception("No se pudo preparar la carpeta de archivos.");
        $nombreAlmacenado = bin2hex(random_bytes(16)) . "." . $ext;
        $rutaFisica = $directorio . "/" . $nombreAlmacenado;
        if (!move_uploaded_file($archivo["tmp_name"], $rutaFisica)) throw new Exception("No se pudo guardar el archivo.");
        $rutaRelativa = "uploads/disenos/PED-" . $idPedidoArchivo . "/detalle-" . $idDetalleArchivo . "/" . $nombreAlmacenado;
        $idUsuarioCarga = (int)$_SESSION["idUsuario"];
        $stmt = $conn->prepare("INSERT INTO archivopedido (idDetalle, nombreOriginal, nombreAlmacenado, ruta, tipoMime, tamano, idUsuario) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssii", $idDetalleArchivo, $nombreOriginal, $nombreAlmacenado, $rutaRelativa, $mime, $tam, $idUsuarioCarga);
        $stmt->execute();
        $stmt->close();
        $mensaje = "Archivo agregado al producto correctamente.";
        $tipoMensaje = "exito";
    } catch (Exception $e) {
        $mensaje = $e->getMessage();
        $tipoMensaje = "error";
    }

} elseif ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["accion"] ?? "") === "reactivar_pedido") {
    $idPedidoReactivar = (int)($_POST["idPedido"] ?? 0);
    if ($idPedidoReactivar <= 0 || ($_POST["vista"] ?? "") !== "historial") {
        $mensaje = "No se pudo reactivar el pedido: datos inválidos.";
        $tipoMensaje = "error";
    } else {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("SELECT idEstado FROM estadopedido WHERE LOWER(descripcionEstado) = 'pendiente' LIMIT 1");
            $stmt->execute();
            $estadoPendiente = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$estadoPendiente) throw new Exception("No existe el estado Pendiente en la base de datos.");
            $idEstadoPendiente = (int)$estadoPendiente["idEstado"];
            $stmt = $conn->prepare("UPDATE pedido SET idEstado = ?, motivoCancelacion = NULL WHERE idPedido = ? AND idEstado = (SELECT idEstado FROM estadopedido WHERE LOWER(descripcionEstado) = 'cancelado' LIMIT 1)");
            $stmt->bind_param("ii", $idEstadoPendiente, $idPedidoReactivar);
            $stmt->execute();
            $afectadas = $stmt->affected_rows;
            $stmt->close();
            if ($afectadas !== 1) throw new Exception("El pedido no está cancelado o ya fue modificado.");
            $conn->commit();
            $mensaje = "Pedido reactivado correctamente. Volvió a Pendiente.";
            $tipoMensaje = "exito";
        } catch (Exception $e) {
            $conn->rollback();
            $mensaje = $e->getMessage();
            $tipoMensaje = "error";
        }
    }

} elseif ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["accion"] ?? "") === "actualizar_pagado") {
    // En Notificar se registra únicamente el importe recibido en este nuevo cobro.
    // Los pagos anteriores (por ejemplo, la seña) se conservan y se suman al mostrar el total abonado.
    $idPedidoPago = (int)($_POST["idPedido"] ?? 0);
    $nuevoPago = (float)($_POST["nuevo_pago"] ?? 0);
    $medioPagoNuevo = (int)($_POST["medio_pago_pago"] ?? 0);

    if ($idPedidoPago <= 0 || $nuevoPago <= 0 || $medioPagoNuevo <= 0) {
        $mensaje = "Ingresá un importe de pago válido y seleccioná el medio de pago.";
        $tipoMensaje = "error";
    } else {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare(
                "SELECT p.precioTotal, ep.descripcionEstado AS estado
                 FROM pedido p
                 INNER JOIN estadopedido ep ON ep.idEstado = p.idEstado
                 WHERE p.idPedido = ?
                 FOR UPDATE"
            );
            $stmt->bind_param("i", $idPedidoPago);
            $stmt->execute();
            $datosPedidoPago = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$datosPedidoPago || mb_strtolower($datosPedidoPago["estado"], "UTF-8") !== "terminado") {
                throw new Exception("Solo se puede completar el pago de pedidos terminados pendientes de entrega.");
            }

            $stmt = $conn->prepare(
                "SELECT COALESCE(SUM(monto), 0) AS pagado FROM pago WHERE idPedido = ?"
            );
            $stmt->bind_param("i", $idPedidoPago);
            $stmt->execute();
            $pagadoActual = (float)$stmt->get_result()->fetch_assoc()["pagado"];
            $stmt->close();

            $totalPedido = (float)$datosPedidoPago["precioTotal"];
            $saldoPendiente = round($totalPedido - $pagadoActual, 2);
            $nuevoPago = round($nuevoPago, 2);

            if ($saldoPendiente <= 0.001) {
                throw new Exception("El pedido ya está completamente pagado.");
            }
            if ($nuevoPago > $saldoPendiente + 0.001) {
                throw new Exception("El nuevo pago no puede superar el saldo pendiente de $ " . number_format($saldoPendiente, 2, ",", ".") . ".");
            }

            $stmt = $conn->prepare(
                "INSERT INTO pago (monto, idPedido, idMedioPago) VALUES (?, ?, ?)"
            );
            $stmt->bind_param("dii", $nuevoPago, $idPedidoPago, $medioPagoNuevo);
            $stmt->execute();
            $stmt->close();
            $conn->commit();

            $mensaje = "Pago registrado correctamente: $ " . number_format($nuevoPago, 2, ",", ".") . ".";
            $tipoMensaje = "exito";
        } catch (Exception $e) {
            $conn->rollback();
            $mensaje = $e->getMessage();
            $tipoMensaje = "error";
        }
    }

} elseif ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["accion"] ?? "") === "confirmar_entrega") {
    // En esta pantalla solo se permite la transición Terminado -> Entregado.
    $idPedidoEntrega = (int)($_POST["idPedido"] ?? 0);

    if ($idPedidoEntrega <= 0) {
        $mensaje = "No se pudo confirmar la entrega: pedido inválido.";
        $tipoMensaje = "error";
    } else {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare(
                "SELECT p.precioTotal, ep.descripcionEstado AS estado
                 FROM pedido p
                 INNER JOIN estadopedido ep ON ep.idEstado = p.idEstado
                 WHERE p.idPedido = ?
                 FOR UPDATE"
            );
            $stmt->bind_param("i", $idPedidoEntrega);
            $stmt->execute();
            $datosEntrega = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$datosEntrega || mb_strtolower($datosEntrega["estado"], "UTF-8") !== "terminado") {
                throw new Exception("El pedido ya no está pendiente de entrega.");
            }

            $stmt = $conn->prepare(
                "SELECT COALESCE(SUM(monto), 0) AS pagado FROM pago WHERE idPedido = ?"
            );
            $stmt->bind_param("i", $idPedidoEntrega);
            $stmt->execute();
            $totalPagadoEntrega = (float)$stmt->get_result()->fetch_assoc()["pagado"];
            $stmt->close();

            $totalPedidoEntrega = (float)$datosEntrega["precioTotal"];
            if ($totalPagadoEntrega + 0.001 < $totalPedidoEntrega) {
                $saldoEntrega = $totalPedidoEntrega - $totalPagadoEntrega;
                throw new Exception("No se puede confirmar la entrega: todavía queda un saldo de $ " . number_format($saldoEntrega, 2, ",", ".") . ". Registrá primero el pago pendiente.");
            }

            $stmt = $conn->prepare(
                "SELECT idEstado FROM estadopedido WHERE LOWER(descripcionEstado) = 'entregado' LIMIT 1"
            );
            $stmt->execute();
            $estadoEntregado = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$estadoEntregado) {
                throw new Exception("No existe el estado 'Entregado' en la base de datos.");
            }

            $idEstadoEntregado = (int)$estadoEntregado["idEstado"];
            $stmt = $conn->prepare(
                "UPDATE pedido SET idEstado = ? WHERE idPedido = ?"
            );
            $stmt->bind_param("ii", $idEstadoEntregado, $idPedidoEntrega);
            $stmt->execute();
            $stmt->close();
            $conn->commit();

            $mensaje = "Entrega del pedido #" . $idPedidoEntrega . " confirmada correctamente.";
            $tipoMensaje = "exito";
        } catch (Exception $e) {
            $conn->rollback();
            $mensaje = $e->getMessage();
            $tipoMensaje = "error";
        }
    }

} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre = trim($_POST["nombre"] ?? "");
    $telefono = trim($_POST["telefono"] ?? "");
    $productosPedido = $_POST["producto"] ?? [];
    $cantidadesPedido = $_POST["cantidad"] ?? [];
    $preciosAcordados = $_POST["precio_acordado"] ?? [];
    $disenosPedido = $_POST["diseno"] ?? [];
    $observacionesPedido = $_POST["observaciones"] ?? [];
    $fechaEntrega = $_POST["fecha_entrega"] ?? "";
    $montoPago = (float)($_POST["monto"] ?? 0);
    $medioPagoId = (int)($_POST["medio_pago"] ?? 0);

    $detalles = [];
    foreach ($productosPedido as $i => $productoValor) {
        $productoId = (int)$productoValor;
        $cantidad = (int)($cantidadesPedido[$i] ?? 0);
        $precioAcordado = round((float)($preciosAcordados[$i] ?? 0), 2);
        $diseno = trim($disenosPedido[$i] ?? "");
        $observaciones = trim($observacionesPedido[$i] ?? "");
        if ($productoId > 0 && $cantidad > 0 && $precioAcordado >= 0) {
            $detalles[] = ["producto"=>$productoId, "cantidad"=>$cantidad, "precio"=>$precioAcordado, "diseno"=>$diseno, "observaciones"=>$observaciones];
        }
    }
    $precioTotal = round(array_sum(array_column($detalles, "precio")), 2);

    if ($nombre === "" || count($detalles) === 0 || $fechaEntrega === "" || $montoPago <= 0 || $medioPagoId <= 0) {
        $mensaje = "Completá los datos del cliente, al menos un producto, la fecha y el pago inicial.";
        $tipoMensaje = "error";
    } elseif ($montoPago > $precioTotal) {
        $mensaje = "La seña no puede ser mayor que el precio total del pedido.";
        $tipoMensaje = "error";
    } else {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO cliente (nombre, telefono) VALUES (?, ?)");
            $stmt->bind_param("ss", $nombre, $telefono); $stmt->execute();
            $idCliente = $conn->insert_id; $stmt->close();

            $stmt = $conn->prepare("SELECT idEstado FROM estadopedido WHERE descripcionEstado = 'Pendiente' LIMIT 1");
            $stmt->execute(); $estado = $stmt->get_result()->fetch_assoc(); $stmt->close();
            if (!$estado) throw new Exception("No existe el estado inicial 'Pendiente' en la base de datos.");

            $idEstado = (int)$estado["idEstado"];
            $idUsuario = (int)$_SESSION["idUsuario"];
            $stmt = $conn->prepare("INSERT INTO pedido (fechaEntrega, precioTotal, idCliente, idEstado, idUsuario) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sdiii", $fechaEntrega, $precioTotal, $idCliente, $idEstado, $idUsuario);
            $stmt->execute(); $idPedido = $conn->insert_id; $stmt->close();

            foreach ($detalles as $detalle) {
                $idDiseno = null;
                $descripcionCompleta = $detalle["diseno"];
                if ($detalle["observaciones"] !== "") {
                    $descripcionCompleta .= ($descripcionCompleta !== "" ? "\n\n" : "") . "Observaciones: " . $detalle["observaciones"];
                }
                        // La descripción queda asociada al diseño del detalle; los archivos se registran
                // independientemente en archivopedido para permitir múltiples adjuntos.
                if ($descripcionCompleta !== "") {
                    $nombreDiseno = "Diseño personalizado";
                    $archivoRelativo = null;
                    $stmt = $conn->prepare("INSERT INTO diseño (nombre, descripcion, archivo) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $nombreDiseno, $descripcionCompleta, $archivoRelativo);
                    $stmt->execute(); $idDiseno = $conn->insert_id; $stmt->close();
                }
                // precioUnitario conserva su significado histórico: total del detalle / cantidad.
                $precioUnitarioBD = $detalle["precio"] / $detalle["cantidad"];
                if ($idDiseno === null) {
                    $stmt = $conn->prepare("INSERT INTO detallepedido (cantidad, precioUnitario, idPedido, idProducto, idDiseño) VALUES (?, ?, ?, ?, NULL)");
                    $stmt->bind_param("idii", $detalle["cantidad"], $precioUnitarioBD, $idPedido, $detalle["producto"]);
                } else {
                    $stmt = $conn->prepare("INSERT INTO detallepedido (cantidad, precioUnitario, idPedido, idProducto, idDiseño) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("idiii", $detalle["cantidad"], $precioUnitarioBD, $idPedido, $detalle["producto"], $idDiseno);
                }
                $stmt->execute(); $idDetalle = $conn->insert_id; $stmt->close();


            }

            $stmt = $conn->prepare("INSERT INTO pago (monto, idPedido, idMedioPago) VALUES (?, ?, ?)");
            $stmt->bind_param("dii", $montoPago, $idPedido, $medioPagoId); $stmt->execute(); $stmt->close();
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
$vista = in_array($vistaSolicitada, ["nuevo", "pedidos", "notificar", "historial"], true)
    ? $vistaSolicitada
    : "nuevo";

function formatearFechaTarjeta(?string $fecha): string {
    if (!$fecha) {
        return "No indicada";
    }
    $ts = strtotime($fecha);
    if ($ts === false) {
        return "No indicada";
    }
    $meses = [
        1 => "Enero", 2 => "Febrero", 3 => "Marzo", 4 => "Abril",
        5 => "Mayo", 6 => "Junio", 7 => "Julio", 8 => "Agosto",
        9 => "Septiembre", 10 => "Octubre", 11 => "Noviembre", 12 => "Diciembre"
    ];
    $dia = (int)date("j", $ts);
    $mes = $meses[(int)date("n", $ts)] ?? "";
    $anio = date("Y", $ts);
    return $dia . " de " . $mes . ", " . $anio;
}

// Pedidos reales con cliente, estado y productos asociados.
$pedidos = [];
$estados = [];
if (in_array($vista, ["pedidos", "notificar", "historial"], true)) {
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
            p.motivoCancelacion,
            c.nombre AS cliente,
            c.telefono,
            ep.descripcionEstado AS estado,
            GROUP_CONCAT(
                CONCAT(pr.nombre, ' (', dp.cantidad, ')')
                ORDER BY pr.nombre SEPARATOR '||'
            ) AS productos,
            GROUP_CONCAT(
                CONCAT(
                    dp.idDetalle, '||',
                    COALESCE(dp.idDiseño, 0), '||',
                    pr.nombre, '||',
                    dp.cantidad, '||',
                    dp.precioUnitario, '||',
                    COALESCE(d.descripcion, ''), '||',
                    COALESCE(d.archivo, '')
                )
                ORDER BY pr.nombre SEPARATOR '##'
            ) AS detalle_productos
        FROM pedido p
        INNER JOIN cliente c ON c.idCliente = p.idCliente
        LEFT JOIN estadopedido ep ON ep.idEstado = p.idEstado
        LEFT JOIN detallepedido dp ON dp.idPedido = p.idPedido
        LEFT JOIN producto pr ON pr.idProducto = dp.idProducto
        LEFT JOIN diseño d ON d.idDiseño = dp.idDiseño
        WHERE " . (
            $vista === "historial"
                ? "LOWER(ep.descripcionEstado) IN ('entregado','cancelado')"
                : ($vista === "pedidos"
                    ? "LOWER(ep.descripcionEstado) NOT IN ('terminado','entregado','cancelado')"
                    : "1 = 0")
        ) . "
        GROUP BY p.idPedido, p.idEstado, p.fechaEntrega, p.precioTotal, p.motivoCancelacion,
                 c.nombre, c.telefono, ep.descripcionEstado
        ORDER BY " . (
            $vista === "pedidos"
                ? "p.fechaEntrega IS NULL ASC, p.fechaEntrega ASC, p.idPedido ASC"
                : "p.idPedido DESC"
        ) . "
    ";
    $resultadoPedidos = $conn->query($sqlPedidos);
    if ($resultadoPedidos) {
        while ($pedido = $resultadoPedidos->fetch_assoc()) {
            $pedidos[] = $pedido;
        }
    }
}

// Archivos asociados a los detalles de los pedidos visibles (Pedidos e Historial).
$archivosPorDetalle = [];
if (count($pedidos) > 0) {
    $idsPedidosVisibles = array_values(array_unique(array_map(static fn($p) => (int)$p["idPedido"], $pedidos)));
    $idsSql = implode(",", $idsPedidosVisibles);
    $resArchivos = $conn->query("SELECT ap.idArchivo, ap.idDetalle, ap.nombreOriginal, ap.tipoMime, ap.tamano FROM archivopedido ap INNER JOIN detallepedido dp ON dp.idDetalle = ap.idDetalle WHERE dp.idPedido IN ($idsSql) ORDER BY ap.fechaCarga ASC, ap.idArchivo ASC");
    if ($resArchivos) while ($a = $resArchivos->fetch_assoc()) $archivosPorDetalle[(int)$a["idDetalle"]][] = $a;
}

// Datos reales para la pantalla Notificar: total y suma de pagos registrados.
$notificaciones = [];
if ($vista === "notificar") {
    $resultadoNotificaciones = $conn->query("
        SELECT
            p.idPedido,
            p.precioTotal,
            c.nombre AS cliente,
            c.telefono,
            ep.idEstado,
            ep.descripcionEstado AS estado,
            COALESCE(SUM(pa.monto), 0) AS pagado
        FROM pedido p
        INNER JOIN cliente c ON c.idCliente = p.idCliente
        INNER JOIN estadopedido ep ON ep.idEstado = p.idEstado
        LEFT JOIN pago pa ON pa.idPedido = p.idPedido
        WHERE LOWER(ep.descripcionEstado) = 'terminado'
        GROUP BY p.idPedido, p.precioTotal, c.nombre, c.telefono,
                 ep.idEstado, ep.descripcionEstado
        ORDER BY p.idPedido DESC
    ");

    if ($resultadoNotificaciones) {
        while ($filaNotificacion = $resultadoNotificaciones->fetch_assoc()) {
            $notificaciones[] = $filaNotificacion;
        }
    }
}

// Detalles y archivos de los pedidos terminados: también se pueden adjuntar diseños finales desde Notificar.
$detallesPorNotificar = [];
$archivosNotificarPorDetalle = [];
if ($vista === "notificar" && count($notificaciones) > 0) {
    $stmtDetalleNotificar = $conn->prepare("SELECT dp.idDetalle, pr.nombre AS producto, dp.cantidad, COALESCE(d.descripcion, '') AS descripcion FROM detallepedido dp INNER JOIN producto pr ON pr.idProducto = dp.idProducto LEFT JOIN diseño d ON d.idDiseño = dp.idDiseño WHERE dp.idPedido = ? ORDER BY dp.idDetalle ASC");
    $stmtArchivosNotificar = $conn->prepare("SELECT idArchivo, nombreOriginal, tamano FROM archivopedido WHERE idDetalle = ? ORDER BY fechaCarga ASC, idArchivo ASC");
    foreach ($notificaciones as $avisoDatos) {
        $idAvisoDatos = (int)$avisoDatos["idPedido"];
        $stmtDetalleNotificar->bind_param("i", $idAvisoDatos);
        $stmtDetalleNotificar->execute();
        $resultadoDetallesNotificar = $stmtDetalleNotificar->get_result();
        while ($detalleNotificar = $resultadoDetallesNotificar->fetch_assoc()) {
            $idDetalleNotificar = (int)$detalleNotificar["idDetalle"];
            $detallesPorNotificar[$idAvisoDatos][] = $detalleNotificar;
            $stmtArchivosNotificar->bind_param("i", $idDetalleNotificar);
            $stmtArchivosNotificar->execute();
            $resultadoArchivosNotificar = $stmtArchivosNotificar->get_result();
            while ($archivoNotificar = $resultadoArchivosNotificar->fetch_assoc()) {
                $archivosNotificarPorDetalle[$idDetalleNotificar][] = $archivoNotificar;
            }
        }
    }
    $stmtDetalleNotificar->close();
    $stmtArchivosNotificar->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kapykuá - Gestión de Pedidos</title>
    <link rel="stylesheet" href="css/style.css">
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
                <span class="icono icono-svg" aria-hidden="true"><svg viewBox="0 0 32 32" focusable="false"><path d="M3.5 9.5a2 2 0 0 1 2-2h8l3 3h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-21a2 2 0 0 1-2-2z"/><path d="M3.8 12h24"/></svg></span>
                <span>Pedidos</span>
            </a>

            <a href="?vista=notificar" class="menu-item <?php echo $vista === "notificar" ? "activo" : ""; ?>" id="menuNotificar">
                <span class="icono icono-svg" aria-hidden="true"><svg viewBox="0 0 32 32" focusable="false"><path d="M7 22.5c1.8-2 2.5-4.3 2.5-9a6.5 6.5 0 0 1 13 0c0 4.7.7 7 2.5 9z"/><path d="M5 22.5h22M13 26a3 3 0 0 0 6 0"/></svg></span>
                <span>Notificar</span>
            </a>
            <a href="?vista=historial" class="menu-item <?php echo $vista === "historial" ? "activo" : ""; ?>" id="menuHistorial">
                <span class="icono">▤</span><span>Historial</span>
            </a>
        </nav>
    </aside>

    <div class="principal">

        <header class="barra-superior">
            <span class="usuario-sesion">USUARIO: <?php echo htmlspecialchars($_SESSION["nombre"]); ?></span>
            <div class="config-menu">
                <button type="button" class="config-trigger" id="configTrigger" aria-label="Abrir configuración" aria-expanded="false" title="Configuración">⚙</button>
                <div class="config-dropdown" id="configDropdown">
                    <div class="config-identidad"><strong><?php echo htmlspecialchars($_SESSION["nombre"]); ?></strong><small><?php echo htmlspecialchars($_SESSION["rol"]); ?></small></div>
                    <a href="cuenta.php">Cambiar contraseña</a>
                    <?php if (($_SESSION["rol"] ?? "") === "ADMIN"): ?><a href="usuarios.php">Administración de usuarios</a><?php endif; ?>
                    <form method="post"><input type="hidden" name="accion" value="logout"><button type="submit" class="config-logout">Cerrar sesión</button></form>
                </div>
            </div>
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

                            <div id="listaDetallesPedido">
                                <div class="detalle-producto campo-detalle" data-detalle>
                                    <div class="detalle-producto-encabezado"><h3>Producto 1</h3></div>
                                    <div class="campo">
                                        <label>Producto *</label>
                                        <select name="producto[]" required>
                                            <option value="">Seleccione un producto</option>
                                            <?php foreach ($productos as $producto): ?>
                                                <option value="<?php echo (int)$producto["idProducto"]; ?>"><?php echo htmlspecialchars($producto["nombre"] . (!empty($producto["descripcion"]) ? " - " . $producto["descripcion"] : "")); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="fila">
                                        <div class="campo"><label>Cantidad *</label><input type="number" name="cantidad[]" min="1" value="1" required></div>
                                        <div class="campo"><label>Precio acordado por este detalle *</label><input type="number" name="precio_acordado[]" min="0" step="0.01" placeholder="0.00" required></div>
                                    </div>
                                    <div class="campo"><label>Descripción e indicaciones</label><textarea name="diseno[]" rows="3" maxlength="1000" placeholder="Describí el diseño solicitado y cualquier indicación importante para este producto..."></textarea></div>
                                    <button type="button" class="btn-quitar-detalle" data-eliminar-detalle disabled>Eliminar producto</button>
                                </div>
                            </div>
                            <button type="button" class="btn-agregar-detalle" id="agregarDetalle">+ Agregar nuevo producto</button>
                            <div class="resumen"><div><span>Total de productos</span><strong id="totalPedidoVisual">$ 0,00</strong></div></div>

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
            <?php elseif ($vista === "pedidos"): ?>

            <section class="seguimiento">
                <h1>Seguimiento de Pedidos</h1>

                <?php if (count($pedidos) === 0): ?>
                    <div class="sin-pedidos">
                        <h2>Todavía no hay pedidos registrados</h2>
                        <p>Cuando registres un pedido desde «Nuevo Pedido», aparecerá en esta sección.</p>
                    </div>
                <?php else: ?>
                    <div class="resumen-pedidos">
                        <span><?php echo count($pedidos); ?> pedido(s) registrado(s)</span>
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
                                </div>

                                <p class="cliente-pedido">Cliente: <?php echo htmlspecialchars($pedido["cliente"]); ?></p>
                                <p class="fecha-pedido">Fecha: <?php echo htmlspecialchars(formatearFechaTarjeta($pedido["fechaEntrega"])); ?></p>

                                <div class="productos-pedido">
                                    <span class="etiqueta-productos">Productos:</span>
                                    <?php if ($productosLista): ?>
                                        <ul class="lista-productos-tarjeta">
                                            <?php foreach ($productosLista as $productoTexto): ?>
                                                <li><?php echo htmlspecialchars($productoTexto); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <span>Sin productos asociados</span>
                                    <?php endif; ?>
                                </div>

                                <form method="POST" class="form-estado form-estado-tarjeta">
                                    <input type="hidden" name="accion" value="actualizar_estado">
                                    <input type="hidden" name="vista" value="pedidos">
                                    <input type="hidden" name="idPedido" value="<?php echo (int)$pedido["idPedido"]; ?>">
                                    <input type="hidden" name="motivoCancelacion" value="">
                                    <label for="estado-<?php echo (int)$pedido["idPedido"]; ?>" class="etiqueta-estado-tarjeta">ESTADO:</label>
                                    <select
                                        id="estado-<?php echo (int)$pedido["idPedido"]; ?>"
                                        name="idEstado"
                                        class="estado selector-estado-tarjeta estado-<?php echo htmlspecialchars($estadoClase); ?>"
                                        data-valor-inicial="<?php echo (int)$pedido["idEstado"]; ?>"
                                        aria-label="Estado del pedido"
                                        onchange="if (this.form.requestSubmit) { this.form.requestSubmit(); } else { this.form.submit(); }"
                                    >
                                        <?php foreach ($estados as $estadoOpcion): ?>
                                            <?php if ((int)$estadoOpcion["idEstado"] === 5) continue; ?>
                                            <option
                                                value="<?php echo (int)$estadoOpcion["idEstado"]; ?>"
                                                <?php echo (int)$estadoOpcion["idEstado"] === (int)$pedido["idEstado"] ? "selected" : ""; ?>
                                            >
                                                <?php echo htmlspecialchars($estadoOpcion["descripcionEstado"]); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>

                                <div class="pie-tarjeta">
                                    <button type="button" class="btn-detalles" aria-expanded="false">Ver Detalles</button>
                                </div>

                                <div class="detalle-expandido" hidden>
                                    <div class="tarjeta-detalle-flotante" role="dialog" aria-modal="true" aria-label="Detalles del pedido #PED-<?php echo str_pad((string)$pedido["idPedido"], 5, "0", STR_PAD_LEFT); ?>">
                                        <div class="cabecera-detalle-flotante">
                                            <h3>Detalle de #PED-<?php echo str_pad((string)$pedido["idPedido"], 5, "0", STR_PAD_LEFT); ?></h3>
                                            <button type="button" class="btn-cerrar-detalle" aria-label="Cerrar ventana de detalles">&times;</button>
                                        </div>
                                        <form method="POST" class="form-estado">
                                            <input type="hidden" name="accion" value="actualizar_estado">
                                            <input type="hidden" name="vista" value="pedidos">
                                            <input type="hidden" name="idPedido" value="<?php echo (int)$pedido["idPedido"]; ?>">
                                            <label for="estado-detalle-<?php echo (int)$pedido["idPedido"]; ?>">Cambiar estado:</label>
                                            <input type="hidden" name="motivoCancelacion" value="">
                                            <select
                                                id="estado-detalle-<?php echo (int)$pedido["idPedido"]; ?>"
                                                name="idEstado"
                                                aria-label="Nuevo estado del pedido"
                                            >
                                                <?php foreach ($estados as $estadoOpcion): ?>
                                                    <?php if ((int)$estadoOpcion["idEstado"] === 5) continue; ?>
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
                                        <p><strong>Número de pedido:</strong> #PED-<?php echo str_pad((string)$pedido["idPedido"], 5, "0", STR_PAD_LEFT); ?></p>
                                        <p><strong>Cliente:</strong> <?php echo htmlspecialchars($pedido["cliente"]); ?></p>
                                        <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($pedido["telefono"] ?: "No informado"); ?></p>
                                        <p><strong>Fecha de entrega:</strong> <?php echo htmlspecialchars(formatearFechaTarjeta($pedido["fechaEntrega"])); ?></p>
                                        <p><strong>Estado:</strong> <?php echo htmlspecialchars($estadoTexto); ?></p>
                                        <p><strong>Total:</strong> $ <?php echo number_format((float)$pedido["precioTotal"], 2, ",", "."); ?></p>
                                        <strong>Detalle:</strong>
                                        <?php foreach ($detallesLista as $detalleTexto): ?>
                                            <?php $partes = explode("||", $detalleTexto, 7); $idDetalleVista = (int)($partes[0] ?? 0); $archivoLegacy = $partes[6] ?? ""; ?>
                                            <div class="linea-detalle">
                                                <strong><?php echo htmlspecialchars($partes[2] ?? "Producto"); ?></strong>
                                                <span>Cantidad: <?php echo htmlspecialchars($partes[3] ?? "0"); ?></span>
                                                <span>Precio acordado del detalle: $ <?php echo number_format((float)($partes[4] ?? 0) * (int)($partes[3] ?? 0), 2, ",", "."); ?></span>
                                                <?php if (!empty($partes[5])): ?><span>Descripción: <?php echo nl2br(htmlspecialchars($partes[5])); ?></span><?php endif; ?>
                                                <?php if ($archivoLegacy !== ""): ?>
                                                    <div class="archivos-detalle"><strong>Archivo anterior:</strong> <a href="archivo_legacy.php?idDiseno=<?php echo (int)($partes[1] ?? 0); ?>">Descargar archivo</a></div>
                                                <?php endif; ?>
                                                <?php if (!empty($archivosPorDetalle[$idDetalleVista])): ?>
                                                    <div class="archivos-detalle"><strong>Archivos adjuntos:</strong>
                                                        <?php foreach ($archivosPorDetalle[$idDetalleVista] as $archivoVista): ?>
                                                            <div class="archivo-adjunto"><span><?php echo htmlspecialchars($archivoVista["nombreOriginal"]); ?></span><small><?php echo number_format((int)$archivoVista["tamano"] / 1024, 1, ",", "."); ?> KB</small><a href="archivo_pedido.php?id=<?php echo (int)$archivoVista["idArchivo"]; ?>&ver=1" target="_blank" rel="noopener">Ver</a><a href="archivo_pedido.php?id=<?php echo (int)$archivoVista["idArchivo"]; ?>">Descargar</a></div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                                <form method="POST" enctype="multipart/form-data" class="form-subir-archivo">
                                                    <input type="hidden" name="accion" value="subir_archivo_pedido">
                                                    <input type="hidden" name="vista" value="pedidos">
                                                    <input type="hidden" name="idPedido" value="<?php echo (int)$pedido["idPedido"]; ?>">
                                                    <input type="hidden" name="idDetalle" value="<?php echo $idDetalleVista; ?>">
                                                    <label>Agregar archivo a este producto</label>
                                                    <input type="file" name="archivo_pedido" accept=".jpg,.jpeg,.png,.webp,.pdf" required>
                                                    <small>JPG, PNG, WEBP o PDF. Máximo 8 MB.</small>
                                                    <button type="submit" class="btn-guardar-pago">Adjuntar archivo</button>
                                                </form>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <?php elseif ($vista === "historial"): ?>

            <section class="seguimiento historial">
                <h1>Historial de Pedidos</h1>
                <p class="descripcion-historial">En esta sección se conservan los pedidos entregados y cancelados. Podés consultar sus datos y, cuando corresponda, el motivo de cancelación.</p>

                <?php if (count($pedidos) === 0): ?>
                    <div class="sin-pedidos">
                        <h2>No hay pedidos en el historial</h2>
                        <p>Los pedidos aparecerán aquí cuando su estado sea «Entregado» o «Cancelado».</p>
                    </div>
                <?php else: ?>
                    <div class="tabla-notificaciones-contenedor">
                        <table class="tabla-notificaciones tabla-historial">
                            <thead>
                                <tr>
                                    <th>Pedido</th>
                                    <th>Cliente</th>
                                    <th>Fecha de entrega</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th>Motivo de cancelación</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pedidos as $pedidoHist): ?>
                                    <?php
                                        $estadoHist = $pedidoHist["estado"] ?: "Sin estado";
                                        $claseHist = strtolower(trim($estadoHist));
                                        $claseHist = strtr($claseHist, ["á"=>"a", "é"=>"e", "í"=>"i", "ó"=>"o", "ú"=>"u", "ñ"=>"n"]);
                                        $claseHist = preg_replace('/[^a-z0-9]+/i', '-', $claseHist);
                                    ?>
                                    <?php $detallesHist = $pedidoHist["detalle_productos"] ? explode("##", $pedidoHist["detalle_productos"]) : []; ?>
                                    <tr>
                                        <td class="celda-pedido">#PED-<?php echo str_pad((string)$pedidoHist["idPedido"], 5, "0", STR_PAD_LEFT); ?></td>
                                        <td><?php echo htmlspecialchars($pedidoHist["cliente"]); ?></td>
                                        <td><?php echo !empty($pedidoHist["fechaEntrega"]) ? date("d/m/Y", strtotime($pedidoHist["fechaEntrega"])) : "—"; ?></td>
                                        <td>$ <?php echo number_format((float)$pedidoHist["precioTotal"], 2, ",", "."); ?></td>
                                        <td><span class="estado estado-<?php echo htmlspecialchars($claseHist); ?>"><?php echo htmlspecialchars($estadoHist); ?></span></td>
                                        <td><?php echo mb_strtolower($estadoHist, "UTF-8") === "cancelado" ? htmlspecialchars($pedidoHist["motivoCancelacion"] ?: "Sin motivo registrado") : "—"; ?></td>
                                        <td><button type="button" class="btn-detalles-historial" aria-expanded="false">Ver detalles</button></td>
                                    </tr>
                                    <tr class="fila-detalles-historial" hidden>
                                        <td colspan="7">
                                            <div class="detalle-historial-expandido">
                                                <p><strong>Número de pedido:</strong> #PED-<?php echo str_pad((string)$pedidoHist["idPedido"], 5, "0", STR_PAD_LEFT); ?></p>
                                                <p><strong>Cliente:</strong> <?php echo htmlspecialchars($pedidoHist["cliente"]); ?></p>
                                                <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($pedidoHist["telefono"] ?: "No informado"); ?></p>
                                                <p><strong>Estado:</strong> <?php echo htmlspecialchars($estadoHist); ?></p>
                                                <p><strong>Total:</strong> $ <?php echo number_format((float)$pedidoHist["precioTotal"], 2, ",", "."); ?></p>
                                                <?php if (mb_strtolower($estadoHist, "UTF-8") === "cancelado"): ?><p><strong>Motivo de cancelación:</strong> <?php echo htmlspecialchars($pedidoHist["motivoCancelacion"] ?: "Sin motivo registrado"); ?></p><?php endif; ?>
                                                <?php if (mb_strtolower($estadoHist, "UTF-8") === "cancelado"): ?>
                                                    <form method="POST" class="form-reactivar-pedido">
                                                        <input type="hidden" name="accion" value="reactivar_pedido">
                                                        <input type="hidden" name="vista" value="historial">
                                                        <input type="hidden" name="idPedido" value="<?php echo (int)$pedidoHist["idPedido"]; ?>">
                                                        <button type="submit" class="btn-guardar-pago">Reactivar pedido</button>
                                                    </form>
                                                <?php endif; ?>
                                                <strong>Detalle del pedido:</strong>
                                                <?php foreach ($detallesHist as $detalleTextoHist): ?>
                                                    <?php $partesHist = explode("||", $detalleTextoHist, 7); $idDetalleHist = (int)($partesHist[0] ?? 0); $archivoLegacyHist = $partesHist[6] ?? ""; ?>
                                                    <div class="linea-detalle">
                                                        <strong><?php echo htmlspecialchars($partesHist[2] ?? "Producto"); ?></strong>
                                                        <span>Cantidad: <?php echo htmlspecialchars($partesHist[3] ?? "0"); ?></span>
                                                        <span>Precio acordado del detalle: $ <?php echo number_format((float)($partesHist[4] ?? 0) * (int)($partesHist[3] ?? 0), 2, ",", "."); ?></span>
                                                        <?php if (!empty($partesHist[5])): ?><span>Descripción: <?php echo nl2br(htmlspecialchars($partesHist[5])); ?></span><?php endif; ?>
                                                        <?php if ($archivoLegacyHist !== ""): ?><div class="archivos-detalle"><strong>Archivo anterior:</strong> <a href="archivo_legacy.php?idDiseno=<?php echo (int)($partesHist[1] ?? 0); ?>">Descargar archivo</a></div><?php endif; ?>
                                                        <?php if (!empty($archivosPorDetalle[$idDetalleHist])): ?>
                                                            <div class="archivos-detalle"><strong>Archivos adjuntos:</strong>
                                                                <?php foreach ($archivosPorDetalle[$idDetalleHist] as $archivoHist): ?>
                                                                    <div class="archivo-adjunto"><span><?php echo htmlspecialchars($archivoHist["nombreOriginal"]); ?></span><small><?php echo number_format((int)$archivoHist["tamano"] / 1024, 1, ",", "."); ?> KB</small><a href="archivo_pedido.php?id=<?php echo (int)$archivoHist["idArchivo"]; ?>&ver=1" target="_blank" rel="noopener">Ver</a><a href="archivo_pedido.php?id=<?php echo (int)$archivoHist["idArchivo"]; ?>">Descargar</a></div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <?php else: ?>

            <section class="notificar">
                <h1>Notificación de Pedidos</h1>

                <?php if (count($notificaciones) === 0): ?>
                    <div class="sin-pedidos">
                        <h2>No hay pedidos listos para entregar</h2>
                        <p>En esta pantalla aparecen los pedidos cuyo estado es «Terminado» y todavía no fueron entregados.</p>
                        <a class="btn-registrar enlace-boton" href="?vista=nuevo">Registrar un pedido</a>
                    </div>
                <?php else: ?>
                    <div class="tabla-notificaciones-contenedor">
                        <table class="tabla-notificaciones">
                            <thead>
                                <tr>
                                    <th>Pedido</th>
                                    <th>Cliente</th>
                                    <th>Total</th>
                                    <th>Pagado</th>
                                    <th>Estado</th>
                                    <th>Archivos</th>
                                    <th>WhatsApp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($notificaciones as $aviso): ?>
                                    <?php
                                        $estadoAviso = $aviso["estado"] ?: "Sin estado";
                                        $claseAviso = strtolower(trim($estadoAviso));
                                        $claseAviso = strtr($claseAviso, [
                                            'á' => 'a', 'é' => 'e', 'í' => 'i',
                                            'ó' => 'o', 'ú' => 'u', 'ñ' => 'n'
                                        ]);
                                        $claseAviso = preg_replace('/[^a-z0-9]+/i', '-', $claseAviso);

                                        $telefonoAviso = preg_replace('/\D+/', '', (string)$aviso["telefono"]);
                                        // Si se guardó un celular argentino local de 10 dígitos,
                                        // se agrega el prefijo móvil internacional 549.
                                        if (strlen($telefonoAviso) === 10) {
                                            $telefonoAviso = "549" . $telefonoAviso;
                                        } elseif (strlen($telefonoAviso) === 11 && substr($telefonoAviso, 0, 1) === "0") {
                                            $telefonoAviso = "549" . substr($telefonoAviso, 1);
                                        }

                                        $saldoAviso = max(0, (float)$aviso["precioTotal"] - (float)$aviso["pagado"]);
                                        $mensajeWhatsapp = "Hola " . $aviso["cliente"] .
                                            ", te avisamos desde Kapykuá que tu pedido #PED-" .
                                            str_pad((string)$aviso["idPedido"], 5, "0", STR_PAD_LEFT) .
                                            " está terminado y listo para retirar. Estado: " . $estadoAviso .
                                            ". Total: $ " . number_format((float)$aviso["precioTotal"], 2, ",", ".") .
                                            ". Pagado: $ " . number_format((float)$aviso["pagado"], 2, ",", ".") .
                                            ". Saldo pendiente: $ " . number_format($saldoAviso, 2, ",", ".") . ".";
                                    ?>
                                    <tr>
                                        <td class="celda-pedido">#<?php echo str_pad((string)$aviso["idPedido"], 5, "0", STR_PAD_LEFT); ?></td>
                                        <td><?php echo htmlspecialchars($aviso["cliente"]); ?></td>
                                        <td>$ <?php echo number_format((float)$aviso["precioTotal"], 2, ",", "."); ?></td>
                                        <td>
                                            <div class="importe-pagado">
                                                <strong>$ <?php echo number_format((float)$aviso["pagado"], 2, ",", "."); ?></strong>
                                                <?php if ($saldoAviso > 0.009): ?>
                                                    <button type="button" class="btn-editar-pago" aria-label="Completar el pago pendiente" aria-expanded="false">✎</button>
                                                <?php else: ?>
                                                    <span class="pago-completo" title="El pedido está completamente pagado">✓</span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($saldoAviso > 0.009): ?>
                                                <form method="POST" class="form-pago" hidden>
                                                    <input type="hidden" name="accion" value="actualizar_pagado">
                                                    <input type="hidden" name="vista" value="notificar">
                                                    <input type="hidden" name="idPedido" value="<?php echo (int)$aviso["idPedido"]; ?>">
                                                    <label>Importe recibido ahora</label>
                                                    <input
                                                        type="number"
                                                        name="nuevo_pago"
                                                        min="0.01"
                                                        max="<?php echo htmlspecialchars(number_format($saldoAviso, 2, ".", "")); ?>"
                                                        step="0.01"
                                                        value="<?php echo htmlspecialchars(number_format($saldoAviso, 2, ".", "")); ?>"
                                                        required
                                                    >
                                                    <small>Saldo pendiente: $ <?php echo number_format($saldoAviso, 2, ",", "."); ?>. Ingresá cuánto recibiste en este cobro.</small>
                                                    <select name="medio_pago_pago" required>
                                                        <option value="">Medio de pago del saldo</option>
                                                        <?php foreach ($mediosPago as $medio): ?>
                                                            <option value="<?php echo (int)$medio["idMedioPago"]; ?>"><?php echo htmlspecialchars($medio["nombre"]); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="submit" class="btn-guardar-pago">Guardar pago</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="POST" class="form-estado-notificar">
                                                <input type="hidden" name="accion" value="confirmar_entrega">
                                                <input type="hidden" name="vista" value="notificar">
                                                <input type="hidden" name="idPedido" value="<?php echo (int)$aviso["idPedido"]; ?>">
                                                <select
                                                    name="accion_visual"
                                                    class="selector-estado estado-terminado"
                                                    aria-label="Confirmar entrega del pedido"
                                                    onchange="if (this.value === 'entregado') { if (window.confirm('¿Confirmás que el pedido fue entregado al cliente? Pasará al Historial.')) this.form.submit(); else this.value = 'terminado'; } else { this.value = 'terminado'; }"
                                                >
                                                    <option value="terminado" selected>Terminado</option>
                                                    <option value="entregado" <?php echo $saldoAviso > 0.009 ? "disabled title=\"Completá el pago pendiente antes de entregar\"" : ""; ?>>Entregado</option>
                                                </select>
                                                <?php if ($saldoAviso > 0.009): ?>
                                                    <small class="ayuda-entrega">Completar pago para entregar</small>
                                                <?php endif; ?>
                                            </form>
                                        </td>
                                        <td><button type="button" class="btn-detalles-notificar" aria-expanded="false">Archivos del pedido</button></td>
                                        <td>
                                            <?php if ($telefonoAviso !== "" && strlen($telefonoAviso) >= 11): ?>
                                                <a class="btn-whatsapp" href="https://wa.me/<?php echo htmlspecialchars($telefonoAviso); ?>?text=<?php echo rawurlencode($mensajeWhatsapp); ?>" target="_blank" rel="noopener noreferrer">
                                                    <span aria-hidden="true">☎</span> WhatsApp
                                                </a>
                                            <?php else: ?>
                                                <span class="sin-telefono" title="Completá el teléfono del cliente con característica y número">Sin teléfono válido</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr class="fila-archivos-notificar" hidden>
                                        <td colspan="7">
                                            <div class="detalle-historial-expandido">
                                                <strong>Archivos del pedido</strong>
                                                <?php foreach (($detallesPorNotificar[(int)$aviso["idPedido"]] ?? []) as $detalleNotificarVista): ?>
                                                    <?php $idDetalleNotificarVista = (int)$detalleNotificarVista["idDetalle"]; ?>
                                                    <div class="linea-detalle">
                                                        <strong><?php echo htmlspecialchars($detalleNotificarVista["producto"]); ?></strong>
                                                        <span>Cantidad: <?php echo (int)$detalleNotificarVista["cantidad"]; ?></span>
                                                        <?php if (!empty($detalleNotificarVista["descripcion"])): ?><span>Descripción: <?php echo nl2br(htmlspecialchars($detalleNotificarVista["descripcion"])); ?></span><?php endif; ?>
                                                        <?php if (!empty($archivosNotificarPorDetalle[$idDetalleNotificarVista])): ?>
                                                            <div class="archivos-detalle"><strong>Archivos adjuntos:</strong>
                                                                <?php foreach ($archivosNotificarPorDetalle[$idDetalleNotificarVista] as $archivoNotificarVista): ?>
                                                                    <div class="archivo-adjunto"><span><?php echo htmlspecialchars($archivoNotificarVista["nombreOriginal"]); ?></span><small><?php echo number_format((int)$archivoNotificarVista["tamano"] / 1024, 1, ",", "."); ?> KB</small><a href="archivo_pedido.php?id=<?php echo (int)$archivoNotificarVista["idArchivo"]; ?>&ver=1" target="_blank" rel="noopener">Ver</a><a href="archivo_pedido.php?id=<?php echo (int)$archivoNotificarVista["idArchivo"]; ?>">Descargar</a></div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <form method="POST" enctype="multipart/form-data" class="form-subir-archivo">
                                                            <input type="hidden" name="accion" value="subir_archivo_pedido">
                                                            <input type="hidden" name="vista" value="notificar">
                                                            <input type="hidden" name="idPedido" value="<?php echo (int)$aviso["idPedido"]; ?>">
                                                            <input type="hidden" name="idDetalle" value="<?php echo $idDetalleNotificarVista; ?>">
                                                            <label>Agregar archivo a este producto</label>
                                                            <input type="file" name="archivo_pedido" accept=".jpg,.jpeg,.png,.webp,.pdf" required>
                                                            <small>JPG, PNG, WEBP o PDF. Máximo 8 MB.</small>
                                                            <button type="submit" class="btn-guardar-pago">Adjuntar archivo</button>
                                                        </form>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="nota-notificar">Esta pantalla muestra únicamente pedidos terminados. WhatsApp prepara un aviso para enviar manualmente. Al completar «Pagado», se registra solo el saldo cobrado; la entrega se confirma cuando el pedido está totalmente abonado.</p>
                <?php endif; ?>
            </section>

            <?php endif; ?>

        </main>
    </div>
</div>

<script src="js/app.js"></script>

</body>
</html>
