import { useState } from 'react';
import { Card } from 'react-bootstrap';
import { useCart } from '../context/CartContext';
import { toast } from 'react-toastify';
import ProductModal from './ProductModal';
import styles from './ProductCard.module.css';

const ProductCard = ({ producto }) => {
  const { addToCart } = useCart();
  const [showModal, setShowModal] = useState(false);

  const handleAddToCart = (e) => {
    e.stopPropagation(); // Evitar que se abra el modal
    addToCart(producto, 1);
    toast.success(`✅ ${producto.nombre} agregado al carrito!`, {
      position: "bottom-right",
      autoClose: 2000,
    });
  };

  const handleCardClick = () => {
    setShowModal(true);
  };

  // Usar la primera imagen o una por defecto
  const imagen = producto.imagenes && producto.imagenes.length > 0
    ? producto.imagenes[0].url_imagen
    : 'https://picsum.photos/300/200';

  // Usar descripción corta, o truncar la descripción larga
  const descripcionMostrar = producto.descripcion_corta || 
    (producto.descripcion?.length > 100 
      ? producto.descripcion.substring(0, 100) + '...' 
      : producto.descripcion);

  return (
    <>
      <div className={styles.card} onClick={handleCardClick} style={{ cursor: 'pointer' }}>
        <img
          src={imagen}
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
              Bs. {parseFloat(producto.precio_minorista).toFixed(2)}
            </div>
            <button 
              className={styles.addBtn}
              onClick={handleAddToCart}
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

      <ProductModal
        show={showModal}
        onHide={() => setShowModal(false)}
        producto={producto}
      />
    </>
  );
};

export default ProductCard;
