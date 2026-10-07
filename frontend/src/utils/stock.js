// Regla de negocio: los pedidos web NO se limitan por el stock registrado
// (la panadería hornea a diario y por encargo; el stock se descuenta al
// entregar el pedido). Estas utilidades las usa solo el flujo web (catálogo,
// carrito y checkout); el punto de venta valida stock por su cuenta.
// Para volver a limitar por stock, poner esto en true.
export const PEDIDOS_WEB_LIMITADOS_POR_STOCK = false;

export const getAvailableUnits = (producto) => {
  if (!PEDIDOS_WEB_LIMITADOS_POR_STOCK) return null;
  if (!producto) return null;
  const rawValue = producto?.inventario?.stock_actual ?? producto?.stock_actual ?? producto?.stock ?? null;
  if (rawValue === null || rawValue === undefined) return null;
  const numericValue = Number(rawValue);
  if (Number.isNaN(numericValue)) return null;
  return Math.max(0, Math.floor(numericValue));
};

export const hasLimitedStock = (producto) => getAvailableUnits(producto) !== null;

// Los extras del carrito tienen id "<productoId>-extra-<indice>". El backend los
// recibe como el id del producto principal + extra_index.
export const toPedidoItem = (item) => {
  if (item?.es_extra) {
    const match = String(item.id).match(/^(\d+)-extra-(\d+)$/);
    if (match) {
      return { id: Number(item.producto_padre_id ?? match[1]), extra_index: Number(match[2]), cantidad: item.cantidad };
    }
  }
  const personalizacion = String(item?.personalizacion ?? '').trim();
  return personalizacion
    ? { id: item.id, cantidad: item.cantidad, personalizacion }
    : { id: item.id, cantidad: item.cantidad };
};
