import React, { Suspense } from 'react';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import { CartProvider } from './context/CartContext';
import { AuthProvider } from './context/AuthContext';
import Header from './components/Header';
import ProtectedRoute from './components/ProtectedRoute';
import ErrorBoundary from './components/ErrorBoundary';
import CartBar from './components/tienda/CartBar';
import Avisos from './components/tienda/Avisos';
import Home from './pages/Home';
import Cart from './pages/Cart';
const Checkout = React.lazy(() => import('./pages/Checkout'));
// Fuera de la portada y el carrito, cada página se descarga al abrirla
const PedidoConfirmado = React.lazy(() => import('./pages/PedidoConfirmado'));
const Login = React.lazy(() => import('./pages/Login'));
const Register = React.lazy(() => import('./pages/Register'));
const OlvideClave = React.lazy(() => import('./pages/OlvideClave'));
const RestablecerClave = React.lazy(() => import('./pages/RestablecerClave'));
const AdminPanel = React.lazy(() => import('./pages/AdminPanel'));
const VendedorPanel = React.lazy(() => import('./pages/VendedorPanel'));
const PerfilPanel = React.lazy(() => import('./pages/admin/PerfilPanel'));
const MisPedidos = React.lazy(() => import('./pages/MisPedidos'));
const Nosotros = React.lazy(() => import('./pages/Nosotros'));
const Contacto = React.lazy(() => import('./pages/Contacto'));
const UsersList = React.lazy(() => import('./pages/admin/UsersList'));
const MisVentas = React.lazy(() => import('./pages/Vendedor/MisVentas'));
const ProduccionForm = React.lazy(() => import('./pages/Panadero/ProduccionForm'));

const Cargando = () => (
  <div className="d-flex justify-content-center py-5" role="status">
    <div className="spinner-border" role="status"><span className="visually-hidden">Cargando...</span></div>
  </div>
);

function App() {
  // Use Vite's BASE_URL so React Router works correctly when the app is served under /app/
  const rawBase = import.meta.env.BASE_URL || '/';
  const basename = rawBase.replace(/\/$/, '') || '/';

  return (
    <ErrorBoundary>
      <CartProvider>
        <Router basename={basename}>
          <AuthProvider>
            <Header />
            <Suspense fallback={<Cargando />}>
            <Routes>
              {/* Rutas públicas */}
              <Route path="/" element={<Home />} />
              <Route path="/carrito" element={<Cart />} />
              <Route path="/productos" element={<Home />} />
              <Route path="/checkout" element={<Suspense fallback={<div className="d-flex justify-content-center py-5" role="status"><div className="spinner-border" role="status"><span className="visually-hidden">Cargando...</span></div></div>}><Checkout /></Suspense>} />
              <Route path="/pedido-confirmado" element={<PedidoConfirmado />} />
              <Route path="/login" element={<Login />} />
              <Route path="/register" element={<Register />} />
              <Route path="/olvide-clave" element={<OlvideClave />} />
              <Route path="/restablecer-clave" element={<RestablecerClave />} />

              {/* Panel Admin unificado - Incluye productos, pedidos y clientes */}
              <Route
                path="/admin"
                element={
                  <ProtectedRoute roles={['admin']}>
                    <Suspense fallback={<div className="d-flex justify-content-center py-5" role="status"><div className="spinner-border" role="status"><span className="visually-hidden">Cargando panel admin...</span></div></div>}>
                      <AdminPanel />
                    </Suspense>
                  </ProtectedRoute>
                }
              />

              {/* Gestión de Usuarios - Solo Admin */}
              <Route
                path="/admin/usuarios"
                element={
                  <ProtectedRoute roles={['admin']}>
                    <Suspense fallback={<div className="d-flex justify-content-center py-5" role="status"><div className="spinner-border" role="status"><span className="visually-hidden">Cargando...</span></div></div>}>
                      <UsersList />
                    </Suspense>
                  </ProtectedRoute>
                }
              />

              {/* Panel Vendedor - Punto de Venta (POS) */}
              <Route
                path="/vendedor"
                element={
                  <ProtectedRoute roles={['admin', 'vendedor']}>
                    <Suspense fallback={<div className="d-flex justify-content-center py-5" role="status"><div className="spinner-border" role="status"><span className="visually-hidden">Cargando panel vendedor...</span></div></div>}>
                      <VendedorPanel />
                    </Suspense>
                  </ProtectedRoute>
                }
              />

              {/* Mis Ventas - Vendedor */}
              <Route
                path="/vendedor/ventas"
                element={
                  <ProtectedRoute roles={['admin', 'vendedor']}>
                    <Suspense fallback={<div className="d-flex justify-content-center py-5" role="status"><div className="spinner-border" role="status"><span className="visually-hidden">Cargando...</span></div></div>}>
                      <MisVentas />
                    </Suspense>
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
              <Route path="/contacto" element={<Suspense fallback={<div className="d-flex justify-content-center py-5" role="status"><div className="spinner-border" role="status"><span className="visually-hidden">Cargando...</span></div></div>}><Contacto /></Suspense>} />
              <Route path="/nosotros" element={<Suspense fallback={<div className="d-flex justify-content-center py-5" role="status"><div className="spinner-border" role="status"><span className="visually-hidden">Cargando...</span></div></div>}><Nosotros /></Suspense>} />
            </Routes>
            </Suspense>
            <CartBar />
            <Avisos />
          </AuthProvider>
        </Router>
      </CartProvider>
    </ErrorBoundary>
  );
}

export default App;
