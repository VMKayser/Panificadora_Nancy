import { useEffect, useState } from 'react';
import { Table, Alert, Spinner, Badge, Card, Row, Col, Nav, Button, Modal, Form, Pagination } from 'react-bootstrap';
import { admin } from '../../services/api';
import { toast } from 'react-toastify';

export default function InventarioPanel() {
  const [activeTab, setActiveTab] = useState('materias');
  const [materias, setMaterias] = useState([]);
  const [productosFinal, setProductosFinal] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [form, setForm] = useState({
    id: null,
    nombre: '',
    codigo_interno: '',
    unidad_medida: 'kg',
    stock_actual: '0.00',
    stock_minimo: '0.00',
    costo_unitario: '0.00',
    proveedor: '',
    ultima_compra: '',
    activo: true,
  });
  const [isSaving, setIsSaving] = useState(false);
  
  // Pagination state
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [perPage] = useState(15);

  useEffect(() => {
    setCurrentPage(1); // Reset page when switching tabs
  }, [activeTab]);

  useEffect(() => {
    cargarDatos(currentPage);
  }, [currentPage, activeTab]);

  const cargarDatos = async (page = 1) => {
    try {
      setLoading(true);
      if (activeTab === 'materias') {
        const data = await admin.getMateriasPrimas({ per_page: perPage, page });
        console.log('[InventarioPanel] Materias Primas data:', data);
        setMaterias(data.data || data);
        if (data.last_page) {
          setTotalPages(data.last_page);
          console.log('[InventarioPanel] Total pages:', data.last_page);
        }
      } else {
        const data = await admin.getProductosFinales({ per_page: perPage, page });
        console.log('[InventarioPanel] Productos Finales data:', data);
        setProductosFinal(data.data || data);
        if (data.last_page) {
          setTotalPages(data.last_page);
          console.log('[InventarioPanel] Total pages:', data.last_page);
        }
      }
    } catch (error) {
      console.error('Error al cargar inventario:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleOpenCreate = () => {
    setForm({
      id: null,
      nombre: '',
      codigo_interno: '',
      unidad_medida: 'kg',
      stock_actual: '0.00',
      stock_minimo: '0.00',
      costo_unitario: '0.00',
      proveedor: '',
      ultima_compra: '',
      activo: true,
    });
    setShowModal(true);
  };

  const handleOpenEdit = (m) => {
    setForm({
      id: m.id,
      nombre: m.nombre || '',
      codigo_interno: m.codigo_interno || '',
      unidad_medida: m.unidad_medida || 'kg',
      stock_actual: m.stock_actual ?? '0.00',
      stock_minimo: m.stock_minimo ?? '0.00',
      costo_unitario: m.costo_unitario ?? '0.00',
      proveedor: m.proveedor || '',
      ultima_compra: m.ultima_compra || '',
      activo: m.activo ?? true,
    });
    setShowModal(true);
  };

  const handleCloseModal = () => {
    setShowModal(false);
  };

  const handleSave = async (e) => {
    e.preventDefault();
    setIsSaving(true);
    try {
      const payload = {
        nombre: form.nombre,
        codigo_interno: form.codigo_interno || null,
        unidad_medida: form.unidad_medida,
        stock_actual: parseFloat(form.stock_actual || 0),
        stock_minimo: parseFloat(form.stock_minimo || 0),
        costo_unitario: parseFloat(form.costo_unitario || 0),
        proveedor: form.proveedor || null,
        ultima_compra: form.ultima_compra || null,
        activo: !!form.activo,
      };

      if (form.id) {
        await admin.actualizarMateriaPrima(form.id, payload);
        toast.success('Materia prima actualizada');
      } else {
        await admin.crearMateriaPrima(payload);
        toast.success('Materia prima creada');
      }

      handleCloseModal();
      await cargarDatos();
    } catch (err) {
      console.error('Error saving materia prima', err);
      toast.error(err.response?.data?.message || 'Error al guardar materia prima');
    } finally {
      setIsSaving(false);
    }
  };

  const handleDelete = async (m) => {
    if (!window.confirm(`¿Eliminar materia prima "${m.nombre}"? Esta acción es reversible (soft-delete).`)) return;
    try {
      await admin.eliminarMateriaPrima(m.id);
      toast.success('Materia prima eliminada');
      await cargarDatos();
    } catch (err) {
      console.error('Error deleting materia prima', err);
      toast.error(err.response?.data?.message || 'Error al eliminar materia prima');
    }
  };

  if (loading) return <div className="text-center py-4"><Spinner animation="border" /></div>;

  const stockBajoMaterias = materias.filter(m => m.stock_actual <= m.stock_minimo);
  const stockBajoProductos = productosFinal.filter(p => p.stock_actual <= (p.stock_minimo || 0));

  return (
    <div>
      {/* Pestañas */}
      <Nav variant="pills" activeKey={activeTab} onSelect={(k) => setActiveTab(k)} className="mb-4">
        <Nav.Item>
          <Nav.Link eventKey="materias">
            📦 Materias Primas
          </Nav.Link>
        </Nav.Item>
        <Nav.Item>
          <Nav.Link eventKey="productos">
            🍞 Productos Finales
          </Nav.Link>
        </Nav.Item>
      </Nav>

      {/* Tab de Materias Primas */}
      {activeTab === 'materias' && (
        <>
          <div className="d-flex justify-content-end mb-3">
            <Button variant="primary" size="sm" onClick={handleOpenCreate}>
              <i className="bi bi-plus-lg"></i> Nueva Materia Prima
            </Button>
          </div>
          <Row className="mb-4">
            <Col md={4}>
              <Card className="shadow-sm">
                <Card.Body>
                  <h6 className="text-muted">Total Materias Primas</h6>
                  <h2 style={{ color: '#8b6f47' }}>{materias.length}</h2>
                </Card.Body>
              </Card>
            </Col>
            <Col md={4}>
              <Card className="shadow-sm">
                <Card.Body>
                  <h6 className="text-muted">Stock Bajo</h6>
                  <h2 className="text-danger">{stockBajoMaterias.length}</h2>
                </Card.Body>
              </Card>
            </Col>
            <Col md={4}>
              <Card className="shadow-sm">
                <Card.Body>
                  <h6 className="text-muted">Valor Total Inventario</h6>
                  <h2 className="text-success">
                    Bs. {materias.reduce((sum, m) => sum + (m.stock_actual * m.costo_unitario), 0).toFixed(2)}
                  </h2>
                </Card.Body>
              </Card>
            </Col>
          </Row>

          {materias.length === 0 ? (
            <Alert variant="info">No hay materias primas registradas.</Alert>
          ) : (
            <>
              <div className="d-flex justify-content-between align-items-center mb-3">
                <h4 className="mb-0">📦 Inventario de Materias Primas</h4>
                {totalPages > 1 && (
                  <small className="text-muted">
                    Página {currentPage} de {totalPages} | Mostrando {materias.length} items
                  </small>
                )}
              </div>
              <Table responsive hover>
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Stock Actual</th>
                    <th>Stock Mínimo</th>
                    <th>Unidad</th>
                    <th>Costo Unit.</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  {materias.map(m => (
                    <tr key={m.id}>
                      <td>{m.id}</td>
                      <td>{m.nombre}</td>
                      <td>
                        <Badge bg={m.stock_actual <= m.stock_minimo ? 'danger' : 'success'}>
                          {m.stock_actual}
                        </Badge>
                      </td>
                      <td>{m.stock_minimo}</td>
                      <td>{m.unidad_medida}</td>
                      <td>Bs. {parseFloat(m.costo_unitario).toFixed(2)}</td>
                      <td>
                        {m.activo ? (
                          <Badge bg="success">Activo</Badge>
                        ) : (
                          <Badge bg="secondary">Inactivo</Badge>
                        )}
                      </td>
                      <td>
                        <Button size="sm" variant="outline-primary" className="me-2" onClick={() => handleOpenEdit(m)}>
                          Editar
                        </Button>
                        <Button size="sm" variant="outline-danger" onClick={() => handleDelete(m)}>
                          Eliminar
                        </Button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </Table>
              
              {/* Pagination Controls for Materias Primas */}
              {totalPages > 1 && (
                <div className="d-flex justify-content-center mt-3">
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
        </>
      )}

      {/* Tab de Productos Finales */}
      {activeTab === 'productos' && (
        <>
          <Row className="mb-4">
            <Col md={4}>
              <Card className="shadow-sm">
                <Card.Body>
                  <h6 className="text-muted">Total Productos</h6>
                  <h2 style={{ color: '#8b6f47' }}>{productosFinal.length}</h2>
                </Card.Body>
              </Card>
            </Col>
            <Col md={4}>
              <Card className="shadow-sm">
                <Card.Body>
                  <h6 className="text-muted">Stock Bajo</h6>
                  <h2 className="text-danger">{stockBajoProductos.length}</h2>
                </Card.Body>
              </Card>
            </Col>
            <Col md={4}>
              <Card className="shadow-sm">
                <Card.Body>
                  <h6 className="text-muted">Stock Total</h6>
                  <h2 className="text-success">
                    {productosFinal.reduce((sum, p) => sum + (p.stock_actual || 0), 0).toFixed(2)}
                  </h2>
                </Card.Body>
              </Card>
            </Col>
          </Row>

          {productosFinal.length === 0 ? (
            <Alert variant="info">No hay productos finales en inventario.</Alert>
          ) : (
            <>
              <div className="d-flex justify-content-between align-items-center mb-3">
                <h4 className="mb-0">🍞 Inventario de Productos Finales</h4>
                {totalPages > 1 && (
                  <small className="text-muted">
                    Página {currentPage} de {totalPages} | Mostrando {productosFinal.length} items
                  </small>
                )}
              </div>
              <Table responsive hover>
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Producto</th>
                    <th>Stock Actual</th>
                    <th>Stock Mínimo</th>
                    <th>Costo Promedio</th>
                    <th>Fecha Elaboración</th>
                    <th>Fecha Vencimiento</th>
                    <th>Estado</th>
                  </tr>
                </thead>
                <tbody>
                  {productosFinal.map(p => (
                    <tr key={p.producto_id}>
                      <td>{p.producto_id}</td>
                      <td>{p.producto || 'N/A'}</td>
                      <td>
                        <Badge bg={p.stock_actual <= (p.stock_minimo || 0) ? 'danger' : 'success'}>
                          {parseFloat(p.stock_actual || 0).toFixed(2)}
                        </Badge>
                      </td>
                      <td>{parseFloat(p.stock_minimo || 0).toFixed(2)}</td>
                      <td>Bs. {parseFloat(p.costo_promedio || 0).toFixed(2)}</td>
                      <td>{p.fecha_elaboracion || 'N/A'}</td>
                      <td>{p.fecha_vencimiento || 'N/A'}</td>
                      <td>
                        <Badge bg={p.stock_actual > 0 ? 'success' : 'secondary'}>
                          {p.stock_actual > 0 ? 'Con Stock' : 'Sin Stock'}
                        </Badge>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </Table>
              
              {/* Pagination Controls for Productos Finales */}
              {totalPages > 1 && (
                <div className="d-flex justify-content-center mt-3">
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
        </>
      )}
      {/* Modal para crear/editar materia prima */}
      <Modal show={showModal} onHide={handleCloseModal}>
        <Form onSubmit={handleSave}>
          <Modal.Header closeButton>
            <Modal.Title>{form.id ? 'Editar Materia Prima' : 'Nueva Materia Prima'}</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            <Form.Group className="mb-2">
              <Form.Label>Nombre *</Form.Label>
              <Form.Control required value={form.nombre} onChange={(e) => setForm({...form, nombre: e.target.value})} />
            </Form.Group>

            <Form.Group className="mb-2">
              <Form.Label>Código interno</Form.Label>
              <Form.Control value={form.codigo_interno} onChange={(e) => setForm({...form, codigo_interno: e.target.value})} />
            </Form.Group>

            <Form.Group className="mb-2">
              <Form.Label>Unidad</Form.Label>
              <Form.Select value={form.unidad_medida} onChange={(e) => setForm({...form, unidad_medida: e.target.value})}>
                <option value="kg">kg</option>
                <option value="g">g</option>
                <option value="L">L</option>
                <option value="ml">ml</option>
                <option value="unidades">unidades</option>
              </Form.Select>
            </Form.Group>

            <Form.Group className="mb-2">
              <Form.Label>Stock actual *</Form.Label>
              <Form.Control type="number" step="0.01" value={form.stock_actual} onChange={(e) => setForm({...form, stock_actual: e.target.value})} required />
            </Form.Group>

            <Form.Group className="mb-2">
              <Form.Label>Stock mínimo *</Form.Label>
              <Form.Control type="number" step="0.01" value={form.stock_minimo} onChange={(e) => setForm({...form, stock_minimo: e.target.value})} required />
            </Form.Group>

            <Form.Group className="mb-2">
              <Form.Label>Costo unitario *</Form.Label>
              <Form.Control type="number" step="0.01" value={form.costo_unitario} onChange={(e) => setForm({...form, costo_unitario: e.target.value})} required />
            </Form.Group>

            <Form.Group className="mb-2">
              <Form.Label>Proveedor</Form.Label>
              <Form.Control value={form.proveedor} onChange={(e) => setForm({...form, proveedor: e.target.value})} />
            </Form.Group>

            <Form.Group className="mb-2">
              <Form.Label>Última compra</Form.Label>
              <Form.Control type="date" value={form.ultima_compra || ''} onChange={(e) => setForm({...form, ultima_compra: e.target.value})} />
            </Form.Group>

            <Form.Group className="mb-2">
              <Form.Check type="checkbox" label="Activo" checked={!!form.activo} onChange={(e) => setForm({...form, activo: e.target.checked})} />
            </Form.Group>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="secondary" onClick={handleCloseModal}>Cancelar</Button>
            <Button variant="primary" type="submit" disabled={isSaving}>{isSaving ? 'Guardando...' : 'Guardar'}</Button>
          </Modal.Footer>
        </Form>
      </Modal>
    </div>
  );
}
