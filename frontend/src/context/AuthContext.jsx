import { createContext, useContext, useState, useEffect } from 'react';
import PropTypes from 'prop-types';
import { auth as authApi } from '../services/api';

const AuthContext = createContext(null);

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [token, setToken] = useState(localStorage.getItem('auth_token'));

  // Cargar usuario al iniciar si hay token
  useEffect(() => {
    const loadUser = async () => {
      if (token) {
        try {
          const data = await authApi.me();
          setUser(data.user);
        } catch (error) {
          console.error('Error al cargar usuario:', error);
          logout();
        }
      }
      setLoading(false);
    };

    loadUser();
  }, [token]);

  const login = async (email, password) => {
    try {
      const data = await authApi.login({ email, password });
      setToken(data.access_token);
      setUser(data.user);
      localStorage.setItem('auth_token', data.access_token);
      localStorage.setItem('user', JSON.stringify(data.user));
      return { success: true };
    } catch (error) {
      return {
        success: false,
        error: error.response?.data?.message || 'Error al iniciar sesión'
      };
    }
  };

  const register = async (userData) => {
    try {
      const data = await authApi.register(userData);
      // El backend ahora requiere verificación por correo antes de emitir token.
      // Si la respuesta incluye access_token (legacy), iniciar sesión automáticamente,
      // si no, simplemente devolver el mensaje para que la UI indique verificar el email.
      if (data.access_token) {
        setToken(data.access_token);
        setUser(data.user);
        localStorage.setItem('auth_token', data.access_token);
        localStorage.setItem('user', JSON.stringify(data.user));
        return { success: true };
      }

      return { success: true, message: data.message };
    } catch (error) {
      return {
        success: false,
        error: error.response?.data?.message || 'Error al registrarse'
      };
    }
  };

  const logout = async () => {
    try {
      if (token) {
        await authApi.logout();
      }
    } catch (error) {
      console.error('Error al cerrar sesión:', error);
    } finally {
      setUser(null);
      setToken(null);
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user');
    }
  };

  const hasRole = (roleName) => {
    return user?.roles?.some(role => role.name === roleName) || false;
  };

  const hasAnyRole = (roleNames) => {
    return user?.roles?.some(role => roleNames.includes(role.name)) || false;
  };

  const value = {
    user,
    token,
    loading,
    login,
    register,
    logout,
    hasRole,
    hasAnyRole,
    isAuthenticated: !!user,
    isAdmin: hasRole('admin'),
    isVendedor: hasRole('vendedor'),
    isCliente: hasRole('cliente'),
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};

AuthProvider.propTypes = {
  children: PropTypes.node.isRequired,
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth debe ser usado dentro de AuthProvider');
  }
  return context;
};
