// Reglas de productos de temporada (las mismas que valida el backend):
// - precio_por_confirmar: se muestra, pero se consulta por WhatsApp en vez de pedirse.
// - pedidos_hasta: último día (hora de Bolivia) en que la web acepta pedidos.
// - etiqueta_personalizacion: dato que el cliente debe escribir (ej. "Nombre del difunto").
import { fechaLocal } from './entrega';
import { enlaceWhatsapp, WHATSAPP_TIENDA } from './whatsapp';

export const precioPorConfirmar = (producto) => Boolean(producto?.precio_por_confirmar);

// "2026-10-24" o "2026-10-24T00:00:00.000000Z" → "2026-10-24"
const diaDe = (valor) => (valor ? String(valor).slice(0, 10) : null);

export const pedidosCerrados = (producto, hoy = fechaLocal(new Date())) => {
  const hasta = diaDe(producto?.pedidos_hasta);
  return Boolean(hasta) && hasta < hoy;
};

// "sábado 24 de octubre"
export const describirPedidosHasta = (producto) => {
  const hasta = diaDe(producto?.pedidos_hasta);
  if (!hasta) return null;
  return new Date(`${hasta}T12:00:00`).toLocaleDateString('es-BO', { weekday: 'long', day: 'numeric', month: 'long' });
};

export const etiquetaDe = (producto) => String(producto?.etiqueta_personalizacion ?? '').trim();

// Línea del carrito a la que le falta el dato que pide el producto
export const faltaPersonalizacion = (item) => !item?.es_extra
  && etiquetaDe(item) !== ''
  && String(item?.personalizacion ?? '').trim() === '';

export const enlaceConsulta = (producto) => enlaceWhatsapp(
  WHATSAPP_TIENDA,
  `Hola, quisiera consultar el precio de ${producto?.nombre ?? 'un producto'}.`,
);
