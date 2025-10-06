import { Navbar, Nav, Container, Badge } from 'react-bootstrap';
import { Link } from 'react-router-dom';
import { useCart } from '../context/CartContext';
import { useState } from 'react';
import CartDrawer from './CartDrawer';

const Header = () => {
  const { getTotalItems } = useCart();
  const cartItemsCount = getTotalItems();
  const [showCart, setShowCart] = useState(false);

  const handleOpenCart = (e) => {
    e.preventDefault();
    setShowCart(true);
  };
  const handleCloseCart = () => setShowCart(false);

  return (
    <Navbar bg="light" expand="lg" sticky="top" className="shadow-sm">
      <Container>
        {/* Logo */}
        <Navbar.Brand as={Link} to="/" className="d-flex align-items-center">
          <img
            src="https://www.oep.org.bo/logos/EscudoBolivia_300x300.webp"
            height="50"
            className="d-inline-block align-top"
            alt="Panificadora Nancy"
          />
          <span className="ms-2 fw-bold" style={{ color: 'rgb(145, 109, 74)' }}>
            Panificadora Nancy
          </span>
        </Navbar.Brand>

        {/* Toggle para móvil */}
        <Navbar.Toggle aria-controls="navbar-nav" />

        <Navbar.Collapse id="navbar-nav">
          {/* Links de navegación */}
          <Nav className="ms-auto align-items-center">
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
            
            {/* Carrito con badge */}
            <Nav.Link href="#" onClick={handleOpenCart} className="mx-2 position-relative">
              🛒 Carrito
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

            {/* Perfil */}
            <Nav.Link as={Link} to="/perfil" className="mx-2">
              👤 Perfil
            </Nav.Link>
          </Nav>
        </Navbar.Collapse>
      </Container>
    </Navbar>
  );
};

export default Header;
