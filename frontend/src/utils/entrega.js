// Horarios de entrega del checkout. La tienda atiende de lunes a sábado de
// 8:00 a 20:00 y se ofrecen turnos cada 30 minutos (el último a las 19:30).
export const HORA_ABRE = 8;
export const HORA_CIERRA = 20;
const TURNO = 30;
// Margen mínimo para preparar cualquier pedido, aunque no pida anticipación
const MARGEN_MINUTOS = 30;

const pad2 = (n) => String(n).padStart(2, '0');

// Fecha en hora local (toISOString usa UTC: desde las 20:00 en Bolivia daba el día siguiente)
export const fechaLocal = (d) => `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}`;
export const horaLocal = (d) => `${pad2(d.getHours())}:${pad2(d.getMinutes())}`;

// "2026-10-03" se interpreta en hora local (new Date('2026-10-03') sería UTC)
const aFecha = (fecha, hora = '00:00') => new Date(`${fecha}T${hora}`);

export const esDomingo = (fecha) => Boolean(fecha) && aFecha(fecha).getDay() === 0;

// Mayor tiempo de anticipación (en minutos) entre los productos del carrito
export const minutosAnticipacion = (cart) => cart.reduce((max, item) => {
  if (!item.requiere_tiempo_anticipacion) return max;
  const cantidad = Number(item.tiempo_anticipacion) || 0;
  const porUnidad = { horas: 60, dias: 24 * 60, semanas: 7 * 24 * 60 };
  return Math.max(max, cantidad * (porUnidad[item.unidad_tiempo] ?? 60));
}, 0);

const TURNOS_DEL_DIA = (() => {
  const turnos = [];
  for (let m = HORA_ABRE * 60; m <= HORA_CIERRA * 60 - TURNO; m += TURNO) {
    turnos.push(`${pad2(Math.floor(m / 60))}:${pad2(m % 60)}`);
  }
  return turnos;
})();

// Primer turno posible: ahora + anticipación + margen, redondeado al turno
// siguiente y movido dentro del horario de atención.
export const primeraEntrega = (cart, ahora = new Date()) => {
  const t = new Date(ahora.getTime() + (minutosAnticipacion(cart) + MARGEN_MINUTOS) * 60000);
  t.setSeconds(0, 0);
  const resto = t.getMinutes() % TURNO;
  if (resto) t.setMinutes(t.getMinutes() + TURNO - resto);

  for (let i = 0; i < 14; i += 1) {
    const minutos = t.getHours() * 60 + t.getMinutes();
    if (t.getDay() === 0 || minutos > HORA_CIERRA * 60 - TURNO) {
      t.setDate(t.getDate() + 1);
      t.setHours(HORA_ABRE, 0, 0, 0);
      continue;
    }
    if (minutos < HORA_ABRE * 60) t.setHours(HORA_ABRE, 0, 0, 0);
    break;
  }
  return t;
};

// Turnos disponibles en una fecha, sin los anteriores a la primera entrega
export const turnosDelDia = (fecha, minima) => {
  if (!fecha || esDomingo(fecha)) return [];
  const diaMinimo = fechaLocal(minima);
  if (fecha < diaMinimo) return [];
  if (fecha > diaMinimo) return TURNOS_DEL_DIA;
  const desde = horaLocal(minima);
  return TURNOS_DEL_DIA.filter((h) => h >= desde);
};

// "hoy", "mañana", "el sábado 4 de octubre"
export const describirDia = (fecha, ahora = new Date()) => {
  if (!fecha) return '';
  if (fecha === fechaLocal(ahora)) return 'hoy';
  if (fecha === fechaLocal(new Date(ahora.getFullYear(), ahora.getMonth(), ahora.getDate() + 1))) return 'mañana';
  return `el ${aFecha(fecha).toLocaleDateString('es-BO', { weekday: 'long', day: 'numeric', month: 'long' }).replace(',', '')}`;
};

// "hoy a las 10:30", "mañana a las 08:00", "el sábado 4 de octubre a las 09:00"
export const describirEntrega = (fecha, hora, ahora = new Date()) => (
  fecha && hora ? `${describirDia(fecha, ahora)} a las ${hora}` : ''
);
