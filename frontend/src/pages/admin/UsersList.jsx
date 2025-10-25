import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { toast } from 'react-toastify';
import { motion } from 'framer-motion';
import { 
  Users, Shield, UserPlus, Search, Eye, Edit2, Trash2, RefreshCw
} from 'lucide-react';
import userService from '../../services/userService';
import './UsersList.css';
import useDebounce from '../../hooks/useDebounce';

const UsersList = () => {
  const [usuarios, setUsuarios] = useState([]);
  const [loading, setLoading] = useState(true);
  const [estadisticas, setEstadisticas] = useState({
    total: 0,
    admins: 0,
    vendedores: 0,
    panaderos: 0,
    clientes: 0
  });

  const [filtros, setFiltros] = useState({
    buscar: '',
    role: ''
  });

  const [paginacion, setPaginacion] = useState({
    paginaActual: 1,
    totalPaginas: 1,
    total: 0
  });

  const debouncedBuscar = useDebounce(filtros.buscar, 350);

  useEffect(() => {
    // Use debounced search value to avoid calling API on every keystroke
    cargarUsuarios(debouncedBuscar);
    cargarEstadisticas();
  }, [filtros.role, debouncedBuscar, paginacion.paginaActual]);

  const cargarUsuarios = async (buscarOverride) => {
    try {
      setLoading(true);
      const params = {
        page: paginacion.paginaActual,
        buscar: typeof buscarOverride !== 'undefined' ? buscarOverride : filtros.buscar,
        role: filtros.role
      };

      const response = await userService.getAll(params);
      
      if (response.success) {
        setUsuarios(response.data.data || response.data);
        setPaginacion({
          paginaActual: response.data.current_page || 1,
          totalPaginas: response.data.last_page || 1,
          total: response.data.total || response.data.length
        });
      }
    } catch (error) {
      console.error('Error cargando usuarios:', error);
      toast.error('Error al cargar los usuarios');
    } finally {
      setLoading(false);
    }
  };

  const cargarEstadisticas = async () => {
    try {
      const response = await userService.getEstadisticas();
      if (response.success) {
        setEstadisticas(response.data);
      }
    } catch (error) {
      console.error('Error cargando estadísticas:', error);
    }
  };

  const handleCambiarRol = async (userId, nuevoRol) => {
    try {
      const response = await userService.cambiarRol(userId, nuevoRol);
      
      if (response.success) {
        toast.success('Rol actualizado correctamente');
        await cargarUsuarios();
        await cargarEstadisticas();
      }
    } catch (error) {
      console.error('Error cambiando rol:', error);
      toast.error(error.response?.data?.message || 'Error al cambiar el rol');
    }
  };

  const handleEliminar = async (id) => {
    if (!window.confirm('¿Estás seguro de eliminar este usuario?')) return;

    try {
      const response = await userService.delete(id);
      
      if (response.success) {
        toast.success('Usuario eliminado correctamente');
        await cargarUsuarios();
        await cargarEstadisticas();
      }
    } catch (error) {
      console.error('Error eliminando usuario:', error);
      toast.error(error.response?.data?.message || 'Error al eliminar el usuario');
    }
  };

  const handleFiltroChange = (campo, valor) => {
    setFiltros(prev => ({ ...prev, [campo]: valor }));
    setPaginacion(prev => ({ ...prev, paginaActual: 1 }));
  };

  const getRoleBadgeClass = (role) => {
    const classes = {
      'admin': 'badge-danger',
      'vendedor': 'badge-success',
      'panadero': 'badge-info',
      'cliente': 'badge-secondary'
    };
    return classes[role] || 'badge-light';
  };

  const getRoleLabel = (role) => {
    const labels = {
      'admin': 'Administrador',
      'vendedor': 'Vendedor',
      'panadero': 'Panadero',
      'cliente': 'Cliente'
    };
    return labels[role] || role;
  };

  if (loading && usuarios.length === 0) {
    return (
      <div className="users-container">
        <div className="loading-container">
          <div className="spinner"></div>
          <p>Cargando usuarios...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="users-container">
      {/* Header */}
      <motion.div
        initial={{ opacity: 0, y: -20 }}
        animate={{ opacity: 1, y: 0 }}
        className="page-header"
      >
        <div className="header-title">
          <h1>Gestión de Usuarios</h1>
          <p>Administra usuarios y roles del sistema</p>
        </div>
        <Link to="/admin/usuarios/nuevo" className="btn btn-primary">
          <UserPlus size={20} />
          Nuevo Usuario
        </Link>
      </motion.div>

      {/* Estadísticas */}
      <div className="stats-grid">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.1 }}
          className="stat-card"
        >
          <div className="stat-icon bg-primary">
            <Users size={28} />
          </div>
          <div className="stat-content">
            <h3>{estadisticas.total || 0}</h3>
            <p>Total Usuarios</p>
          </div>
        </motion.div>

        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.2 }}
          className="stat-card"
        >
          <div className="stat-icon bg-danger">
            <Shield size={28} />
          </div>
          <div className="stat-content">
            <h3>{estadisticas.admins || 0}</h3>
            <p>Administradores</p>
          </div>
        </motion.div>

        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.3 }}
          className="stat-card"
        >
          <div className="stat-icon bg-success">
            <Users size={28} />
          </div>
          <div className="stat-content">
            <h3>{estadisticas.vendedores || 0}</h3>
            <p>Vendedores</p>
          </div>
        </motion.div>

        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.4 }}
          className="stat-card"
        >
          <div className="stat-icon bg-info">
            <Users size={28} />
          </div>
          <div className="stat-content">
            <h3>{estadisticas.panaderos || 0}</h3>
            <p>Panaderos</p>
          </div>
        </motion.div>
      </div>

      {/* Filtros */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.5 }}
        className="filters-section"
      >
        <div className="filters-grid">
          <div className="filter-group">
            <Search size={18} />
            <input
              type="text"
              placeholder="Buscar por nombre o email..."
              value={filtros.buscar}
              onChange={(e) => handleFiltroChange('buscar', e.target.value)}
              className="form-control"
            />
          </div>

          <select
            value={filtros.role}
            onChange={(e) => handleFiltroChange('role', e.target.value)}
            className="form-control"
          >
            <option value="">Todos los roles</option>
            <option value="admin">Administradores</option>
            <option value="vendedor">Vendedores</option>
            <option value="panadero">Panaderos</option>
            <option value="cliente">Clientes</option>
          </select>

          <button
            onClick={() => {
              setFiltros({ buscar: '', role: '' });
              setPaginacion(prev => ({ ...prev, paginaActual: 1 }));
            }}
            className="btn btn-secondary"
          >
            <RefreshCw size={18} />
            Limpiar Filtros
          </button>
        </div>
      </motion.div>

      {/* Tabla */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.6 }}
        className="card"
      >
        <div className="table-responsive">
          <table className="table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Email</th>
                <th>Rol Actual</th>
                <th>Cambiar Rol</th>
                <th>Fecha Registro</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              {usuarios.length > 0 ? (
                usuarios.map((usuario) => (
                  <tr key={usuario.id}>
                    <td>
                      <strong>#{usuario.id}</strong>
                    </td>
                    <td>
                      <div className="user-info">
                        <div className="user-avatar">
                          {(String(usuario.name || '').charAt(0).toUpperCase()) || 'U'}
                        </div>
                        <strong>{usuario.name}</strong>
                      </div>
                    </td>
                    <td>{usuario.email}</td>
                    <td>
                      <span className={`badge ${getRoleBadgeClass(usuario.role)}`}>
                        {getRoleLabel(usuario.role)}
                      </span>
                    </td>
                    <td>
                      <select
                        value={usuario.role}
                        onChange={(e) => handleCambiarRol(usuario.id, e.target.value)}
                        className={`role-select ${getRoleBadgeClass(usuario.role)}`}
                      >
                        <option value="cliente">Cliente</option>
                        <option value="panadero">Panadero</option>
                        <option value="vendedor">Vendedor</option>
                        <option value="admin">Administrador</option>
                      </select>
                    </td>
                    <td>
                      {new Date(usuario.created_at).toLocaleDateString('es-BO')}
                    </td>
                    <td>
                      <div className="action-buttons">
                        <Link
                          to={`/admin/usuarios/${usuario.id}`}
                          className="btn btn-sm btn-info"
                          title="Ver detalle"
                        >
                          <Eye size={16} />
                        </Link>
                        <Link
                          to={`/admin/usuarios/${usuario.id}/editar`}
                          className="btn btn-sm btn-warning"
                          title="Editar"
                        >
                          <Edit2 size={16} />
                        </Link>
                        <button
                          onClick={() => handleEliminar(usuario.id)}
                          className="btn btn-sm btn-danger"
                          title="Eliminar"
                        >
                          <Trash2 size={16} />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan="7">
                    <div className="empty-state">
                      <Users size={64} />
                      <h3>No se encontraron usuarios</h3>
                      <p>Ajusta los filtros o crea un nuevo usuario</p>
                    </div>
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>

        {/* Paginación */}
        {usuarios.length > 0 && (
          <div className="pagination-container">
            <div className="pagination-info">
              Mostrando {usuarios.length} de {paginacion.total} usuarios
            </div>
            <div className="pagination-buttons">
              <button
                className="btn btn-secondary"
                onClick={() => setPaginacion(prev => ({ ...prev, paginaActual: prev.paginaActual - 1 }))}
                disabled={paginacion.paginaActual === 1}
              >
                Anterior
              </button>
              <span className="pagination-current">
                Página {paginacion.paginaActual} de {paginacion.totalPaginas}
              </span>
              <button
                className="btn btn-secondary"
                onClick={() => setPaginacion(prev => ({ ...prev, paginaActual: prev.paginaActual + 1 }))}
                disabled={paginacion.paginaActual >= paginacion.totalPaginas}
              >
                Siguiente
              </button>
            </div>
          </div>
        )}
      </motion.div>
    </div>
  );
};

export default UsersList;
