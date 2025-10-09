import { Navigate } from 'react-router-dom';
import PropTypes from 'prop-types';
import { useAuth } from '../context/AuthContext';

export default function ProtectedRoute({ children, roles = [] }) {
  const { user, loading } = useAuth();

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
    return <Navigate to="/login" replace />;
  }

  if (roles.length > 0) {
    const hasRequiredRole = user.roles?.some(role => roles.includes(role.name));
    if (!hasRequiredRole) {
      return (
        <div className="min-vh-100 d-flex align-items-center justify-content-center">
          <div className="text-center">
            <h1 style={{ fontSize: '4rem' }}>🔒</h1>
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

  return children;
}

ProtectedRoute.propTypes = {
  children: PropTypes.node.isRequired,
  roles: PropTypes.arrayOf(PropTypes.string),
};
