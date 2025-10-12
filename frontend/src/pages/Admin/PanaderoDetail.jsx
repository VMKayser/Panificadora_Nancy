import { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { toast } from 'react-toastify';
import { motion } from 'framer-motion';
import {
  ArrowLeft, Edit2, Trash2, User, Mail, Phone, MapPin, Calendar,
  DollarSign, Briefcase, Clock, TrendingUp, Package, Award, Power
} from 'lucide-react';
import { pananaderoService } from '../../services/empleadosService';
import './PanaderoDetail.css';

const PanaderoDetail = () => {
  const { id } = useParams();
  const navigate = useNavigate();

  const [panadero, setPanadero] = useState(null);
  const [loading, setLoading] = useState(true);
  const [deleting, setDeleting] = useState(false);

  useEffect(() => {
    cargarPanadero();
  }, [id]);

  const cargarPanadero = async () => {
    try {
      setLoading(true);
      const response = await pananaderoService.getById(id);
      
      if (response.success) {
        setPanadero(response.data);
      }
    } catch (error) {
      console.error('Error cargando panadero:', error);
      toast.error('Error al cargar los datos del panadero');
      navigate('/admin/empleados/panaderos');
    } finally {
      setLoading(false);
    }
  };

  const handleToggleActivo = async () => {
    try {
      const response = await pananaderoService.toggleActivo(id);
      
      if (response.success) {
        toast.success(
          panadero.activo 
            ? 'Panadero desactivado correctamente' 
            : 'Panadero activado correctamente'
        );
        await cargarPanadero();
      }
    } catch (error) {
      console.error('Error toggling activo:', error);
      toast.error('Error al cambiar el estado del panadero');
    }
  };

  const handleDelete = async () => {
    if (!window.confirm('¿Estás seguro de eliminar este panadero? Esta acción no se puede deshacer.')) {
      return;
    }

    try {
      setDeleting(true);
      const response = await pananaderoService.delete(id);
      
      if (response.success) {
        toast.success('Panadero eliminado correctamente');
        navigate('/admin/empleados/panaderos');
      }
    } catch (error) {
      console.error('Error eliminando panadero:', error);
      toast.error('Error al eliminar el panadero');
    } finally {
      setDeleting(false);
    }
  };

  const getTurnoBadgeClass = (turno) => {
    const classes = {
      'mañana': 'badge-warning',
      'tarde': 'badge-info',
      'noche': 'badge-dark',
      'rotativo': 'badge-secondary'
    };
    return classes[turno] || 'badge-light';
  };

  const getEspecialidadBadgeClass = (especialidad) => {
    const classes = {
      'pan': 'badge-primary',
      'reposteria': 'badge-success',
      'ambos': 'badge-info'
    };
    return classes[especialidad] || 'badge-light';
  };

  const formatFecha = (fecha) => {
    if (!fecha) return 'N/A';
    return new Date(fecha).toLocaleDateString('es-BO', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  };

  const calcularAntiguedad = (fechaIngreso) => {
    if (!fechaIngreso) return 'N/A';
    const inicio = new Date(fechaIngreso);
    const hoy = new Date();
    const años = hoy.getFullYear() - inicio.getFullYear();
    const meses = hoy.getMonth() - inicio.getMonth();
    
    if (años > 0) {
      return `${años} año${años > 1 ? 's' : ''} ${meses > 0 ? `y ${meses} mes${meses > 1 ? 'es' : ''}` : ''}`;
    }
    return `${meses} mes${meses > 1 ? 'es' : ''}`;
  };

  if (loading) {
    return (
      <div className="panadero-detail-container">
        <div className="loading-container">
          <div className="spinner"></div>
          <p>Cargando datos...</p>
        </div>
      </div>
    );
  }

  if (!panadero) {
    return (
      <div className="panadero-detail-container">
        <div className="empty-state">
          <User size={64} />
          <h3>Panadero no encontrado</h3>
          <Link to="/admin/empleados/panaderos" className="btn btn-primary">
            Volver a la lista
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="panadero-detail-container">
      {/* Header */}
      <motion.div
        initial={{ opacity: 0, y: -20 }}
        animate={{ opacity: 1, y: 0 }}
        className="page-header"
      >
        <div className="header-content">
          <div className="header-title">
            <Link to="/admin/empleados/panaderos" className="back-link">
              <ArrowLeft size={20} />
              Volver a la lista
            </Link>
            <div className="title-row">
              <h1>{panadero.nombre} {panadero.apellido}</h1>
              <span className={`badge ${panadero.activo ? 'badge-success' : 'badge-danger'}`}>
                {panadero.activo ? 'Activo' : 'Inactivo'}
              </span>
            </div>
            <p className="subtitle">Detalles completos del panadero</p>
          </div>

          <div className="header-actions">
            <button
              className={`btn btn-toggle ${panadero.activo ? 'active' : 'inactive'}`}
              onClick={handleToggleActivo}
            >
              <Power size={18} />
              {panadero.activo ? 'Desactivar' : 'Activar'}
            </button>
            
            <Link
              to={`/admin/empleados/panaderos/${id}/editar`}
              className="btn btn-warning"
            >
              <Edit2 size={18} />
              Editar
            </Link>

            <button
              className="btn btn-danger"
              onClick={handleDelete}
              disabled={deleting}
            >
              {deleting ? (
                <>
                  <div className="spinner-sm"></div>
                  Eliminando...
                </>
              ) : (
                <>
                  <Trash2 size={18} />
                  Eliminar
                </>
              )}
            </button>
          </div>
        </div>
      </motion.div>

      <div className="detail-grid">
        {/* Información Personal */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.1 }}
          className="detail-card"
        >
          <div className="card-header">
            <User size={20} />
            <h2>Información Personal</h2>
          </div>
          <div className="card-body">
            <div className="info-grid">
              <div className="info-item">
                <label>Nombre Completo</label>
                <p>{panadero.nombre} {panadero.apellido}</p>
              </div>

              <div className="info-item">
                <label>CI</label>
                <p>{panadero.ci}</p>
              </div>

              <div className="info-item">
                <label>
                  <Mail size={16} />
                  Email
                </label>
                <p>
                  <a href={`mailto:${panadero.email}`}>{panadero.email}</a>
                </p>
              </div>

              <div className="info-item">
                <label>
                  <Phone size={16} />
                  Teléfono
                </label>
                <p>
                  <a href={`tel:${panadero.telefono}`}>{panadero.telefono}</a>
                </p>
              </div>

              {panadero.direccion && (
                <div className="info-item full-width">
                  <label>
                    <MapPin size={16} />
                    Dirección
                  </label>
                  <p>{panadero.direccion}</p>
                </div>
              )}
            </div>
          </div>
        </motion.div>

        {/* Información Laboral */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.2 }}
          className="detail-card"
        >
          <div className="card-header">
            <Briefcase size={20} />
            <h2>Información Laboral</h2>
          </div>
          <div className="card-body">
            <div className="info-grid">
              <div className="info-item">
                <label>
                  <Calendar size={16} />
                  Fecha de Ingreso
                </label>
                <p>{formatFecha(panadero.fecha_ingreso)}</p>
              </div>

              <div className="info-item">
                <label>
                  <Award size={16} />
                  Antigüedad
                </label>
                <p>{calcularAntiguedad(panadero.fecha_ingreso)}</p>
              </div>

              <div className="info-item">
                <label>
                  <Clock size={16} />
                  Turno
                </label>
                <p>
                  <span className={`badge ${getTurnoBadgeClass(panadero.turno)}`}>
                    {panadero.turno?.charAt(0).toUpperCase() + panadero.turno?.slice(1)}
                  </span>
                </p>
              </div>

              <div className="info-item">
                <label>
                  <Briefcase size={16} />
                  Especialidad
                </label>
                <p>
                  <span className={`badge ${getEspecialidadBadgeClass(panadero.especialidad)}`}>
                    {panadero.especialidad?.charAt(0).toUpperCase() + panadero.especialidad?.slice(1)}
                  </span>
                </p>
              </div>

              <div className="info-item">
                <label>
                  <DollarSign size={16} />
                  Salario Base
                </label>
                <p className="salario">Bs. {parseFloat(panadero.salario_base || 0).toFixed(2)}</p>
              </div>
            </div>
          </div>
        </motion.div>

        {/* Estadísticas de Producción */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.3 }}
          className="detail-card stats-card"
        >
          <div className="card-header">
            <TrendingUp size={20} />
            <h2>Estadísticas de Producción</h2>
          </div>
          <div className="card-body">
            <div className="stats-grid">
              <div className="stat-box">
                <div className="stat-icon bg-primary">
                  <Package size={24} />
                </div>
                <div className="stat-content">
                  <h3>{parseFloat(panadero.total_kilos_producidos || 0).toFixed(2)}</h3>
                  <p>Kilos Producidos</p>
                </div>
              </div>

              <div className="stat-box">
                <div className="stat-icon bg-success">
                  <Award size={24} />
                </div>
                <div className="stat-content">
                  <h3>{panadero.total_lotes_producidos || 0}</h3>
                  <p>Lotes Completados</p>
                </div>
              </div>

              <div className="stat-box">
                <div className="stat-icon bg-info">
                  <TrendingUp size={24} />
                </div>
                <div className="stat-content">
                  <h3>
                    {panadero.total_kilos_producidos && panadero.total_lotes_producidos
                      ? (panadero.total_kilos_producidos / panadero.total_lotes_producidos).toFixed(2)
                      : '0.00'}
                  </h3>
                  <p>Promedio kg/lote</p>
                </div>
              </div>
            </div>

            {(!panadero.total_kilos_producidos || panadero.total_kilos_producidos === 0) && (
              <div className="empty-stats">
                <p>Aún no hay registros de producción para este panadero</p>
              </div>
            )}
          </div>
        </motion.div>

        {/* Información Adicional */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.4 }}
          className="detail-card"
        >
          <div className="card-header">
            <Calendar size={20} />
            <h2>Información del Sistema</h2>
          </div>
          <div className="card-body">
            <div className="info-grid">
              <div className="info-item">
                <label>Creado</label>
                <p>{formatFecha(panadero.created_at)}</p>
              </div>

              <div className="info-item">
                <label>Última Actualización</label>
                <p>{formatFecha(panadero.updated_at)}</p>
              </div>

              <div className="info-item">
                <label>ID del Sistema</label>
                <p>#{panadero.id}</p>
              </div>

              <div className="info-item">
                <label>Estado</label>
                <p>
                  <span className={`badge ${panadero.activo ? 'badge-success' : 'badge-danger'}`}>
                    {panadero.activo ? 'Activo' : 'Inactivo'}
                  </span>
                </p>
              </div>
            </div>
          </div>
        </motion.div>
      </div>
    </div>
  );
};

export default PanaderoDetail;
