import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import { CartProvider } from './context/CartContext';
import { AuthProvider } from './context/AuthContext';
import { ToastContainer } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';
import Header from './components/Header';
import ProtectedRoute from './components/ProtectedRoute';
import Home from './pages/Home';
import Cart from './pages/Cart';
import Checkout from './pages/Checkout';
import PedidoConfirmado from './pages/PedidoConfirmado';
import Login from './pages/Login';
import Register from './pages/Register';
import AdminPanel from './pages/AdminPanel';

function App() {
  return (
    <AuthProvider>
      <CartProvider>
        <Router>
          <Header />
          <Routes>
            {/* Rutas públicas */}
            <Route path="/" element={<Home />} />
            <Route path="/carrito" element={<Cart />} />
            <Route path="/productos" element={<Home />} />
            <Route path="/checkout" element={<Checkout />} />
            <Route path="/pedido-confirmado" element={<PedidoConfirmado />} />
            <Route path="/login" element={<Login />} />
            <Route path="/register" element={<Register />} />
            
            {/* Panel Admin unificado - Incluye productos, pedidos y clientes */}
            <Route 
              path="/admin" 
              element={
                <ProtectedRoute roles={['admin', 'vendedor']}>
                  <AdminPanel />
                </ProtectedRoute>
              } 
            />
            
            {/* Rutas de cliente */}
            <Route 
              path="/perfil" 
              element={
                <ProtectedRoute>
                  <div style={{ textAlign: 'center', padding: '50px' }}>Perfil (próximamente)</div>
                </ProtectedRoute>
              } 
            />
            <Route 
              path="/mis-pedidos" 
              element={
                <ProtectedRoute>
                  <div style={{ textAlign: 'center', padding: '50px' }}>Mis Pedidos (próximamente)</div>
                </ProtectedRoute>
              } 
            />
            <Route path="/contacto" element={<div style={{ textAlign: 'center', padding: '50px' }}>Contacto (próximamente)</div>} />
            <Route path="/nosotros" element={<div style={{ textAlign: 'center', padding: '50px' }}>Nosotros (próximamente)</div>} />
          </Routes>
          <ToastContainer
            position="bottom-right"
            autoClose={3000}
            hideProgressBar={false}
            newestOnTop={false}
            closeOnClick
            rtl={false}
            pauseOnFocusLoss
            draggable
            pauseOnHover
            theme="light"
          />
        </Router>
      </CartProvider>
    </AuthProvider>
  );
}

export default App;
