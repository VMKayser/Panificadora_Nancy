import { Card, Button, Badge } from 'react-bootstrap';
import { useCart } from '../context/CartContext';

const ProductCard = ({ producto }) => {
  const { addToCart } = useCart();

  const handleAddToCart = () => {
    addToCart(producto, 1);
    // Toast notification (puedes agregar react-toastify después)
    alert(`✅ ${producto.nombre} agregado al carrito!`);
  };

  // Usar la primera imagen o una por defecto
  const imagen = producto.imagenes && producto.imagenes.length > 0
    ? producto.imagenes[0].url_imagen
    : 'https://picsum.photos/300/200';

  return (
    <Card className="h-100 shadow-sm hover-shadow" style={{ transition: 'transform 0.2s' }}>
      <Card.Img 
        variant="top" 
        src={imagen} 
        alt={producto.nombre}
        style={{ height: '200px', objectFit: 'cover' }}
      />
      <Card.Body className="d-flex flex-column">
        <Card.Title className="text-truncate">{producto.nombre}</Card.Title>
        <Card.Text className="text-muted small flex-grow-1">
          {producto.descripcion_corta || producto.descripcion}
        </Card.Text>
        
        {/* Badges */}
        <div className="mb-2">
          {producto.es_de_temporada && (
            <Badge bg="warning" text="dark" className="me-1">
              🎃 Temporada
            </Badge>
          )}
          {producto.requiere_tiempo_anticipacion && (
            <Badge bg="danger">
              ⏰ Anticipación
            </Badge>
          )}
        </div>

        {/* Precio y botón */}
        <div className="d-flex justify-content-between align-items-center mt-auto">
          <h5 className="mb-0 text-success">
            Bs. {parseFloat(producto.precio_minorista).toFixed(2)}
          </h5>
          <Button 
            variant="primary" 
            size="sm"
            onClick={handleAddToCart}
            style={{ backgroundColor: 'rgb(145, 109, 74)', borderColor: 'rgb(145, 109, 74)' }}
          >
            🛒 Añadir
          </Button>
        </div>
      </Card.Body>
    </Card>
  );
};

export default ProductCard;
