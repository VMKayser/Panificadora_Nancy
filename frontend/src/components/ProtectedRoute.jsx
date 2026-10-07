import { Navigate } from 'react-router-dom';
import { Lock } from 'lucide-react';
import PropTypes from 'prop-types';
import { useAuth } from '../context/AuthContext';

export default function ProtectedRoute({ children, roles = [] }) {
  const { user, loading, hasAnyRole } = useAuth();

  if (import.meta.env.DEV) console.debug('[ProtectedRoute] Estado:', { user, loading, roles });

  if (loading) {
    return (
      <div className="min-vh-100 d-flex align-items-center justify-content-center">
        <div className="spinner-border" style={{ color: '#8b6f47' }} role="status">
          <span className="visually-hidden">Cargando...</span>
        </div>
      </div>
    );
  }

  if (!user) {
    if (import.meta.env.DEV) console.debug('[ProtectedRoute] No hay usuario, redirigiendo a /login');
    return <Navigate to="/login" replace />;
  }

  if (roles.length > 0) {
    const safeHasAnyRole = (typeof hasAnyRole === 'function')
      ? hasAnyRole(roles)
      : (Array.isArray(user.roles)
          ? user.roles.some(r => typeof r === 'string' ? roles.includes(r) : roles.includes(r?.name || r?.role || r?.rol))
          : (typeof user.role === 'string' ? roles.includes(user.role) : false)
        );
  if (import.meta.env.DEV) console.debug('[ProtectedRoute] Verificando roles (safe):', { userRoles: user.roles, requiredRoles: roles, safeHasAnyRole });
    if (!safeHasAnyRole) {
      return (
        <div className="min-vh-100 d-flex align-items-center justify-content-center">
          <div className="text-center">
            <Lock size={56} color="#8b6f47" className="mb-3" />
            <h2 style={{ color: '#534031' }}>Acceso Denegado</h2>
            <p className="text-muted">No tienes permisos para acceder a esta página</p>
            <a href="/" className="btn btn-primary" style={{ backgroundColor: '#8b6f47', border: 'none' }}>
              Volver al inicio
            </a>
          </div>
        </div>
      );
    }
  }

  if (import.meta.env.DEV) console.debug('[ProtectedRoute] Acceso concedido, renderizando children');
  return children;
}

ProtectedRoute.propTypes = {
  children: PropTypes.node.isRequired,
  roles: PropTypes.arrayOf(PropTypes.string),
};
