import { useState, memo } from 'react';
import PropTypes from 'prop-types';
import { useCart } from '../context/CartContext';
import ProductModal from './ProductModal';
import { Package, Clock, Plus } from 'lucide-react';
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
    const available = producto?.inventario?.stock_actual ?? producto?.stock_actual ?? producto?.stock ?? null;
    if (available !== null && Number(available) <= 0) {
      // Mostrar mensaje y no intentar agregar
      return;
    }
    addToCart(producto);
  };

  const handleCardClick = (producto) => {
    setSelectedProducto(producto);
    setShowModal(true);
  };

  // Triplicar los productos para carrusel verdaderamente infinito y sin cortes
  const productosExtendidos = [...productos, ...productos, ...productos];

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
                  (producto.imagenes && producto.imagenes.length > 0)
                    ? (producto.imagenes[0].url_imagen_completa || producto.imagenes[0].url_imagen || producto.imagenes[0].url)
                    : 'https://picsum.photos/300/200'
                }
                alt={producto.nombre}
                className={styles.cardImg}
              />
              <div className={styles.cardBody}>
                <h5 className={styles.title}>{producto.nombre}</h5>
                {String(producto.presentacion ?? '').trim() !== '' && (
                  <p className={styles.presentation}>
                    <Package size={14} style={{ marginRight: '4px', verticalAlign: 'middle' }} />
                    {producto.presentacion}
                  </p>
                )}
              <p className={styles.text}>{descripcionMostrar}</p>
              <div className={styles.price}>
                <div className={styles.priceH5}>
                  Bs. {(parseFloat(String(producto.precio_minorista ?? producto.precio ?? 0)) || 0).toFixed(2)}
                </div>
                { (producto?.inventario?.stock_actual ?? producto?.stock_actual ?? producto?.stock ?? null) <= 0 ? (
                  <button className={styles.addBtn} disabled title="Sin stock">
                    <span style={{ fontSize: 12, fontWeight: 700, color: '#fff' }}>Sin stock</span>
                  </button>
                ) : (
                  <button 
                    className={styles.addBtn}
                    onClick={(e) => handleAddToCart(e, producto)}
                    title="Agregar al carrito"
                  >
                    <Plus size={20} />
                  </button>
                )}
              </div>
              {producto.requiere_tiempo_anticipacion && (
                <div className={styles.badge}>
                  <Clock size={12} style={{ marginRight: '4px', verticalAlign: 'middle' }} />
                  *Pedido con {producto.tiempo_anticipacion || 24} {producto.unidad_tiempo || 'horas'} de anticipación
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

export default memo(Carousel);
