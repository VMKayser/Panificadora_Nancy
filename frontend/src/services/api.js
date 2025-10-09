import axios from 'axios';

// Configurar la URL base de la API.
// Primero intenta la variable de entorno VITE_API_URL (recomendada),
// si no existe usa el origen actual del navegador + '/api' para que
// funcione tanto en local como desde dispositivos en la misma LAN.
const defaultBase = (typeof window !== 'undefined')
  ? `${window.location.origin}/api`
  : 'http://localhost/api';

const baseURL = import.meta?.env?.VITE_API_URL || defaultBase;

const api = axios.create({
  baseURL,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Interceptor para agregar token de autenticación
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Interceptor para manejar errores de autenticación
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Token expirado o inválido
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);


export const getProductos = async (params = {}) => {
  const response = await api.get('/productos', { params });
  return response.data;
};

export const getProducto = async (id) => {
  const response = await api.get(`/productos/${id}`);
  return response.data;
};


export const crearPedido = async (pedidoData) => {
  const response = await api.post('/pedidos', pedidoData);
  return response.data;
};

export const getMetodosPago = async () => {
  const response = await api.get('/metodos-pago');
  return response.data;
};

// ============================================
// CATEGORÍAS
// ============================================
export const getCategorias = async () => {
  const response = await api.get('/categorias');
  return response.data;
};

// ============================================
// AUTENTICACIÓN
// ============================================
export const auth = {
  // Registro
  register: async (userData) => {
    const response = await api.post('/register', userData);
    return response.data;
  },

  // Login
  login: async (credentials) => {
    const response = await api.post('/login', credentials);
    return response.data;
  },

  // Logout
  logout: async () => {
    const response = await api.post('/logout');
    return response.data;
  },

  // Obtener usuario actual
  me: async () => {
    const response = await api.get('/me');
    return response.data;
  },

  // Actualizar perfil
  updateProfile: async (profileData) => {
    const response = await api.put('/profile', profileData);
    return response.data;
  },
};

// ============================================
// ADMIN - PRODUCTOS
// ============================================
export const admin = {
  // Listar productos
  getProductos: async (params = {}) => {
    const response = await api.get('/admin/productos', { params });
    return response.data;
  },

  // Obtener producto por ID
  getProducto: async (id) => {
    const response = await api.get(`/admin/productos/${id}`);
    return response.data;
  },

  // Crear producto
  crearProducto: async (productoData) => {
    const response = await api.post('/admin/productos', productoData);
    return response.data;
  },

  // Actualizar producto
  actualizarProducto: async (id, productoData) => {
    const response = await api.put(`/admin/productos/${id}`, productoData);
    return response.data;
  },

  // Eliminar producto
  eliminarProducto: async (id) => {
    const response = await api.delete(`/admin/productos/${id}`);
    return response.data;
  },

  // Restaurar producto
  restaurarProducto: async (id) => {
    const response = await api.post(`/admin/productos/${id}/restore`);
    return response.data;
  },

  // Toggle activo/inactivo
  toggleActive: async (id) => {
    const response = await api.post(`/admin/productos/${id}/toggle-active`);
    return response.data;
  },

  // Upload de imagen
  uploadImage: async (file) => {
    console.log('API: Preparando upload de archivo:', file.name);
    const formData = new FormData();
    formData.append('image', file);
    
    console.log('API: Enviando request a /admin/productos/upload-image');
    const response = await api.post('/admin/productos/upload-image', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    
    console.log('API: Respuesta recibida:', response.data);
    return response.data;
  },

  // Estadísticas
  getStats: async () => {
    const response = await api.get('/admin/stats');
    return response.data;
  },

  // ============================================
  // ADMIN - PEDIDOS
  // ============================================
  
  // Listar pedidos con filtros
  getPedidos: async (params = {}) => {
    const response = await api.get('/admin/pedidos', { params });
    return response.data;
  },

  // Obtener pedido por ID
  getPedido: async (id) => {
    const response = await api.get(`/admin/pedidos/${id}`);
    return response.data;
  },

  // Pedidos de hoy
  getPedidosHoy: async () => {
    const response = await api.get('/admin/pedidos/hoy');
    return response.data;
  },

  // Pedidos pendientes
  getPedidosPendientes: async () => {
    const response = await api.get('/admin/pedidos/pendientes');
    return response.data;
  },

  // Pedidos para entregar hoy
  getPedidosParaHoy: async () => {
    const response = await api.get('/admin/pedidos/para-hoy');
    return response.data;
  },

  // Estadísticas de pedidos
  getPedidosStats: async (params = {}) => {
    const response = await api.get('/admin/pedidos/stats', { params });
    return response.data;
  },

  // Actualizar estado del pedido
  updateEstadoPedido: async (id, data) => {
    const response = await api.put(`/admin/pedidos/${id}/estado`, data);
    return response.data;
  },

  // Actualizar fecha y hora de entrega
  updateFechaEntrega: async (id, data) => {
    const response = await api.put(`/admin/pedidos/${id}/fecha-entrega`, data);
    return response.data;
  },

  // Agregar notas administrativas
  addNotasPedido: async (id, data) => {
    const response = await api.post(`/admin/pedidos/${id}/notas`, data);
    return response.data;
  },

  // Cancelar pedido
  cancelarPedido: async (id, data) => {
    const response = await api.post(`/admin/pedidos/${id}/cancelar`, data);
    return response.data;
  },

  // ============================================
  // ADMIN - CLIENTES
  // ============================================

  // Listar clientes
  getClientes: async (params = {}) => {
    const response = await api.get('/admin/clientes', { params });
    return response.data;
  },

  // Obtener cliente por ID
  getCliente: async (id) => {
    const response = await api.get(`/admin/clientes/${id}`);
    return response.data;
  },

  // Crear cliente
  crearCliente: async (clienteData) => {
    const response = await api.post('/admin/clientes', clienteData);
    return response.data;
  },

  // Actualizar cliente
  actualizarCliente: async (id, clienteData) => {
    const response = await api.put(`/admin/clientes/${id}`, clienteData);
    return response.data;
  },

  // Eliminar cliente
  eliminarCliente: async (id) => {
    const response = await api.delete(`/admin/clientes/${id}`);
    return response.data;
  },

  // Toggle activo/inactivo
  toggleActiveCliente: async (id) => {
    const response = await api.post(`/admin/clientes/${id}/toggle-active`);
    return response.data;
  },

  // Buscar cliente por email
  buscarClientePorEmail: async (email) => {
    const response = await api.post('/admin/clientes/buscar-email', { email });
    return response.data;
  },

  // Estadísticas de clientes
  getClientesEstadisticas: async () => {
    const response = await api.get('/admin/clientes/estadisticas');
    return response.data;
  },
};

export default api;
