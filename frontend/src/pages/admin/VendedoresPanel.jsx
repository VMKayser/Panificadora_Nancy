import { useEffect, useState } from 'react';
import { Table, Button, Alert, Spinner, Badge, Card, Row, Col, Form, Modal } from 'react-bootstrap';
import { admin } from '../../services/api';
import { toast } from 'react-toastify';

export default function VendedoresPanel() {
  const [vendedores, setVendedores] = useState([]);
  const [loading, setLoading] = useState(true);
  const [estadisticas, setEstadisticas] = useState(null);
  const [showModal, setShowModal] = useState(false);
  const [editing, setEditing] = useState(null);
  const [form, setForm] = useState({
    user_id: null,
    name: '',
    email: '',
    comision_porcentaje: 2.5,
    descuento_maximo_bs: 50,
    puede_dar_descuentos: false,
    puede_cancelar_ventas: false,
    turno: '',
    fecha_ingreso: '',
    estado: 'activo',
    observaciones: ''
  });
  const [filtros, setFiltros] = useState({
    periodo: 'mes', // día, semana, mes
    turno: '',
    estado: 'activo'
  });
  const [showPagoModal, setShowPagoModal] = useState(false);
  const [pagarVendedor, setPagarVendedor] = useState(null);
  const [pagoForm, setPagoForm] = useState({ monto: '', comision_pagada: '', tipo_pago: 'comision', notas: '' });

  useEffect(() => {
    cargarDatos();
  }, [filtros]);

  const cargarDatos = async () => {
    try {
      setLoading(true);
      // clean filters
      const params = {};
      Object.keys(filtros).forEach(k => {
        const v = filtros[k];
        if (v !== null && v !== undefined && v !== '') params[k] = v;
      });

      const [vendedoresData, statsData] = await Promise.all([
        admin.getVendedores(params),
        admin.getVendedoresEstadisticas().catch(() => null)
      ]);
      setVendedores(vendedoresData.data || vendedoresData);
      setEstadisticas(statsData);
    } catch (error) {
      console.error('Error al cargar vendedores:', error);
      toast.error('Error al cargar vendedores');
    } finally {
      setLoading(false);
    }
  };

  const handleActualizarVendedor = async (id, data) => {
    try {
      await admin.actualizarVendedor(id, data);
      toast.success('Vendedor actualizado exitosamente');
      cargarDatos();
    } catch (error) {
      toast.error('Error al actualizar vendedor');
      console.error(error);
    }
  };

  const handleEliminar = async (id, nombre) => {
    if (!window.confirm(`¿Estás seguro de eliminar al vendedor "${nombre}"?`)) return;

    try {
      await admin.eliminarVendedor(id);
      toast.success('Vendedor eliminado exitosamente');
      cargarDatos();
    } catch (error) {
      toast.error('Error al eliminar vendedor');
    }
  };

  const handleCambiarEstado = async (id, nombre) => {
    try {
      await admin.cambiarEstadoVendedor(id);
      toast.success(`Estado de "${nombre}" actualizado`);
      cargarDatos();
    } catch (error) {
      toast.error('Error al cambiar estado');
    }
  };

  const handleFiltroChange = (campo, valor) => {
    setFiltros(prev => ({ ...prev, [campo]: valor }));
  };

  const handleNuevo = () => {
    setEditing(null);
    setForm({
      user_id: null,
      name: '',
      email: '',
      comision_porcentaje: 2.5,
      descuento_maximo_bs: 50,
      puede_dar_descuentos: false,
      puede_cancelar_ventas: false,
      turno: '',
      fecha_ingreso: '',
      estado: 'activo',
      observaciones: ''
    });
    setShowModal(true);
  };

  const handleFormChange = (field, value) => setForm(prev => ({ ...prev, [field]: value }));

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      const payload = { ...form };
      if (!payload.observaciones) delete payload.observaciones;
      if (editing) {
        await admin.actualizarVendedor(editing.id, payload);
      } else {
        await admin.crearVendedor(payload);
      }
      setShowModal(false);
      cargarDatos();
    } catch (err) {
      console.error('Error guardando vendedor:', err);
      toast.error(err.response?.data?.message || 'Error guardando vendedor');
    }
  };

  const handleEditar = (vendedor) => {
    // Populate modal form for editing
    setEditing(vendedor);
    setForm({
      user_id: vendedor.user_id || null,
      name: vendedor.user?.name || vendedor.name || '',
      email: vendedor.user?.email || vendedor.email || '',
      comision_porcentaje: vendedor.comision_porcentaje ?? 2.5,
      descuento_maximo_bs: vendedor.descuento_maximo_bs ?? 50,
      puede_dar_descuentos: !!vendedor.puede_dar_descuentos,
      puede_cancelar_ventas: !!vendedor.puede_cancelar_ventas,
      turno: vendedor.turno || '',
      fecha_ingreso: vendedor.fecha_ingreso ? vendedor.fecha_ingreso.split('T')[0] : '',
      estado: vendedor.estado || 'activo',
      observaciones: vendedor.observaciones || ''
    });
    setShowModal(true);
  };

  const openPagoModal = (vendedor, tipo = 'comision') => {
    setPagarVendedor(vendedor);
    setPagoForm({
      monto: tipo === 'comision' ? (vendedor.comision_acumulada || 0) : (vendedor.salario_base || 0),
      comision_pagada: tipo === 'comision' ? (vendedor.comision_acumulada || 0) : '',
      tipo_pago: tipo,
      notas: ''
    });
    setShowPagoModal(true);
  };

  const handleClosePagoModal = () => { setShowPagoModal(false); setPagarVendedor(null); };

  const handlePagoFormChange = (field, value) => setPagoForm(prev => ({ ...prev, [field]: value }));

  const handleSubmitPago = async (e) => {
    e.preventDefault();
    if (!pagarVendedor) return;
    try {
      // Client-side validation: do not allow to pay more than commission accumulated
      if (pagoForm.tipo_pago === 'comision') {
        const disponible = parseFloat(pagarVendedor.comision_acumulada || 0);
        const comisionPagada = pagoForm.comision_pagada ? parseFloat(pagoForm.comision_pagada) : (pagoForm.monto ? parseFloat(pagoForm.monto) : 0);
        if (comisionPagada > disponible) {
          return toast.error('No se puede pagar más que la comisión acumulada');
        }
      }
      const payload = {
        empleado_tipo: 'vendedor',
        empleado_id: pagarVendedor.id,
        monto: parseFloat(pagoForm.monto) || 0,
        comision_pagada: pagoForm.tipo_pago === 'comision' ? (pagoForm.comision_pagada ? parseFloat(pagoForm.comision_pagada) : null) : null,
        tipo_pago: pagoForm.tipo_pago === 'sueldo' ? 'sueldo_fijo' : 'comision',
        es_sueldo_fijo: pagoForm.tipo_pago === 'sueldo' ? true : false,
    notas: pagoForm.notas || '',
      };
      await admin.crearEmpleadoPago(payload);
      handleClosePagoModal();
      cargarDatos();
      toast.success('Pago registrado');
    } catch (err) {
      console.error('Error registrando pago:', err);
      toast.error(err.response?.data?.message || 'Error registrando pago');
    }
  };

  if (loading) {
    return (
      <div className="text-center py-4">
        <Spinner animation="border" />
      </div>
    );
  }

  return (
    <div>
      {/* Estadísticas */}
      {estadisticas && (
        <Row className="mb-4">
          <Col md={3}>
            <Card className="shadow-sm">
              <Card.Body>
                <h6 className="text-muted">Total Vendedores</h6>
                <h2 style={{ color: '#8b6f47' }}>{estadisticas.total_vendedores || 0}</h2>
              </Card.Body>
            </Card>
          </Col>
          <Col md={3}>
            <Card className="shadow-sm">
              <Card.Body>
                <h6 className="text-muted">Activos</h6>
                <h2 className="text-success">{estadisticas.vendedores_activos || 0}</h2>
              </Card.Body>
            </Card>
          </Col>
          <Col md={3}>
            <Card className="shadow-sm">
              <Card.Body>
                <h6 className="text-muted">Ventas del Mes</h6>
                <h2 className="text-primary">{estadisticas.ventas_mes || 0}</h2>
              </Card.Body>
            </Card>
          </Col>
          <Col md={3}>
            <Card className="shadow-sm">
              <Card.Body>
                <h6 className="text-muted">Ingresos del Mes</h6>
                <h2 className="text-success">
                  Bs. {parseFloat(estadisticas.ingresos_mes || 0).toFixed(2)}
                </h2>
              </Card.Body>
            </Card>
          </Col>
        </Row>
      )}

      {/* Filtros */}
      <Row className="mb-3">
        <Col md={4}>
          <Form.Group>
            <Form.Label>Período</Form.Label>
            <Form.Select 
              value={filtros.periodo} 
              onChange={(e) => handleFiltroChange('periodo', e.target.value)}
            >
              <option value="dia">Hoy</option>
              <option value="semana">Esta Semana</option>
              <option value="mes">Este Mes</option>
            </Form.Select>
          </Form.Group>
        </Col>
        <Col md={4}>
          <Form.Group>
            <Form.Label>Turno</Form.Label>
            <Form.Select 
              value={filtros.turno} 
              onChange={(e) => handleFiltroChange('turno', e.target.value)}
            >
              <option value="">Todos</option>
              <option value="mañana">Mañana</option>
              <option value="tarde">Tarde</option>
              <option value="noche">Noche</option>
              <option value="rotativo">Rotativo</option>
            </Form.Select>
          </Form.Group>
        </Col>
        <Col md={4}>
          <Form.Group>
            <Form.Label>Estado</Form.Label>
            <Form.Select 
              value={filtros.estado} 
              onChange={(e) => handleFiltroChange('estado', e.target.value)}
            >
              <option value="">Todos</option>
              <option value="activo">Activos</option>
              <option value="inactivo">Inactivos</option>
              <option value="suspendido">Suspendidos</option>
            </Form.Select>
          </Form.Group>
        </Col>
      </Row>

      {/* Título */}
      <div className="d-flex justify-content-between align-items-center mb-3">
        <h4>👥 Gestión de Vendedores</h4>
        {/* Botón removido: Los vendedores se crean desde el Panel de Clientes cambiando el rol */}
      </div>

      {/* Tabla */}
      {vendedores.length === 0 ? (
        <Alert variant="info">No hay vendedores registrados.</Alert>
      ) : (
        <Table responsive hover>
          <thead className="table-light">
            <tr>
              <th>ID</th>
              <th>Nombre</th>
              <th>Email</th>
              <th>Total Vendido</th>
              <th>Comisión %</th>
              <th>Comisión acumulada</th>
              <th>Fecha Registro</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            {vendedores.map(vendedor => (
              <tr key={vendedor.id}>
                <td>{vendedor.id}</td>
                <td>{vendedor.user?.name || 'Sin nombre'}</td>
                <td>{vendedor.user?.email || vendedor.email || 'N/A'}</td>
                <td>Bs. {parseFloat(vendedor.total_vendido || 0).toFixed(2)}</td>
                <td>{parseFloat(vendedor.comision_porcentaje || 0).toFixed(2)}%</td>
                <td>Bs. {parseFloat(vendedor.comision_acumulada || 0).toFixed(2)}</td>
                <td>{new Date(vendedor.created_at).toLocaleDateString()}</td>
                <td>
                  <Badge bg={vendedor.estado === 'activo' ? 'success' : vendedor.estado === 'inactivo' ? 'secondary' : 'warning'}>
                    {vendedor.estado || 'N/A'}
                  </Badge>
                </td>
                <td>
                  <div className="d-flex gap-2">
                    <Button size="sm" variant="outline-primary" onClick={() => handleEditar(vendedor)}>
                      Editar
                    </Button>
                    <Button size="sm" variant="outline-success" onClick={() => openPagoModal(vendedor, 'comision')}>
                      Pagar
                    </Button>
                    {typeof onOpenPayments === 'function' && (
                      <Button size="sm" variant="outline-secondary" onClick={() => onOpenPayments('vendedor', vendedor.id, vendedor.comision_acumulada || 0)}>
                        Ver Pagos
                      </Button>
                    )}
                    <Button
                      size="sm"
                      variant={vendedor.estado === 'activo' ? 'outline-warning' : 'outline-success'}
                      onClick={() => handleCambiarEstado(vendedor.id, vendedor.user?.name || vendedor.name)}
                    >
                      {vendedor.estado === 'activo' ? 'Desactivar' : 'Activar'}
                    </Button>
                    <Button
                      size="sm"
                      variant="outline-danger"
                      onClick={() => handleEliminar(vendedor.id, vendedor.user?.name || vendedor.name)}
                    >
                      Eliminar
                    </Button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </Table>
      )}

      {/* Modal Crear/Editar Vendedor */}
      <Modal show={showModal} onHide={() => setShowModal(false)}>
        <Form onSubmit={handleSubmit}>
          <Modal.Header closeButton>
            <Modal.Title>{editing ? 'Editar Vendedor' : 'Nuevo Vendedor'}</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            <Form.Group className="mb-2">
              <Form.Label>Nombre</Form.Label>
              <Form.Control value={form.name} onChange={e => handleFormChange('name', e.target.value)} required />
            </Form.Group>
            <Form.Group className="mb-2">
              <Form.Label>Email</Form.Label>
              <Form.Control type="email" value={form.email} onChange={e => handleFormChange('email', e.target.value)} required />
            </Form.Group>
            <Form.Group className="mb-2">
              <Form.Label>Comisión %</Form.Label>
              <Form.Control type="number" step="0.01" value={form.comision_porcentaje} onChange={e => handleFormChange('comision_porcentaje', e.target.value)} />
            </Form.Group>
            <Form.Group className="mb-2">
              <Form.Label>Descuento máximo (Bs)</Form.Label>
              <Form.Control type="number" step="0.01" value={form.descuento_maximo_bs} onChange={e => handleFormChange('descuento_maximo_bs', e.target.value)} />
            </Form.Group>
            <Form.Group className="mb-2">
              <Form.Check type="checkbox" label="Puede dar descuentos" checked={!!form.puede_dar_descuentos} onChange={e => handleFormChange('puede_dar_descuentos', e.target.checked)} />
            </Form.Group>
            <Form.Group className="mb-2">
              <Form.Check type="checkbox" label="Puede cancelar ventas" checked={!!form.puede_cancelar_ventas} onChange={e => handleFormChange('puede_cancelar_ventas', e.target.checked)} />
            </Form.Group>
            <Form.Group className="mb-2">
              <Form.Label>Turno</Form.Label>
              <Form.Select value={form.turno} onChange={e => handleFormChange('turno', e.target.value)}>
                <option value="">Seleccione</option>
                <option value="mañana">Mañana</option>
                <option value="tarde">Tarde</option>
                <option value="noche">Noche</option>
                <option value="rotativo">Rotativo</option>
              </Form.Select>
            </Form.Group>
            <Form.Group className="mb-2">
              <Form.Label>Fecha Ingreso</Form.Label>
              <Form.Control type="date" value={form.fecha_ingreso} onChange={e => handleFormChange('fecha_ingreso', e.target.value)} />
            </Form.Group>
            <Form.Group className="mb-2">
              <Form.Label>Estado</Form.Label>
              <Form.Select value={form.estado} onChange={e => handleFormChange('estado', e.target.value)}>
                <option value="activo">Activo</option>
                <option value="inactivo">Inactivo</option>
                <option value="suspendido">Suspendido</option>
              </Form.Select>
            </Form.Group>
            <Form.Group className="mb-2">
              <Form.Label>Observaciones</Form.Label>
              <Form.Control as="textarea" rows={3} value={form.observaciones} onChange={e => handleFormChange('observaciones', e.target.value)} />
            </Form.Group>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="secondary" onClick={() => setShowModal(false)}>Cancelar</Button>
            <Button variant="primary" type="submit" style={{ backgroundColor: '#8b6f47', borderColor: '#8b6f47' }}>{editing ? 'Actualizar' : 'Crear'}</Button>
          </Modal.Footer>
        </Form>
      </Modal>

      {/* Modal Pago Vendedor */}
      <Modal show={showPagoModal} onHide={handleClosePagoModal}>
        <Form onSubmit={handleSubmitPago}>
          <Modal.Header closeButton>
            <Modal.Title>Registrar Pago - {pagarVendedor?.user?.name || pagarVendedor?.name}</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            <Form.Group className="mb-2">
              <Form.Label>Tipo de pago</Form.Label>
              <Form.Select value={pagoForm.tipo_pago} onChange={e => handlePagoFormChange('tipo_pago', e.target.value)}>
                <option value="comision">Comisión (parcial o total)</option>
                <option value="sueldo">Sueldo fijo</option>
              </Form.Select>
            </Form.Group>

            <Form.Group className="mb-2">
              <Form.Label>Monto (Bs)</Form.Label>
              <Form.Control type="number" step="0.01" value={pagoForm.monto} onChange={e => handlePagoFormChange('monto', e.target.value)} required />
            </Form.Group>

            {pagoForm.tipo_pago === 'comision' && (
              <Form.Group className="mb-2">
                <Form.Label>Comisión pagada (Bs)</Form.Label>
                <Form.Control type="number" step="0.01" value={pagoForm.comision_pagada} onChange={e => handlePagoFormChange('comision_pagada', e.target.value)} />
                <Form.Text className="text-muted">Si deja vacío, se descontará el monto ingresado de la comisión acumulada.</Form.Text>
              </Form.Group>
            )}

            <Form.Group className="mb-2">
              <Form.Label>Notas</Form.Label>
              <Form.Control as="textarea" rows={3} value={pagoForm.notas} onChange={e => handlePagoFormChange('notas', e.target.value)} />
            </Form.Group>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="secondary" onClick={handleClosePagoModal}>Cancelar</Button>
            <Button variant="success" type="submit">Registrar Pago</Button>
          </Modal.Footer>
        </Form>
      </Modal>
    </div>
  );
}
