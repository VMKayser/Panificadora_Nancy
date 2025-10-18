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

  // Group parents and extras for drawer
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
              {parents.map(parent => (
                <ListGroup.Item key={parent.id} className="mb-2">
                  <div className="d-flex align-items-center">
                    <Image
                      src={parent.imagenes && parent.imagenes.length > 0 ? (parent.imagenes[0].url_imagen_completa || parent.imagenes[0].url_imagen || parent.imagenes[0].url) : 'https://picsum.photos/80/80'}
                      alt={parent.nombre}
                      rounded
                      style={{ width: 64, height: 64, objectFit: 'cover' }}
                    />

                    <div className="ms-3 flex-grow-1">
                      <div className="fw-bold" style={{ color: '#000', fontSize: '15px' }}>{parent.nombre}</div>
                      <div style={{ color: '#000', fontWeight: 'bold', fontSize: '16px', marginTop: '4px' }}>Bs {( (parentTotal(parent)) ).toFixed(2)}</div>
                    </div>

                    <div className="text-center">
                      <div className="d-flex align-items-center">
                        <Button 
                          size="sm" 
                          onClick={() => updateQuantity(parent.id, parent.cantidad - 1)}
                          style={{ backgroundColor: 'transparent', border: 'none', color: '#000', fontSize: '18px', padding: '0 8px' }}
                        >-</Button>
                        <span className="px-2" style={{ color: '#000', fontWeight: 'bold', fontSize: '16px' }}>{parent.cantidad}</span>
                        <Button 
                          size="sm" 
                          onClick={() => updateQuantity(parent.id, parent.cantidad + 1)}
                          style={{ backgroundColor: 'transparent', border: 'none', color: '#000', fontSize: '18px', padding: '0 8px' }}
                        >+</Button>
                      </div>

                      <div className="mt-2">
                        <Button 
                          size="sm" 
                          onClick={() => removeFromCart(parent.id)}
                          style={{ backgroundColor: 'transparent', border: 'none', fontSize: '16px', padding: '0' }}
                        >🗑️</Button>
                      </div>
                    </div>
                  </div>

                  {/* Extras */}
                  {(extrasByParent[parent.id] || []).map(ex => (
                    <div key={ex.id} className="d-flex align-items-center mt-2" style={{ marginLeft: 56 }}>
                      <Image
                        src={ex.imagenes && ex.imagenes.length > 0 ? (ex.imagenes[0].url_imagen_completa || ex.imagenes[0].url_imagen || ex.imagenes[0].url) : 'https://picsum.photos/60/60'}
                        alt={ex.nombre}
                        rounded
                        style={{ width: 46, height: 46, objectFit: 'cover' }}
                      />
                      <div className="ms-3 flex-grow-1">
                        <div style={{ color: '#555' }}>{ex.nombre} <small style={{ color: '#888' }}>(extra)</small></div>
                        <div style={{ color: '#000', fontWeight: 'bold', marginTop: 4 }}>Bs {( ((ex.precio !== undefined) ? parseFloat(ex.precio) : parseFloat(ex.precio_minorista || 0)) * (ex.cantidad || 0) ).toFixed(2)}</div>
                      </div>
                      <div className="text-center">
                        <div className="d-flex align-items-center">
                          <Button size="sm" onClick={() => updateQuantity(ex.id, ex.cantidad - 1)} style={{ backgroundColor: 'transparent', border: 'none', color: '#000', fontSize: '18px', padding: '0 8px' }}>-</Button>
                          <span className="px-2" style={{ color: '#000', fontWeight: 'bold', fontSize: '16px' }}>{ex.cantidad}</span>
                          <Button size="sm" onClick={() => updateQuantity(ex.id, ex.cantidad + 1)} style={{ backgroundColor: 'transparent', border: 'none', color: '#000', fontSize: '18px', padding: '0 8px' }}>+</Button>
                        </div>
                        <div className="mt-2">
                          <Button size="sm" onClick={() => removeFromCart(ex.id)} style={{ backgroundColor: 'transparent', border: 'none', fontSize: '16px', padding: '0' }}>🗑️</Button>
                        </div>
                      </div>
                    </div>
                  ))}
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
