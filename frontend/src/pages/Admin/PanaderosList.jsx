import { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { motion } from 'framer-motion';
import { 
  UserPlus, 
  Search, 
  Filter,
  Edit2,
  Trash2,
  Eye,
  CheckCircle,
  XCircle,
  Clock,
  TrendingUp
} from 'lucide-react';
import { toast } from 'react-toastify';
import { pananaderoService } from '../../services/empleadosService';
import './PanaderosList.css';

const PanaderosList = () => {
  const navigate = useNavigate();
  const [panaderos, setPanaderos] = useState([]);
  const [loading, setLoading] = useState(true);
  const [estadisticas, setEstadisticas] = useState(null);
  
  // Filtros
  const [filtros, setFiltros] = useState({
    buscar: '',
    activo: '',
    turno: '',
    especialidad: '',
    sort_by: 'created_at',
    sort_order: 'desc',
    per_page: 15
  });

  // Paginación
  const [paginacion, setPaginacion] = useState({
    current_page: 1,
    last_page: 1,
    total: 0
  });

  useEffect(() => {
    cargarPanaderos();
    cargarEstadisticas();
  }, [filtros]);

  const cargarPanaderos = async () => {
    try {
      setLoading(true);
      const response = await pananaderoService.getAll(filtros);
      setPanaderos(response.data);
      setPaginacion({
        current_page: response.current_page,
        last_page: response.last_page,
        total: response.total
      });
    } catch (error) {
      console.error('Error al cargar panaderos:', error);
      toast.error('Error al cargar la lista de panaderos');
    } finally {
      setLoading(false);
    }
  };

  const cargarEstadisticas = async () => {
    try {
      const response = await pananaderoService.getEstadisticas();
      setEstadisticas(response);
    } catch (error) {
      console.error('Error al cargar estadísticas:', error);
    }
  };

  const handleFiltroChange = (e) => {
    const { name, value } = e.target;
    setFiltros(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleToggleActivo = async (id) => {
    try {
      await pananaderoService.toggleActivo(id);
      toast.success('Estado actualizado correctamente');
      cargarPanaderos();
      cargarEstadisticas();
    } catch (error) {
      console.error('Error al cambiar estado:', error);
      toast.error('Error al actualizar el estado');
    }
  };

  const handleEliminar = async (id, nombre) => {
    if (window.confirm(`¿Está seguro de eliminar al panadero ${nombre}?`)) {
      try {
        await pananaderoService.delete(id);
        toast.success('Panadero eliminado correctamente');
        cargarPanaderos();
        cargarEstadisticas();
      } catch (error) {
        console.error('Error al eliminar:', error);
        toast.error('Error al eliminar el panadero');
      }
    }
  };

  const getTurnoColor = (turno) => {
    const colors = {
      'mañana': 'badge-warning',
      'tarde': 'badge-info',
      'noche': 'badge-dark',
      'rotativo': 'badge-secondary'
    };
    return colors[turno] || 'badge-secondary';
  };

  const getEspecialidadIcon = (especialidad) => {
    const icons = {
      'pan': '🍞',
      'reposteria': '🍰',
      'ambos': '🥐'
    };
    return icons[especialidad] || '👨‍🍳';
  };

  return (
    <div className="panaderos-container">
      {/* Header con estadísticas */}
      <div className="page-header">
        <div className="header-title">
          <h1>👨‍🍳 Gestión de Panaderos</h1>
          <p>Administra el personal de producción de la panadería</p>
        </div>
        <Link to="/admin/empleados/panaderos/nuevo" className="btn btn-primary">
          <UserPlus size={20} />
          Nuevo Panadero
        </Link>
      </div>

      {/* Estadísticas */}
      {estadisticas && (
        <motion.div 
          className="stats-grid"
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
        >
          <div className="stat-card">
            <div className="stat-icon bg-primary">
              <UserPlus size={24} />
            </div>
            <div className="stat-content">
              <h3>{estadisticas.total_panaderos}</h3>
              <p>Total Panaderos</p>
            </div>
          </div>

          <div className="stat-card">
            <div className="stat-icon bg-success">
              <CheckCircle size={24} />
            </div>
            <div className="stat-content">
              <h3>{estadisticas.panaderos_activos}</h3>
              <p>Activos</p>
            </div>
          </div>

          <div className="stat-card">
            <div className="stat-icon bg-warning">
              <TrendingUp size={24} />
            </div>
            <div className="stat-content">
              <h3>{estadisticas.total_kilos_producidos.toLocaleString()}</h3>
              <p>Kilos Producidos</p>
            </div>
          </div>

          <div className="stat-card">
            <div className="stat-icon bg-info">
              <Clock size={24} />
            </div>
            <div className="stat-content">
              <h3>Bs {estadisticas.salario_total_mensual.toLocaleString()}</h3>
              <p>Salarios Totales</p>
            </div>
          </div>
        </motion.div>
      )}

      {/* Filtros */}
      <div className="filters-section">
        <div className="filters-grid">
          <div className="filter-group">
            <Search size={18} />
            <input
              type="text"
              name="buscar"
              placeholder="Buscar por nombre, CI o email..."
              value={filtros.buscar}
              onChange={handleFiltroChange}
              className="form-control"
            />
          </div>

          <select
            name="activo"
            value={filtros.activo}
            onChange={handleFiltroChange}
            className="form-control"
          >
            <option value="">Todos los estados</option>
            <option value="1">Activos</option>
            <option value="0">Inactivos</option>
          </select>

          <select
            name="turno"
            value={filtros.turno}
            onChange={handleFiltroChange}
            className="form-control"
          >
            <option value="">Todos los turnos</option>
            <option value="mañana">Mañana</option>
            <option value="tarde">Tarde</option>
            <option value="noche">Noche</option>
            <option value="rotativo">Rotativo</option>
          </select>

          <select
            name="especialidad"
            value={filtros.especialidad}
            onChange={handleFiltroChange}
            className="form-control"
          >
            <option value="">Todas las especialidades</option>
            <option value="pan">Pan</option>
            <option value="reposteria">Repostería</option>
            <option value="ambos">Ambos</option>
          </select>
        </div>
      </div>

      {/* Tabla de panaderos */}
      <div className="card">
        <div className="table-responsive">
          {loading ? (
            <div className="loading-container">
              <div className="spinner"></div>
              <p>Cargando panaderos...</p>
            </div>
          ) : panaderos.length === 0 ? (
            <div className="empty-state">
              <UserPlus size={64} />
              <h3>No hay panaderos registrados</h3>
              <p>Comienza agregando tu primer panadero</p>
              <Link to="/admin/empleados/panaderos/nuevo" className="btn btn-primary">
                Agregar Panadero
              </Link>
            </div>
          ) : (
            <table className="table">
              <thead>
                <tr>
                  <th>Panadero</th>
                  <th>Contacto</th>
                  <th>Turno</th>
                  <th>Especialidad</th>
                  <th>Salario</th>
                  <th>Producción</th>
                  <th>Estado</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                {panaderos.map((panadero) => (
                  <motion.tr
                    key={panadero.id}
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    transition={{ duration: 0.3 }}
                  >
                    <td>
                      <div className="panadero-info">
                        <div className="panadero-avatar">
                          {getEspecialidadIcon(panadero.especialidad)}
                        </div>
                        <div>
                          <strong>{panadero.nombre_completo}</strong>
                          <small>CI: {panadero.ci}</small>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div className="contact-info">
                        <div>{panadero.telefono}</div>
                        <small>{panadero.email}</small>
                      </div>
                    </td>
                    <td>
                      <span className={`badge ${getTurnoColor(panadero.turno)}`}>
                        {panadero.turno}
                      </span>
                    </td>
                    <td>
                      <span className="badge badge-light">
                        {getEspecialidadIcon(panadero.especialidad)} {panadero.especialidad}
                      </span>
                    </td>
                    <td>
                      <strong>Bs {panadero.salario_base.toLocaleString()}</strong>
                    </td>
                    <td>
                      <div className="produccion-info">
                        <div>{panadero.total_kilos_producidos} kg</div>
                        <small>{panadero.total_unidades_producidas} uds</small>
                      </div>
                    </td>
                    <td>
                      <button
                        onClick={() => handleToggleActivo(panadero.id)}
                        className={`btn-toggle ${panadero.activo ? 'active' : 'inactive'}`}
                        title={panadero.activo ? 'Desactivar' : 'Activar'}
                      >
                        {panadero.activo ? (
                          <>
                            <CheckCircle size={16} /> Activo
                          </>
                        ) : (
                          <>
                            <XCircle size={16} /> Inactivo
                          </>
                        )}
                      </button>
                    </td>
                    <td>
                      <div className="action-buttons">
                        <button
                          onClick={() => navigate(`/admin/empleados/panaderos/${panadero.id}`)}
                          className="btn btn-sm btn-info"
                          title="Ver detalle"
                        >
                          <Eye size={16} />
                        </button>
                        <button
                          onClick={() => navigate(`/admin/empleados/panaderos/${panadero.id}/editar`)}
                          className="btn btn-sm btn-warning"
                          title="Editar"
                        >
                          <Edit2 size={16} />
                        </button>
                        <button
                          onClick={() => handleEliminar(panadero.id, panadero.nombre_completo)}
                          className="btn btn-sm btn-danger"
                          title="Eliminar"
                        >
                          <Trash2 size={16} />
                        </button>
                      </div>
                    </td>
                  </motion.tr>
                ))}
              </tbody>
            </table>
          )}
        </div>

        {/* Paginación */}
        {paginacion.last_page > 1 && (
          <div className="pagination-container">
            <div className="pagination-info">
              Mostrando página {paginacion.current_page} de {paginacion.last_page} ({paginacion.total} registros)
            </div>
            <div className="pagination-buttons">
              <button
                onClick={() => setFiltros(prev => ({ ...prev, page: paginacion.current_page - 1 }))}
                disabled={paginacion.current_page === 1}
                className="btn btn-sm btn-secondary"
              >
                Anterior
              </button>
              <button
                onClick={() => setFiltros(prev => ({ ...prev, page: paginacion.current_page + 1 }))}
                disabled={paginacion.current_page === paginacion.last_page}
                className="btn btn-sm btn-secondary"
              >
                Siguiente
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default PanaderosList;
