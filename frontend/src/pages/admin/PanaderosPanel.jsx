import { useEffect, useState } from 'react';
import { Table, Button, Alert, Spinner, Form, Row, Col, Modal, Card } from 'react-bootstrap';
import { admin } from '../../services/api';

export default function PanaderosPanel() {
  const [panaderos, setPanaderos] = useState([]);
  const [loading, setLoading] = useState(true);
  const [estadisticas, setEstadisticas] = useState(null);
  const [filtros, setFiltros] = useState({});
  const [showPagoModal, setShowPagoModal] = useState(false);
  const [pagarPanadero, setPagarPanadero] = useState(null);
  const [pagoForm, setPagoForm] = useState({ monto: '', kilos_pagados: '' , notas: '' , metodos_pago_id: '', tipo_pago: 'produccion' });

  const cargar = async () => {
    try {
      setLoading(true);
      // Remove empty filter values so backend does not treat empty strings as real filters
      const params = {};
      Object.keys(filtros).forEach(k => {
        const v = filtros[k];
        if (v !== null && v !== undefined && v !== '') params[k] = v;
      });
      const [data, stats] = await Promise.all([
        admin.getPanaderos(params),
        admin.getPanaderosEstadisticas().catch(() => null)
      ]);
      // admin.getPanaderos already normalizes to paginator.data or array
      setPanaderos(Array.isArray(data) ? data : data.data || []);
      setEstadisticas(stats);
    } catch (error) {
      console.error('Error al cargar panaderos:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { cargar(); }, []);

  // Minimal handlers / placeholders (panel intentionally simplified)
  const handleEditar = (p) => {
    // Edit UI was removed from this simplified panel; redirect or open modal if needed later
    console.info('Editar panadero (no implementado):', p?.id);
  };

  const handleEliminar = async (p) => {
    // Basic confirm + API call placeholder
    try {
      if (!confirm(`Eliminar panadero ${p?.user?.name || p?.nombre || p?.id}?`)) return;
      await admin.eliminarPanadero(p.id);
      cargar();
    } catch (err) {
      console.error('Error eliminando panadero:', err);
      alert(err?.response?.data?.message || 'Error eliminando panadero');
    }
  };

  const handleToggleActivo = async (p) => {
    try {
      await admin.toggleActivoPanadero(p.id);
      cargar();
    } catch (err) {
      console.error(err);
      alert('Error al cambiar estado');
    }
  };

  const openPagoModal = (p, tipo = 'produccion') => {
    setPagarPanadero(p);
    setPagoForm({
      monto: tipo === 'produccion' ? ((parseFloat(p.salario_por_kilo||0) * parseFloat(p.total_kilos_producidos||0)).toFixed(2)) : (p.salario_base || 0),
      kilos_pagados: p.total_kilos_producidos || 0,
      notas: '',
      metodos_pago_id: '',
      tipo_pago: tipo
    });
    setShowPagoModal(true);
  };

  const handleClosePagoModal = () => {
    setShowPagoModal(false);
    setPagarPanadero(null);
  };

  const handlePagoFormChange = (field, value) => {
    setPagoForm(prev => ({ ...prev, [field]: value }));
  };

  const handleSubmitPago = async (e) => {
    e.preventDefault();
    if (!pagarPanadero) return;
    try {
      // Client-side validation: do not allow pagar más kilos de los disponibles
      const availableKilos = parseFloat(pagarPanadero.total_kilos_producidos || 0);
      const kilosToPay = pagoForm.kilos_pagados ? parseFloat(pagoForm.kilos_pagados) : null;
      if (kilosToPay !== null && kilosToPay > availableKilos) {
        return alert('No se puede pagar más kilos de los que produjo el panadero');
      }

      const payload = {
        empleado_tipo: 'panadero',
        empleado_id: pagarPanadero.id,
        monto: parseFloat(pagoForm.monto) || 0,
        kilos_pagados: pagoForm.tipo_pago === 'produccion' ? (pagoForm.kilos_pagados ? parseFloat(pagoForm.kilos_pagados) : null) : null,
        tipo_pago: pagoForm.tipo_pago === 'sueldo' ? 'sueldo_fijo' : 'pago_produccion',
        es_sueldo_fijo: pagoForm.tipo_pago === 'sueldo',
  metodos_pago_id: pagoForm.metodos_pago_id || '',
  notas: pagoForm.notas || '',
      };
      await admin.crearEmpleadoPago(payload);
      handleClosePagoModal();
      cargar();
    } catch (err) {
      console.error('Error creando pago:', err);
      alert(err.response?.data?.message || 'Error registrando pago');
    }
  };

  const handleCloseModal = () => { /* noop - create/edit removed in simplified panel */ };

  if (loading) return <div className="text-center py-4"><Spinner animation="border" /></div>;

  return (
    <div>
      <h4>Panaderos</h4>
      {estadisticas && (
        <Row className="mb-4">
          <Col md={3}>
            <Card className="shadow-sm">
              <Card.Body>
                <h6 className="text-muted">Total Panaderos</h6>
                <h2 style={{ color: '#8b6f47' }}>{estadisticas.total_panaderos || 0}</h2>
              </Card.Body>
            </Card>
          </Col>
          <Col md={3}>
            <Card className="shadow-sm">
              <Card.Body>
                <h6 className="text-muted">Activos</h6>
                <h2 className="text-success">{estadisticas.panaderos_activos || 0}</h2>
              </Card.Body>
            </Card>
          </Col>
          <Col md={3}>
            <Card className="shadow-sm">
              <Card.Body>
                <h6 className="text-muted">Kilos producidos</h6>
                <h2 className="text-primary">{estadisticas.total_kilos_producidos || 0} kg</h2>
              </Card.Body>
            </Card>
          </Col>
          <Col md={3}>
            <Card className="shadow-sm">
              <Card.Body>
                <h6 className="text-muted">Salario base total (activos)</h6>
                <h2 className="text-success">Bs. {parseFloat(estadisticas.salario_total_mensual || 0).toFixed(2)}</h2>
              </Card.Body>
            </Card>
          </Col>
        </Row>
      )}
      {/* Simplified panel: filters and create/edit removed for clarity */}

      {panaderos.length === 0 ? (
        <Alert variant="info">No hay panaderos registrados.</Alert>
      ) : (
        <Table responsive hover>
          <thead className="table-light">
            <tr>
              <th>ID</th>
              <th>Nombre</th>
              <th>Email</th>
              <th>Turno</th>
              <th>Especialidad</th>
              <th>Kilos Producidos</th>
              <th>Salario Base</th>
              <th>Salario/kg</th>
              <th>Pago por producción</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            {panaderos.map(p => (
              <tr key={p.id}>
                <td>{p.id}</td>
                <td>{p.user?.name || 'Sin nombre'}</td>
                <td>{p.email || p.user?.email || 'N/A'}</td>
                <td>{p.turno}</td>
                <td>{p.especialidad}</td>
                <td>{parseFloat(p.total_kilos_producidos || 0).toFixed(2)} kg</td>
                <td>Bs. {parseFloat(p.salario_base || 0).toFixed(2)}</td>
                <td>Bs. {parseFloat(p.salario_por_kilo || 0).toFixed(2)}</td>
                <td>Bs. { (parseFloat(p.salario_por_kilo || 0) * parseFloat(p.total_kilos_producidos || 0)).toFixed(2) }</td>
                <td>
                  <div className="d-flex gap-2">
                    <Button size="sm" variant="outline-primary" onClick={() => handleEditar(p)}>
                      Editar
                    </Button>
                                <Button size="sm" variant="outline-success" onClick={() => openPagoModal(p)}>
                                  Pagar
                                </Button>
                                {typeof window !== 'undefined' && typeof window.__REACT_DEVTOOLS_GLOBAL_HOOK__ === 'undefined' /* noop to avoid lint */}
                                {/** If parent provided onOpenPayments, show quick 'Ver Pagos' */}
                                {typeof onOpenPayments === 'function' && (
                                  <Button size="sm" variant="outline-secondary" onClick={() => onOpenPayments('panadero', p.id, (parseFloat(p.salario_por_kilo||0) * parseFloat(p.total_kilos_producidos||0)).toFixed(2))}>
                                    Ver Pagos
                                  </Button>
                                )}
                    <Button 
                      size="sm" 
                      variant={p.activo ? "outline-warning" : "outline-success"} 
                      onClick={() => handleToggleActivo(p)}
                    >
                      {p.activo ? 'Desactivar' : 'Activar'}
                    </Button>
                    <Button size="sm" variant="outline-danger" onClick={() => handleEliminar(p)}>
                      Eliminar
                    </Button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </Table>
      )}

      {/* Create/Edit removed: manage panaderos from ClientesPanel. */}

      {/* Modal de Pago */}
      <Modal show={showPagoModal} onHide={handleClosePagoModal}>
        <Form onSubmit={handleSubmitPago}>
          <Modal.Header closeButton>
            <Modal.Title>Registrar Pago - {pagarPanadero?.user?.name || pagarPanadero?.nombre}</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            <Form.Group className="mb-2">
              <Form.Label>Tipo de pago</Form.Label>
              <Form.Select value={pagoForm.tipo_pago} onChange={e => handlePagoFormChange('tipo_pago', e.target.value)}>
                <option value="produccion">Pago por producción (Bs/kg)</option>
                <option value="sueldo">Sueldo fijo</option>
              </Form.Select>
            </Form.Group>

            <Form.Group className="mb-2">
              <Form.Label>Monto (Bs)</Form.Label>
              <Form.Control type="number" step="0.01" value={pagoForm.monto} onChange={e => handlePagoFormChange('monto', e.target.value)} required />
            </Form.Group>

            {pagoForm.tipo_pago === 'produccion' && (
              <Form.Group className="mb-2">
                <Form.Label>Kilos pagados</Form.Label>
                <Form.Control type="number" step="0.01" value={pagoForm.kilos_pagados} onChange={e => handlePagoFormChange('kilos_pagados', e.target.value)} />
                <Form.Text className="text-muted">Si se deja vacío, se pondrá a 0 los kilos producidos.</Form.Text>
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
