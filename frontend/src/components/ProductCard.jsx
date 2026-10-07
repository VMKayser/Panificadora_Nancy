import { useEffect, useRef, useState, memo } from 'react';
import PropTypes from 'prop-types';
import { Check, Clock, MessageCircle, Plus, Wheat } from 'lucide-react';
import { toast } from 'react-toastify';
import { avisarAgregado } from '../utils/avisos';
import { useCart } from '../context/CartContext';
import { formatCurrency } from '../utils/number';
import { getAvailableUnits } from '../utils/stock';
import { enlaceConsulta, etiquetaDe, pedidosCerrados, precioPorConfirmar } from '../utils/temporada';
import ProductModal from './ProductModal';

const UNIDADES = { horas: 'h', dias: 'días', semanas: 'sem.' };

const ProductCard = ({ producto }) => {
  const { addToCart } = useCart();
  const [showModal, setShowModal] = useState(false);
  const [agregado, setAgregado] = useState(false);
  const [imgRota, setImgRota] = useState(false);
  const timer = useRef(null);

  useEffect(() => () => clearTimeout(timer.current), []);

  // Respeta la regla de stock de la web (utils/stock): hoy los pedidos web no
  // se limitan por stock, así que la tarjeta nunca se bloquea.
  const disponible = getAvailableUnits(producto);
  const agotado = disponible !== null && disponible <= 0;
  const porConfirmar = precioPorConfirmar(producto);
  const cerrado = pedidosCerrados(producto);

  // Versiones reducidas que genera el backend (480 y 960 px); el original si no existen
  const foto = producto.imagenes?.[0];
  const imagen = foto ? (foto.url_miniatura || foto.url_imagen_completa || foto.url_imagen) : null;
  const srcSet = foto?.url_miniatura && foto?.url_mediana && foto.url_miniatura !== foto.url_mediana
    ? `${foto.url_miniatura} 480w, ${foto.url_mediana} 960w`
    : undefined;

  const anticipacion = producto.requiere_tiempo_anticipacion
    ? `${producto.tiempo_anticipacion || 24} ${UNIDADES[producto.unidad_tiempo] || 'h'}`
    : null;

  const handleAdd = () => {
    if (agotado) {
      toast.error('Sin stock');
      return;
    }
    // El dato que pide el producto (ej. nombre del difunto) se escribe en el detalle
    if (etiquetaDe(producto)) {
      setShowModal(true);
      return;
    }
    addToCart(producto, 1);
    avisarAgregado(producto, 1);
    setAgregado(true);
    clearTimeout(timer.current);
    timer.current = setTimeout(() => setAgregado(false), 1400);
  };

  const abrir = () => setShowModal(true);

  return (
    <>
      <article className="pn-card">
        <button type="button" className="pn-card__media" onClick={abrir} aria-label={`Ver ${producto.nombre}`}>
          {imagen && !imgRota ? (
            <img
              src={imagen}
              srcSet={srcSet}
              sizes="(min-width: 1200px) 270px, (min-width: 768px) 30vw, 50vw"
              alt=""
              loading="lazy"
              decoding="async"
              onError={() => setImgRota(true)}
            />
          ) : (
            <span className="pn-noimg"><Wheat size={36} /></span>
          )}
          {(anticipacion || agotado || cerrado) && (
            <span className="pn-card__tags">
              {cerrado ? (
                <span className="pn-chip">Pedidos cerrados</span>
              ) : anticipacion && (
                <span className="pn-chip pn-chip--aviso"><Clock size={12} /> Pedir con {anticipacion}</span>
              )}
              {agotado && <span className="pn-chip">Agotado</span>}
            </span>
          )}
        </button>

        <div className="pn-card__body">
          <h3 className="m-0">
            <button type="button" className="pn-card__name" onClick={abrir}>{producto.nombre}</button>
          </h3>
          {String(producto.presentacion ?? '').trim() !== '' && (
            <p className="pn-card__meta">{producto.presentacion}</p>
          )}
          <div className="pn-card__foot">
            {porConfirmar ? (
              <>
                <span className="pn-price pn-price--consulta">Precio por confirmar</span>
                <a
                  href={enlaceConsulta(producto)}
                  className="pn-add pn-add--wa"
                  target="_blank"
                  rel="noopener noreferrer"
                  aria-label={`Consultar el precio de ${producto.nombre} por WhatsApp`}
                >
                  <MessageCircle size={20} />
                </a>
              </>
            ) : (
              <>
                <span className="pn-price">
                  <small>Bs</small>{formatCurrency(producto.precio_minorista ?? producto.precio ?? 0)}
                </span>
                <button
                  type="button"
                  className={`pn-add${agregado ? ' is-done' : ''}`}
                  onClick={handleAdd}
                  disabled={agotado || cerrado}
                  aria-label={`Agregar ${producto.nombre} al carrito`}
                >
                  {agregado ? <Check size={20} /> : <Plus size={20} />}
                </button>
              </>
            )}
          </div>
        </div>
      </article>

      <ProductModal show={showModal} onHide={() => setShowModal(false)} producto={producto} />
    </>
  );
};

ProductCard.propTypes = {
  producto: PropTypes.shape({
    id: PropTypes.number,
    nombre: PropTypes.string,
    presentacion: PropTypes.string,
    precio: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
    precio_minorista: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
    precio_por_confirmar: PropTypes.bool,
    pedidos_hasta: PropTypes.string,
    etiqueta_personalizacion: PropTypes.string,
    requiere_tiempo_anticipacion: PropTypes.bool,
    tiempo_anticipacion: PropTypes.number,
    unidad_tiempo: PropTypes.string,
    imagenes: PropTypes.array,
  }).isRequired,
};

export default memo(ProductCard);
