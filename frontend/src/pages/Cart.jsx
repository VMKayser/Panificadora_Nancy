import { useNavigate } from 'react-router-dom';
import { Container, Row, Col, Button, ListGroup, Card, Image, Badge } from 'react-bootstrap';
import { useCart } from '../context/CartContext';

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
    return (
      <Container className="text-center py-5">
        <div className="mb-4">
          <h2>🛒 Tu carrito está vacío</h2>
          <p className="text-muted">Agrega productos para comenzar tu pedido</p>
        </div>
        <Button 
          variant="primary"
          onClick={() => navigate('/productos')}
          style={{ backgroundColor: 'rgb(145, 109, 74)', borderColor: 'rgb(145, 109, 74)' }}
        >
          Ver Productos
        </Button>
      </Container>
    );
  }

  return (
    <Container className="py-5">
      <h1 className="mb-4">
        🛒 Tu Carrito 
        <Badge bg="primary" className="ms-3">{getTotalItems()} productos</Badge>
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
                          ? item.imagenes[0].url_imagen
                          : 'https://picsum.photos/100/100'
                      }
                      alt={item.nombre}
                      rounded
                      fluid
                    />
                  </Col>
                  
                  {/* Información */}
                  <Col xs={9} md={4}>
                    <h5>{item.nombre}</h5>
                    <p className="text-muted mb-0">
                      Bs. {parseFloat(item.precio_minorista).toFixed(2)} c/u
                    </p>
                  </Col>

                  {/* Controles de cantidad */}
                  <Col xs={6} md={3} className="text-center mt-2 mt-md-0">
                    <div className="d-flex align-items-center justify-content-center">
                      <Button
                        variant="outline-secondary"
                        size="sm"
                        onClick={() => updateQuantity(item.id, item.cantidad - 1)}
                      >
                        -
                      </Button>
                      <span className="mx-3 fw-bold">{item.cantidad}</span>
                      <Button
                        variant="outline-secondary"
                        size="sm"
                        onClick={() => updateQuantity(item.id, item.cantidad + 1)}
                      >
                        +
                      </Button>
                    </div>
                  </Col>

                  {/* Subtotal */}
                  <Col xs={4} md={2} className="text-end mt-2 mt-md-0">
                    <h5 className="text-success mb-0">
                      Bs. {(parseFloat(item.precio_minorista) * item.cantidad).toFixed(2)}
                    </h5>
                  </Col>

                  {/* Botón eliminar */}
                  <Col xs={2} md={1} className="text-end mt-2 mt-md-0">
                    <Button
                      variant="danger"
                      size="sm"
                      onClick={() => removeFromCart(item.id)}
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
          <Card className="sticky-top" style={{ top: '100px' }}>
            <Card.Header as="h5" className="bg-primary text-white">
              📋 Resumen del Pedido
            </Card.Header>
            <Card.Body>
              <div className="d-flex justify-content-between mb-2">
                <span>Subtotal:</span>
                <strong>Bs. {getTotal().toFixed(2)}</strong>
              </div>
              <div className="d-flex justify-content-between mb-2">
                <span>Descuento:</span>
                <strong className="text-danger">Bs. 0.00</strong>
              </div>
              <hr />
              <div className="d-flex justify-content-between mb-3">
                <h5>TOTAL:</h5>
                <h4 className="text-success">Bs. {getTotal().toFixed(2)}</h4>
              </div>
              
              <Button
                variant="success"
                size="lg"
                className="w-100 mb-2"
                onClick={handleCheckout}
              >
                💳 Ir a Pagar
              </Button>
              
              <Button
                variant="outline-secondary"
                size="sm"
                className="w-100"
                onClick={() => navigate('/')}
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
