import { useState } from 'react';
import PropTypes from 'prop-types';
import { useCart } from '../context/CartContext';
import ProductModal from './ProductModal';
import styles from './Carousel.module.css';

function Carousel({ productos = [], speed = 40 }) {
  const { addToCart } = useCart();
  const [showModal, setShowModal] = useState(false);
  const [selectedProducto, setSelectedProducto] = useState(null);

  if (productos.length === 0) {
    return null;
  }

  const handleAddToCart = (e, producto) => {
    e.stopPropagation(); // Evitar que se abra el modal
    addToCart(producto);
  };

  const handleCardClick = (producto) => {
    setSelectedProducto(producto);
    setShowModal(true);
  };

  // Solo 2 copias como requiere el usuario
  const productosExtendidos = [...productos, ...productos];

  return (
    <>
      <div className={styles.containerCustom}>
        <div 
          className={styles.track}
          style={{ '--speed': `${speed}s` }}
        >
          {productosExtendidos.map((producto, index) => {
            // Usar descripción corta, o truncar la descripción larga
            const descripcionMostrar = producto.descripcion_corta || 
              (producto.descripcion?.length > 100 
                ? producto.descripcion.substring(0, 100) + '...' 
                : producto.descripcion);

            return (
            <div 
              key={`${producto.id}-${index}`} 
              className={styles.card}
              onClick={() => handleCardClick(producto)}
              style={{ cursor: 'pointer' }}
            >
              <img
                src={
                  (producto.imagenes && producto.imagenes.length > 0 && (producto.imagenes[0].url_imagen || producto.imagenes[0].url))
                    ? (producto.imagenes[0].url_imagen || producto.imagenes[0].url)
                    : 'https://picsum.photos/300/200'
                }
                alt={producto.nombre}
                className={styles.cardImg}
              />
              <div className={styles.cardBody}>
                <h5 className={styles.title}>{producto.nombre}</h5>
                {producto.presentacion && (
                  <p className={styles.presentation}>📦 {producto.presentacion}</p>
                )}
              <p className={styles.text}>{descripcionMostrar}</p>
              <div className={styles.price}>
                <div className={styles.priceH5}>
                  Bs. {producto.precio_minorista ? parseFloat(producto.precio_minorista).toFixed(2) : (producto.precio ? parseFloat(producto.precio).toFixed(2) : 'NaN')}
                </div>
                <button 
                  className={styles.addBtn}
                  onClick={(e) => handleAddToCart(e, producto)}
                  title="Agregar al carrito"
                >
                  +
                </button>
              </div>
              {producto.requiere_tiempo_anticipacion && (
                <div className={styles.badge}>
                  ⏰ *Pedido con {producto.tiempo_anticipacion || 24} {producto.unidad_tiempo || 'horas'} de anticipación
                </div>
              )}
            </div>
          </div>
            );
          })}
      </div>
    </div>

    <ProductModal
      show={showModal}
      onHide={() => setShowModal(false)}
      producto={selectedProducto}
    />
  </>
  );
}

Carousel.propTypes = {
  productos: PropTypes.arrayOf(
    PropTypes.shape({
      id: PropTypes.number.isRequired,
      nombre: PropTypes.string.isRequired,
      descripcion: PropTypes.string,
      presentacion: PropTypes.string,
      precio: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
      precio_minorista: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
      requiere_tiempo_anticipacion: PropTypes.bool,
      tiempo_anticipacion: PropTypes.number,
      unidad_tiempo: PropTypes.string,
      imagenes: PropTypes.arrayOf(
        PropTypes.shape({
          url: PropTypes.string,
          url_imagen: PropTypes.string
        })
      )
    })
  ),
  speed: PropTypes.number
};

export default Carousel;
