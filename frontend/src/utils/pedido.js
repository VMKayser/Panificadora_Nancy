import { formatCurrency } from './number';

// El checkout guarda aquí el pedido recién creado para que la página de
// confirmación sobreviva a una recarga (en el teléfono, volver de WhatsApp
// suele recargar la pestaña).
export const CLAVE_ULTIMO_PEDIDO = 'pn_ultimo_pedido';

export const leerUltimoPedido = () => {
  try {
    return JSON.parse(sessionStorage.getItem(CLAVE_ULTIMO_PEDIDO) || 'null');
  } catch {
    return null;
  }
};

export const guardarUltimoPedido = (confirmacion) => {
  try {
    sessionStorage.setItem(CLAVE_ULTIMO_PEDIDO, JSON.stringify(confirmacion));
  } catch {
    // Sin sessionStorage la confirmación igual llega por la navegación
  }
};

export const ENTREGA_TEXTO = {
  recoger: 'Recoger en la tienda',
  delivery: 'Delivery',
  envio_nacional: 'Envío a otra ciudad',
};

// Mensaje de WhatsApp con todo el pedido: la tienda lo recibe completo y el
// cliente solo adjunta la captura del pago.
export const mensajePedido = (p) => {
  const lineas = (p.lineas || [])
    .map((l) => `• ${l.cantidad} × ${l.nombre} — Bs ${formatCurrency(l.subtotal)}${l.detalle ? `\n   ${l.detalle}` : ''}`)
    .join('\n');

  const entrega = {
    recoger: 'Recoger en la tienda',
    delivery: `Delivery a: ${p.direccion || ''}${p.ubicacion ? `\nUbicación: ${p.ubicacion}` : ''}`,
    envio_nacional: `Envío a: ${p.direccion || ''}`,
  }[p.tipoEntrega] || '';

  const detalles = [
    `*Total: Bs ${formatCurrency(p.total)}*`,
    entrega,
    p.cuando && `Cuándo: ${p.cuando}`,
    p.nota && `Nota: ${p.nota}`,
    p.nit && `Factura a NIT/CI: ${p.nit}`,
  ].filter(Boolean).join('\n');

  return [
    `Hola, soy ${p.nombre}. Hice el pedido *${p.numero}* en la web:`,
    lineas,
    detalles,
    'Te envío el comprobante del pago por QR.',
  ].join('\n\n');
};
