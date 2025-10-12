import { useEffect, useState } from 'react';
import { Table, Button, Alert, Spinner, Form, Row, Col, Modal, Card } from 'react-bootstrap';
import { admin } from '../../services/api';

export default function PanaderosPanel() {
  const [panaderos, setPanaderos] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [editingPanadero, setEditingPanadero] = useState(null);
  const [form, setForm] = useState({
    nombre: '',
    apellido: '',
    email: '',
    telefono: '',
    ci: '',
    direccion: '',
    fecha_ingreso: '',
    turno: '',
    especialidad: '',
    salario_base: '',
    salario_por_kilo: '',
    activo: true,
    observaciones: ''
  });
  const [filtros, setFiltros] = useState({
    periodo: 'mes', // día, semana, mes
    turno: '',
    especialidad: '',
    activo: ''
  });
  const [estadisticas, setEstadisticas] = useState(null);

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

  useEffect(() => {
    cargar();
  }, [filtros]);

  const handleEditar = async (p) => {
    // Open modal and populate form for editing
    setEditingPanadero(p);
    setForm({
      nombre: p.nombre || '',
      apellido: p.apellido || '',
      email: p.user?.email || p.email || '',
      telefono: p.telefono || '',
      ci: p.ci || '',
      direccion: p.direccion || '',
      fecha_ingreso: p.fecha_ingreso ? p.fecha_ingreso.split('T')[0] : '',
      turno: p.turno || '',
      especialidad: p.especialidad || '',
      salario_base: p.salario_base || '',
      salario_por_kilo: p.salario_por_kilo || '',
      activo: !!p.activo,
      observaciones: p.observaciones || ''
    });
    setShowModal(true);
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

  const handleEliminar = async (p) => {
    if (!window.confirm(`¿Eliminar panadero ${p.user?.name || p.nombre || p.id}?`)) return;
    try {
      await admin.eliminarPanadero(p.id);
      cargar();
    } catch (err) {
      console.error(err);
      alert('Error al eliminar');
    }
  };

  const handleFiltroChange = (campo, valor) => {
    setFiltros(prev => ({ ...prev, [campo]: valor }));
  };

  const handleNuevo = () => {
    setEditingPanadero(null);
    setForm({
      nombre: '',
      apellido: '',
      email: '',
      telefono: '',
      ci: '',
      direccion: '',
      fecha_ingreso: '',
      turno: '',
      especialidad: '',
      salario_base: '',
      salario_por_kilo: '',
      activo: true,
      observaciones: ''
    });
    setShowModal(true);
  };

  const handleFormChange = (field, value) => {
    setForm(prev => ({ ...prev, [field]: value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      if (editingPanadero) {
        await admin.actualizarPanadero(editingPanadero.id, form);
      } else {
        await admin.crearPanadero(form);
      }
      setShowModal(false);
      cargar();
    } catch (err) {
      console.error('Error guardando panadero:', err);
      alert(err.response?.data?.message || 'Error guardando panadero');
    }
  };

  const handleCloseModal = () => {
    setShowModal(false);
  };

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
      <div className="d-flex justify-content-end mb-2">
        <Button size="sm" onClick={handleNuevo} style={{ backgroundColor: '#8b6f47', borderColor: '#8b6f47' }}>+ Nuevo Panadero</Button>
      </div>
      
      {/* Filtros */}
      <Row className="mb-3">
        <Col md={3}>
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
        <Col md={3}>
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
        <Col md={3}>
          <Form.Group>
            <Form.Label>Especialidad</Form.Label>
            <Form.Select 
              value={filtros.especialidad} 
              onChange={(e) => handleFiltroChange('especialidad', e.target.value)}
            >
              <option value="">Todas</option>
              <option value="pan">Pan</option>
              <option value="reposteria">Repostería</option>
              <option value="ambos">Ambos</option>
            </Form.Select>
          </Form.Group>
        </Col>
        <Col md={3}>
          <Form.Group>
            <Form.Label>Estado</Form.Label>
            <Form.Select 
              value={filtros.activo} 
              onChange={(e) => handleFiltroChange('activo', e.target.value)}
            >
              <option value="">Todos</option>
              <option value="1">Activos</option>
              <option value="0">Inactivos</option>
            </Form.Select>
          </Form.Group>
        </Col>
      </Row>

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

      {/* Modal Crear / Editar */}
      <Modal show={showModal} onHide={handleCloseModal} size="lg">
        <Form onSubmit={handleSubmit}>
          <Modal.Header closeButton>
            <Modal.Title>{editingPanadero ? 'Editar Panadero' : 'Nuevo Panadero'}</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            <Row>
              <Col md={6}>
                <Form.Group className="mb-2">
                  <Form.Label>Nombre</Form.Label>
                  <Form.Control value={form.nombre} onChange={e => handleFormChange('nombre', e.target.value)} required />
                </Form.Group>
              </Col>
              <Col md={6}>
                <Form.Group className="mb-2">
                  <Form.Label>Apellido</Form.Label>
                  <Form.Control value={form.apellido} onChange={e => handleFormChange('apellido', e.target.value)} />
                </Form.Group>
              </Col>
            </Row>

            <Row>
              <Col md={6}>
                <Form.Group className="mb-2">
                  <Form.Label>Email</Form.Label>
                  <Form.Control type="email" value={form.email} onChange={e => handleFormChange('email', e.target.value)} />
                </Form.Group>
              </Col>
              <Col md={6}>
                <Form.Group className="mb-2">
                  <Form.Label>Teléfono</Form.Label>
                  <Form.Control value={form.telefono} onChange={e => handleFormChange('telefono', e.target.value)} />
                </Form.Group>
              </Col>
            </Row>

            <Row>
              <Col md={4}>
                <Form.Group className="mb-2">
                  <Form.Label>CI</Form.Label>
                  <Form.Control value={form.ci} onChange={e => handleFormChange('ci', e.target.value)} />
                </Form.Group>
              </Col>
              <Col md={4}>
                <Form.Group className="mb-2">
                  <Form.Label>Fecha Ingreso</Form.Label>
                  <Form.Control type="date" value={form.fecha_ingreso} onChange={e => handleFormChange('fecha_ingreso', e.target.value)} />
                </Form.Group>
              </Col>
              <Col md={4}>
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
              </Col>
            </Row>

            <Row>
              <Col md={6}>
                <Form.Group className="mb-2">
                  <Form.Label>Especialidad</Form.Label>
                  <Form.Select value={form.especialidad} onChange={e => handleFormChange('especialidad', e.target.value)}>
                    <option value="">Seleccione</option>
                    <option value="pan">Pan</option>
                    <option value="reposteria">Repostería</option>
                    <option value="ambos">Ambos</option>
                  </Form.Select>
                </Form.Group>
              </Col>
              <Col md={6}>
                <Form.Group className="mb-2">
                  <Form.Label>Salario Base (Bs)</Form.Label>
                  <Form.Control type="number" step="0.01" value={form.salario_base} onChange={e => handleFormChange('salario_base', e.target.value)} />
                </Form.Group>
              </Col>
            </Row>

            <Row>
              <Col md={6}>
                <Form.Group className="mb-2">
                  <Form.Label>Salario por kilo (Bs/kg)</Form.Label>
                  <Form.Control type="number" step="0.01" value={form.salario_por_kilo} onChange={e => handleFormChange('salario_por_kilo', e.target.value)} />
                </Form.Group>
              </Col>
              <Col md={6} />
            </Row>

            <Form.Group className="mb-2">
              <Form.Label>Dirección</Form.Label>
              <Form.Control value={form.direccion} onChange={e => handleFormChange('direccion', e.target.value)} />
            </Form.Group>

            <Form.Group className="mb-2">
              <Form.Label>Observaciones</Form.Label>
              <Form.Control as="textarea" rows={3} value={form.observaciones} onChange={e => handleFormChange('observaciones', e.target.value)} />
            </Form.Group>

            <Form.Group className="mb-2">
              <Form.Check type="checkbox" label="Activo" checked={!!form.activo} onChange={e => handleFormChange('activo', e.target.checked)} />
            </Form.Group>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="secondary" onClick={handleCloseModal}>Cancelar</Button>
            <Button variant="primary" type="submit" style={{ backgroundColor: '#8b6f47', borderColor: '#8b6f47' }}>{editingPanadero ? 'Actualizar' : 'Crear'}</Button>
          </Modal.Footer>
        </Form>
      </Modal>
    </div>
  );
}
