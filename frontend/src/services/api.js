import axios from 'axios';

// Configurar la URL base de la API.
// Preferir la variable de entorno VITE_API_URL (recomendada).
// Si no está, usar por defecto el backend en el host local (puerto 80): http://localhost/api
// Esto evita que el dev-server de Vite (por ejemplo :5174) pase a apuntar a /api en su propio origen,
// lo que provoca 'Failed to fetch' o 401 al llamar al backend real.
let baseURL = import.meta?.env?.VITE_API_URL || 'http://localhost/api';

// If VITE_API_URL is a relative path (starts with '/'), let axios use the current origin
// (important when code runs through a public tunnel — the browser origin will be the tunnel).
if (typeof baseURL === 'string' && baseURL.startsWith('/')) {
  // keep as-is
} else if (typeof baseURL === 'string' && baseURL.endsWith('/api')) {
  // normalize to ensure trailing /api is present without double slashes
  baseURL = baseURL.replace(/\/$/, '');
}

// Log para depuración rápida en desarrollo
if (typeof window !== 'undefined') {
  // eslint-disable-next-line no-console
  console.info('[api] baseURL =', baseURL, ' (window.location.origin =', window.location.origin + ')');
}

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
      // Token expirado o inválido: limpiar storage. No hacemos redirect aquí
      // para evitar recargas completas; la lógica de React (AuthContext) se encargará
      // de dirigir al usuario a la página de login usando el router.
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user');
    }
    return Promise.reject(error);
  }
);


export const getProductos = async (params = {}) => {
  try {
    const response = await api.get('/productos', { params });
    return response.data;
  } catch (error) {
    console.warn('API /productos failed, loading sample products fallback:', error.message);
    // Fallback to a local sample JSON so the frontend can work offline during dev
    const resp = await fetch('/sample-products.json');
    if (!resp.ok) throw error; // rethrow original error if fallback unavailable
    const data = await resp.json();
    return data;
  }
};

export const getProducto = async (id) => {
  const response = await api.get(`/productos/${id}`);
  return response.data;
};

// Fallback to get users from sample JSON when backend is not available
export const getUsuarios = async () => {
  // Direct call to backend; do not fallback to local sample users in production builds.
  const response = await api.get('/usuarios');
  return response.data;
};


export const crearPedido = async (pedidoData) => {
  const response = await api.post('/pedidos', pedidoData);
  return response.data;
};

// Crear producción (interfaz panadero)
export const crearProduccion = async (produccionData) => {
  const response = await api.post('/inventario/producciones', produccionData);
  return response.data;
};

export const getMetodosPago = async () => {
  const response = await api.get('/metodos-pago');
  return response.data;
};

// Simple in-memory cache for methods of payment to avoid repeated calls
// TTL is configurable here (milliseconds). This cache only lives for the lifetime
// of the page (memory) which is fine for a small public list like payment methods.
let _metodosPagoCache = null;
let _metodosPagoCacheAt = 0;
const METODOS_TTL = 1000 * 60 * 5; // 5 minutes

export const getMetodosPagoCached = async (forceRefresh = false) => {
  const now = Date.now();
  if (!forceRefresh && _metodosPagoCache && now - _metodosPagoCacheAt < METODOS_TTL) {
    return _metodosPagoCache;
  }

  const response = await api.get('/metodos-pago');
  _metodosPagoCache = response.data;
  _metodosPagoCacheAt = Date.now();
  return _metodosPagoCache;
};

export const clearMetodosPagoCache = () => {
  _metodosPagoCache = null;
  _metodosPagoCacheAt = 0;
};

