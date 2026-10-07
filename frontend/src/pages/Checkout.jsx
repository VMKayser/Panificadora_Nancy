import { useEffect, useMemo, useRef, useState } from 'react';
import PropTypes from 'prop-types';
import { ArrowLeft, Bike, ChevronDown, LocateFixed, MapPin, QrCode, ShoppingBag, Store, Truck, X } from 'lucide-react';
import { Link, useNavigate } from 'react-router-dom';
import { toast } from 'react-toastify';
import { useSEO } from '../hooks/useSEO';
import { useCart } from '../context/CartContext';
import { useAuth } from '../context/AuthContext';
import { useSiteConfig } from '../context/SiteConfigContext';
import { crearPedido, getMetodosPagoCached as getMetodosPago, assetBase } from '../services/api';
import { formatCurrency } from '../utils/number';
import { toPedidoItem } from '../utils/stock';
import { celularValido, limpiarCelular, enlaceWhatsapp, WHATSAPP_TIENDA } from '../utils/whatsapp';
import {
  describirDia, describirEntrega, esDomingo, fechaLocal, horaLocal, minutosAnticipacion, primeraEntrega, turnosDelDia,
} from '../utils/entrega';
import { guardarUltimoPedido } from '../utils/pedido';
import { etiquetaDe, faltaPersonalizacion } from '../utils/temporada';
import CartLines from '../components/tienda/CartLines';

const TIENDA = {
  direccion: 'HPW9+J94, Av. Martín Cardenas, Quillacollo',
  mapa: 'https://www.google.com/maps/search/?api=1&query=-17.403381642688004,-66.2815992191286',
};

// Recuadro aproximado de Quillacollo (el backend usa el mismo)
const enQuillacollo = ({ lat, lng }) => lat >= -17.45 && lat <= -17.22 && lng >= -66.35 && lng <= -66.10;

const FUERA_DE_ZONA = 'El delivery solo llega a Quillacollo. Elige Envío o Recoger, o quita la ubicación.';

const PREGUNTA_CUANDO = {
  recoger: '¿Cuándo pasas a recoger?',
  delivery: '¿Cuándo te lo llevamos?',
  envio_nacional: '¿Cuándo lo despachamos?',
};

const Campo = ({ id, label, error, ayuda, children }) => (
  <div className="pn-field">
    <label htmlFor={id} className="pn-field__label">{label}</label>
    {children}
    {error ? (
      <p id={`${id}-error`} className="pn-field__error" role="alert">{error}</p>
    ) : ayuda ? (
      <p className="pn-field__help">{ayuda}</p>
    ) : null}
  </div>
);

Campo.propTypes = {
  id: PropTypes.string.isRequired,
  label: PropTypes.node.isRequired,
  error: PropTypes.string,
  ayuda: PropTypes.node,
  children: PropTypes.node.isRequired,
};

// Imagen del QR de pago: la que sube el admin en la configuración o, si no
// hay, el icono del método de pago.
const imagenQr = (qrUrl, metodo) => {
  if (qrUrl) return qrUrl;
  if (metodo?.icono_url) return metodo.icono_url;
  if (metodo?.icono) {
    return metodo.icono.startsWith('http')
      ? metodo.icono
      : `${assetBase().replace(/\/$/, '')}/${metodo.icono.replace(/^\//, '')}`;
  }
  return null;
};

