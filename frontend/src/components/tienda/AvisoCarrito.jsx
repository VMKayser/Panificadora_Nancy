import PropTypes from 'prop-types';
import { Link } from 'react-router-dom';
import { Check, Wheat } from 'lucide-react';

// Contenido del aviso "agregado al carrito": foto, nombre y acceso al carrito.
// react-toastify le pasa closeToast al renderizarlo.
const AvisoCarrito = ({ producto, cantidad, closeToast }) => {
  const img = producto.imagenes?.[0];
  const foto = img?.url_miniatura || img?.url_imagen_completa || img?.url_imagen;

  return (
    <div className="pn-aviso">
      <span className="pn-aviso__img" aria-hidden="true">
        {foto ? <img src={foto} alt="" /> : <Wheat size={20} />}
        <span className="pn-aviso__ok"><Check size={12} strokeWidth={3} /></span>
      </span>
      <span className="pn-aviso__txt">
        <small>Agregado al carrito</small>
        <strong>{cantidad > 1 ? `${cantidad} × ` : ''}{producto.nombre}</strong>
      </span>
      <Link to="/carrito" className="pn-aviso__cta" onClick={closeToast}>Ver carrito</Link>
    </div>
  );
};

AvisoCarrito.propTypes = {
  producto: PropTypes.shape({
    nombre: PropTypes.string,
    imagenes: PropTypes.array,
  }).isRequired,
  cantidad: PropTypes.number.isRequired,
  closeToast: PropTypes.func,
};

export default AvisoCarrito;
