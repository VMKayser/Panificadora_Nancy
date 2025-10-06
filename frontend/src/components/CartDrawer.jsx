import React from 'react';
import { Offcanvas, Button, ListGroup, Image, Badge } from 'react-bootstrap';
import { useCart } from '../context/CartContext';
import { useNavigate } from 'react-router-dom';

const CartDrawer = ({ show, onHide }) => {
  const navigate = useNavigate();
  const { cart, updateQuantity, removeFromCart, getTotal, getTotalItems } = useCart();

  const handleCheckout = () => {
    onHide();
    if (cart.length === 0) return alert('El carrito está vacío');
    navigate('/checkout');
  };

  return (
    <Offcanvas show={show} onHide={onHide} placement="end">
      <Offcanvas.Header closeButton>
        <Offcanvas.Title>🛒 Tu Carrito <Badge bg="primary" className="ms-2">{getTotalItems()}</Badge></Offcanvas.Title>
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
                      src={item.imagenes && item.imagenes.length > 0 ? (item.imagenes[0].url_imagen || item.imagenes[0].url) : 'https://picsum.photos/80/80'}
                      alt={item.nombre}
                      rounded
                      style={{ width: 64, height: 64, objectFit: 'cover' }}
                    />

                    <div className="ms-3 flex-grow-1">
                      <div className="fw-bold">{item.nombre}</div>
                      <div className="text-success">Bs. {(parseFloat(item.precio_minorista) * item.cantidad).toFixed(2)}</div>
                      <div className="small text-muted">Bs. {parseFloat(item.precio_minorista).toFixed(2)} c/u</div>
                    </div>

                    <div className="text-center">
                      <div className="d-flex align-items-center">
                        <Button variant="outline-secondary" size="sm" onClick={() => updateQuantity(item.id, item.cantidad - 1)}>-</Button>
                        <span className="px-2">{item.cantidad}</span>
                        <Button variant="outline-secondary" size="sm" onClick={() => updateQuantity(item.id, item.cantidad + 1)}>+</Button>
                      </div>

                      <div className="mt-2">
                        <Button variant="light" size="sm" onClick={() => removeFromCart(item.id)}>🗑️</Button>
                      </div>
                    </div>
                  </div>
                </ListGroup.Item>
              ))}
            </ListGroup>

            <div className="mt-3">
              <div className="d-flex justify-content-between mb-2">
                <strong>TOTAL:</strong>
                <strong className="text-success">Bs. {getTotal().toFixed(2)}</strong>
              </div>

              <Button
                variant="primary"
                className="w-100 mb-2"
                style={{ backgroundColor: 'rgb(145, 109, 74)', borderColor: 'rgb(145, 109, 74)' }}
                onClick={handleCheckout}
              >
                Ir a pagar
              </Button>

              <Button variant="outline-secondary" className="w-100" onClick={onHide}>
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
