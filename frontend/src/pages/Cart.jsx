import { useNavigate } from 'react-router-dom';
import { Container, Row, Col, Button, ListGroup, Card, Image, Badge } from 'react-bootstrap';
import { useCart } from '../context/CartContext';
import { useSEO } from '../hooks/useSEO';

const Cart = () => {
  const navigate = useNavigate();
  const { cart, updateQuantity, removeFromCart, getTotal, getTotalItems } = useCart();

  const handleCheckout = () => {
    if (cart.length === 0) {
      alert('El carrito está vacío');
      return;
    }
    navigate('/checkout');
  };

  if (cart.length === 0) {
    // SEO: marcar como noindex cuando el carrito está vacío (no queremos indexar carritos individuales)
    useSEO({
      title: 'Carrito - Panificadora Nancy',
      description: 'Revisa tu carrito y completa tu pedido. Delivery rápido y productos frescos garantizados.',
      noindex: true
    });
    return (
      <Container className="text-center py-5">
        <div className="mb-4">
          <h2 style={{ color: '#000' }}>🛒 Tu carrito está vacío</h2>
          <p style={{ color: '#666' }}>Agrega productos para comenzar tu pedido</p>
        </div>
        <Button 
          variant="primary"
          onClick={() => navigate('/productos')}
        >
          Ver Productos
        </Button>
      </Container>
    );
  }

  return (
    // SEO: noindex para la página de carrito
    useSEO({
      title: `Carrito - Panificadora Nancy (${getTotalItems()} productos)`,
      description: 'Revisa tu carrito y completa tu pedido. Delivery rápido y productos frescos garantizados.',
      noindex: true
    }),
    <Container className="py-5">
      <h1 className="mb-4" style={{ color: '#000' }}>
        🛒 Tu Carrito ({getTotalItems()} productos)
      </h1>
      
      <Row>
        <Col lg={8}>
          <ListGroup>
            {cart.map(item => (
              <ListGroup.Item key={item.id} className="mb-3">
                <Row className="align-items-center">
                  {/* Imagen */}
                  <Col xs={3} md={2}>
                    <Image 
                      src={
                        item.imagenes && item.imagenes.length > 0
                          ? (item.imagenes[0].url_imagen_completa || item.imagenes[0].url_imagen)
                          : 'https://picsum.photos/100/100'
                      }
                      alt={item.nombre}
                      rounded
                      fluid
                    />
                  </Col>
                  
                  {/* Información */}
                  <Col xs={9} md={4}>
                    <h5 style={{ color: '#000' }}>{item.nombre}</h5>
                    <p className="mb-0" style={{ color: '#666' }}>
                      Bs {parseFloat(item.precio_minorista).toFixed(2)} c/u
                    </p>
                  </Col>

                  {/* Controles de cantidad */}
                  <Col xs={6} md={3} className="text-center mt-2 mt-md-0">
                    <div className="d-flex align-items-center justify-content-center">
                      <Button
                        size="sm"
                        onClick={() => updateQuantity(item.id, item.cantidad - 1)}
                        style={{ backgroundColor: 'transparent', border: 'none', color: '#000', fontSize: '20px', padding: '0 10px' }}
                      >
                        -
                      </Button>
                      <span className="mx-3 fw-bold" style={{ color: '#000', fontSize: '18px' }}>{item.cantidad}</span>
                      <Button
                        size="sm"
                        onClick={() => updateQuantity(item.id, item.cantidad + 1)}
                        style={{ backgroundColor: 'transparent', border: 'none', color: '#000', fontSize: '20px', padding: '0 10px' }}
                      >
                        +
                      </Button>
                    </div>
                  </Col>

                  {/* Subtotal */}
                  <Col xs={4} md={2} className="text-end mt-2 mt-md-0">
                    <h5 className="mb-0" style={{ color: '#000', fontWeight: 'bold' }}>
                      Bs {(parseFloat(item.precio_minorista) * item.cantidad).toFixed(2)}
                    </h5>
                  </Col>

                  {/* Botón eliminar */}
                  <Col xs={2} md={1} className="text-end mt-2 mt-md-0">
                    <Button
                      size="sm"
                      onClick={() => removeFromCart(item.id)}
                      style={{ backgroundColor: 'transparent', border: 'none', fontSize: '18px', padding: '0' }}
                    >
                      🗑️
                    </Button>
                  </Col>
                </Row>
              </ListGroup.Item>
            ))}
          </ListGroup>
        </Col>

        {/* Resumen del pedido */}
        <Col lg={4}>
          <Card className="sticky-top" style={{ top: '100px', border: '1px solid #ddd' }}>
            <Card.Header as="h5" style={{ backgroundColor: '#fff', color: '#000', borderBottom: '1px solid #ddd' }}>
              📋 Resumen del Pedido
            </Card.Header>
            <Card.Body>
              <div className="d-flex justify-content-between mb-2">
                <span style={{ color: '#000' }}>Subtotal:</span>
                <strong style={{ color: '#000' }}>Bs {getTotal().toFixed(2)}</strong>
              </div>
              <div className="d-flex justify-content-between mb-2">
                <span style={{ color: '#000' }}>Descuento:</span>
                <strong className="text-danger">Bs 0.00</strong>
              </div>
              <hr />
              <div className="d-flex justify-content-between mb-3">
                <h5 style={{ color: '#000' }}>TOTAL:</h5>
                <h4 style={{ color: '#000', fontWeight: 'bold' }}>Bs {getTotal().toFixed(2)}</h4>
              </div>
              
              <Button
                variant="primary"
                size="lg"
                className="w-100 mb-2"
                onClick={handleCheckout}
              >
                💳 Ir a Pagar
              </Button>
              
              <Button
                size="sm"
                className="w-100"
                onClick={() => navigate('/')}
                style={{ backgroundColor: '#fff', border: '1px solid #ccc', color: '#000' }}
              >
                ← Seguir comprando
              </Button>
            </Card.Body>
          </Card>
        </Col>
      </Row>
    </Container>
  );
};

export default Cart;
