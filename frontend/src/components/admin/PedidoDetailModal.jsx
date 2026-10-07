import { useState } from 'react';
import PropTypes from 'prop-types';
import { admin } from '../../services/api';
import { toast } from 'react-toastify';
import { enlaceWhatsapp } from '../../utils/whatsapp';

// Flujo de estados: solo se avanza (se pueden saltar pasos); entregado y cancelado son finales
const FLUJO_ESTADOS = ['pendiente', 'confirmado', 'en_preparacion', 'listo', 'entregado'];
const ETIQUETAS_ESTADO = {
  pendiente: 'Pendiente',
  confirmado: 'Confirmado',
  en_preparacion: 'En Preparación',
  listo: 'Listo para Entregar',
  entregado: 'Entregado',
};
const ESTADOS_FINALES = ['entregado', 'cancelado'];

const PedidoDetailModal = ({ pedido, show, onClose }) => {
  const [estado, setEstado] = useState(pedido.estado);
  // fecha_entrega llega como "2025-12-20T15:30:00" (hora local de Bolivia)
  const [fechaEntrega, setFechaEntrega] = useState(pedido.fecha_entrega ? String(pedido.fecha_entrega).slice(0, 10) : '');
  const [horaEntrega, setHoraEntrega] = useState(
    pedido.hora_entrega
      ? String(pedido.hora_entrega).slice(0, 5)
      : (pedido.fecha_entrega && String(pedido.fecha_entrega).length > 10 ? String(pedido.fecha_entrega).slice(11, 16) : '')
  );
  const [notas, setNotas] = useState('');
  const [motivoCancelacion, setMotivoCancelacion] = useState('');
  const [updating, setUpdating] = useState(false);

  if (!show) return null;

  const esFinal = ESTADOS_FINALES.includes(pedido.estado);
  const indiceActual = FLUJO_ESTADOS.indexOf(pedido.estado);
  const estadoPermitido = (e) => Array.isArray(pedido.estados_permitidos)
    ? pedido.estados_permitidos.includes(e)
    : !esFinal && FLUJO_ESTADOS.indexOf(e) > indiceActual;
  const estaPagado = pedido.estado_pago === 'pagado';
  // El celular es el contacto principal (el correo es opcional en la web)
  const telefonoCliente = pedido.cliente_telefono ? (
    <a href={enlaceWhatsapp(pedido.cliente_telefono)} target="_blank" rel="noreferrer">{pedido.cliente_telefono}</a>
  ) : <span className="text-muted fst-italic">No especificado</span>;

  // Formatear fecha
  const formatFecha = (fecha) => {
    if (!fecha) return '-';
    return new Date(fecha).toLocaleDateString('es-AR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  // Actualizar estado
  const handleUpdateEstado = async () => {
    if (estado === pedido.estado) {
      toast.info('No hay cambios en el estado');
      return;
    }

    try {
      setUpdating(true);
      await admin.updateEstadoPedido(pedido.id, {
        estado,
        notas_admin: notas || undefined,
      });
      toast.success('Estado actualizado correctamente');
      onClose();
    } catch (error) {
      console.error('Error actualizando estado:', error);
      toast.error(error.response?.data?.message || 'Error al actualizar el estado');
    } finally {
      setUpdating(false);
    }
  };

  // Actualizar fecha y hora de entrega
  const handleUpdateFechaEntrega = async () => {
    if (!fechaEntrega) {
      toast.warning('Debes seleccionar una fecha de entrega');
      return;
    }

    try {
      setUpdating(true);
      await admin.updateFechaEntrega(pedido.id, {
        fecha_entrega: fechaEntrega,
        hora_entrega: horaEntrega || undefined,
      });
      toast.success('Fecha de entrega actualizada');
      onClose();
    } catch (error) {
      console.error('Error actualizando fecha:', error);
      toast.error(error.response?.data?.message || 'Error al actualizar la fecha de entrega');
    } finally {
      setUpdating(false);
    }
  };

  // Marcar / desmarcar pago (independiente del estado del pedido)
  const handleTogglePago = async () => {
    const nuevoEstadoPago = estaPagado ? 'pendiente' : 'pagado';
    if (estaPagado && !window.confirm('¿Marcar este pedido como NO pagado?')) {
      return;
    }

    try {
      setUpdating(true);
      await admin.updatePagoPedido(pedido.id, { estado_pago: nuevoEstadoPago });
      toast.success(nuevoEstadoPago === 'pagado' ? 'Pedido marcado como pagado' : 'Pago marcado como pendiente');
      onClose();
    } catch (error) {
      console.error('Error actualizando pago:', error);
      toast.error(error.response?.data?.message || 'Error al actualizar el pago');
    } finally {
      setUpdating(false);
    }
  };

  // Agregar notas
  const handleAddNotas = async () => {
    if (!notas.trim()) {
      toast.warning('Debes escribir una nota');
      return;
    }

    try {
      setUpdating(true);
      await admin.addNotasPedido(pedido.id, { notas_admin: notas.trim() });
      toast.success('Nota agregada correctamente');
      setNotas('');
      onClose();
    } catch (error) {
      console.error('Error agregando nota:', error);
      toast.error('Error al agregar la nota');
    } finally {
      setUpdating(false);
    }
  };

  // Cancelar pedido
  const handleCancelar = async () => {
    if (!motivoCancelacion.trim()) {
      toast.warning('Debes indicar el motivo de cancelación');
      return;
    }

    if (!window.confirm('¿Estás seguro de cancelar este pedido?')) {
      return;
    }

    try {
      setUpdating(true);
      await admin.cancelarPedido(pedido.id, { motivo_cancelacion: motivoCancelacion.trim() });
      toast.success('Pedido cancelado');
      onClose();
    } catch (error) {
      console.error('Error cancelando pedido:', error);
      toast.error(error.response?.data?.message || 'Error al cancelar el pedido');
    } finally {
      setUpdating(false);
    }
  };

  return (
    <>
      {/* Overlay */}
      <div
        className="modal-backdrop fade show"
        onClick={onClose}
        style={{ zIndex: 1040 }}
      ></div>

      {/* Modal */}
      <div
        className="modal fade show d-block"
        tabIndex="-1"
        style={{ zIndex: 1050 }}
      >
        <div className="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-sm-down">
          <div className="modal-content">
            <div className="modal-header" style={{ backgroundColor: '#f8f4f0', borderBottom: '2px solid #8b6f47' }}>
              <h5 className="modal-title">
                <i className="bi bi-receipt me-2"></i>
                Pedido #{pedido.numero_pedido}
              </h5>
              <button
                type="button"
                className="btn-close"
                onClick={onClose}
                disabled={updating}
              ></button>
            </div>

            <div className="modal-body">
              <div className="row">
                {/* Información del cliente */}
                <div className="col-md-6 mb-4">
                  <div className="card h-100">
                    <div className="card-header bg-light">
                      <h6 className="mb-0"><i className="bi bi-person me-2"></i>Datos del Cliente</h6>
                    </div>
                    <div className="card-body">
                      {/* Desktop: table */}
                      <div className="d-none d-md-block">
                        <table className="table table-sm table-borderless mb-0">
                          <tbody>
                            <tr>
                              <td className="text-muted" style={{ width: '40%' }}>Nombre:</td>
                              <td style={{ wordBreak: 'break-word', overflowWrap: 'break-word' }}>
                                <strong>{pedido.cliente_nombre} {pedido.cliente_apellido}</strong>
                              </td>
                            </tr>
                            <tr>
                              <td className="text-muted">Email:</td>
                              <td style={{ wordBreak: 'break-word', overflowWrap: 'break-word' }}>{pedido.cliente_email || <span className="text-muted fst-italic">No especificado</span>}</td>
                            </tr>
                            <tr>
                              <td className="text-muted">Teléfono:</td>
                              <td style={{ wordBreak: 'break-word', overflowWrap: 'break-word' }}>{telefonoCliente}</td>
                            </tr>
                            <tr>
                              <td className="text-muted">NIT/CI:</td>
                              <td style={{ wordBreak: 'break-word', overflowWrap: 'break-word' }}>
                                {pedido.nit_ci_factura || <span className="text-muted fst-italic">No especificado</span>}
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </div>

                      {/* Mobile: stacked list */}
                      <div className="d-block d-md-none">
                        <div className="mb-2">
                          <div className="text-muted">Nombre</div>
                          <div style={{ wordBreak: 'break-word', overflowWrap: 'break-word' }}>
                            <strong>{pedido.cliente_nombre} {pedido.cliente_apellido}</strong>
                          </div>
                        </div>
                        <div className="mb-2">
                          <div className="text-muted">Email</div>
                          <div style={{ wordBreak: 'break-word', overflowWrap: 'break-word' }}>{pedido.cliente_email || <span className="text-muted fst-italic">No especificado</span>}</div>
                        </div>
                        <div className="mb-2">
                          <div className="text-muted">Teléfono</div>
                          <div style={{ wordBreak: 'break-word', overflowWrap: 'break-word' }}>{telefonoCliente}</div>
                        </div>
                        <div className="mb-0">
                          <div className="text-muted">NIT/CI</div>
                          <div style={{ wordBreak: 'break-word', overflowWrap: 'break-word' }}>
                            {pedido.nit_ci_factura || <span className="text-muted fst-italic">No especificado</span>}
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                {/* Información del pedido */}
                <div className="col-md-6 mb-4">
                  <div className="card h-100">
                    <div className="card-header bg-light">
                      <h6 className="mb-0"><i className="bi bi-info-circle me-2"></i>Datos del Pedido</h6>
                    </div>
                    <div className="card-body">
                      {/* Desktop */}
                      <div className="d-none d-md-block">
                        <table className="table table-sm table-borderless mb-0">
                          <tbody>
                            <tr>
                              <td className="text-muted" style={{ width: '40%' }}>Fecha:</td>
                              <td>{formatFecha(pedido.created_at)}</td>
                            </tr>
                            <tr>
                              <td className="text-muted">Estado:</td>
                              <td>
                                <span className={`badge ${pedido.estado === 'pendiente' ? 'bg-warning text-dark' :
                                  pedido.estado === 'confirmado' ? 'bg-info' :
                                    pedido.estado === 'en_preparacion' ? 'bg-primary' :
                                      pedido.estado === 'listo' ? 'bg-success' :
                                        pedido.estado === 'entregado' ? 'bg-secondary' :
                                          'bg-danger'
                                  }`}>
                                  {String(pedido.estado || '').replace('_', ' ').toUpperCase()}
                                </span>
                              </td>
                            </tr>
                            <tr>
                              <td className="text-muted">Pago:</td>
                              <td>
                                <span className={`badge ${estaPagado ? 'bg-success' : pedido.estado_pago === 'rechazado' ? 'bg-danger' : 'bg-warning text-dark'}`}>
                                  {estaPagado ? 'PAGADO' : String(pedido.estado_pago || 'pendiente').toUpperCase()}
                                </span>
                              </td>
                            </tr>
                            <tr>
                              <td className="text-muted">Tipo:</td>
                              <td>
                                <span className={`badge ${pedido.tipo_entrega === 'delivery' ? 'bg-info' : 'bg-secondary'}`}>
                                  {pedido.tipo_entrega === 'delivery' ? 'Delivery' : 'Retiro en local'}
                                </span>
                              </td>
                            </tr>
                            {pedido.tipo_entrega === 'delivery' && (
                              <tr>
                                <td className="text-muted">Dirección:</td>
                                <td style={{ wordBreak: 'break-word', overflowWrap: 'break-word' }}>{pedido.direccion_entrega}</td>
                              </tr>
                            )}
                          </tbody>
                        </table>
                      </div>

                      {/* Mobile stacked */}
                      <div className="d-block d-md-none">
                        <div className="mb-2">
                          <div className="text-muted">Fecha</div>
                          <div>{formatFecha(pedido.created_at)}</div>
                        </div>
                        <div className="mb-2">
                          <div className="text-muted">Estado</div>
                          <div>
                            <span className={`badge ${pedido.estado === 'pendiente' ? 'bg-warning text-dark' :
                              pedido.estado === 'confirmado' ? 'bg-info' :
                                pedido.estado === 'en_preparacion' ? 'bg-primary' :
                                  pedido.estado === 'listo' ? 'bg-success' :
                                    pedido.estado === 'entregado' ? 'bg-secondary' :
                                      'bg-danger'
                              }`}>{String(pedido.estado || '').replace('_', ' ').toUpperCase()}</span>
                          </div>
                        </div>
                        <div className="mb-2">
                          <div className="text-muted">Pago</div>
                          <div>
                            <span className={`badge ${estaPagado ? 'bg-success' : pedido.estado_pago === 'rechazado' ? 'bg-danger' : 'bg-warning text-dark'}`}>
                              {estaPagado ? 'PAGADO' : String(pedido.estado_pago || 'pendiente').toUpperCase()}
                            </span>
                          </div>
                        </div>
                        <div className="mb-2">
                          <div className="text-muted">Tipo</div>
                          <div><span className={`badge ${pedido.tipo_entrega === 'delivery' ? 'bg-info' : 'bg-secondary'}`}>{pedido.tipo_entrega === 'delivery' ? 'Delivery' : 'Retiro en local'}</span></div>
                        </div>
                        {pedido.tipo_entrega === 'delivery' && (
                          <div className="mb-0">
                            <div className="text-muted">Dirección</div>
                            <div style={{ wordBreak: 'break-word', overflowWrap: 'break-word' }}>{pedido.direccion_entrega}</div>
                          </div>
                        )}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              {/* Productos */}
              <div className="row">
                <div className="col-12 mb-4">
                  <div className="card">
                    <div className="card-header bg-light">
                      <h6 className="mb-0"><i className="bi bi-cart me-2"></i>Productos</h6>
                    </div>
                    <div className="card-body p-0">
                      <div className="table-responsive">
                        <table className="table table-sm mb-0">
                          <thead className="table-light">
                            <tr>
                              <th>Producto</th>
                              <th className="text-center">Cantidad</th>
                              <th className="text-end">Precio Unit.</th>
                              <th className="text-end">Subtotal</th>
                            </tr>
                          </thead>
                          <tbody>
                            {pedido.detalles && pedido.detalles.map((detalle, index) => (
                              <tr key={index}>
                                <td>
                                  <div>{detalle.nombre_producto}</div>
                                  {detalle.personalizacion && (
                                    <small className="d-block fw-semibold">
                                      {detalle.personalizacion}
                                    </small>
                                  )}
                                </td>
                                <td className="text-center">{detalle.cantidad}</td>
                                <td className="text-end">
                                  ${parseFloat(detalle.precio_unitario).toLocaleString('es-AR', { minimumFractionDigits: 2 })}
                                </td>
                                <td className="text-end">
                                  <strong>${parseFloat(detalle.subtotal).toLocaleString('es-AR', { minimumFractionDigits: 2 })}</strong>
                                </td>
                              </tr>
                            ))}
                            <tr className="table-light">
                              <td colSpan="3" className="text-end"><strong>Total:</strong></td>
                              <td className="text-end">
                                <strong className="fs-5 text-success">
                                  ${parseFloat(pedido.total).toLocaleString('es-AR', { minimumFractionDigits: 2 })}
                                </strong>
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>

                {/* Método de pago */}
                <div className="col-md-6 mb-4">
                  <div className="card">
                    <div className="card-header bg-light">
                      <h6 className="mb-0"><i className="bi bi-credit-card me-2"></i>Método de Pago</h6>
                    </div>
                    <div className="card-body">
                      <p className="mb-1"><strong>{pedido.metodo_pago?.nombre || 'No especificado'}</strong></p>
                      {pedido.metodo_pago?.descripcion && (
                        <small className="text-muted">{pedido.metodo_pago.descripcion}</small>
                      )}
                    </div>
                  </div>
                </div>

                {/* Instrucciones especiales */}
                {pedido.instrucciones_especiales && (
                  <div className="col-md-6 mb-4">
                    <div className="card">
                      <div className="card-header bg-light">
                        <h6 className="mb-0"><i className="bi bi-chat-text me-2"></i>Instrucciones Especiales</h6>
                      </div>
                      <div className="card-body">
                        <p className="mb-0">{pedido.instrucciones_especiales}</p>
                      </div>
                    </div>
                  </div>
                )}

                {/* Notas administrativas */}
                {pedido.notas_admin && (
                  <div className="col-12 mb-4">
                    <div className="alert alert-info">
                      <h6><i className="bi bi-sticky me-2"></i>Notas Administrativas</h6>
                      <p className="mb-0">{pedido.notas_admin}</p>
                    </div>
                  </div>
                )}

                {/* Gestión del pedido */}
                <div className="col-12">
                  <div className="card border-primary">
                    <div className="card-header bg-primary text-white">
                      <h6 className="mb-0"><i className="bi bi-gear me-2"></i>Gestión del Pedido</h6>
                    </div>
                    <div className="card-body">
                      <div className="row g-3">
                        {/* Cambiar estado */}
                        <div className="col-12 col-md-6">
                          <label className="form-label fw-bold">Cambiar Estado</label>
                          <select
                            className="form-select mb-2"
                            value={estado}
                            onChange={(e) => setEstado(e.target.value)}
                            disabled={updating || esFinal}
                          >
                            {pedido.estado === 'cancelado' && <option value="cancelado">Cancelado</option>}
                            {FLUJO_ESTADOS.map((e) => (
                              <option key={e} value={e} disabled={e !== pedido.estado && !estadoPermitido(e)}>
                                {ETIQUETAS_ESTADO[e]}
                              </option>
                            ))}
                          </select>
                          <button
                            className="btn btn-primary btn-sm w-100"
                            onClick={handleUpdateEstado}
                            disabled={updating || estado === pedido.estado || esFinal}
                          >
                            {updating ? 'Actualizando...' : 'Actualizar Estado'}
                          </button>
                          {esFinal && (
                            <small className="text-muted d-block mt-1">
                              Un pedido {pedido.estado} ya no cambia de estado.
                            </small>
                          )}

                          <button
                            className={`btn btn-sm w-100 mt-3 ${estaPagado ? 'btn-outline-secondary' : 'btn-success'}`}
                            onClick={handleTogglePago}
                            disabled={updating || pedido.estado === 'cancelado'}
                          >
                            {estaPagado ? 'Marcar pago como pendiente' : 'Marcar como pagado'}
                          </button>
                        </div>

                        {/* Fecha y hora de entrega */}
                        <div className="col-12 col-md-6">
                          <label className="form-label fw-bold">Fecha y Hora de Entrega</label>
                          <div className="row g-2">
                            <div className="col-7">
                              <input
                                type="date"
                                className="form-control form-control-sm"
                                value={fechaEntrega}
                                onChange={(e) => setFechaEntrega(e.target.value)}
                                disabled={updating || pedido.estado === 'cancelado'}
                              />
                            </div>
                            <div className="col-5">
                              <input
                                type="time"
                                className="form-control form-control-sm"
                                value={horaEntrega}
                                onChange={(e) => setHoraEntrega(e.target.value)}
                                disabled={updating || pedido.estado === 'cancelado'}
                              />
                            </div>
                          </div>
                          <button
                            className="btn btn-info btn-sm w-100 mt-2 text-white"
                            onClick={handleUpdateFechaEntrega}
                            disabled={updating || !fechaEntrega || pedido.estado === 'cancelado'}
                          >
                            {updating ? 'Guardando...' : 'Guardar Fecha/Hora'}
                          </button>
                        </div>

                        {/* Agregar notas */}
                        <div className="col-12 col-md-6">
                          <label className="form-label fw-bold">Agregar Notas</label>
                          <textarea
                            className="form-control form-control-sm mb-2"
                            rows="3"
                            value={notas}
                            onChange={(e) => setNotas(e.target.value)}
                            placeholder="Escribir notas administrativas..."
                            disabled={updating || pedido.estado === 'cancelado'}
                          ></textarea>
                          <button
                            className="btn btn-secondary btn-sm w-100"
                            onClick={handleAddNotas}
                            disabled={updating || !notas.trim() || pedido.estado === 'cancelado'}
                          >
                            {updating ? 'Guardando...' : 'Agregar Nota'}
                          </button>
                        </div>

                        {/* Cancelar pedido */}
                        <div className="col-12 col-md-6">
                          <label className="form-label fw-bold">Cancelar Pedido</label>
                          <textarea
                            className="form-control form-control-sm mb-2"
                            rows="3"
                            value={motivoCancelacion}
                            onChange={(e) => setMotivoCancelacion(e.target.value)}
                            placeholder="Motivo de cancelación..."
                            disabled={updating || esFinal}
                          ></textarea>
                          <button
                            className="btn btn-danger btn-sm w-100"
                            onClick={handleCancelar}
                            disabled={updating || !motivoCancelacion.trim() || esFinal}
                          >
                            {updating ? 'Cancelando...' : 'Cancelar Pedido'}
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div className="modal-footer">
              <button
                type="button"
                className="btn btn-secondary"
                onClick={onClose}
                disabled={updating}
              >
                Cerrar
              </button>
            </div>
          </div>
        </div>
      </div>
    </>
  );
};

PedidoDetailModal.propTypes = {
  pedido: PropTypes.object.isRequired,
  show: PropTypes.bool.isRequired,
  onClose: PropTypes.func.isRequired,
};

export default PedidoDetailModal;
