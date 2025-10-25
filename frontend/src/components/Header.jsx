import { Navbar, Nav, Container, Badge, NavDropdown } from 'react-bootstrap';
import { Link, useNavigate } from 'react-router-dom';
import { useCart } from '../context/CartContext';
import { useAuth } from '../context/AuthContext';
import { useState } from 'react';
import { toast } from 'react-toastify';
import { Home, ShoppingCart, Phone, Info, DollarSign, Settings, User, Edit, Package as PackageIcon, LogOut, LogIn } from 'lucide-react';
import CartDrawer from './CartDrawer';
import UserDropdown from './UserDropdown';
import { useSiteConfig } from '../context/SiteConfigContext';

const Header = () => {
  const { getTotalItems } = useCart();
  const { user, logout, isAdmin, isVendedor, isPanadero } = useAuth();
  const navigate = useNavigate();
  const cartItemsCount = getTotalItems();
  const [showCart, setShowCart] = useState(false);
  const { logoUrl } = useSiteConfig();

  const handleOpenCart = (e) => {
    e.preventDefault();
    setShowCart(true);
  };
  const handleCloseCart = () => setShowCart(false);

  const handleLogout = async () => {
    await logout();
    toast.success('Sesión cerrada exitosamente');
    navigate('/');
  };

  return (
  <Navbar bg="light" expand="lg" sticky="top" className="shadow-sm" collapseOnSelect>
      <Container>
        {/* Logo: usar logo configurado si existe, fallback a public/images/logo.jpg */}
        <Navbar.Brand as={Link} to="/" className="d-flex align-items-center">
          <img
            src={logoUrl || `${import.meta.env.BASE_URL}images/logo.jpg`}
            className="site-logo d-inline-block align-top"
            alt="Panificadora Nancy"
          />
          <span className="ms-2 fw-bold" style={{ color: 'rgb(145, 109, 74)' }}>
            Panificadora Nancy
          </span>
        </Navbar.Brand>

        {/* Toggle para móvil */}
  <Navbar.Toggle aria-controls="navbar-nav" aria-label="Toggle navigation" />

        {/* Cart button visible on mobile outside the collapse (next to the toggle) */}
        <div className="d-flex d-md-none align-items-center ms-2">
          <a href="#" onClick={handleOpenCart} className="position-relative" style={{ color: 'inherit', textDecoration: 'none' }}>
            <span style={{ fontSize: 20 }}>🛒</span>
            {cartItemsCount > 0 && (
              <Badge
                bg="danger"
                pill
                className="position-absolute top-0 start-100 translate-middle"
              >
                {cartItemsCount}
              </Badge>
            )}
          </a>
        </div>

        <Navbar.Collapse id="navbar-nav">
          {/* Links de navegación */}
          <Nav className="ms-auto align-items-center flex-nowrap" style={{ overflowX: 'auto', WebkitOverflowScrolling: 'touch' }}>
            <Nav.Link as={Link} to="/" className="mx-2">
              🏠 Inicio
            </Nav.Link>
            <Nav.Link as={Link} to="/productos" className="mx-2">
              🍞 Productos
            </Nav.Link>
            <Nav.Link as={Link} to="/contacto" className="mx-2">
              📞 Contáctanos
            </Nav.Link>
            <Nav.Link as={Link} to="/nosotros" className="mx-2">
              ℹ️ Nosotros
            </Nav.Link>
            
            {/* Carrito (oculto en móvil porque lo mostramos fuera del collapse al lado del toggle) */}
            <Nav.Link href="#" onClick={handleOpenCart} className="mx-2 position-relative d-none d-md-flex align-items-center">
              <span className="">🛒 Carrito</span>
              {cartItemsCount > 0 && (
                <Badge 
                  bg="danger" 
                  pill 
                  className="position-absolute top-0 start-100 translate-middle"
                >
                  {cartItemsCount}
                </Badge>
              )}
            </Nav.Link>

            {/* Drawer del carrito */}
            <CartDrawer show={showCart} onHide={handleCloseCart} />

            {/* Panel de Vendedor - Solo para vendedor y admin */}
            {(isAdmin || isVendedor) && (
              <Nav.Link as={Link} to="/vendedor" className="mx-2">
                💰 Punto de Venta
              </Nav.Link>
            )}

            {/* Panel Panadero - Solo para panadero y admin */}
            {(isAdmin || (typeof isPanadero !== 'undefined' && isPanadero)) && (
              <Nav.Link as={Link} to="/panadero/produccion" className="mx-2">
                🧑‍🍳 Producción
              </Nav.Link>
            )}

            {/* Admin Button - Solo para admin */}
            {isAdmin && (
              <Nav.Link as={Link} to="/admin" className="mx-2">
                ⚙️ Panel Admin
              </Nav.Link>
            )}

            {/* Usuario autenticado o Login */}
            {user ? (
              // Render dropdown via Overlay appended to document.body to avoid
              // parent overflow creating static dropdowns that push layout.
              <>
                {/* lazy-load UserDropdown to keep Header lightweight */}
                <UserDropdown user={user} onLogout={handleLogout} />
              </>
            ) : (
              <>
                <Nav.Link as={Link} to="/login" className="mx-2">
                  � Iniciar Sesión
                </Nav.Link>
                <Link to="/register" className="btn btn-sm text-white ms-2" style={{ backgroundColor: '#8b6f47', border: 'none' }}>
                  Registrarse
                </Link>
              </>
            )}
          </Nav>
        </Navbar.Collapse>
      </Container>
    </Navbar>
  );
};

export default Header;
