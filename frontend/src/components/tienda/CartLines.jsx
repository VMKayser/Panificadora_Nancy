import { useId, useState } from 'react';
import PropTypes from 'prop-types';
import { Trash2, Wheat } from 'lucide-react';
import { useCart } from '../../context/CartContext';
import { formatCurrency } from '../../utils/number';
import { getAvailableUnits } from '../../utils/stock';
import QtyStepper from './QtyStepper';
import { agruparCarrito, imagenDe, precioDe } from '../../utils/carrito';
import { etiquetaDe, faltaPersonalizacion } from '../../utils/temporada';

const Miniatura = ({ item }) => {
  const src = imagenDe(item);
  const [rota, setRota] = useState(false);
  return (
    <div className="pn-line__img">
      {src && !rota ? (
        <img src={src} alt="" loading="lazy" decoding="async" onError={() => setRota(true)} />
      ) : (
        <div className="pn-noimg"><Wheat size={22} /></div>
      )}
    </div>
  );
};

Miniatura.propTypes = { item: PropTypes.object.isRequired };

// Lista de productos del carrito. La usan el cajón lateral, la página del
// carrito y el resumen del checkout, para que se vean y funcionen igual.
// marcarFaltantes: el checkout lo activa al intentar enviar, para resaltar
// los productos a los que les falta el dato que piden.
const CartLines = ({ marcarSinEnvioNacional = false, marcarFaltantes = false }) => {
  const { cart, updateQuantity, removeFromCart, setPersonalizacion } = useCart();
  const { padres, extrasPorPadre } = agruparCarrito(cart);
  // El checkout dibuja la lista dos veces (móvil y escritorio): ids únicos por lista
  const base = useId();

  return (
    <ul className="pn-lines">
      {padres.map((item) => {
        const extras = extrasPorPadre[item.id] || [];
        const disponible = getAvailableUnits(item);
        const totalLinea = precioDe(item) * (item.cantidad || 0)
          + extras.reduce((s, ex) => s + precioDe(ex) * (ex.cantidad || 0), 0);
        const etiqueta = etiquetaDe(item);
        const falta = marcarFaltantes && faltaPersonalizacion(item);
        const idDato = `${base}-dato-${item.id}`;

        return (
          <li key={item.id} className="pn-line">
            <Miniatura item={item} />
            <div>
              <p className="pn-line__name">{item.nombre}</p>
              <span className="pn-line__unit">Bs {formatCurrency(precioDe(item))} c/u</span>
              {marcarSinEnvioNacional && item.permite_envio_nacional === false && (
                <div><span className="pn-chip pn-chip--aviso pn-line__flag">Sin envío nacional</span></div>
              )}
            </div>
            <span className="pn-line__total">Bs {formatCurrency(totalLinea)}</span>

            {etiqueta && (
              <div className="pn-line__dato">
                <label htmlFor={idDato} className="pn-field__label">{etiqueta}</label>
                <input
                  id={idDato}
                  className={`pn-input${falta ? ' is-invalid' : ''}`}
                  value={item.personalizacion || ''}
                  onChange={(e) => setPersonalizacion(item.id, e.target.value)}
                  maxLength={180}
                  autoComplete="off"
                  data-dato={item.id}
                  aria-invalid={falta}
                  aria-describedby={falta ? `${idDato}-error` : undefined}
                />
                {falta && (
                  <p id={`${idDato}-error`} className="pn-field__error" role="alert">Escribe «{etiqueta}».</p>
                )}
              </div>
            )}

            <div className="pn-line__controls">
              <QtyStepper
                size="sm"
                value={item.cantidad}
                max={disponible}
                label={`Cantidad de ${item.nombre}`}
                onDecrease={() => updateQuantity(item.id, item.cantidad - 1)}
                onIncrease={() => updateQuantity(item.id, item.cantidad + 1)}
              />
              <button type="button" className="pn-line__remove" onClick={() => removeFromCart(item.id)}>
                <Trash2 size={16} /> Quitar
              </button>
            </div>

            {extras.length > 0 && (
              <ul className="pn-line__extras" aria-label={`Extras de ${item.nombre}`}>
                {extras.map((ex) => (
                  <li key={ex.id} className="pn-line__extra">
                    <span className="pn-line__extra-name">
                      {String(ex.nombre).replace(`${item.nombre} - `, '')}
                      {' '}<small className="text-muted">Bs {formatCurrency(precioDe(ex) * (ex.cantidad || 0))}</small>
                    </span>
                    <QtyStepper
                      size="sm"
                      value={ex.cantidad}
                      max={getAvailableUnits(ex)}
                      label={`Cantidad de ${ex.nombre}`}
                      onDecrease={() => updateQuantity(ex.id, ex.cantidad - 1)}
                      onIncrease={() => updateQuantity(ex.id, ex.cantidad + 1)}
                    />
                    <button type="button" className="pn-line__remove" onClick={() => removeFromCart(ex.id)} aria-label={`Quitar ${ex.nombre}`}>
                      <Trash2 size={16} />
                    </button>
                  </li>
                ))}
              </ul>
            )}
          </li>
        );
      })}
    </ul>
  );
};

CartLines.propTypes = { marcarSinEnvioNacional: PropTypes.bool, marcarFaltantes: PropTypes.bool };

export default CartLines;
