document.addEventListener("DOMContentLoaded", function() {
    const configTrigger = document.getElementById("configTrigger");
    const configDropdown = document.getElementById("configDropdown");

    if (configTrigger && configDropdown) {
        configTrigger.addEventListener("click", function(e) {
            e.stopPropagation();
            const open = configDropdown.classList.toggle("visible");
            configTrigger.setAttribute("aria-expanded", open ? "true" : "false");
        });

        document.addEventListener("click", function(e) {
            if (!configDropdown.contains(e.target) && !configTrigger.contains(e.target)) {
                configDropdown.classList.remove("visible");
                configTrigger.setAttribute("aria-expanded", "false");
            }
        });
    }

    document.querySelectorAll(".form-estado").forEach(function(form) {
        form.addEventListener("submit", function(event) {
            const estado = form.querySelector('select[name="idEstado"]');
            const motivo = form.querySelector('input[name="motivoCancelacion"]');
            const revertir = function() {
                if (estado && estado.dataset.valorInicial) {
                    estado.value = estado.dataset.valorInicial;
                }
            };
            if (estado && estado.value === "4" && !window.confirm("¿Confirmás que el pedido está terminado y listo para pasar a Notificar?")) {
                event.preventDefault();
                revertir();
                return;
            }
            if (estado && estado.value === "6") {
                if (!window.confirm("¿Confirmás que querés cancelar este pedido?")) {
                    event.preventDefault();
                    revertir();
                    return;
                }
                const razon = window.prompt("Indique brevemente el motivo de cancelación:");
                if (razon === null || razon.trim() === "") {
                    event.preventDefault();
                    revertir();
                    if (razon !== null) alert("Debe ingresar un motivo para cancelar el pedido.");
                    return;
                }
                motivo.value = razon.trim();
            }
        });
    });

    document.querySelectorAll(".btn-editar-pago").forEach(function(boton) {
        boton.addEventListener("click", function() {
            const formulario = boton.closest("td").querySelector(".form-pago");
            const estaAbierto = !formulario.hidden;
            formulario.hidden = estaAbierto;
            boton.setAttribute("aria-expanded", String(!estaAbierto));
            boton.textContent = estaAbierto ? "✎" : "×";
        });
    });

    function cerrarDetalleFlotante(detalle) {
        if (!detalle) return;
        detalle.hidden = true;
        const tarjeta = detalle.closest(".tarjeta-pedido");
        const boton = tarjeta ? tarjeta.querySelector(".btn-detalles") : null;
        if (boton) {
            boton.setAttribute("aria-expanded", "false");
        }
    }

    document.querySelectorAll(".btn-detalles").forEach(function(boton) {
        boton.addEventListener("click", function() {
            const detalle = boton.closest(".tarjeta-pedido").querySelector(".detalle-expandido");
            if (!detalle) return;
            document.querySelectorAll(".detalle-expandido:not([hidden])").forEach(function(abierto) {
                if (abierto !== detalle) cerrarDetalleFlotante(abierto);
            });
            const abrir = detalle.hidden;
            detalle.hidden = !abrir;
            boton.setAttribute("aria-expanded", String(abrir));
        });
    });

    document.querySelectorAll(".btn-cerrar-detalle").forEach(function(botonCerrar) {
        botonCerrar.addEventListener("click", function() {
            cerrarDetalleFlotante(botonCerrar.closest(".detalle-expandido"));
        });
    });

    document.querySelectorAll(".detalle-expandido").forEach(function(overlay) {
        overlay.addEventListener("click", function(evento) {
            if (evento.target === overlay) {
                cerrarDetalleFlotante(overlay);
            }
        });
    });

    document.addEventListener("keydown", function(evento) {
        if (evento.key === "Escape") {
            document.querySelectorAll(".detalle-expandido:not([hidden])").forEach(function(abierto) {
                cerrarDetalleFlotante(abierto);
            });
        }
    });

    document.querySelectorAll(".form-reactivar-pedido").forEach(function(formulario) {
        formulario.addEventListener("submit", function(evento) {
            if (!window.confirm("¿Querés reactivar este pedido? Volverá a Pendiente y se limpiará el motivo de cancelación.")) {
                evento.preventDefault();
            }
        });
    });

    document.querySelectorAll(".btn-detalles-notificar").forEach(function(boton) {
        boton.addEventListener("click", function() {
            const fila = boton.closest("tr");
            const detalle = fila ? fila.nextElementSibling : null;
            if (!detalle || !detalle.classList.contains("fila-archivos-notificar")) return;
            detalle.hidden = !detalle.hidden;
            boton.setAttribute("aria-expanded", String(!detalle.hidden));
            boton.textContent = detalle.hidden ? "Archivos del pedido" : "Ocultar archivos";
        });
    });

    document.querySelectorAll(".btn-detalles-historial").forEach(function(boton) {
        boton.addEventListener("click", function() {
            const filaPrincipal = boton.closest("tr");
            const filaDetalle = filaPrincipal ? filaPrincipal.nextElementSibling : null;
            if (!filaDetalle || !filaDetalle.classList.contains("fila-detalles-historial")) return;
            const estaAbierto = !filaDetalle.hidden;
            filaDetalle.hidden = estaAbierto;
            boton.textContent = estaAbierto ? "Ver detalles" : "Ocultar detalles";
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
    const formularioPedido = document.getElementById("pedidoForm");

    if (menuNuevoPedido && formularioPedido) {
        menuNuevoPedido.addEventListener("click", function(evento) {
            evento.preventDefault();
            mostrarPaso(1);
        });
    }

    const listaDetalles = document.getElementById("listaDetallesPedido");
    const totalVisual = document.getElementById("totalVisual");
    const totalPedidoVisual = document.getElementById("totalPedidoVisual");
    const botonAgregarDetalle = document.getElementById("agregarDetalle");

    function formatoMoneda(valor) {
        return "$ " + valor.toLocaleString("es-AR", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function actualizarTotalPedido() {
        let total = 0;
        listaDetalles.querySelectorAll("[data-detalle]").forEach(function(bloque) {
            total += parseFloat(bloque.querySelector('[name="precio_acordado[]"]').value) || 0;
        });
        totalVisual.textContent = formatoMoneda(total);
        totalPedidoVisual.textContent = formatoMoneda(total);
    }

    function actualizarNumeracion() {
        const bloques = listaDetalles.querySelectorAll("[data-detalle]");
        bloques.forEach(function(bloque, indice) {
            bloque.querySelector("h3").textContent = "Producto " + (indice + 1);
            bloque.querySelector("[data-eliminar-detalle]").disabled = bloques.length === 1;
        });
    }

    if (listaDetalles && botonAgregarDetalle) {
        botonAgregarDetalle.addEventListener("click", function() {
            const original = listaDetalles.querySelector("[data-detalle]");
            const nuevo = original.cloneNode(true);
            nuevo.querySelectorAll("input, textarea, select").forEach(function(campo) {
                if (campo.tagName === "SELECT") campo.selectedIndex = 0;
                else if (campo.type === "number") campo.value = campo.name === "cantidad[]" ? "1" : "";
                else campo.value = "";
            });
            listaDetalles.appendChild(nuevo);
            actualizarNumeracion();
            actualizarTotalPedido();
        });

        listaDetalles.addEventListener("click", function(evento) {
            const boton = evento.target.closest("[data-eliminar-detalle]");
            if (boton && listaDetalles.querySelectorAll("[data-detalle]").length > 1) {
                boton.closest("[data-detalle]").remove();
                actualizarNumeracion();
                actualizarTotalPedido();
            }
        });

        listaDetalles.addEventListener("input", actualizarTotalPedido);
        listaDetalles.addEventListener("change", actualizarTotalPedido);
        actualizarNumeracion();
        actualizarTotalPedido();

        document.getElementById("pedidoForm").addEventListener("submit", function(evento) {
            const monto = parseFloat(document.getElementById("monto").value) || 0;
            let total = 0;
            let valido = true;
            listaDetalles.querySelectorAll("[data-detalle]").forEach(function(bloque) {
                const producto = bloque.querySelector('[name="producto[]"]');
                const cantidad = parseInt(bloque.querySelector('[name="cantidad[]"]').value, 10) || 0;
                const precio = parseFloat(bloque.querySelector('[name="precio_acordado[]"]').value) || 0;
                if (!producto.value || cantidad <= 0 || precio < 0) valido = false;
                total += precio;
            });
            if (!valido || listaDetalles.querySelectorAll("[data-detalle]").length === 0) {
                evento.preventDefault();
                alert("Completá producto, cantidad y precio acordado en cada detalle.");
            } else if (monto > total) {
                evento.preventDefault();
                alert("La seña no puede ser mayor que el precio total del pedido.");
            }
        });
    }
});
