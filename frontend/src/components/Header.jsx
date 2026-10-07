import { useEffect, useState } from 'react';
import { Navbar, Nav, Container, Offcanvas } from 'react-bootstrap';
import { Link, NavLink, useLocation, useNavigate } from 'react-router-dom';
import { Menu, ShoppingBag, User } from 'lucide-react';
import { toast } from 'react-toastify';
import { useCart } from '../context/CartContext';
import { useAuth } from '../context/AuthContext';
import { useSiteConfig } from '../context/SiteConfigContext';
import CartDrawer from './CartDrawer';
import UserDropdown from './UserDropdown';

const Header = () => {
  const { getTotalItems } = useCart();
  const { user, logout, isAdmin, isVendedor, isPanadero } = useAuth();
  const { logoChico } = useSiteConfig();
  const navigate = useNavigate();
  const { pathname } = useLocation();
  const cartItemsCount = getTotalItems();
  const [showCart, setShowCart] = useState(false);
  const [menuOpen, setMenuOpen] = useState(false);

  // Cerrar el menú lateral al cambiar de página
  useEffect(() => {
    setMenuOpen(false);
  }, [pathname]);

  const openCart = () => {
    setMenuOpen(false);
    setShowCart(true);
  };

  const handleLogout = async () => {
    setMenuOpen(false);
    await logout();
    toast.success('Sesión cerrada exitosamente');
    navigate('/');
  };

  const esEquipo = isAdmin || isVendedor || isPanadero;

  const cartButton = (
    <button type="button" className="pn-iconbtn" onClick={openCart} aria-label="Abrir carrito">
      <ShoppingBag size={22} />
      {cartItemsCount > 0 && (
        // key: al cambiar la cantidad el globo se vuelve a montar y repite la animación
        <span className="pn-badge" key={cartItemsCount}>
          {cartItemsCount}
          <span className="pn-visually-hidden"> productos</span>
        </span>
      )}
    </button>
  );

  return (
    <>
      <Navbar expand="lg" sticky="top" className="pn-header" expanded={menuOpen} onToggle={setMenuOpen}>
        <Container fluid className="pn-wrap pn-header__inner">
          <Navbar.Brand as={Link} to="/" className="pn-brand">
            <img src={logoChico} alt="" width="48" height="48" />
            <span>Panificadora Nancy</span>
          </Navbar.Brand>

          <div className="pn-header__actions d-lg-none">
            {cartButton}
            <Navbar.Toggle aria-controls="pn-menu" className="pn-iconbtn" aria-label="Abrir menú">
              <Menu size={24} />
            </Navbar.Toggle>
          </div>

          <Navbar.Offcanvas id="pn-menu" placement="end" className="pn-menu" aria-labelledby="pn-menu-title">
            <Offcanvas.Header closeButton>
              <Offcanvas.Title id="pn-menu-title">Panificadora Nancy</Offcanvas.Title>
            </Offcanvas.Header>
            <Offcanvas.Body>
              <Nav className="pn-nav ms-lg-auto">
                <Nav.Link as={NavLink} to="/" end>Inicio</Nav.Link>
                <Nav.Link as={NavLink} to="/productos">Productos</Nav.Link>
                <Nav.Link as={NavLink} to="/nosotros">Nosotros</Nav.Link>
                <Nav.Link as={NavLink} to="/contacto">Contacto</Nav.Link>

                {esEquipo && (
                  <>
                    <div className="pn-nav__group">Equipo</div>
                    <span className="pn-nav__sep d-none d-lg-block" aria-hidden="true" />
                    {(isAdmin || isVendedor) && <Nav.Link as={NavLink} to="/vendedor">Punto de Venta</Nav.Link>}
                    {(isAdmin || isPanadero) && <Nav.Link as={NavLink} to="/panadero/produccion">Producción</Nav.Link>}
                    {isAdmin && <Nav.Link as={NavLink} to="/admin">Panel Admin</Nav.Link>}
                  </>
                )}

                <span className="pn-nav__sep d-none d-lg-block" aria-hidden="true" />

                {user ? (
                  <>
                    <div className="d-lg-none">
                      <div className="pn-nav__group">Hola, {user.name}</div>
                      <Nav.Link as={NavLink} to="/mis-pedidos">Mis Pedidos</Nav.Link>
                      <Nav.Link as={NavLink} to="/perfil">Mi Perfil</Nav.Link>
                      <Nav.Link as="button" type="button" onClick={handleLogout} className="w-100">Cerrar Sesión</Nav.Link>
                    </div>
                    <div className="d-none d-lg-block">
                      <UserDropdown user={user} onLogout={handleLogout} />
                    </div>
                  </>
                ) : (
                  <>
                    <div className="pn-nav__group">Tu cuenta</div>
                    <Nav.Link as={NavLink} to="/login"><User size={18} /> Iniciar Sesión</Nav.Link>
                    <Link to="/register" className="pn-btn pn-btn--primary pn-nav__register">Registrarse</Link>
                  </>
                )}

                <div className="d-none d-lg-block ms-2">{cartButton}</div>
              </Nav>
            </Offcanvas.Body>
          </Navbar.Offcanvas>
        </Container>
      </Navbar>

      {/* Fuera del Navbar: dentro, react-bootstrap lo trataría como el menú de navegación */}
      <CartDrawer show={showCart} onHide={() => setShowCart(false)} />
    </>
  );
};

export default Header;
