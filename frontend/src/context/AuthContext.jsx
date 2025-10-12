import { createContext, useContext, useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import PropTypes from 'prop-types';
import { auth as authApi } from '../services/api';

const AuthContext = createContext(null);

export const AuthProvider = ({ children }) => {
  const navigate = useNavigate();
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [token, setToken] = useState(localStorage.getItem('auth_token'));

  // Cargar usuario al iniciar si hay token
  useEffect(() => {
    let mounted = true;
    const loadUser = async () => {
      if (token) {
        try {
          const data = await authApi.me();
          // El endpoint /me puede devolver directamente el usuario o un objeto { user: ... }
          const userObj = data?.user ? data.user : data;
          if (mounted) setUser(userObj);
        } catch (error) {
          console.error('Error al cargar usuario:', error);
          // Solo limpiar el storage, no navegar automáticamente
          // para evitar redirects en páginas públicas
          localStorage.removeItem('auth_token');
          localStorage.removeItem('user');
          if (mounted) {
            setUser(null);
            setToken(null);
          }
        }
      }
      if (mounted) setLoading(false);
    };

    loadUser();
    return () => { mounted = false; };
  }, [token]);

  const login = async (email, password) => {
    try {
      console.log('[AuthContext] Intentando login con:', email);
      const data = await authApi.login({ email, password });
      console.log('[AuthContext] Respuesta del login:', data);
      
      const tokenValue = data.access_token || data.token || localStorage.getItem('auth_token');
      const userValue = data.user || JSON.parse(localStorage.getItem('user') || 'null');

      console.log('[AuthContext] Token extraído:', tokenValue?.substring(0, 10) + '...');
      console.log('[AuthContext] Usuario extraído:', userValue);

      if (tokenValue) {
        setToken(tokenValue);
        localStorage.setItem('auth_token', tokenValue);
      }

      if (userValue) {
        setUser(userValue);
        localStorage.setItem('user', JSON.stringify(userValue));
      }
      return { success: true };
    } catch (error) {
      console.error('[AuthContext] Error en login:', error);
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
    // During HMR or mount ordering issues components may try to use the hook
    // before the provider is ready. Log a clear warning and return a safe
    // fallback so the app doesn't crash in the browser.
    console.warn('useAuth debe ser usado dentro de AuthProvider — devolviendo un valor por defecto seguro');
    return {
      user: null,
      token: null,
      loading: false,
      login: async () => ({ success: false, error: 'Auth provider no disponible' }),
      register: async () => ({ success: false, error: 'Auth provider no disponible' }),
      logout: async () => {},
      hasRole: () => false,
      hasAnyRole: () => false,
      isAuthenticated: false,
      isAdmin: false,
      isVendedor: false,
      isCliente: false,
    };
  }
  return context;
};
