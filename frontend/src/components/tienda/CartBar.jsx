import { Link, useLocation } from 'react-router-dom';
import { ArrowRight } from 'lucide-react';
import { useCart } from '../../context/CartContext';
import { formatCurrency } from '../../utils/number';

// Páginas de la tienda donde se muestra la barra (en carrito y checkout ya hay
// su propia barra de pago; en admin y punto de venta no corresponde).
const RUTAS_TIENDA = ['/', '/productos', '/contacto', '/nosotros'];

// Barra fija inferior en móvil con el total del carrito. En escritorio se
// oculta por CSS: ahí el carrito está siempre a mano en la cabecera.
const CartBar = () => {
  const { pathname } = useLocation();
  const { getTotalItems, getTotal } = useCart();
  const items = getTotalItems();

  if (!items || !RUTAS_TIENDA.includes(pathname)) return null;

  return (
    <>
      <div className="pn-cartbar-spacer" aria-hidden="true" />
      <div className="pn-cartbar" role="region" aria-label="Tu carrito">
        <div className="pn-cartbar__info">
          <small>{items} {items === 1 ? 'producto' : 'productos'}</small>
          <strong key={items}>Bs {formatCurrency(getTotal())}</strong>
        </div>
        <Link to="/carrito" className="pn-btn">
          Ver carrito <ArrowRight size={16} />
        </Link>
      </div>
    </>
  );
};

export default CartBar;
