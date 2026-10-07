import { useEffect } from 'react';
import PropTypes from 'prop-types';
import { Offcanvas } from 'react-bootstrap';
import { Link, useNavigate } from 'react-router-dom';
import { ShoppingBag } from 'lucide-react';
import { useCart } from '../context/CartContext';
import { formatCurrency } from '../utils/number';
import CartLines from './tienda/CartLines';

const CartDrawer = ({ show, onHide }) => {
  const navigate = useNavigate();
  const { cart, getTotal, getTotalItems } = useCart();
  const items = getTotalItems();

  // Al abrir el carrito ya se descarga el checkout
  useEffect(() => {
    if (show) import('../pages/Checkout');
  }, [show]);

  const handleCheckout = () => {
    onHide();
    navigate('/checkout');
  };

  return (
    <Offcanvas show={show} onHide={onHide} placement="end" className="pn-drawer" aria-labelledby="pn-drawer-title">
      <Offcanvas.Header closeButton closeLabel="Cerrar carrito">
        <Offcanvas.Title id="pn-drawer-title">
          Tu carrito {items > 0 && <small className="text-muted fs-6 fw-normal">({items})</small>}
        </Offcanvas.Title>
      </Offcanvas.Header>

      <Offcanvas.Body>
        {cart.length === 0 ? (
          <div className="pn-empty">
            <ShoppingBag size={40} />
            <h3>Tu carrito está vacío</h3>
            <p>Agrega productos desde el catálogo para armar tu pedido.</p>
            <Link to="/productos" className="pn-btn pn-btn--primary" onClick={onHide}>Ver productos</Link>
          </div>
        ) : (
          <CartLines />
        )}
      </Offcanvas.Body>

      {cart.length > 0 && (
        <div className="pn-drawer__foot">
          <div className="pn-sum__row fw-semibold fs-5">
            <span>Subtotal</span>
            <span>Bs {formatCurrency(getTotal())}</span>
          </div>
          <p className="pn-sum__note">La forma de entrega y el pago se eligen en el siguiente paso.</p>
          <button type="button" className="pn-btn pn-btn--primary pn-btn--lg pn-btn--block" onClick={handleCheckout}>
            Ir a pagar
          </button>
          <button type="button" className="pn-btn pn-btn--ghost pn-btn--block" onClick={onHide}>
            Seguir comprando
          </button>
        </div>
      )}
    </Offcanvas>
  );
};

CartDrawer.propTypes = {
  show: PropTypes.bool.isRequired,
  onHide: PropTypes.func.isRequired,
};

export default CartDrawer;
