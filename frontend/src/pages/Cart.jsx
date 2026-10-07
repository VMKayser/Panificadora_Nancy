import { useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { ArrowLeft, ShoppingBag } from 'lucide-react';
import { useCart } from '../context/CartContext';
import { formatCurrency } from '../utils/number';
import { useSEO } from '../hooks/useSEO';
import CartLines from '../components/tienda/CartLines';

const Cart = () => {
  const navigate = useNavigate();
  const { cart, getTotal, getTotalItems } = useCart();
  const items = getTotalItems();

  // El checkout se descarga mientras el cliente revisa el carrito
  useEffect(() => {
    import('./Checkout');
  }, []);

  // SEO: noindex para la página de carrito (no queremos indexar carritos individuales)
  useSEO({
    title: cart.length === 0
      ? 'Carrito - Panificadora Nancy'
      : `Carrito - Panificadora Nancy (${items} productos)`,
    description: 'Revisa tu carrito y completa tu pedido. Delivery rápido y productos frescos garantizados.',
    noindex: true
  });

  if (cart.length === 0) {
    return (
      <main className="pn-wrap pn-page">
        <div className="pn-empty">
          <ShoppingBag size={44} />
          <h2>Tu carrito está vacío</h2>
          <p>Agrega productos para comenzar tu pedido.</p>
          <Link to="/productos" className="pn-btn pn-btn--primary">Ver productos</Link>
        </div>
      </main>
    );
  }

  const total = formatCurrency(getTotal());

  return (
    <main className="pn-wrap pn-page">
      <Link to="/productos" className="pn-back"><ArrowLeft size={16} /> Seguir comprando</Link>
      <h1 className="pn-page__title">Tu carrito</h1>
      <p className="pn-page__sub">{items} {items === 1 ? 'producto' : 'productos'}</p>

      <div className="pn-split">
        <section className="pn-panel" aria-label="Productos en el carrito">
          <CartLines />
        </section>

        <aside className="pn-split__aside">
          <div className="pn-panel">
            <h2 className="pn-panel__title">Resumen</h2>
            <div className="pn-sum">
              <div className="pn-sum__row"><span>Subtotal</span><span>Bs {total}</span></div>
              <div className="pn-sum__row--total pn-sum__row"><span>Total</span><span>Bs {total}</span></div>
              <p className="pn-sum__note">Eliges retiro, delivery o envío nacional en el siguiente paso.</p>
            </div>
            <button type="button" className="pn-btn pn-btn--primary pn-btn--lg pn-btn--block mt-3 d-none d-lg-flex" onClick={() => navigate('/checkout')}>
              Ir a pagar
            </button>
          </div>
        </aside>
      </div>

      <div className="pn-paybar-spacer" aria-hidden="true" />
      <div className="pn-paybar">
        <div className="pn-paybar__total">
          <small>Total</small>
          <strong>Bs {total}</strong>
        </div>
        <button type="button" className="pn-btn pn-btn--primary pn-btn--lg" onClick={() => navigate('/checkout')}>
          Ir a pagar
        </button>
      </div>
    </main>
  );
};

export default Cart;
