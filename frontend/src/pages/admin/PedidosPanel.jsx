import { useState, useEffect } from 'react';
import { admin } from '../../services/api';
import { toast } from 'react-toastify';
import PedidoDetailModal from '../../components/admin/PedidoDetailModal';

const PedidosPanel = () => {
  const [pedidos, setPedidos] = useState([]);
  const [loading, setLoading] = useState(true);
  const [stats, setStats] = useState(null);
  const [selectedPedido, setSelectedPedido] = useState(null);
  const [showModal, setShowModal] = useState(false);
  
  // Filtros
  const [filtros, setFiltros] = useState({
    estado: '',
    search: '',
    fecha_desde: '',
    fecha_hasta: '',
    tipo_entrega: '',
  });

  // Cargar pedidos y estadísticas
  useEffect(() => {
    cargarDatos();
  }, [filtros]);

  const cargarDatos = async () => {
    try {
      setLoading(true);
      
      // Filtros limpios (sin valores vacíos)
      const filtrosLimpios = Object.entries(filtros).reduce((acc, [key, value]) => {
        if (value) acc[key] = value;
        return acc;
      }, {});
      
      // Cargar solo pedidos primero, stats de forma optimista
      const pedidosData = await admin.getPedidos(filtrosLimpios);
      setPedidos(pedidosData.data || []);
      
      // Cargar stats después (no bloqueante)
      admin.getPedidosStats(filtrosLimpios)
        .then(statsData => setStats(statsData))
        .catch(err => console.error('Error cargando stats:', err));
        
    } catch (error) {
      console.error('Error cargando datos:', error);
      toast.error('Error al cargar los pedidos');
    } finally {
      setLoading(false);
    }
  };

  const handleFiltroChange = (e) => {
    const { name, value } = e.target;
    setFiltros(prev => ({ ...prev, [name]: value }));
  };

  const limpiarFiltros = () => {
    setFiltros({
      estado: '',
      search: '',
      fecha_desde: '',
      fecha_hasta: '',
      tipo_entrega: '',
    });
  };

  const verDetalle = async (id) => {
    try {
      const pedido = await admin.getPedido(id);
      setSelectedPedido(pedido);
      setShowModal(true);
    } catch (error) {
      console.error('Error cargando pedido:', error);
      toast.error('Error al cargar detalles del pedido');
    }
  };

  const handleModalClose = () => {
    setShowModal(false);
    setSelectedPedido(null);
    cargarDatos(); // Recargar después de cambios
  };

  // Calcular badge de estado
  const getEstadoBadge = (estado) => {
    const badges = {
      pendiente: 'bg-warning text-dark',
      confirmado: 'bg-info text-white',
      en_preparacion: 'bg-primary',
      listo: 'bg-success',
      entregado: 'bg-secondary',
      cancelado: 'bg-danger',
    };
    
    const labels = {
      pendiente: 'Pendiente',
      confirmado: 'Confirmado',
      en_preparacion: 'En Preparación',
      listo: 'Listo',
      entregado: 'Entregado',
      cancelado: 'Cancelado',
    };
    
    return (
      <span className={`badge ${badges[estado] || 'bg-secondary'}`}>
        {labels[estado] || estado}
      </span>
    );
  };

  // Formatear fecha
  const formatFecha = (fecha) => {
    if (!fecha) return '-';
    return new Date(fecha).toLocaleDateString('es-AR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
    });
  };

  // Formatear hora
  const formatHora = (hora) => {
    if (!hora) return '-';
    return hora.substring(0, 5); // HH:MM
  };

  return (
    <div className="container-fluid py-4">
      <div className="row mb-4">
        <div className="col">
          <h2 className="mb-0">
            <i className="bi bi-clipboard-check me-2"></i>
            Gestión de Pedidos
          </h2>
        </div>
      </div>

      {/* Estadísticas */}
      {stats && (
        <div className="row mb-4">
          <div className="col-md-3 mb-3">
            <div className="card text-center border-primary">
              <div className="card-body">
                <h6 className="card-subtitle mb-2 text-muted">Total Pedidos</h6>
                <h3 className="mb-0">{stats.total_pedidos}</h3>
              </div>
            </div>
          </div>
          <div className="col-md-3 mb-3">
            <div className="card text-center border-success">
              <div className="card-body">
                <h6 className="card-subtitle mb-2 text-muted">Ingresos Totales</h6>
                <h3 className="mb-0 text-success">
                  ${parseFloat(stats.ingresos_totales || 0).toLocaleString('es-AR', { minimumFractionDigits: 2 })}
                </h3>
              </div>
            </div>
          </div>
          <div className="col-md-3 mb-3">
            <div className="card text-center border-warning">
              <div className="card-body">
                <h6 className="card-subtitle mb-2 text-muted">Pendientes</h6>
                <h3 className="mb-0 text-warning">{stats.por_estado?.pendiente || 0}</h3>
              </div>
            </div>
          </div>
          <div className="col-md-3 mb-3">
            <div className="card text-center border-info">
              <div className="card-body">
                <h6 className="card-subtitle mb-2 text-muted">Promedio Pedido</h6>
                <h3 className="mb-0 text-info">
                  ${parseFloat(stats.promedio_pedido || 0).toLocaleString('es-AR', { minimumFractionDigits: 2 })}
                </h3>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Filtros */}
      <div className="card mb-4">
        <div className="card-body">
          <div className="row g-3">
            <div className="col-md-3">
              <label className="form-label">Estado</label>
              <select
                className="form-select"
                name="estado"
                value={filtros.estado}
                onChange={handleFiltroChange}
              >
                <option value="">Todos los estados</option>
                <option value="pendiente">Pendiente</option>
                <option value="confirmado">Confirmado</option>
                <option value="en_preparacion">En Preparación</option>
                <option value="listo">Listo</option>
                <option value="entregado">Entregado</option>
                <option value="cancelado">Cancelado</option>
              </select>
            </div>
            
            <div className="col-md-2">
              <label className="form-label">Tipo Entrega</label>
              <select
                className="form-select"
                name="tipo_entrega"
                value={filtros.tipo_entrega}
                onChange={handleFiltroChange}
              >
                <option value="">Todos</option>
                <option value="retiro">Retiro</option>
                <option value="delivery">Delivery</option>
              </select>
            </div>

            <div className="col-md-2">
              <label className="form-label">Desde</label>
              <input
                type="date"
                className="form-control"
                name="fecha_desde"
                value={filtros.fecha_desde}
                onChange={handleFiltroChange}
              />
            </div>

            <div className="col-md-2">
              <label className="form-label">Hasta</label>
              <input
                type="date"
                className="form-control"
                name="fecha_hasta"
                value={filtros.fecha_hasta}
                onChange={handleFiltroChange}
              />
            </div>

            <div className="col-md-3">
              <label className="form-label">Buscar</label>
              <input
                type="text"
                className="form-control"
                name="search"
                value={filtros.search}
                onChange={handleFiltroChange}
                placeholder="Nº pedido, cliente, email..."
              />
            </div>
            
            <div className="col-12">
              <button
                className="btn btn-outline-secondary btn-sm"
                onClick={limpiarFiltros}
              >
                <i className="bi bi-x-circle me-1"></i>
                Limpiar filtros
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* Tabla de pedidos */}
      <div className="card">
        <div className="card-body">
          {loading ? (
            <div className="text-center py-5">
              <div className="spinner-border text-cafe" role="status">
                <span className="visually-hidden">Cargando...</span>
              </div>
            </div>
          ) : pedidos.length === 0 ? (
            <div className="text-center py-5 text-muted">
              <i className="bi bi-inbox fs-1 d-block mb-2"></i>
              <p>No hay pedidos que mostrar</p>
            </div>
          ) : (
            <div className="table-responsive">
              <table className="table table-hover align-middle">
                <thead className="table-light">
                  <tr>
                    <th>Nº Pedido</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Tipo</th>
                    <th>Entrega</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  {pedidos.map(pedido => (
                    <tr key={pedido.id}>
                      <td>
                        <strong>#{pedido.numero_pedido}</strong>
                      </td>
                      <td>
                        {formatFecha(pedido.created_at)}
                      </td>
                      <td>
                        <div>
                          <div>{pedido.nombre_cliente} {pedido.apellido_cliente}</div>
                          <small className="text-muted">{pedido.email_cliente}</small>
                        </div>
                      </td>
                      <td>
                        <span className={`badge ${pedido.tipo_entrega === 'delivery' ? 'bg-info' : 'bg-secondary'}`}>
                          {pedido.tipo_entrega === 'delivery' ? 'Delivery' : 'Retiro'}
                        </span>
                      </td>
                      <td>
                        {pedido.fecha_entrega ? (
                          <div>
                            <div>{formatFecha(pedido.fecha_entrega)}</div>
                            {pedido.hora_entrega && (
                              <small className="text-muted">{formatHora(pedido.hora_entrega)}</small>
                            )}
                          </div>
                        ) : (
                          <span className="text-muted">Sin definir</span>
                        )}
                      </td>
                      <td>
                        <strong>${parseFloat(pedido.total).toLocaleString('es-AR', { minimumFractionDigits: 2 })}</strong>
                      </td>
                      <td>
                        {getEstadoBadge(pedido.estado)}
                      </td>
                      <td>
                        <button
                          className="btn btn-sm btn-outline-primary"
                          onClick={() => verDetalle(pedido.id)}
                        >
                          <i className="bi bi-eye me-1"></i>
                          Ver
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </div>

      {/* Modal de detalle */}
      {showModal && selectedPedido && (
        <PedidoDetailModal
          pedido={selectedPedido}
          show={showModal}
          onClose={handleModalClose}
        />
      )}
    </div>
  );
};

export default PedidosPanel;
