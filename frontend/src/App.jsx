import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import { CartProvider } from './context/CartContext';
import Header from './components/Header';
import Home from './pages/Home';
import Cart from './pages/Cart';

function App() {
  return (
    <CartProvider>
      <Router>
        <Header />
        <Routes>
          <Route path="/" element={<Home />} />
          <Route path="/carrito" element={<Cart />} />
          <Route path="/productos" element={<Home />} />
          {/* Rutas pendientes */}
          <Route path="/checkout" element={<div style={{ textAlign: 'center', padding: '50px' }}>Página de Checkout (próximamente)</div>} />
          <Route path="/perfil" element={<div style={{ textAlign: 'center', padding: '50px' }}>Perfil (próximamente)</div>} />
          <Route path="/contacto" element={<div style={{ textAlign: 'center', padding: '50px' }}>Contacto (próximamente)</div>} />
          <Route path="/nosotros" element={<div style={{ textAlign: 'center', padding: '50px' }}>Nosotros (próximamente)</div>} />
        </Routes>
      </Router>
    </CartProvider>
  );
}

export default App;
