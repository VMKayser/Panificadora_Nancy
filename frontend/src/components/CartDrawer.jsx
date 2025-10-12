import React from 'react';
import { Offcanvas, Button, ListGroup, Image, Badge } from 'react-bootstrap';
import { useCart } from '../context/CartContext';
import { useNavigate } from 'react-router-dom';
import { toast } from 'react-toastify';

const CartDrawer = ({ show, onHide }) => {
  const navigate = useNavigate();
  const { cart, updateQuantity, removeFromCart, getTotal, getTotalItems } = useCart();

  const handleCheckout = () => {
    onHide();
    if (cart.length === 0) {
      toast.warning('El carrito está vacío');
      return;
    }
    navigate('/checkout');
  };

  return (
    <Offcanvas show={show} onHide={onHide} placement="end">
      <Offcanvas.Header closeButton style={{ borderBottom: '1px solid #ddd' }}>
        <Offcanvas.Title style={{ color: '#000', fontWeight: '600' }}>Tu Carrito ({getTotalItems()})</Offcanvas.Title>
      </Offcanvas.Header>
      <Offcanvas.Body>
        {cart.length === 0 ? (
          <div className="text-center py-4">Tu carrito está vacío</div>
        ) : (
          <div>
            <ListGroup variant="flush">
              {cart.map(item => (
                <ListGroup.Item key={item.id} className="mb-2">
                  <div className="d-flex align-items-center">
                    <Image
                      src={item.imagenes && item.imagenes.length > 0 ? (item.imagenes[0].url_imagen_completa || item.imagenes[0].url_imagen || item.imagenes[0].url) : 'https://picsum.photos/80/80'}
                      alt={item.nombre}
                      rounded
                      style={{ width: 64, height: 64, objectFit: 'cover' }}
                    />

                    <div className="ms-3 flex-grow-1">
                      <div className="fw-bold" style={{ color: '#000', fontSize: '15px' }}>{item.nombre}</div>
                      <div style={{ color: '#000', fontWeight: 'bold', fontSize: '16px', marginTop: '4px' }}>Bs {(parseFloat(item.precio_minorista) * item.cantidad).toFixed(2)}</div>
                    </div>

                    <div className="text-center">
                      <div className="d-flex align-items-center">
                        <Button 
                          size="sm" 
                          onClick={() => updateQuantity(item.id, item.cantidad - 1)}
                          style={{ backgroundColor: 'transparent', border: 'none', color: '#000', fontSize: '18px', padding: '0 8px' }}
                        >-</Button>
                        <span className="px-2" style={{ color: '#000', fontWeight: 'bold', fontSize: '16px' }}>{item.cantidad}</span>
                        <Button 
                          size="sm" 
                          onClick={() => updateQuantity(item.id, item.cantidad + 1)}
                          style={{ backgroundColor: 'transparent', border: 'none', color: '#000', fontSize: '18px', padding: '0 8px' }}
                        >+</Button>
                      </div>

                      <div className="mt-2">
                        <Button 
                          size="sm" 
                          onClick={() => removeFromCart(item.id)}
                          style={{ backgroundColor: 'transparent', border: 'none', fontSize: '16px', padding: '0' }}
                        >🗑️</Button>
                      </div>
                    </div>
                  </div>
                </ListGroup.Item>
              ))}
            </ListGroup>

            <div className="mt-3" style={{ borderTop: '1px solid #ddd', paddingTop: '15px' }}>
              <div className="d-flex justify-content-between mb-3">
                <strong style={{ color: '#000', fontSize: '18px' }}>TOTAL:</strong>
                <strong style={{ color: '#000', fontSize: '20px' }}>Bs {getTotal().toFixed(2)}</strong>
              </div>

              <Button
                variant="primary"
                className="w-100 mb-2"
                onClick={handleCheckout}
                style={{ padding: '12px', fontSize: '16px', fontWeight: '600' }}
              >
                Ir a pagar
              </Button>

              <Button 
                className="w-100" 
                onClick={onHide}
                style={{ backgroundColor: '#fff', border: '1px solid #ccc', color: '#000', padding: '10px', fontSize: '15px' }}
              >
                ← Seguir comprando
              </Button>
            </div>
          </div>
        )}
      </Offcanvas.Body>
    </Offcanvas>
  );
};

export default CartDrawer;
