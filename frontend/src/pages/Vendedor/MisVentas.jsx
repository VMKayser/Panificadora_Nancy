import { useState, useEffect } from 'react';
import { toast } from 'react-toastify';
import { motion } from 'framer-motion';
import { 
  TrendingUp, DollarSign, ShoppingBag, Calendar,
  Download, Filter, RefreshCw
} from 'lucide-react';
import { vendedorService } from '../../services/empleadosService';
import { useAuth } from '../../context/AuthContext';
import './MisVentas.css';

const MisVentas = () => {
  const { user } = useAuth();
  const [loading, setLoading] = useState(true);
  const [ventas, setVentas] = useState([]);
  const [estadisticas, setEstadisticas] = useState({
    total_ventas: 0,
    total_comisiones: 0,
    total_pedidos: 0,
    ticket_promedio: 0
  });

  const [filtros, setFiltros] = useState({
    fecha_inicio: new Date(new Date().setDate(1)).toISOString().split('T')[0], // Primer día del mes
    fecha_fin: new Date().toISOString().split('T')[0] // Hoy
  });

  const [vendedorId, setVendedorId] = useState(null);

  useEffect(() => {
    cargarVendedorInfo();
  }, [user]);

  useEffect(() => {
    if (vendedorId) {
      cargarVentas();
    }
  }, [vendedorId, filtros]);

  const cargarVendedorInfo = async () => {
    try {
      // Obtener información del vendedor basado en el user_id
      const response = await vendedorService.getAll({ user_id: user?.id });
      if (response.success && response.data.length > 0) {
        setVendedorId(response.data[0].id);
      } else {
        toast.error('No se encontró información de vendedor para este usuario');
      }
    } catch (error) {
      console.error('Error cargando información del vendedor:', error);
      toast.error('Error al cargar información del vendedor');
    }
  };

  const cargarVentas = async () => {
    try {
      setLoading(true);
      const response = await vendedorService.getReporteVentas(vendedorId, filtros);
      
      if (response.success) {
        setVentas(response.data.ventas || []);
        setEstadisticas({
          total_ventas: response.data.total_ventas || 0,
          total_comisiones: response.data.total_comisiones || 0,
          total_pedidos: response.data.total_pedidos || 0,
          ticket_promedio: response.data.ticket_promedio || 0
        });
      }
    } catch (error) {
      console.error('Error cargando ventas:', error);
      toast.error('Error al cargar el reporte de ventas');
    } finally {
      setLoading(false);
    }
  };

  const handleFiltroChange = (campo, valor) => {
    setFiltros(prev => ({ ...prev, [campo]: valor }));
  };

  const handleExportar = () => {
    // TODO: Implementar exportación a Excel/PDF
    toast.info('Funcionalidad de exportación próximamente');
  };

  const formatFecha = (fecha) => {
    return new Date(fecha).toLocaleDateString('es-BO', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  };

  const getEstadoBadgeClass = (estado) => {
    const classes = {
      'pendiente': 'badge-warning',
      'en_proceso': 'badge-info',
      'completado': 'badge-success',
      'cancelado': 'badge-danger'
    };
    return classes[estado] || 'badge-light';
  };

  const getEstadoLabel = (estado) => {
    const labels = {
      'pendiente': 'Pendiente',
      'en_proceso': 'En Proceso',
      'completado': 'Completado',
      'cancelado': 'Cancelado'
    };
    return labels[estado] || estado;
  };

  if (loading && ventas.length === 0) {
    return (
      <div className="mis-ventas-container">
        <div className="loading-container">
          <div className="spinner"></div>
          <p>Cargando reporte de ventas...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="mis-ventas-container">
      {/* Header */}
      <motion.div
        initial={{ opacity: 0, y: -20 }}
        animate={{ opacity: 1, y: 0 }}
        className="page-header"
      >
        <div className="header-title">
          <h1>Mis Ventas y Comisiones</h1>
          <p>Reporte de ventas del periodo seleccionado</p>
        </div>
        <button onClick={handleExportar} className="btn btn-primary">
          <Download size={20} />
          Exportar Reporte
        </button>
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
            <DollarSign size={28} />
          </div>
          <div className="stat-content">
            <h3>Bs. {parseFloat(estadisticas.total_ventas || 0).toFixed(2)}</h3>
            <p>Total en Ventas</p>
          </div>
        </motion.div>

        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.2 }}
          className="stat-card"
        >
          <div className="stat-icon bg-success">
            <TrendingUp size={28} />
          </div>
          <div className="stat-content">
            <h3>Bs. {parseFloat(estadisticas.total_comisiones || 0).toFixed(2)}</h3>
            <p>Total en Comisiones</p>
          </div>
        </motion.div>

        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.3 }}
          className="stat-card"
        >
          <div className="stat-icon bg-info">
            <ShoppingBag size={28} />
          </div>
          <div className="stat-content">
            <h3>{estadisticas.total_pedidos || 0}</h3>
            <p>Pedidos Realizados</p>
          </div>
        </motion.div>

        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.4 }}
          className="stat-card"
        >
          <div className="stat-icon bg-warning">
            <DollarSign size={28} />
          </div>
          <div className="stat-content">
            <h3>Bs. {parseFloat(estadisticas.ticket_promedio || 0).toFixed(2)}</h3>
            <p>Ticket Promedio</p>
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
            <Calendar size={18} />
            <label>Fecha Inicio:</label>
            <input
              type="date"
              value={filtros.fecha_inicio}
              onChange={(e) => handleFiltroChange('fecha_inicio', e.target.value)}
              className="form-control"
            />
          </div>

          <div className="filter-group">
            <Calendar size={18} />
            <label>Fecha Fin:</label>
            <input
              type="date"
              value={filtros.fecha_fin}
              onChange={(e) => handleFiltroChange('fecha_fin', e.target.value)}
              className="form-control"
            />
          </div>

          <button onClick={cargarVentas} className="btn btn-secondary">
            <RefreshCw size={18} />
            Actualizar
          </button>
        </div>
      </motion.div>

      {/* Tabla de Ventas */}
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
                <th>Pedido #</th>
                <th>Fecha</th>
                <th>Cliente</th>
                <th>Total</th>
                <th>Comisión</th>
                <th>Estado</th>
                <th>Forma de Pago</th>
              </tr>
            </thead>
            <tbody>
              {ventas.length > 0 ? (
                ventas.map((venta) => (
                  <tr key={venta.id}>
                    <td>
                      <strong>#{venta.id}</strong>
                    </td>
                    <td>{formatFecha(venta.fecha_pedido)}</td>
                    <td>{venta.cliente?.name || 'Cliente General'}</td>
                    <td>
                      <strong className="text-primary">
                        Bs. {parseFloat(venta.total || 0).toFixed(2)}
                      </strong>
                    </td>
                    <td>
                      <strong className="text-success">
                        Bs. {parseFloat(venta.comision || 0).toFixed(2)}
                      </strong>
                    </td>
                    <td>
                      <span className={`badge ${getEstadoBadgeClass(venta.estado)}`}>
                        {getEstadoLabel(venta.estado)}
                      </span>
                    </td>
                    <td>{venta.metodo_pago?.nombre || 'N/A'}</td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan="7">
                    <div className="empty-state">
                      <ShoppingBag size={64} />
                      <h3>No hay ventas en este periodo</h3>
                      <p>Ajusta los filtros de fecha para ver más resultados</p>
                    </div>
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </motion.div>
    </div>
  );
};

export default MisVentas;
