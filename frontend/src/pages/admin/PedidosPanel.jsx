import { useState, useEffect, useCallback } from 'react';
import { admin } from '../../services/api';
import { toast } from 'react-toastify';
import PedidoDetailModal from '../../components/admin/PedidoDetailModal';
import { Pagination } from 'react-bootstrap';
import * as XLSX from 'xlsx';

import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';

const PedidosPanel = () => {
  const [pedidos, setPedidos] = useState([]);
  const [loading, setLoading] = useState(true);
  const [stats, setStats] = useState(null);
  const [selectedPedido, setSelectedPedido] = useState(null);
  const [showModal, setShowModal] = useState(false);

  // Paginación
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [perPage] = useState(20);

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
    setCurrentPage(1); // Reset page when filters change
  }, [filtros]);

  const cargarDatos = useCallback(async () => {
    try {
      setLoading(true);

      // Filtros limpios (sin valores vacíos)
      const filtrosLimpios = Object.entries(filtros).reduce((acc, [key, value]) => {
        if (value) acc[key] = value;
        return acc;
      }, {});

      // Agregar paginación
      filtrosLimpios.per_page = perPage;
      filtrosLimpios.page = currentPage;

      // Cargar solo pedidos primero, stats de forma optimista
      const pedidosData = await admin.getPedidos(filtrosLimpios);
      setPedidos(pedidosData.data || []);

      // Actualizar información de paginación
      if (pedidosData.last_page) {
        setTotalPages(pedidosData.last_page);
      }

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
  }, [currentPage, filtros, perPage]);

  useEffect(() => {
    cargarDatos();
  }, [cargarDatos]);

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

  // ============================================
  // FUNCIONES DE EXPORTACIÓN
  // ============================================

  // Helper: Formatear productos para exportación
  const getProductosDetalle = (detalles) => {
    if (!detalles || detalles.length === 0) return 'Sin productos';

    return detalles.map(detalle => {
      const nombre = detalle.producto_nombre || detalle.producto?.nombre || 'Producto';
      const cantidad = detalle.cantidad || 0;
      const subtotal = parseFloat(detalle.subtotal || 0).toFixed(2);
      return `${nombre} x${cantidad} (Bs.${subtotal})`;
    }).join(', ');
  };

  // Helper: Calcular total de items
  const getTotalItems = (detalles) => {
    if (!detalles) return 0;
    return detalles.reduce((sum, d) => sum + (d.cantidad || 0), 0);
  };

  // Helper: Formatear fecha completa
  const formatFechaCompleta = (fecha) => {
    if (!fecha) return '-';
    return new Date(fecha).toLocaleDateString('es-BO', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  // Exportar a Excel
  const exportToExcel = () => {
    try {
      if (!pedidos || pedidos.length === 0) {
        toast.warning('No hay pedidos para exportar');
        return;
      }

      // Preparar datos para Excel
      const data = pedidos.map(pedido => ({
        'Nº Pedido': pedido.numero_pedido || '-',
        'Fecha Pedido': formatFechaCompleta(pedido.created_at),
        'Fecha Entrega': pedido.fecha_entrega ? formatFechaCompleta(pedido.fecha_entrega) : '-',
        'Cliente': `${pedido.cliente_nombre || ''} ${pedido.cliente_apellido || ''}`.trim(),
        'NIT/CI': pedido.nit_ci_factura || 'Sin NIT',
        'Teléfono': pedido.cliente_telefono || '-',
        'Email': pedido.cliente_email || '-',
        'Tipo Entrega': pedido.tipo_entrega || '-',
        'Dirección': pedido.direccion_entrega || '-',
        'Productos': getProductosDetalle(pedido.detalles),
        'Cant. Items': getTotalItems(pedido.detalles),
        'Subtotal (Bs.)': parseFloat(pedido.subtotal || 0).toFixed(2),
        'Descuento (Bs.)': parseFloat(pedido.descuento || 0).toFixed(2),
        'Total (Bs.)': parseFloat(pedido.total || 0).toFixed(2),
        'Método Pago': pedido.metodo_pago?.nombre || pedido.metodo_pago_nombre || '-',
        'Estado Pedido': pedido.estado || '-',
        'Estado Pago': pedido.estado_pago || '-',
      }));

      // Crear workbook
      const ws = XLSX.utils.json_to_sheet(data);

      // Ajustar anchos de columnas
      const colWidths = [
        { wch: 15 }, // Nº Pedido
        { wch: 18 }, // Fecha Pedido
        { wch: 18 }, // Fecha Entrega
        { wch: 25 }, // Cliente
        { wch: 15 }, // NIT/CI
        { wch: 12 }, // Teléfono
        { wch: 25 }, // Email
        { wch: 15 }, // Tipo Entrega
        { wch: 30 }, // Dirección
        { wch: 50 }, // Productos
        { wch: 10 }, // Cant. Items
        { wch: 12 }, // Subtotal
        { wch: 12 }, // Descuento
        { wch: 12 }, // Total
        { wch: 15 }, // Método Pago
        { wch: 15 }, // Estado Pedido
        { wch: 15 }, // Estado Pago
      ];
      ws['!cols'] = colWidths;

      const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, 'Pedidos');

      // Generar nombre de archivo con fechas si hay filtros
      let fileName = 'pedidos_panificadora_nancy';
      if (filtros.fecha_desde && filtros.fecha_hasta) {
        fileName += `_${filtros.fecha_desde}_${filtros.fecha_hasta}`;
      } else if (filtros.fecha_desde) {
        fileName += `_desde_${filtros.fecha_desde}`;
      }
      fileName += '.xlsx';

      // Guardar archivo
      XLSX.writeFile(wb, fileName);
      toast.success(`${pedidos.length} pedidos exportados a Excel`);
    } catch (error) {
      console.error('Error al exportar a Excel:', error);
      toast.error('Error al generar archivo Excel');
    }
  };

  // Exportar a PDF
  const exportToPDF = () => {
    try {
      if (!pedidos || pedidos.length === 0) {
        toast.warning('No hay pedidos para exportar');
        return;
      }

      const doc = new jsPDF('landscape');

      // Título
      doc.setFontSize(16);
      doc.setFont('helvetica', 'bold');
      doc.text('Reporte de Pedidos - Panificadora Nancy', 14, 15);

      // Período
      doc.setFontSize(10);
      doc.setFont('helvetica', 'normal');
      let periodo = 'Todos los pedidos';
      if (filtros.fecha_desde && filtros.fecha_hasta) {
        periodo = `Período: ${filtros.fecha_desde} - ${filtros.fecha_hasta}`;
      } else if (filtros.fecha_desde) {
        periodo = `Desde: ${filtros.fecha_desde}`;
      }
      doc.text(periodo, 14, 22);

      // Total
      const totalGeneral = pedidos.reduce((sum, p) => sum + parseFloat(p.total || 0), 0);
      doc.text(`Total General: Bs. ${totalGeneral.toFixed(2)}`, 14, 28);

      // Preparar datos para tabla
      const tableData = pedidos.map(p => [
        p.numero_pedido || '-',
        formatFecha(p.created_at),
        `${p.cliente_nombre || ''} ${p.cliente_apellido || ''}`.trim(),
        p.nit_ci_factura || 'Sin NIT',
        getTotalItems(p.detalles),
        `Bs. ${parseFloat(p.total || 0).toFixed(2)}`,
        p.estado || '-',
      ]);

      // Crear tabla
      autoTable(doc, {
        startY: 35,
        head: [['Nº Pedido', 'Fecha', 'Cliente', 'NIT/CI', 'Items', 'Total', 'Estado']],
        body: tableData,
        styles: {
          fontSize: 8,
          cellPadding: 2,
        },
        headStyles: {
          fillColor: [139, 111, 71], // Color café del tema
          textColor: 255,
          fontStyle: 'bold',
        },
        alternateRowStyles: {
          fillColor: [245, 245, 245],
        },
        margin: { top: 35 },
      });

      // Pie de página
      const pageCount = doc.internal.getNumberOfPages();
      for (let i = 1; i <= pageCount; i++) {
        doc.setPage(i);
        doc.setFontSize(8);
        doc.text(
          `Página ${i} de ${pageCount}`,
          doc.internal.pageSize.getWidth() - 30,
          doc.internal.pageSize.getHeight() - 10
        );
      }

      // Generar nombre de archivo
      let fileName = 'pedidos_panificadora_nancy';
      if (filtros.fecha_desde && filtros.fecha_hasta) {
        fileName += `_${filtros.fecha_desde}_${filtros.fecha_hasta}`;
      }
      fileName += '.pdf';

      // Guardar
      doc.save(fileName);
      toast.success(`${pedidos.length} pedidos exportados a PDF`);
    } catch (error) {
      console.error('Error al exportar a PDF:', error);
      toast.error('Error al generar archivo PDF');
    }
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

            <div className="col-12 d-flex gap-2 align-items-center">
              <button
                className="btn btn-outline-secondary btn-sm"
                onClick={limpiarFiltros}
              >
                <i className="bi bi-x-circle me-1"></i>
                Limpiar filtros
              </button>

              {/* Separador visual */}
              <div style={{ borderLeft: '1px solid #ddd', height: '30px', margin: '0 10px' }}></div>

              {/* Botones de Exportación */}
              <button
                className="btn btn-success btn-sm"
                onClick={exportToExcel}
                disabled={!pedidos || pedidos.length === 0}
                title="Exportar pedidos actuales a Excel"
              >
                <i className="bi bi-file-earmark-excel me-1"></i>
                Exportar Excel
              </button>

              <button
                className="btn btn-danger btn-sm"
                onClick={exportToPDF}
                disabled={!pedidos || pedidos.length === 0}
                title="Exportar pedidos actuales a PDF"
              >
                <i className="bi bi-file-earmark-pdf me-1"></i>
                Exportar PDF
              </button>

              {/* Información de exportación */}
              {pedidos && pedidos.length > 0 && (
                <small className="text-muted ms-2">
                  {pedidos.length} pedido{pedidos.length !== 1 ? 's' : ''} a exportar
                  {filtros.fecha_desde && filtros.fecha_hasta && (
                    <> | Período: {filtros.fecha_desde} - {filtros.fecha_hasta}</>
                  )}
                </small>
              )}
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
            <>
              {totalPages > 1 && (
                <div className="d-flex justify-content-end mb-2">
                  <small className="text-muted">
                    Página {currentPage} de {totalPages} | Mostrando {pedidos.length} pedidos
                  </small>
                </div>
              )}
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
                      <th>WhatsApp</th>
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
                            <div>{pedido.cliente_nombre} {pedido.cliente_apellido}</div>
                            <small className="text-muted">{pedido.cliente_email || pedido.cliente_telefono}</small>
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
                          <div className="mt-1">
                            {pedido.estado_pago === 'pagado'
                              ? <span className="badge bg-success">Pagado</span>
                              : <span className="badge bg-light text-dark border">Pago pendiente</span>}
                          </div>
                        </td>
                        <td>
                          {pedido.cliente_telefono ? (
                            <a
                              href={`https://wa.me/${pedido.cliente_telefono.replace(/\D/g, '')}?text=${encodeURIComponent(`Hola ${pedido.cliente_nombre}, te contacto de Panificadora Nancy sobre tu pedido #${pedido.numero_pedido}. ¿Requieres factura para este pedido?`)}`}
                              target="_blank"
                              rel="noopener noreferrer"
                              className="btn btn-sm btn-success"
                              title="Enviar mensaje por WhatsApp"
                            >
                              <i className="bi bi-whatsapp me-1"></i>
                              Contactar
                            </a>
                          ) : (
                            <span className="text-muted" style={{ fontSize: '0.85rem' }}>Sin teléfono</span>
                          )}
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

              {/* Controles de Paginación */}
              {totalPages > 1 && (
                <div className="d-flex justify-content-center mt-4">
                  <Pagination>
                    <Pagination.First onClick={() => setCurrentPage(1)} disabled={currentPage === 1} />
                    <Pagination.Prev onClick={() => setCurrentPage(prev => Math.max(1, prev - 1))} disabled={currentPage === 1} />

                    {[...Array(totalPages)].map((_, idx) => {
                      const page = idx + 1;
                      if (
                        page === 1 ||
                        page === totalPages ||
                        (page >= currentPage - 1 && page <= currentPage + 1)
                      ) {
                        return (
                          <Pagination.Item
                            key={page}
                            active={page === currentPage}
                            onClick={() => setCurrentPage(page)}
                          >
                            {page}
                          </Pagination.Item>
                        );
                      } else if (page === currentPage - 2 || page === currentPage + 2) {
                        return <Pagination.Ellipsis key={page} disabled />;
                      }
                      return null;
                    })}

                    <Pagination.Next onClick={() => setCurrentPage(prev => Math.min(totalPages, prev + 1))} disabled={currentPage === totalPages} />
                    <Pagination.Last onClick={() => setCurrentPage(totalPages)} disabled={currentPage === totalPages} />
                  </Pagination>
                </div>
              )}
            </>
          )}
        </div>
      </div>

      {/* Modal de detalle */}
      {
        showModal && selectedPedido && (
          <PedidoDetailModal
            pedido={selectedPedido}
            show={showModal}
            onClose={handleModalClose}
          />
        )
      }
    </div >
  );
};

export default PedidosPanel;
