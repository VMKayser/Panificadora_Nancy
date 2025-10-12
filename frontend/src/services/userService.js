import api from './api';

/**
 * Servicio para gestión de usuarios
 */
const userService = {
  /**
   * Obtener todos los usuarios con filtros
   */
  async getAll(params = {}) {
    try {
      const response = await api.get('/admin/usuarios', { params });
      return response.data;
    } catch (error) {
      console.error('Error obteniendo usuarios:', error);
      throw error;
    }
  },

  /**
   * Obtener un usuario por ID
   */
  async getById(id) {
    try {
      const response = await api.get(`/admin/usuarios/${id}`);
      return response.data;
    } catch (error) {
      console.error('Error obteniendo usuario:', error);
      throw error;
    }
  },

  /**
   * Crear un nuevo usuario
   */
  async create(data) {
    try {
      const response = await api.post('/admin/usuarios', data);
      return response.data;
    } catch (error) {
      console.error('Error creando usuario:', error);
      throw error;
    }
  },

  /**
   * Actualizar un usuario
   */
  async update(id, data) {
    try {
      const response = await api.put(`/admin/usuarios/${id}`, data);
      return response.data;
    } catch (error) {
      console.error('Error actualizando usuario:', error);
      throw error;
    }
  },

  /**
   * Eliminar un usuario
   */
  async delete(id) {
    try {
      const response = await api.delete(`/admin/usuarios/${id}`);
      return response.data;
    } catch (error) {
      console.error('Error eliminando usuario:', error);
      throw error;
    }
  },

  /**
   * Cambiar el rol de un usuario
   */
  async cambiarRol(id, role) {
    try {
      const response = await api.post(`/admin/usuarios/${id}/cambiar-rol`, { role });
      return response.data;
    } catch (error) {
      console.error('Error cambiando rol:', error);
      throw error;
    }
  },

  /**
   * Obtener estadísticas de usuarios
   */
  async getEstadisticas() {
    try {
      const response = await api.get('/admin/usuarios/estadisticas');
      return response.data;
    } catch (error) {
      console.error('Error obteniendo estadísticas:', error);
      throw error;
    }
  },

  /**
   * Obtener usuarios disponibles para ser vendedores
   */
  async getUsuariosDisponiblesVendedor() {
    try {
      const response = await api.get('/admin/usuarios/disponibles-vendedor');
      return response.data;
    } catch (error) {
      console.error('Error obteniendo usuarios disponibles:', error);
      throw error;
    }
  }
};

export default userService;
