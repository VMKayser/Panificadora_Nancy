import { useState } from 'react';
import { Modal, Form } from 'react-bootstrap';
import { Clock, MessageCircle, ShoppingBag, Wheat } from 'lucide-react';
import PropTypes from 'prop-types';
import { useCart } from '../context/CartContext';
import { toast } from 'react-toastify';
import { avisarAgregado } from '../utils/avisos';
import { formatCurrency } from '../utils/number';
import { getAvailableUnits } from '../utils/stock';
import QtyStepper from './tienda/QtyStepper';
import {
  describirPedidosHasta, enlaceConsulta, etiquetaDe, pedidosCerrados, precioPorConfirmar,
} from '../utils/temporada';

const UNIDAD_TIEMPO = {
  horas: ['hora', 'horas'],
  dias: ['día', 'días'],
  semanas: ['semana', 'semanas'],
};

const ProductModal = ({ show, onHide, producto }) => {
  const { addToCart } = useCart();
  const [cantidad, setCantidad] = useState(1);
  const [extrasSeleccionados, setExtrasSeleccionados] = useState({});
  const [imgRota, setImgRota] = useState(false);
  const [fotoActiva, setFotoActiva] = useState(0);
  const [personalizacion, setPersonalizacion] = useState('');
  const [faltaDato, setFaltaDato] = useState(false);

  // Return early si no hay producto
  if (!producto) return null;

  // Versión de 960 px que genera el backend; el original si no existe
  const fotos = producto.imagenes || [];
  const foto = fotos[fotoActiva] || fotos[0];
  const imagen = foto ? (foto.url_mediana || foto.url_imagen_completa || foto.url_imagen) : null;
  const elegirFoto = (i) => {
    setFotoActiva(i);
    setImgRota(false);
  };

  const porConfirmar = precioPorConfirmar(producto);
  const cerrado = pedidosCerrados(producto);
  const pedidosHasta = describirPedidosHasta(producto);
  const etiqueta = etiquetaDe(producto);

  const stockDisponible = getAvailableUnits(producto);

  const handleCantidadChange = (delta) => {
    setCantidad(prev => {
      const next = Math.max(1, prev + delta);
      if (stockDisponible !== null && next > stockDisponible) {
        toast.error(`Máximo disponible: ${stockDisponible} unidad${stockDisponible === 1 ? '' : 'es'}`);
        return prev;
      }
      return next;
    });
  };

  const handleExtraToggle = (extraIndex, checked) => {
    if (!checked) {
      setExtrasSeleccionados(prev => ({
        ...prev,
        [extraIndex]: 0
      }));
      return;
    }
    const extra = producto.extras_disponibles?.[extraIndex];
    const available = getAvailableUnits(extra);
    if (available !== null && available < 1) {
      toast.error(`${extra?.nombre || 'Extra'} sin stock disponible`);
      return;
    }
    setExtrasSeleccionados(prev => ({
      ...prev,
      [extraIndex]: 1
    }));
  };

  const handleExtraQuantity = (extraIndex, nextValue) => {
    const extra = producto.extras_disponibles?.[extraIndex];
    const available = getAvailableUnits(extra);
    const safeValue = Math.max(0, Math.floor(Number(nextValue) || 0));
    if (available !== null && safeValue > available) {
      toast.error(`Máximo disponible para ${extra?.nombre || 'extra'}: ${available}`);
      setExtrasSeleccionados(prev => ({ ...prev, [extraIndex]: available }));
      return;
    }
    setExtrasSeleccionados(prev => ({
      ...prev,
      [extraIndex]: safeValue
    }));
  };

  const calcularTotalExtras = () => {
    if (!producto.extras_disponibles) return 0;

    return Object.entries(extrasSeleccionados).reduce((total, [index, docenas]) => {
      const extra = producto.extras_disponibles[parseInt(index)];
      if (!extra) return total;

      // Support multiple shapes: precio_unitario or precio
      const precioUnitario = parseFloat(extra.precio_unitario ?? extra.precio ?? 0) || 0;
      const cantidadMinima = parseFloat(extra.cantidad_minima ?? extra.cantidad ?? 1) || 1;
      const unidades = parseInt(docenas) || 0;

      return total + (precioUnitario * cantidadMinima * unidades);
    }, 0);
  };

  const handleAgregarAlCarrito = () => {
    if (etiqueta && personalizacion.trim() === '') {
      setFaltaDato(true);
      document.getElementById(`pmodal-dato-${producto.id}`)?.focus();
      return;
    }

    // Comprobar stock del producto principal
    if (stockDisponible !== null && stockDisponible <= 0) {
      toast.error('Producto sin stock');
      return;
    }

    const unidadesSolicitadas = Math.max(1, Math.floor(Number(cantidad) || 1));
    if (stockDisponible !== null && unidadesSolicitadas > stockDisponible) {
      toast.error(`Cantidad solicitada supera stock disponible (${stockDisponible})`);
      return;
    }

    // Agregar producto principal
    addToCart(producto, unidadesSolicitadas, personalizacion);

    // Agregar extras seleccionados
    if (producto.extras_disponibles) {
      Object.entries(extrasSeleccionados).forEach(([index, docenas]) => {
        if (docenas > 0) {
          const extra = producto.extras_disponibles[parseInt(index)];
          if (!extra) return;

          const extraDisponible = getAvailableUnits(extra);
          const docenasInt = Math.max(0, Math.floor(Number(docenas) || 0));
          if (extraDisponible !== null && extraDisponible <= 0) {
            toast.error(`${extra.nombre} sin stock`);
            return;
          }

          if (extraDisponible !== null && docenasInt > extraDisponible) {
            toast.error(`Cantidad de extra supera stock (${extraDisponible})`);
            return;
          }

          const precioUnitario = parseFloat(extra.precio_unitario ?? extra.precio ?? 0) || 0;
          const cantidadMinima = parseFloat(extra.cantidad_minima ?? extra.cantidad ?? 1) || 1;
          const unidadExtra = extra.unidad ?? extra.unidad_medida ?? 'unidad';

          const productoExtra = {
            ...producto,
            id: `${producto.id}-extra-${index}`,
            nombre: `${producto.nombre} - ${extra.nombre}`,
            precio_minorista: precioUnitario,
            presentacion: `${cantidadMinima} ${unidadExtra}`,
            es_extra: true,
            producto_padre_id: producto.id
          };
          addToCart(productoExtra, docenasInt);
        }
      });
    }

    avisarAgregado(producto, unidadesSolicitadas);

    // Resetear y cerrar
    setCantidad(1);
    setExtrasSeleccionados({});
    setPersonalizacion('');
    setFaltaDato(false);
    onHide();
  };

  const totalExtras = calcularTotalExtras();
  const basePrice = parseFloat(producto.precio_minorista) || 0;
  const totalGeneral = (basePrice * (parseInt(cantidad) || 0)) + totalExtras;

  const descripcionCorta = String(producto.descripcion_corta || '').trim();
  const descripcion = String(producto.descripcion || '').trim();
  const tiempo = Number(producto.tiempo_anticipacion) || 24;
  const [singular, plural] = UNIDAD_TIEMPO[producto.unidad_tiempo] || UNIDAD_TIEMPO.horas;
  const unidadTiempo = tiempo === 1 ? singular : plural;

  return (
    <Modal show={show} onHide={onHide} size="lg" centered scrollable fullscreen="sm-down" className="pn-pmodal">
      <Modal.Header closeButton closeLabel="Cerrar">
        <Modal.Title as="h2">{producto.nombre}</Modal.Title>
      </Modal.Header>
      <Modal.Body>
        <div className="pn-pmodal__grid">
          <div className="pn-pmodal__galeria">
            <div className="pn-pmodal__media">
              {imagen && !imgRota ? (
                <img
                  onError={() => setImgRota(true)}
                  src={imagen}
                  alt={foto?.texto_alternativo || producto.nombre}
                  decoding="async"
                />
              ) : (
                <div className="pn-noimg"><Wheat size={56} /></div>
              )}
            </div>
            {fotos.length > 1 && (
              <div className="pn-pmodal__thumbs" role="group" aria-label="Fotos del producto">
                {fotos.map((f, i) => (
                  <button
                    key={f.id ?? i}
                    type="button"
                    aria-pressed={f === foto}
                    aria-label={f.texto_alternativo || `Foto ${i + 1}`}
                    onClick={() => elegirFoto(i)}
                  >
                    <img src={f.url_miniatura || f.url_imagen_completa || f.url_imagen} alt="" loading="lazy" decoding="async" />
                  </button>
                ))}
              </div>
            )}
          </div>

          <div className="pn-pmodal__info">
            <div>
              <p className="pn-pmodal__price">
                {porConfirmar ? 'Precio por confirmar' : `Bs ${formatCurrency(producto.precio_minorista)}`}
              </p>
              {(producto.presentacion || producto.categoria?.nombre) && (
                <div className="pn-pmodal__meta mt-2">
                  {String(producto.presentacion || '').trim() !== '' && <span className="pn-chip">{producto.presentacion}</span>}
                  {producto.categoria?.nombre && <span className="pn-chip">{producto.categoria.nombre}</span>}
                </div>
              )}
            </div>

            {porConfirmar && (
              <div className="pn-notice">
                <MessageCircle size={18} />
                <span>Estamos actualizando el precio. Escríbenos por WhatsApp y te lo confirmamos.</span>
              </div>
            )}

            {cerrado ? (
              <div className="pn-notice">
                <Clock size={18} />
                <span><strong>Pedidos cerrados:</strong> ya no recibimos pedidos de este producto por la web.</span>
              </div>
            ) : (producto.requiere_tiempo_anticipacion || pedidosHasta) && (
              <div className="pn-notice">
                <Clock size={18} />
                <span>
                  {producto.requiere_tiempo_anticipacion && (
                    <><strong>Pedido con anticipación:</strong> este producto se prepara por encargo y requiere {tiempo} {unidadTiempo}. </>
                  )}
                  {pedidosHasta && <>Recibimos pedidos hasta el <strong>{pedidosHasta}</strong>.</>}
                </span>
              </div>
            )}

            {descripcionCorta && descripcionCorta !== descripcion && <p className="pn-pmodal__lead">{descripcionCorta}</p>}
            {descripcion && <p className="pn-pmodal__desc">{descripcion}</p>}

            {etiqueta && !porConfirmar && !cerrado && (
              <div className="pn-field">
                <label htmlFor={`pmodal-dato-${producto.id}`} className="pn-field__label">{etiqueta}</label>
                <input
                  id={`pmodal-dato-${producto.id}`}
                  className={`pn-input${faltaDato ? ' is-invalid' : ''}`}
                  value={personalizacion}
                  onChange={(e) => {
                    setPersonalizacion(e.target.value);
                    if (e.target.value.trim()) setFaltaDato(false);
                  }}
                  maxLength={180}
                  autoComplete="off"
                  aria-invalid={faltaDato}
                  aria-describedby={faltaDato ? `pmodal-dato-${producto.id}-error` : undefined}
                />
                {faltaDato && (
                  <p id={`pmodal-dato-${producto.id}-error`} className="pn-field__error" role="alert">
                    Escribe «{etiqueta}» para agregarlo.
                  </p>
                )}
              </div>
            )}

            {!porConfirmar && !cerrado && (
              <div>
                <span className="pn-field-label" id="pmodal-cantidad">Cantidad</span>
                <QtyStepper
                  value={cantidad}
                  max={stockDisponible}
                  label="Cantidad"
                  onDecrease={() => handleCantidadChange(-1)}
                  onIncrease={() => handleCantidadChange(1)}
                />
                {stockDisponible !== null && (
                  <small className="d-block mt-1 text-muted">Disponibles: {stockDisponible}</small>
                )}
              </div>
            )}

            {!porConfirmar && !cerrado && producto.extras_disponibles && producto.extras_disponibles.length > 0 && (
              <div className="pn-extras">
                <h3>Extras opcionales</h3>
                <p>No están incluidos en el producto: se agregan a tu pedido y se cobran aparte.</p>
                {producto.extras_disponibles.map((extra, index) => {
                  const seleccion = extrasSeleccionados[index] || 0;
                  const disponibleExtra = getAvailableUnits(extra);
                  const precioExtra = (parseFloat(extra.precio_unitario ?? extra.precio ?? 0) || 0) * (parseFloat(extra.cantidad_minima ?? extra.cantidad ?? 1) || 1);
                  return (
                    <div key={index} className="pn-extra">
                      {extra.imagen_url ? (
                        <img src={extra.imagen_url} alt="" loading="lazy" decoding="async" />
                      ) : <span />}
                      <Form.Check
                        type="checkbox"
                        id={`extra-${producto.id}-${index}`}
                        checked={seleccion > 0}
                        onChange={(e) => handleExtraToggle(index, e.target.checked)}
                        label={
                          <span>
                            <strong>{extra.nombre}</strong>
                            {extra.descripcion && <span className="d-block small text-muted">{extra.descripcion}</span>}
                            <span className="d-block small fw-semibold" style={{ color: 'var(--pn-cafe-900)' }}>
                              Bs {formatCurrency(extra.precio_unitario ?? extra.precio ?? 0)}
                              {(extra.cantidad_minima > 1 || extra.unidad) && ` × ${extra.cantidad_minima || 1} ${extra.unidad || 'unidad'}`}
                            </span>
                          </span>
                        }
                      />
                      {seleccion > 0 && (
                        <div className="pn-extra__qty">
                          <QtyStepper
                            size="sm"
                            value={seleccion}
                            min={0}
                            max={disponibleExtra}
                            label={`Cantidad de ${extra.nombre}`}
                            onDecrease={() => handleExtraQuantity(index, seleccion - 1)}
                            onIncrease={() => handleExtraQuantity(index, seleccion + 1)}
                          />
                          <span>= Bs {formatCurrency(precioExtra * seleccion)}</span>
                        </div>
                      )}
                    </div>
                  );
                })}
                {totalExtras > 0 && (
                  <div className="pn-sum__row fw-semibold">
                    <span>Subtotal extras</span>
                    <span>Bs {formatCurrency(totalExtras)}</span>
                  </div>
                )}
              </div>
            )}
          </div>
        </div>
      </Modal.Body>
      <Modal.Footer>
        {porConfirmar ? (
          <a href={enlaceConsulta(producto)} className="pn-btn pn-btn--wa pn-btn--lg" target="_blank" rel="noopener noreferrer">
            <MessageCircle size={18} /> Consultar precio por WhatsApp
          </a>
        ) : cerrado ? (
          <button type="button" className="pn-btn pn-btn--primary pn-btn--lg" disabled>Pedidos cerrados</button>
        ) : (
          <button type="button" className="pn-btn pn-btn--primary pn-btn--lg" onClick={handleAgregarAlCarrito}>
            <ShoppingBag size={18} /> Agregar · Bs {formatCurrency(totalGeneral)}
          </button>
        )}
      </Modal.Footer>
    </Modal>
  );
};

