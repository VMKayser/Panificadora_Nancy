// Ayudas para mostrar el carrito (precios, imagen y agrupación de extras).

export const precioDe = (item) => {
  const precio = item.precio !== undefined ? parseFloat(item.precio) : parseFloat(item.precio_minorista || 0);
  return Number.isNaN(precio) ? 0 : precio;
};

export const imagenDe = (item) => {
  const img = item?.imagenes?.[0];
  return img ? (img.url_miniatura || img.url_imagen_completa || img.url_imagen || img.url || null) : null;
};

// Separa productos y extras: los extras del carrito tienen id "<padre>-extra-<i>".
export const agruparCarrito = (cart) => {
  const padres = cart.filter((i) => !i.es_extra);
  const extrasPorPadre = cart
    .filter((i) => i.es_extra)
    .reduce((acc, ex) => {
      const key = ex.producto_padre_id || String(ex.id).split('-extra-')[0];
      (acc[key] = acc[key] || []).push(ex);
      return acc;
    }, {});
  return { padres, extrasPorPadre };
};
