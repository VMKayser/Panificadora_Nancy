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

  // Group parents and extras
  const parents = cart.filter(i => !i.es_extra);
  const extras = cart.filter(i => i.es_extra);

  const extrasByParent = extras.reduce((acc, ex) => {
    const key = ex.producto_padre_id || String(ex.id).split('-extra-')[0];
    if (!acc[key]) acc[key] = [];
    acc[key].push(ex);
    return acc;
  }, {});

  const parentTotal = (parent) => {
    const pPrice = (parent.precio !== undefined) ? parseFloat(parent.precio) : parseFloat(parent.precio_minorista || 0);
    const pQty = parent.cantidad || 0;
    const extrasForParent = extrasByParent[parent.id] || [];
    const extrasTotal = extrasForParent.reduce((s, e) => {
      const ePrice = (e.precio !== undefined) ? parseFloat(e.precio) : parseFloat(e.precio_minorista || 0);
      return s + ((isNaN(ePrice) ? 0 : ePrice) * (e.cantidad || 0));
    }, 0);
    return ( (isNaN(pPrice) ? 0 : pPrice) * pQty ) + extrasTotal;
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
            {parents.map(parent => (
              <ListGroup.Item key={parent.id} className="mb-3">
                <Row className="align-items-center">
                  {/* Imagen */}
                  <Col xs={3} md={2}>
                    <Image 
                      src={
                        parent.imagenes && parent.imagenes.length > 0
                          ? (parent.imagenes[0].url_imagen_completa || parent.imagenes[0].url_imagen)
                          : 'https://picsum.photos/100/100'
                      }
                      srcSet={parent.imagenes && parent.imagenes.length > 0 ? `${parent.imagenes[0].url_imagen || parent.imagenes[0].url_imagen_completa || ''} 300w, ${parent.imagenes[0].url_imagen_completa || parent.imagenes[0].url_imagen || ''} 800w` : undefined}
                      sizes="(max-width: 600px) 40vw, 100px"
                      alt={parent.nombre}
                      rounded
                      fluid
                      loading="lazy"
                      decoding="async"
                    />
                  </Col>
                  
                  {/* Información */}
                  <Col xs={9} md={4}>
                    <h5 style={{ color: '#000' }}>{parent.nombre}</h5>
                    <p className="mb-0" style={{ color: '#666' }}>
                      Bs {(parseFloat(String(parent.precio_minorista ?? parent.precio ?? 0)) || 0).toFixed(2)} c/u
                    </p>
                  </Col>

                  {/* Controles de cantidad */}
                  <Col xs={6} md={3} className="text-center mt-2 mt-md-0">
                    <div className="d-flex align-items-center justify-content-center">
                      <Button
                        size="sm"
                        onClick={() => updateQuantity(parent.id, parent.cantidad - 1)}
                        style={{ backgroundColor: 'transparent', border: 'none', color: '#000', fontSize: '20px', padding: '0 10px' }}
                      >
                        -
                      </Button>
                      <span className="mx-3 fw-bold" style={{ color: '#000', fontSize: '18px' }}>{parent.cantidad}</span>
                      <Button
                        size="sm"
                        onClick={() => updateQuantity(parent.id, parent.cantidad + 1)}
                        style={{ backgroundColor: 'transparent', border: 'none', color: '#000', fontSize: '20px', padding: '0 10px' }}
                      >
                        +
                      </Button>
                    </div>
                  </Col>

                  {/* Subtotal */}
                  <Col xs={4} md={2} className="text-end mt-2 mt-md-0">
                    <h5 className="mb-0" style={{ color: '#000', fontWeight: 'bold' }}>
                      Bs {( (parentTotal(parent)) ).toFixed(2)}
                    </h5>
                  </Col>

                  {/* Botón eliminar */}
                  <Col xs={2} md={1} className="text-end mt-2 mt-md-0">
                    <Button
                      size="sm"
                      onClick={() => removeFromCart(parent.id)}
                      style={{ backgroundColor: 'transparent', border: 'none', fontSize: '18px', padding: '0' }}
                    >
                      🗑️
                    </Button>
                  </Col>
                </Row>

                {/* Extras for this parent */}
                {(extrasByParent[parent.id] || []).map(ex => (
                  <Row key={ex.id} className="align-items-center mt-2" style={{ marginLeft: '40px' }}>
                    <Col xs={8} md={6}>
                      <div style={{ color: '#555' }}>{ex.nombre || ex.producto?.nombre} <small style={{ color: '#888' }}>(extra)</small></div>
                    </Col>
                    <Col xs={4} md={6} className="text-end">
                      <div className="d-flex align-items-center justify-content-end">
                        <Button size="sm" onClick={() => updateQuantity(ex.id, ex.cantidad - 1)} style={{ backgroundColor: 'transparent', border: 'none', color: '#000' }}>-</Button>
                        <span className="px-2" style={{ color: '#000', fontWeight: 'bold' }}>{ex.cantidad}</span>
                        <Button size="sm" onClick={() => updateQuantity(ex.id, ex.cantidad + 1)} style={{ backgroundColor: 'transparent', border: 'none', color: '#000' }}>+</Button>
                        <Button size="sm" onClick={() => removeFromCart(ex.id)} style={{ backgroundColor: 'transparent', border: 'none', color: '#000' }}>🗑️</Button>
                      </div>
                    </Col>
                  </Row>
                ))}
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
