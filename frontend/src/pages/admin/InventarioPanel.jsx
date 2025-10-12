import { useEffect, useState } from 'react';
import { Table, Alert, Spinner, Badge, Card, Row, Col, Nav } from 'react-bootstrap';
import { admin } from '../../services/api';

export default function InventarioPanel() {
  const [activeTab, setActiveTab] = useState('materias');
  const [materias, setMaterias] = useState([]);
  const [productosFinal, setProductosFinal] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    cargarDatos();
  }, [activeTab]);

  const cargarDatos = async () => {
    try {
      setLoading(true);
      if (activeTab === 'materias') {
        const data = await admin.getMateriasPrimas();
        setMaterias(data.data || data);
      } else {
        const data = await admin.getProductosFinales();
        setProductosFinal(data.data || data);
      }
    } catch (error) {
      console.error('Error al cargar inventario:', error);
    } finally {
      setLoading(false);
    }
  };

  if (loading) return <div className="text-center py-4"><Spinner animation="border" /></div>;

  const stockBajoMaterias = materias.filter(m => m.stock_actual <= m.stock_minimo);
  const stockBajoProductos = productosFinal.filter(p => p.cantidad_disponible <= (p.stock_minimo || 0));

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
              <h4 className="mb-3">📦 Inventario de Materias Primas</h4>
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
                    </tr>
                  ))}
                </tbody>
              </Table>
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
                    {productosFinal.reduce((sum, p) => sum + (p.cantidad_disponible || 0), 0)}
                  </h2>
                </Card.Body>
              </Card>
            </Col>
          </Row>

          {productosFinal.length === 0 ? (
            <Alert variant="info">No hay productos finales en inventario.</Alert>
          ) : (
            <>
              <h4 className="mb-3">🍞 Inventario de Productos Finales</h4>
              <Table responsive hover>
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Producto</th>
                    <th>Cantidad Disponible</th>
                    <th>Stock Mínimo</th>
                    <th>Ubicación</th>
                    <th>Lote</th>
                    <th>Fecha Producción</th>
                    <th>Estado</th>
                  </tr>
                </thead>
                <tbody>
                  {productosFinal.map(p => (
                    <tr key={p.id}>
                      <td>{p.id}</td>
                      <td>{p.producto?.nombre || 'N/A'}</td>
                      <td>
                        <Badge bg={p.cantidad_disponible <= (p.stock_minimo || 0) ? 'danger' : 'success'}>
                          {p.cantidad_disponible || 0}
                        </Badge>
                      </td>
                      <td>{p.stock_minimo || 0}</td>
                      <td>{p.ubicacion || 'N/A'}</td>
                      <td>{p.lote || 'N/A'}</td>
                      <td>{p.fecha_produccion ? new Date(p.fecha_produccion).toLocaleDateString() : 'N/A'}</td>
                      <td>
                        {p.activo ? (
                          <Badge bg="success">Activo</Badge>
                        ) : (
                          <Badge bg="secondary">Inactivo</Badge>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </Table>
            </>
          )}
        </>
      )}
    </div>
  );
}
