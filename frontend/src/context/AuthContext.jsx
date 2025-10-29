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
    if (!user) return false;
    // Caso 1: user.roles es array de objetos { name }
    if (Array.isArray(user.roles) && user.roles.length > 0) {
      // roles puede ser array de strings o array de objetos
      if (typeof user.roles[0] === 'string') {
        return user.roles.includes(roleName);
      }
      return user.roles.some(role => role?.name === roleName || role?.rol === roleName || role?.role === roleName);
    }
    // Caso 2: user.role singular (string)
    if (typeof user.role === 'string') {
      return user.role === roleName;
    }
    // Caso 3: campo role_name u otros alias
    if (typeof user.role_name === 'string') {
      return user.role_name === roleName;
    }
    // Caso 4: user.roles como objeto de mapeo { admin: true }
    if (user.roles && typeof user.roles === 'object') {
      return !!user.roles[roleName];
    }
    return false;
  };

  const hasAnyRole = (roleNames) => {
    if (!user) return false;
    if (Array.isArray(user.roles) && user.roles.length > 0) {
      if (typeof user.roles[0] === 'string') {
        return user.roles.some(r => roleNames.includes(r));
      }
      return user.roles.some(role => role && (roleNames.includes(role.name) || roleNames.includes(role.role) || roleNames.includes(role.rol)));
    }
    if (typeof user.role === 'string') {
      return roleNames.includes(user.role);
    }
    if (typeof user.role_name === 'string') {
      return roleNames.includes(user.role_name);
    }
    if (user.roles && typeof user.roles === 'object') {
      return roleNames.some(rn => !!user.roles[rn]);
    }
    return false;
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
    // Nuevo: flag para panadero
    isPanadero: hasRole('panadero'),
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