const Checkout = () => {
  const navigate = useNavigate();
  const { cart, getTotal, getTotalItems, clearCart } = useCart();
  const { user } = useAuth();
  const { qrUrl } = useSiteConfig();

  const [tipoEntrega, setTipoEntrega] = useState('recoger');
  const [direccion, setDireccion] = useState('');
  const [ubicacion, setUbicacion] = useState(null);
  const [buscandoUbicacion, setBuscandoUbicacion] = useState(false);
  const [ciudad, setCiudad] = useState('');
  const [cuando, setCuando] = useState('pronto');
  const [fecha, setFecha] = useState('');
  const [hora, setHora] = useState('');
  const [nombre, setNombre] = useState('');
  const [celular, setCelular] = useState('');
  const [email, setEmail] = useState('');
  const [nit, setNit] = useState('');
  const [nota, setNota] = useState('');
  const [masOpciones, setMasOpciones] = useState(false);
  const [metodo, setMetodo] = useState(null);
  const [metodosCargados, setMetodosCargados] = useState(false);
  const [errores, setErrores] = useState({});
  const [marcarFaltantes, setMarcarFaltantes] = useState(false);
  const resumenMovilRef = useRef(null);
  const [enviando, setEnviando] = useState(false);
  const formRef = useRef(null);

  // La página de confirmación se descarga mientras el cliente llena el formulario
  useEffect(() => {
    import('./PedidoConfirmado');
  }, []);

  useSEO({
    title: 'Finalizar pedido - Panificadora Nancy',
    description: 'Completa tu pedido de pan y repostería.',
    noindex: true,
  });

  // En la web solo se paga con QR: se usa el primer método QR activo
  useEffect(() => {
    getMetodosPago()
      .then((metodos) => setMetodo(metodos.find((m) => m.esta_activo && /qr/i.test(m.codigo || '')) || null))
      .catch(() => setMetodo(null))
      .finally(() => setMetodosCargados(true));
  }, []);

  // Con sesión iniciada los datos ya se conocen
  useEffect(() => {
    if (!user) return;
    const nombreCuenta = user.cliente?.nombre
      ? `${user.cliente.nombre} ${user.cliente.apellido || ''}`.trim()
      : (user.name || '').trim();
    setNombre((prev) => prev || nombreCuenta);
    setCelular((prev) => prev || user.phone || user.cliente?.telefono || '');
    setNit((prev) => prev || user.nit_ci || user.cliente?.nit_ci || '');
  }, [user]);

  const sinEnvioNacional = cart.filter((i) => i.permite_envio_nacional === false);

  useEffect(() => {
    if (tipoEntrega === 'envio_nacional' && sinEnvioNacional.length > 0) setTipoEntrega('recoger');
  }, [tipoEntrega, sinEnvioNacional.length]);

  // Primer turno posible según la anticipación de los productos del carrito.
  // El reloj avanza cada minuto para que "lo antes posible" no quede en el
  // pasado si el cliente se demora en completar el formulario.
  const [ahora, setAhora] = useState(() => new Date());
  useEffect(() => {
    const id = setInterval(() => setAhora(new Date()), 60000);
    return () => clearInterval(id);
  }, []);
  const minima = useMemo(() => primeraEntrega(cart, ahora), [cart, ahora]);
  const turnos = turnosDelDia(fecha, minima);
  // "Lo antes posible" no promete hora (la tienda la confirma por WhatsApp);
  // solo avisa el día cuando no puede ser hoy: productos por encargo, domingo
  // o un pedido hecho después del cierre.
  const diaPronto = fechaLocal(minima) !== fechaLocal(ahora) ? describirDia(fechaLocal(minima), ahora) : '';

  const limpiarError = (campo) => setErrores((e) => (e[campo] ? { ...e, [campo]: undefined } : e));

  const elegirCuando = (valor) => {
    setCuando(valor);
    setErrores((e) => ({ ...e, fecha: undefined, hora: undefined }));
    if (valor === 'programar' && !fecha) {
      setFecha(fechaLocal(minima));
      setHora(horaLocal(minima));
    }
  };

  const cambiarFecha = (valor) => {
    setFecha(valor);
    const disponibles = turnosDelDia(valor, minima);
    if (!disponibles.includes(hora)) setHora(disponibles[0] || '');
    setErrores((e) => ({ ...e, fecha: undefined, hora: undefined }));
  };

  const usarUbicacion = () => {
    if (!navigator.geolocation) {
      toast.error('Tu navegador no permite compartir la ubicación. Escribe la dirección con una referencia.');
      return;
    }
    setBuscandoUbicacion(true);
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        setBuscandoUbicacion(false);
        setUbicacion({ lat: pos.coords.latitude, lng: pos.coords.longitude });
        limpiarError('direccion');
      },
      () => {
        setBuscandoUbicacion(false);
        toast.error('No pudimos obtener tu ubicación. Escribe la dirección con una referencia.');
      },
      { enableHighAccuracy: true, timeout: 10000 },
    );
  };

  const ubicacionFuera = tipoEntrega === 'delivery' && ubicacion && !enQuillacollo(ubicacion);

  const validar = () => {
    const e = {};
    if (tipoEntrega === 'delivery') {
      if (!direccion.trim() && !ubicacion) e.direccion = 'Escribe tu dirección o comparte tu ubicación.';
      else if (ubicacionFuera) e.direccion = FUERA_DE_ZONA;
    }
    if (tipoEntrega === 'envio_nacional' && !ciudad.trim()) e.ciudad = 'Escribe la ciudad a la que lo enviamos.';
    if (cuando === 'programar') {
      if (esDomingo(fecha)) e.fecha = 'Los domingos no atendemos. Elige otro día.';
      else if (!fecha || turnos.length === 0) e.fecha = 'Ese día ya no quedan horarios. Elige otro día.';
      else if (!hora) e.hora = 'Elige una hora.';
      else if (!turnos.includes(hora)) e.hora = 'Ese horario ya pasó. Elige uno más tarde.';
    }
    if (!nombre.trim()) e.nombre = 'Escribe tu nombre.';
    if (!celularValido(celular)) e.celular = 'Escribe tu número de celular (8 dígitos).';
    if (email.trim() && !/^\S+@\S+\.\S+$/.test(email.trim())) e.email = 'Revisa el correo o déjalo vacío.';
    return e;
  };

  const handleSubmit = async (ev) => {
    ev.preventDefault();
    // Productos que piden un dato (ej. nombre del difunto) y no lo tienen
    const sinDato = cart.find(faltaPersonalizacion);
    if (sinDato) {
      setMarcarFaltantes(true);
      // En el teléfono el resumen está plegado: se abre para mostrar el campo
      if (resumenMovilRef.current) resumenMovilRef.current.open = true;
      toast.error(`Escribe «${etiquetaDe(sinDato)}» en ${sinDato.nombre}.`);
      setTimeout(() => {
        const campo = [...document.querySelectorAll(`[data-dato="${sinDato.id}"]`)]
          .find((el) => el.offsetParent !== null);
        campo?.focus({ preventScroll: true });
        campo?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }, 0);
      return;
    }
    const e = validar();
    setErrores(e);
    const primero = Object.keys(e)[0];
    if (primero) {
      if (primero === 'email') setMasOpciones(true);
      setTimeout(() => {
        const campo = formRef.current?.querySelector(`[name="${primero}"]`);
        campo?.focus({ preventScroll: true });
        campo?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }, 0);
      return;
    }
    if (!metodo) return;

    const entrega = cuando === 'pronto'
      ? { fecha: fechaLocal(minima), hora: horaLocal(minima) }
      : { fecha, hora };
    const [primerNombre, ...apellidos] = nombre.trim().split(/\s+/);
    const linkUbicacion = ubicacion
      ? `https://maps.google.com/?q=${ubicacion.lat.toFixed(6)},${ubicacion.lng.toFixed(6)}`
      : null;
    const correo = user?.email || email.trim() || null;
    const direccionEntrega = {
      recoger: null,
      delivery: [direccion.trim(), linkUbicacion && `Ubicación: ${linkUbicacion}`].filter(Boolean).join(' · '),
      envio_nacional: ciudad.trim(),
    }[tipoEntrega];

    setEnviando(true);
    try {
      const respuesta = await crearPedido({
        cliente_nombre: primerNombre,
        cliente_apellido: apellidos.join(' ') || null,
        cliente_email: correo,
        cliente_telefono: limpiarCelular(celular),
        nit_ci_factura: nit.trim() || null,
        tipo_entrega: tipoEntrega,
        direccion_entrega: direccionEntrega,
        direccion_lat: tipoEntrega === 'delivery' ? ubicacion?.lat ?? null : null,
        direccion_lng: tipoEntrega === 'delivery' ? ubicacion?.lng ?? null : null,
        indicaciones_especiales: nota.trim() || null,
        metodos_pago_id: metodo.id,
        productos: cart.map(toPedidoItem),
        entrega_datetime: `${entrega.fecha} ${entrega.hora}:00`,
        // Delivery y envío se cobran al recibir
        envio_por_pagar: tipoEntrega !== 'recoger',
      });

      const pedido = respuesta?.pedido || respuesta;
      const lineas = (pedido.detalles || []).map((d) => ({
        nombre: d.nombre_producto, cantidad: d.cantidad, subtotal: d.subtotal, detalle: d.personalizacion || null,
      }));
      const confirmacion = {
        numero: pedido.numero_pedido,
        total: pedido.total ?? getTotal(),
        lineas: lineas.length > 0 ? lineas : cart.map((i) => ({
          nombre: i.nombre,
          detalle: !i.es_extra && String(i.personalizacion ?? '').trim()
            ? `${etiquetaDe(i)}: ${String(i.personalizacion).trim()}`
            : null,
          cantidad: i.cantidad,
          subtotal: (Number(i.precio ?? i.precio_minorista) || 0) * i.cantidad,
        })),
        nombre: nombre.trim(),
        celular: limpiarCelular(celular),
        email: correo,
        tipoEntrega,
        direccion: { recoger: TIENDA.direccion, delivery: direccion.trim(), envio_nacional: ciudad.trim() }[tipoEntrega],
        ubicacion: linkUbicacion,
        cuando: cuando === 'pronto'
          ? `Lo antes posible${diaPronto ? ` (desde ${diaPronto})` : ''}`
          : describirEntrega(entrega.fecha, entrega.hora),
        nota: nota.trim(),
        nit: nit.trim(),
        qr: imagenQr(qrUrl, metodo),
        conCuenta: Boolean(user),
      };
      guardarUltimoPedido(confirmacion);
      // Los avisos de un intento anterior ("Escribe tu celular"…) ya no aplican
      toast.dismiss();
      // Primero se cambia de página y después se vacía el carrito, para que
      // esta página no alcance a mostrarse vacía.
      navigate('/pedido-confirmado', { replace: true, state: confirmacion });
      clearCart();
    } catch (error) {
      const datos = error.response?.data;
      if (datos?.blocking_products) {
        toast.error(`No enviamos a otras ciudades: ${datos.blocking_products.map((p) => p.nombre).join(', ')}.`, { autoClose: 7000 });
      } else if (datos?.errors) {
        toast.error(Object.values(datos.errors).flat()[0] || 'Revisa los datos del pedido.', { autoClose: 6000 });
      } else {
        toast.error(datos?.message || 'No pudimos registrar el pedido. Inténtalo de nuevo.');
      }
    } finally {
      setEnviando(false);
    }
  };

  if (cart.length === 0) {
    return (
      <main className="pn-wrap pn-page">
        <div className="pn-empty">
          <ShoppingBag size={44} />
          <h2>Tu carrito está vacío</h2>
          <p>Agrega productos para realizar un pedido.</p>
          <Link to="/productos" className="pn-btn pn-btn--primary">Ver productos</Link>
        </div>
      </main>
    );
  }

  const total = formatCurrency(getTotal());
  const sinMetodo = metodosCargados && !metodo;
  const invalido = (campo) => (errores[campo] ? { 'aria-invalid': true, 'aria-describedby': `${campo}-error` } : {});
  const claseInput = (campo) => `pn-input${errores[campo] ? ' is-invalid' : ''}`;

  const resumen = (
    <>
      <CartLines marcarSinEnvioNacional={sinEnvioNacional.length > 0} marcarFaltantes={marcarFaltantes} />
      <div className="pn-sum mt-3">
        <div className="pn-sum__row pn-sum__row--total"><span>Total</span><span>Bs {total}</span></div>
        {tipoEntrega !== 'recoger' && <p className="pn-sum__note">El costo del envío se paga al recibir.</p>}
      </div>
    </>
  );

  const botonConfirmar = (clase = '') => (
    <button type="submit" className={`pn-btn pn-btn--primary pn-btn--lg ${clase}`} disabled={enviando || sinMetodo}>
      {enviando ? 'Enviando pedido…' : 'Confirmar pedido'}
    </button>
  );

  const opcion = (valor, actual, alElegir) => ({
    type: 'button',
    role: 'radio',
    'aria-checked': actual === valor,
    onClick: () => alElegir(valor),
  });

  return (
    <main className="pn-wrap pn-page pn-checkout">
      <Link to="/carrito" className="pn-back"><ArrowLeft size={16} /> Volver al carrito</Link>
      <h1 className="pn-page__title">Finalizar pedido</h1>
      <p className="pn-page__sub">Dos pasos y listo. El pago es con QR al final.</p>

      <form ref={formRef} onSubmit={handleSubmit} noValidate>
        <div className="pn-split">
          <div className="pn-stack">
            <details className="pn-sumtoggle d-lg-none" ref={resumenMovilRef}>
              <summary>
                <ShoppingBag size={18} /> Tu pedido ({getTotalItems()})
                <strong>Bs {total}</strong>
                <ChevronDown size={18} className="pn-sumtoggle__chev" />
              </summary>
              <div className="pn-sumtoggle__body">{resumen}</div>
            </details>

            {sinMetodo && (
              <div className="pn-notice" role="alert">
                <span>
                  En este momento no podemos recibir pagos en línea.{' '}
                  <a href={enlaceWhatsapp(WHATSAPP_TIENDA, 'Hola, quiero hacer un pedido.')} target="_blank" rel="noreferrer">Escríbenos por WhatsApp</a> y te atendemos.
                </span>
              </div>
            )}

            <section className="pn-panel" aria-labelledby="paso-entrega">
              <h2 className="pn-panel__title" id="paso-entrega"><span className="pn-step">1</span> ¿Cómo lo recibes?</h2>

              <div className="pn-seg" role="radiogroup" aria-labelledby="paso-entrega">
                <button {...opcion('recoger', tipoEntrega, setTipoEntrega)}>
                  <Store size={20} /> Recoger <small>En la tienda</small>
                </button>
                <button {...opcion('delivery', tipoEntrega, setTipoEntrega)}>
                  <Bike size={20} /> Delivery <small>Quillacollo</small>
                </button>
                <button {...opcion('envio_nacional', tipoEntrega, setTipoEntrega)} disabled={sinEnvioNacional.length > 0}>
                  <Truck size={20} /> Envío <small>Otra ciudad</small>
                </button>
              </div>
              {sinEnvioNacional.length > 0 && (
                <p className="pn-field__help mt-2">
                  No enviamos a otras ciudades: {sinEnvioNacional.map((i) => i.nombre).join(', ')}.
                </p>
              )}

              <div className="pn-entrega">
                {tipoEntrega === 'recoger' && (
                  <div className="pn-pickup">
                    <MapPin size={18} aria-hidden="true" />
                    <span>
                      {TIENDA.direccion}.{' '}
                      <a href={TIENDA.mapa} target="_blank" rel="noreferrer">Ver en el mapa</a>
                    </span>
                  </div>
                )}

                {tipoEntrega === 'delivery' && (
                  <Campo id="direccion" label="Dirección" error={errores.direccion} ayuda={ubicacionFuera ? FUERA_DE_ZONA : 'El delivery se paga al recibir.'}>
                    <textarea
                      id="direccion"
                      name="direccion"
                      className={claseInput('direccion')}
                      rows={2}
                      value={direccion}
                      onChange={(ev) => { setDireccion(ev.target.value); limpiarError('direccion'); }}
                      placeholder="Calle, número y una referencia (ej. a media cuadra de la plaza)"
                      autoComplete="street-address"
                      {...invalido('direccion')}
                    />
                    {ubicacion ? (
                      <div className={`pn-ubicacion${ubicacionFuera ? ' is-fuera' : ''}`}>
                        <LocateFixed size={16} aria-hidden="true" />
                        <span>{ubicacionFuera ? 'Ubicación fuera de Quillacollo' : 'Ubicación agregada'}</span>
                        <button type="button" onClick={() => { setUbicacion(null); limpiarError('direccion'); }} aria-label="Quitar ubicación">
                          <X size={16} />
                        </button>
                      </div>
                    ) : (
                      <button type="button" className="pn-btn pn-btn--ghost pn-btn--sm pn-ubicacion__btn" onClick={usarUbicacion} disabled={buscandoUbicacion}>
                        <LocateFixed size={16} /> {buscandoUbicacion ? 'Buscando…' : 'Usar mi ubicación'}
                      </button>
                    )}
                  </Campo>
                )}

                {tipoEntrega === 'envio_nacional' && (
                  <Campo
                    id="ciudad"
                    label="Ciudad de destino"
                    error={errores.ciudad}
                    ayuda="El envío se paga al recibir. Te escribimos para coordinar la empresa de transporte."
                  >
                    <input
                      id="ciudad"
                      name="ciudad"
                      className={claseInput('ciudad')}
                      value={ciudad}
                      onChange={(ev) => { setCiudad(ev.target.value); limpiarError('ciudad'); }}
                      placeholder="Ej. La Paz, Santa Cruz, Oruro"
                      autoComplete="address-level2"
                      {...invalido('ciudad')}
                    />
                  </Campo>
                )}
              </div>

              <h3 className="pn-subtitle" id="pregunta-cuando">{PREGUNTA_CUANDO[tipoEntrega]}</h3>
              <div className="pn-seg pn-seg--2" role="radiogroup" aria-labelledby="pregunta-cuando">
                <button {...opcion('pronto', cuando, elegirCuando)}>
                  Lo antes posible <small>{diaPronto ? `Desde ${diaPronto}` : 'Te confirmamos por WhatsApp'}</small>
                </button>
                <button {...opcion('programar', cuando, elegirCuando)}>
                  Elegir día y hora <small>Lunes a sábado</small>
                </button>
              </div>
              {cuando === 'pronto' && diaPronto && (
                <p className="pn-field__help mt-2">
                  {minutosAnticipacion(cart) > 0
                    ? `Algunos productos se preparan por encargo: estarán listos desde ${diaPronto}.`
                    : `Ahora estamos cerrados: lo preparamos desde ${diaPronto}.`}
                  {' '}Te confirmamos la hora por WhatsApp.
                </p>
              )}
              {cuando === 'programar' && (
                <div className="pn-datetime mt-3">
                  <Campo id="fecha" label="Día" error={errores.fecha}>
                    <input
                      type="date"
                      id="fecha"
                      name="fecha"
                      className={claseInput('fecha')}
                      value={fecha}
                      min={fechaLocal(minima)}
                      onChange={(ev) => cambiarFecha(ev.target.value)}
                      {...invalido('fecha')}
                    />
                  </Campo>
                  <Campo id="hora" label="Hora" error={errores.hora}>
                    <select
                      id="hora"
                      name="hora"
                      className={claseInput('hora')}
                      value={hora}
                      onChange={(ev) => { setHora(ev.target.value); limpiarError('hora'); }}
                      disabled={turnos.length === 0}
                      {...invalido('hora')}
                    >
                      {turnos.length === 0 && <option value="">Sin horarios</option>}
                      {turnos.map((t) => <option key={t} value={t}>{t}</option>)}
                    </select>
                  </Campo>
                </div>
              )}
            </section>

            <section className="pn-panel" aria-labelledby="paso-datos">
              <h2 className="pn-panel__title" id="paso-datos"><span className="pn-step">2</span> Tus datos</h2>
              <div className="pn-fields">
                <Campo id="nombre" label="Nombre" error={errores.nombre}>
                  <input
                    id="nombre"
                    name="nombre"
                    className={claseInput('nombre')}
                    value={nombre}
                    onChange={(ev) => { setNombre(ev.target.value); limpiarError('nombre'); }}
                    placeholder="Nombre y apellido"
                    autoComplete="name"
                    {...invalido('nombre')}
                  />
                </Campo>
                <Campo id="celular" label="Celular (WhatsApp)" error={errores.celular} ayuda="Te escribimos a este número para confirmar tu pedido.">
                  <input
                    id="celular"
                    name="celular"
                    type="tel"
                    inputMode="tel"
                    className={claseInput('celular')}
                    value={celular}
                    onChange={(ev) => { setCelular(ev.target.value); limpiarError('celular'); }}
                    placeholder="Ej. 71234567"
                    autoComplete="tel-national"
                    maxLength={16}
                    {...invalido('celular')}
                  />
                </Campo>
              </div>

              <details className="pn-opcional" open={masOpciones} onToggle={(ev) => setMasOpciones(ev.currentTarget.open)}>
                <summary>
                  {user ? 'Agregar una nota o datos de factura' : 'Agregar una nota, factura o correo'}
                  <ChevronDown size={18} className="pn-opcional__chev" />
                </summary>
                <div className="pn-fields pn-opcional__body">
                  <Campo id="nota" label="Nota para la panadería">
                    <textarea
                      id="nota"
                      name="nota"
                      className="pn-input"
                      rows={2}
                      value={nota}
                      onChange={(ev) => setNota(ev.target.value)}
                      placeholder="Ej. Torta para el cumpleaños de Juan"
                    />
                  </Campo>
                  <Campo id="nit" label="NIT o CI para la factura">
                    <input id="nit" name="nit" className="pn-input" value={nit} onChange={(ev) => setNit(ev.target.value)} inputMode="numeric" maxLength={20} />
                  </Campo>
                  {!user && (
                    <Campo id="email" label="Correo" error={errores.email} ayuda="Opcional. Para avisarte por correo cómo va tu pedido.">
                      <input
                        id="email"
                        name="email"
                        type="email"
                        className={claseInput('email')}
                        value={email}
                        onChange={(ev) => { setEmail(ev.target.value); limpiarError('email'); }}
                        autoComplete="email"
                        {...invalido('email')}
                      />
                    </Campo>
                  )}
                </div>
              </details>
            </section>

            <div className="pn-payinfo">
              <QrCode size={22} aria-hidden="true" />
              <span><strong>Pagas con QR.</strong> Al confirmar te mostramos el código y nos mandas el comprobante por WhatsApp.</span>
            </div>
          </div>

          <aside className="pn-split__aside d-none d-lg-block">
            <div className="pn-panel">
              <h2 className="pn-panel__title">Tu pedido</h2>
              {resumen}
              {botonConfirmar('pn-btn--block mt-3')}
            </div>
          </aside>
        </div>

        <div className="pn-paybar-spacer" aria-hidden="true" />
        <div className="pn-paybar">
          <div className="pn-paybar__total">
            <small>Total</small>
            <strong>Bs {total}</strong>
          </div>
          {botonConfirmar()}
        </div>
      </form>
    </main>
  );
};

export default Checkout;
