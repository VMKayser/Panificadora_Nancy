import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { toast } from 'react-toastify';
import { motion } from 'framer-motion';
import { 
  Users, TrendingUp, DollarSign, Award,
  Search, Eye, Edit2, Trash2, UserX, UserCheck,
  Plus, Download
} from 'lucide-react';
import { vendedorService } from '../../services/empleadosService';
import './VendedoresList.css';

const VendedoresList = () => {
  const [vendedores, setVendedores] = useState([]);
  const [loading, setLoading] = useState(true);
  const [estadisticas, setEstadisticas] = useState({
    total: 0,
    activos: 0,
    total_ventas: 0,
    total_comisiones: 0
  });

  const [filtros, setFiltros] = useState({
    buscar: '',
    estado: '',
    turno: ''
  });

  const [paginacion, setPaginacion] = useState({
    paginaActual: 1,
    totalPaginas: 1,
    total: 0
  });

  useEffect(() => {
    cargarVendedores();
    cargarEstadisticas();
  }, [filtros, paginacion.paginaActual]);

  const cargarVendedores = async () => {
    try {
      setLoading(true);
      const params = {
        page: paginacion.paginaActual,
        buscar: filtros.buscar,
        estado: filtros.estado,
        turno: filtros.turno
      };

      const response = await vendedorService.getAll(params);
      
      if (response.success) {
        setVendedores(response.data.data || response.data);
        setPaginacion({
          paginaActual: response.data.current_page || 1,
          totalPaginas: response.data.last_page || 1,
          total: response.data.total || response.data.length
        });
      }
    } catch (error) {
      console.error('Error cargando vendedores:', error);
      toast.error('Error al cargar los vendedores');
    } finally {
      setLoading(false);
    }
  };

  const cargarEstadisticas = async () => {
    try {
      const response = await vendedorService.getEstadisticas();
      if (response.success) {
        setEstadisticas(response.data);
      }
    } catch (error) {
      console.error('Error cargando estadísticas:', error);
    }
  };

  const handleCambiarEstado = async (id, nuevoEstado) => {
    try {
      const response = await vendedorService.cambiarEstado(id, nuevoEstado);
      
      if (response.success) {
        toast.success('Estado actualizado correctamente');
        await cargarVendedores();
        await cargarEstadisticas();
      }
    } catch (error) {
      console.error('Error cambiando estado:', error);
      toast.error('Error al cambiar el estado');
    }
  };

  const handleEliminar = async (id) => {
    if (!window.confirm('¿Estás seguro de eliminar este vendedor?')) return;

    try {
      const response = await vendedorService.delete(id);
      
      if (response.success) {
        toast.success('Vendedor eliminado correctamente');
        await cargarVendedores();
        await cargarEstadisticas();
      }
    } catch (error) {
      console.error('Error eliminando vendedor:', error);
      toast.error('Error al eliminar el vendedor');
    }
  };

  const handleFiltroChange = (campo, valor) => {
    setFiltros(prev => ({ ...prev, [campo]: valor }));
    setPaginacion(prev => ({ ...prev, paginaActual: 1 }));
  };

  const getEstadoBadgeClass = (estado) => {
    const classes = {
      'activo': 'badge-success',
      'inactivo': 'badge-secondary',
      'suspendido': 'badge-danger'
    };
    return classes[estado] || 'badge-light';
  };

  const getTurnoBadgeClass = (turno) => {
    const classes = {
      'mañana': 'badge-warning',
      'tarde': 'badge-info',
      'noche': 'badge-dark',
      'flexible': 'badge-secondary'
    };
    return classes[turno] || 'badge-light';
  };

  if (loading && vendedores.length === 0) {
    return (
      <div className="vendedores-container">
        <div className="loading-container">
          <div className="spinner"></div>
          <p>Cargando vendedores...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="vendedores-container">
      {/* Header */}
      <motion.div
        initial={{ opacity: 0, y: -20 }}
        animate={{ opacity: 1, y: 0 }}
        className="page-header"
      >
        <div className="header-title">
          <h1>Gestión de Vendedores</h1>
          <p>Administra tu equipo de ventas</p>
        </div>
        <Link to="/admin/empleados/vendedores/nuevo" className="btn btn-primary">
          <Plus size={20} />
          Nuevo Vendedor
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
            <p>Total Vendedores</p>
          </div>
        </motion.div>

        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.2 }}
          className="stat-card"
        >
          <div className="stat-icon bg-success">
            <UserCheck size={28} />
          </div>
          <div className="stat-content">
            <h3>{estadisticas.activos || 0}</h3>
            <p>Vendedores Activos</p>
          </div>
        </motion.div>

        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.3 }}
          className="stat-card"
        >
          <div className="stat-icon bg-warning">
            <TrendingUp size={28} />
          </div>
          <div className="stat-content">
            <h3>Bs. {parseFloat(estadisticas.total_ventas || 0).toFixed(2)}</h3>
            <p>Total en Ventas</p>
          </div>
        </motion.div>

        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.4 }}
          className="stat-card"
        >
          <div className="stat-icon bg-info">
            <DollarSign size={28} />
          </div>
          <div className="stat-content">
            <h3>Bs. {parseFloat(estadisticas.total_comisiones || 0).toFixed(2)}</h3>
            <p>Total en Comisiones</p>
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
              placeholder="Buscar por nombre, email o código..."
              value={filtros.buscar}
              onChange={(e) => handleFiltroChange('buscar', e.target.value)}
              className="form-control"
            />
          </div>

          <select
            value={filtros.estado}
            onChange={(e) => handleFiltroChange('estado', e.target.value)}
            className="form-control"
          >
            <option value="">Todos los estados</option>
            <option value="activo">Activos</option>
            <option value="inactivo">Inactivos</option>
            <option value="suspendido">Suspendidos</option>
          </select>

          <select
            value={filtros.turno}
            onChange={(e) => handleFiltroChange('turno', e.target.value)}
            className="form-control"
          >
            <option value="">Todos los turnos</option>
            <option value="mañana">Mañana</option>
            <option value="tarde">Tarde</option>
            <option value="noche">Noche</option>
            <option value="flexible">Flexible</option>
          </select>
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
                <th>Vendedor</th>
                <th>Código</th>
                <th>Contacto</th>
                <th>Turno</th>
                <th>Comisión %</th>
                <th>Ventas</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              {vendedores.length > 0 ? (
                vendedores.map((vendedor) => (
                  <tr key={vendedor.id}>
                    <td>
                      <div className="vendedor-info">
                        <div className="vendedor-avatar">
                          {vendedor.user?.name?.charAt(0).toUpperCase() || 'V'}
                        </div>
                        <div>
                          <strong>{vendedor.user?.name || 'Sin nombre'}</strong>
                          <small>Usuario: {vendedor.user?.email || 'N/A'}</small>
                        </div>
                      </div>
                    </td>
                    <td>
                      <code className="codigo">{vendedor.codigo_vendedor}</code>
                    </td>
                    <td>
                      <div className="contact-info">
                        <div>{vendedor.user?.email || 'N/A'}</div>
                      </div>
                    </td>
                    <td>
                      <span className={`badge ${getTurnoBadgeClass(vendedor.turno)}`}>
                        {vendedor.turno?.charAt(0).toUpperCase() + vendedor.turno?.slice(1)}
                      </span>
                    </td>
                    <td>
                      <strong className="comision">{vendedor.comision_porcentaje}%</strong>
                    </td>
                    <td>
                      <div className="ventas-info">
                        <div>Bs. {parseFloat(vendedor.total_ventas || 0).toFixed(2)}</div>
                        <small>{vendedor.total_pedidos || 0} pedidos</small>
                      </div>
                    </td>
                    <td>
                      <select
                        value={vendedor.estado}
                        onChange={(e) => handleCambiarEstado(vendedor.id, e.target.value)}
                        className={`badge-select ${getEstadoBadgeClass(vendedor.estado)}`}
                      >
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                        <option value="suspendido">Suspendido</option>
                      </select>
                    </td>
                    <td>
                      <div className="action-buttons">
                        <Link
                          to={`/admin/empleados/vendedores/${vendedor.id}`}
                          className="btn btn-sm btn-info"
                          title="Ver detalle"
                        >
                          <Eye size={16} />
                        </Link>
                        <Link
                          to={`/admin/empleados/vendedores/${vendedor.id}/editar`}
                          className="btn btn-sm btn-warning"
                          title="Editar"
                        >
                          <Edit2 size={16} />
                        </Link>
                        <button
                          onClick={() => handleEliminar(vendedor.id)}
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
                  <td colSpan="8">
                    <div className="empty-state">
                      <UserX size={64} />
                      <h3>No hay vendedores registrados</h3>
                      <p>Comienza agregando tu primer vendedor</p>
                      <Link to="/admin/empleados/vendedores/nuevo" className="btn btn-primary">
                        <Plus size={18} />
                        Crear Primer Vendedor
                      </Link>
                    </div>
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>

        {/* Paginación */}
        {vendedores.length > 0 && (
          <div className="pagination-container">
            <div className="pagination-info">
              Mostrando {vendedores.length} de {paginacion.total} vendedores
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

export default VendedoresList;
