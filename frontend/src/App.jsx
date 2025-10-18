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
import VendedorPanel from './pages/VendedorPanel';
import PerfilPanel from './pages/admin/PerfilPanel';
import MisPedidos from './pages/MisPedidos';
import Nosotros from './pages/Nosotros';
import Contacto from './pages/Contacto';
import UsersList from './pages/Admin/UsersList';
import MisVentas from './pages/Vendedor/MisVentas';
import ProduccionForm from './pages/Panadero/ProduccionForm';

function App() {
  // Use Vite's BASE_URL so React Router works correctly when the app is served under /app/
  const rawBase = import.meta.env.BASE_URL || '/';
  const basename = rawBase.replace(/\/$/, '') || '/';

  return (
    <CartProvider>
      <Router basename={basename}>
        <AuthProvider>
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
                <ProtectedRoute roles={['admin']}>
                  <AdminPanel />
                </ProtectedRoute>
              } 
            />
            
            {/* Gestión de Usuarios - Solo Admin */}
            <Route 
              path="/admin/usuarios" 
              element={
                <ProtectedRoute roles={['admin']}>
                  <UsersList />
                </ProtectedRoute>
              } 
            />
            
            {/* Panel Vendedor - Punto de Venta (POS) */}
            <Route 
              path="/vendedor" 
              element={
                <ProtectedRoute roles={['admin', 'vendedor']}>
                  <VendedorPanel />
                </ProtectedRoute>
              } 
            />
            
            {/* Mis Ventas - Vendedor */}
            <Route 
              path="/vendedor/ventas" 
              element={
                <ProtectedRoute roles={['admin', 'vendedor']}>
                  <MisVentas />
                </ProtectedRoute>
              } 
            />
            
            {/* Rutas de cliente */}
            {/* Panel Panadero - Registrar producción */}
            <Route
              path="/panadero/produccion"
              element={
                <ProtectedRoute roles={["panadero"]}>
                  <ProduccionForm />
                </ProtectedRoute>
              }
            />
            <Route 
              path="/perfil" 
              element={
                <ProtectedRoute>
                  <PerfilPanel />
                </ProtectedRoute>
              } 
            />
            <Route 
              path="/mis-pedidos" 
              element={
                <ProtectedRoute>
                  <MisPedidos />
                </ProtectedRoute>
              } 
            />
            <Route path="/contacto" element={<Contacto />} />
            <Route path="/nosotros" element={<Nosotros />} />
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
        </AuthProvider>
      </Router>
    </CartProvider>
  );
}

export default App;
