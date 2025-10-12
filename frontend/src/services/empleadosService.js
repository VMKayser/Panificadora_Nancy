import api from './api';

// ============================================
// SERVICIOS PARA PANADEROS
// ============================================

export const pananaderoService = {
  // Listar panaderos con filtros
  getAll: (params = {}) => {
    return api.get('/admin/empleados/panaderos', { params });
  },

  // Obtener un panadero por ID
  getById: (id) => {
    return api.get(`/admin/empleados/panaderos/${id}`);
  },

  // Crear nuevo panadero
  create: (data) => {
    return api.post('/admin/empleados/panaderos', data);
  },

  // Actualizar panadero
  update: (id, data) => {
    return api.put(`/admin/empleados/panaderos/${id}`, data);
  },

  // Eliminar panadero
  delete: (id) => {
    return api.delete(`/admin/empleados/panaderos/${id}`);
  },

  // Activar/Desactivar panadero
  toggleActivo: (id) => {
    return api.post(`/admin/empleados/panaderos/${id}/toggle-activo`);
  },

  // Obtener estadísticas
  getEstadisticas: () => {
    return api.get('/admin/empleados/panaderos/estadisticas');
  }
};

// ============================================
// SERVICIOS PARA VENDEDORES
// ============================================

export const vendedorService = {
  // Listar vendedores con filtros
  getAll: (params = {}) => {
    return api.get('/admin/empleados/vendedores', { params });
  },

  // Obtener un vendedor por ID
  getById: (id) => {
    return api.get(`/admin/empleados/vendedores/${id}`);
  },

  // Crear nuevo vendedor
  create: (data) => {
    return api.post('/admin/empleados/vendedores', data);
  },

  // Actualizar vendedor
  update: (id, data) => {
    return api.put(`/admin/empleados/vendedores/${id}`, data);
  },

  // Eliminar vendedor
  delete: (id) => {
    return api.delete(`/admin/empleados/vendedores/${id}`);
  },

  // Cambiar estado del vendedor
  cambiarEstado: (id, estado) => {
    return api.post(`/admin/empleados/vendedores/${id}/cambiar-estado`, { estado });
  },

  // Obtener estadísticas
  getEstadisticas: () => {
    return api.get('/admin/empleados/vendedores/estadisticas');
  },

  // Reporte de ventas
  getReporteVentas: (id, params = {}) => {
    return api.get(`/admin/empleados/vendedores/${id}/reporte-ventas`, { params });
  }
};

// ============================================
// SERVICIOS PARA CONFIGURACIONES
// ============================================

export const configuracionService = {
  // Listar todas las configuraciones
  getAll: () => {
    return api.get('/admin/configuraciones');
  },

  // Obtener una configuración específica
  getByKey: (clave) => {
    return api.get(`/admin/configuraciones/${clave}`);
  },

  // Obtener solo el valor
  getValue: (clave) => {
    return api.get(`/admin/configuraciones/${clave}/valor`);
  },

  // Crear o actualizar configuración
  save: (data) => {
    return api.post('/admin/configuraciones', data);
  },

  // Actualizar múltiples configuraciones
  updateMultiple: (configuraciones) => {
    return api.put('/admin/configuraciones/actualizar-multiples', { configuraciones });
  },

  // Eliminar configuración
  delete: (clave) => {
    return api.delete(`/admin/configuraciones/${clave}`);
  },

  // Inicializar configuraciones por defecto
  initDefaults: () => {
    return api.post('/admin/configuraciones/inicializar-defecto');
  }
};

export default {
  panaderos: pananaderoService,
  vendedores: vendedorService,
  configuraciones: configuracionService
};
