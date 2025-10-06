import PropTypes from 'prop-types';
import { useCart } from '../context/CartContext';
import '../estilos.css';

function Carousel({ productos = [] }) {
  const { addToCart } = useCart();


  const productosExtendidos = [];
  for (let i = 0; i < 20; i++) {
    productosExtendidos.push(...productos);
  }

  if (productos.length === 0) {
    return null;
  }

  const handleAddToCart = (producto) => {
    addToCart(producto);
  };

  return (
    <div className="carousel-container-custom">
      <div className="carousel-track">
        {productosExtendidos.map((producto, index) => (
          <div key={`${producto.id}-${index}`} className="carousel-card">
            <img
              src={
                (producto.imagenes && producto.imagenes.length > 0 && (producto.imagenes[0].url_imagen || producto.imagenes[0].url))
                  ? (producto.imagenes[0].url_imagen || producto.imagenes[0].url)
                  : 'https://picsum.photos/300/200'
              }
              alt={producto.nombre}
              className="carousel-card-img"
            />
            <div className="carousel-card-body">
              <h5 className="carousel-card-title">{producto.nombre}</h5>
              <p className="carousel-card-text">{producto.descripcion}</p>
              <div className="carousel-card-price">
                Bs. {producto.precio_minorista ? parseFloat(producto.precio_minorista).toFixed(2) : (producto.precio ? parseFloat(producto.precio).toFixed(2) : 'NaN')}
              </div>
              {producto.requiere_tiempo_anticipacion && (
                <div className="carousel-card-badge">
                  ⏰ *Pedido con {producto.dias_anticipacion_requeridos || 1} semana de anticipación
                </div>
              )}
              <button 
                className="carousel-add-btn"
                onClick={() => handleAddToCart(producto)}
                title="Agregar al carrito"
              >
                +
              </button>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

Carousel.propTypes = {
  productos: PropTypes.arrayOf(
    PropTypes.shape({
      id: PropTypes.number.isRequired,
      nombre: PropTypes.string.isRequired,
      descripcion: PropTypes.string,
      precio: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
      requiere_tiempo_anticipacion: PropTypes.bool,
      dias_anticipacion_requeridos: PropTypes.number,
      imagenes: PropTypes.arrayOf(
        PropTypes.shape({
          url: PropTypes.string
        })
      )
    })
  )
};

export default Carousel;