// Helper para construir URLs a assets subidos en el backend (storage)
export const assetBase = () => {
  // Si la VITE_API_URL apunta a /api, quitar el sufijo para obtener el host
  const env = import.meta?.env?.VITE_API_URL || '';
  if (!env) return '';
  // Si termina en /api, removerlo
  if (env.endsWith('/api')) return env.replace(/\/api$/, '');
  return env.replace(/\/$/, '');
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
    try {
      const response = await api.post('/login', credentials);
      const data = response.data;

      // Backend may return access_token or token
      const token = data.access_token || data.token || data.accessToken || null;
      const user = data.user || data.usuario || data;

      if (token) {
        localStorage.setItem('auth_token', token);
      }

      if (user) {
        localStorage.setItem('user', JSON.stringify(user));
      }

      return data;
    } catch (error) {
      // Do not fallback to local sample users for login in production-ready frontend.
      throw error;
    }
  },

  // Logout
  logout: async () => {
    try {
      const response = await api.post('/logout');
      // Clear storage regardless of response
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user');
      return response.data;
    } catch (error) {
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user');
      throw error;
    }
  },

  // Obtener usuario actual
  me: async () => {
    try {
      const response = await api.get('/me');
      return response.data;
    } catch (error) {
      throw error;
    }
  },

  // Actualizar perfil
  updateProfile: async (profileData) => {
    const response = await api.put('/profile', profileData);
    return response.data;
  },

  // Resend verification email. If `email` is provided we send for that address (public),
  // otherwise call authenticated resend endpoint.
  resendVerification: async (email = null) => {
    if (email) {
      const response = await api.post('/email/resend', { email });
      return response.data;
    }
    const response = await api.post('/email/resend');
    return response.data;
  },

  // Obtener pedidos del usuario actual
  getMisPedidos: async () => {
    const response = await api.get('/mis-pedidos');
    return response.data;
  },

  // Obtener detalle de un pedido específico
  getMiPedidoDetalle: async (id) => {
    const response = await api.get(`/mis-pedidos/${id}`);
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

  // Crear pedido (venta mostrador)
  createPedido: async (pedidoData) => {
    const response = await api.post('/pedidos', pedidoData);
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

  // Crear usuario (administración)
  crearUsuario: async (userData) => {
    const response = await api.post('/admin/usuarios', userData);
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

  // ============================================
  // ADMIN - PANADEROS
  // ============================================
  getPanaderos: async (params = {}) => {
    const response = await api.get('/admin/panaderos', { params });
    // Backend may return a paginator directly or wrapped under data
    return response.data?.data || response.data;
  },

  getPanaderosEstadisticas: async () => {
    const response = await api.get('/admin/empleados/panaderos/estadisticas');
    return response.data;
  },

  crearPanadero: async (panaderoData) => {
    const response = await api.post('/admin/panaderos', panaderoData);
    return response.data;
  },
  // Empleado pagos (history + create)
  crearEmpleadoPago: async (pagoData) => {
    const response = await api.post('/admin/empleado-pagos', pagoData);
    return response.data;
  },

  listarEmpleadoPagos: async (params = {}) => {
    const response = await api.get('/admin/empleado-pagos', { params });
    return response.data;
  },

  actualizarPanadero: async (id, data) => {
    const response = await api.put(`/admin/panaderos/${id}`, data);
    return response.data;
  },

  eliminarPanadero: async (id) => {
    const response = await api.delete(`/admin/panaderos/${id}`);
    return response.data;
  },
  
  // Toggle activo/inactivo panadero
  toggleActivoPanadero: async (id) => {
    const response = await api.post(`/admin/empleados/panaderos/${id}/toggle-activo`);
    return response.data;
  },

  // ============================================
  // ADMIN - CATEGORÍAS
  // ============================================
  getCategorias: async (params = {}) => {
    const response = await api.get('/admin/categorias', { params });
    return response.data;
  },

  createCategoria: async (data) => {
    const response = await api.post('/admin/categorias', data);
    return response.data;
  },

  updateCategoria: async (id, data) => {
    const response = await api.put(`/admin/categorias/${id}`, data);
    return response.data;
  },

  deleteCategoria: async (id) => {
    const response = await api.delete(`/admin/categorias/${id}`);
    return response.data;
  },

  toggleCategoriaActive: async (id) => {
    const response = await api.post(`/admin/categorias/${id}/toggle-active`);
    return response.data;
  },

  reorderCategorias: async (categorias) => {
    const response = await api.post('/admin/categorias/reorder', { categorias });
    return response.data;
  },

  // ============================================
  // ADMIN - INVENTARIO (materias primas)
  // ============================================
  getMateriasPrimas: async (params = {}) => {
    const response = await api.get('/inventario/materias-primas', { params });
    return response.data;
  },

  crearMateriaPrima: async (data) => {
    const response = await api.post('/inventario/materias-primas', data);
    return response.data;
  },

  actualizarMateriaPrima: async (id, data) => {
    const response = await api.put(`/inventario/materias-primas/${id}`, data);
    return response.data;
  },

  eliminarMateriaPrima: async (id) => {
    const response = await api.delete(`/inventario/materias-primas/${id}`);
    return response.data;
  },

  registrarCompraMateriaPrima: async (id, data) => {
    const response = await api.post(`/inventario/materias-primas/${id}/compra`, data);
    return response.data;
  },

  ajustarStockMateriaPrima: async (id, data) => {
    const response = await api.post(`/inventario/materias-primas/${id}/ajuste`, data);
    return response.data;
  },

  getMovimientosMateriaPrima: async (id, params = {}) => {
    const response = await api.get(`/inventario/materias-primas/${id}/movimientos`, { params });
    return response.data;
  },

  // INVENTARIO PRODUCTOS FINALES
  getProductosFinales: async (params = {}) => {
    const response = await api.get('/inventario/productos-finales', { params });
    return response.data;
  },

  getMovimientosProductos: async (params = {}) => {
    const response = await api.get('/inventario/movimientos-productos', { params });
    return response.data;
  },

  ajustarInventarioProducto: async (productoId, data) => {
    const response = await api.post(`/inventario/productos/${productoId}/ajustar`, data);
    return response.data;
  },

  getKardex: async (productoId, params = {}) => {
    const response = await api.get(`/inventario/kardex/${productoId}`, { params });
    return response.data;
  },

  getDashboardInventario: async () => {
    const response = await api.get('/inventario/dashboard');
    return response.data;
  },

  // ============================================
  // ADMIN - VENDEDORES
  // ============================================
  getVendedores: async (params = {}) => {
    const response = await api.get('/admin/empleados/vendedores', { params });
    // Normalize to support both { data: [...] } or direct array/paginator
    return response.data?.data || response.data;
  },

  getVendedor: async (id) => {
    const response = await api.get(`/admin/empleados/vendedores/${id}`);
    return response.data;
  },

  crearVendedor: async (data) => {
    const response = await api.post('/admin/empleados/vendedores', data);
    return response.data;
  },

  actualizarVendedor: async (id, data) => {
    const response = await api.put(`/admin/empleados/vendedores/${id}`, data);
    return response.data;
  },

  eliminarVendedor: async (id) => {
    const response = await api.delete(`/admin/empleados/vendedores/${id}`);
    return response.data;
  },

  cambiarEstadoVendedor: async (id) => {
    const response = await api.post(`/admin/empleados/vendedores/${id}/cambiar-estado`);
    return response.data;
  },

  getVendedoresEstadisticas: async () => {
    const response = await api.get('/admin/empleados/vendedores/estadisticas');
    return response.data;
  },

  reporteVentasVendedor: async (id, params = {}) => {
    const response = await api.get(`/admin/empleados/vendedores/${id}/reporte-ventas`, { params });
    return response.data;
  },

  // ============================================
  // ADMIN - USUARIOS (Gestión de roles)
  // ============================================
  getUsuarios: async (params = {}) => {
    const response = await api.get('/admin/usuarios', { params });
    // The UserController returns { success: true, data: paginator }
    // Normalize to either paginator or array for callers
    if (response.data && response.data.data) return response.data.data;
    return response.data;
  },

  actualizarRolUsuario: async (id, role) => {
    const response = await api.put(`/admin/usuarios/${id}`, { role });
    return response.data;
  },
  // Actualizar usuario (Admin user record)
  actualizarUsuario: async (id, data) => {
    const response = await api.put(`/admin/usuarios/${id}`, data);
    return response.data;
  },
  eliminarUsuario: async (id) => {
    const response = await api.delete(`/admin/usuarios/${id}`);
    return response.data;
  },
};

export default api;