ProductModal.propTypes = {
  show: PropTypes.bool.isRequired,
  onHide: PropTypes.func.isRequired,
  producto: PropTypes.shape({
    id: PropTypes.number,
    nombre: PropTypes.string,
    descripcion: PropTypes.string,
    descripcion_corta: PropTypes.string,
    presentacion: PropTypes.string,
    precio_minorista: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
    precio_por_confirmar: PropTypes.bool,
    pedidos_hasta: PropTypes.string,
    etiqueta_personalizacion: PropTypes.string,
    requiere_tiempo_anticipacion: PropTypes.bool,
    tiempo_anticipacion: PropTypes.number,
    unidad_tiempo: PropTypes.string,
    extras_disponibles: PropTypes.arrayOf(PropTypes.shape({
      nombre: PropTypes.string,
      descripcion: PropTypes.string,
      precio_unitario: PropTypes.number,
      precio: PropTypes.number,
      unidad: PropTypes.string,
      cantidad_minima: PropTypes.number,
    })),
    imagenes: PropTypes.arrayOf(PropTypes.shape({
      url_imagen: PropTypes.string,
      url_mediana: PropTypes.string,
      url_miniatura: PropTypes.string,
      texto_alternativo: PropTypes.string,
    })),
  }),
};

export default ProductModal;
