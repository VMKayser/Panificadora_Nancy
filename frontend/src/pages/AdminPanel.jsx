import { useState, useEffect } from 'react';
import { Container, Row, Col, Card, Button, Table, Badge, Form, InputGroup, Modal, Spinner, Alert, Nav } from 'react-bootstrap';
import { admin, getCategorias } from '../services/api';
import { toast } from 'react-toastify';
import ProductoForm from '../components/admin/ProductoForm';
import PedidosPanel from './admin/PedidosPanel';
import ClientesPanel from './admin/ClientesPanel';
import PanaderosPanel from './admin/PanaderosPanel';
import VendedoresPanel from './admin/VendedoresPanel';
import InventarioPanel from './admin/InventarioPanel';
import CategoriasPanel from './admin/CategoriasPanel';
import MovimientosInventarioPanel from './admin/MovimientosInventarioPanel';

const AdminPanel = () => {
  const [productos, setProductos] = useState([]);
  const [categorias, setCategorias] = useState([]);
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [productoEditar, setProductoEditar] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [filtroCategoria, setFiltroCategoria] = useState('');
  const [filtroActivo, setFiltroActivo] = useState('');
  const [activeTab, setActiveTab] = useState('productos');

  useEffect(() => {
    // Solo cargar productos cuando la pestaña activa sea 'productos'
    if (activeTab === 'productos') {
      cargarDatos();
    } else {
      // No cargar nada si no está en productos (optimización)
      setLoading(false);
    }
  }, [searchTerm, filtroCategoria, filtroActivo, activeTab]);

  const cargarDatos = async () => {
    try {
      setLoading(true);
      
      const params = {};
      if (searchTerm) params.search = searchTerm;
      if (filtroCategoria) params.categoria_id = filtroCategoria;
      if (filtroActivo !== '') params.activo = filtroActivo;

      const [productosData, categoriasData, statsData] = await Promise.all([
        admin.getProductos(params),
        getCategorias(),
        admin.getStats()
      ]);

      setProductos(productosData.data || productosData);
      setCategorias(categoriasData);
      setStats(statsData);
    } catch (error) {
      console.error('Error al cargar datos:', error);
      toast.error('Error al cargar los datos');
    } finally {
      setLoading(false);
    }
  };

  const handleNuevoProducto = () => {
    setProductoEditar(null);
    setShowModal(true);
  };

  const handleEditarProducto = (producto) => {
    setProductoEditar(producto);
    setShowModal(true);
  };

  const handleEliminarProducto = async (id, nombre) => {
    if (!window.confirm(`¿Estás seguro de eliminar "${nombre}"?`)) return;

    try {
      await admin.eliminarProducto(id);
      toast.success('Producto eliminado exitosamente');
      cargarDatos();
    } catch (error) {
      toast.error('Error al eliminar producto');
    }
  };

  const handleToggleActive = async (id, nombre) => {
    try {
      await admin.toggleActive(id);
      toast.success(`Estado de "${nombre}" actualizado`);
      cargarDatos();
    } catch (error) {
      toast.error('Error al cambiar estado');
    }
  };

  const handleGuardarProducto = async () => {
    setShowModal(false);
    cargarDatos();
  };

  if (loading && activeTab === 'productos') {
    return (
      <Container className="text-center py-5">
        <Spinner animation="border" style={{ color: '#8b6f47' }} />
        <p className="mt-3">Cargando productos...</p>
      </Container>
    );
  }

  return (
    <Container fluid className="py-4">
      {/* Header */}
      <Row className="mb-4">
        <Col>
          <h1 style={{ color: '#534031', fontWeight: 'bold' }}>
            📦 Panel de Administración
          </h1>
          <p className="text-muted">
            {activeTab === 'productos' && 'Gestiona tu catálogo de productos'}
            {activeTab === 'pedidos' && 'Gestiona los pedidos de clientes'}
            {activeTab === 'clientes' && 'Gestiona la base de clientes'}
            {activeTab === 'panaderos' && 'Gestiona el equipo de panaderos'}
            {activeTab === 'vendedores' && 'Gestiona el equipo de vendedores'}
            {activeTab === 'inventario' && 'Controla el inventario de materias primas y productos finales'}
            {activeTab === 'categorias' && 'Organiza tus categorías de productos'}
            {activeTab === 'movimientos' && 'Registra entradas y salidas de stock'}
          </p>
        </Col>
        {activeTab === 'productos' && (
          <Col xs="auto">
            <Button
              size="lg"
              onClick={handleNuevoProducto}
              style={{ backgroundColor: '#8b6f47', borderColor: '#8b6f47' }}
            >
              + Nuevo Producto
            </Button>
          </Col>
        )}
      </Row>

      {/* Pestañas de navegación */}
      <Nav variant="tabs" activeKey={activeTab} onSelect={(k) => setActiveTab(k)} className="mb-4">
        <Nav.Item>
          <Nav.Link eventKey="productos">
            📦 Productos
          </Nav.Link>
        </Nav.Item>
        <Nav.Item>
          <Nav.Link eventKey="pedidos">
            📋 Pedidos
          </Nav.Link>
        </Nav.Item>
        <Nav.Item>
          <Nav.Link eventKey="clientes">
            👥 Clientes
          </Nav.Link>
        </Nav.Item>
        <Nav.Item>
          <Nav.Link eventKey="panaderos">
            🧑‍🍳 Panaderos
          </Nav.Link>
        </Nav.Item>
        <Nav.Item>
          <Nav.Link eventKey="vendedores">
            💼 Vendedores
          </Nav.Link>
        </Nav.Item>
        <Nav.Item>
          <Nav.Link eventKey="inventario">
            📦 Inventario
          </Nav.Link>
        </Nav.Item>
        <Nav.Item>
          <Nav.Link eventKey="categorias">
            🏷️ Categorías
          </Nav.Link>
        </Nav.Item>
        <Nav.Item>
          <Nav.Link eventKey="movimientos">
            ↔️ Movimientos
          </Nav.Link>
        </Nav.Item>
      </Nav>

      {activeTab === 'pedidos' && <PedidosPanel />}
      {activeTab === 'clientes' && <ClientesPanel />}
      {activeTab === 'panaderos' && <PanaderosPanel />}
      {activeTab === 'vendedores' && <VendedoresPanel />}
      {activeTab === 'inventario' && <InventarioPanel />}
      {activeTab === 'categorias' && <CategoriasPanel />}
      {activeTab === 'movimientos' && <MovimientosInventarioPanel />}

      {activeTab === 'productos' && (
        <>

      {/* Estadísticas */}
      {stats && (
        <Row className="mb-4">
          <Col md={3}>
            <Card className="shadow-sm">
              <Card.Body>
                <h6 className="text-muted">Total Productos</h6>
                <h2 style={{ color: '#8b6f47' }}>{stats.total_productos}</h2>
              </Card.Body>
            </Card>
          </Col>
          <Col md={3}>
            <Card className="shadow-sm">
              <Card.Body>
                <h6 className="text-muted">Activos</h6>
                <h2 className="text-success">{stats.productos_activos}</h2>
              </Card.Body>
            </Card>
          </Col>
          <Col md={3}>
            <Card className="shadow-sm">
              <Card.Body>
                <h6 className="text-muted">De Temporada</h6>
                <h2 className="text-warning">{stats.productos_temporada}</h2>
              </Card.Body>
            </Card>
          </Col>
          <Col md={3}>
            <Card className="shadow-sm">
              <Card.Body>
                <h6 className="text-muted">Sin Imagen</h6>
                <h2 className="text-danger">{stats.productos_sin_imagen}</h2>
              </Card.Body>
            </Card>
          </Col>
        </Row>
      )}

      {/* Filtros */}
      <Card className="shadow-sm mb-4">
        <Card.Body>
          <Row>
            <Col md={4}>
              <InputGroup>
                <InputGroup.Text>🔍</InputGroup.Text>
                <Form.Control
                  placeholder="Buscar producto..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                />
              </InputGroup>
            </Col>
            <Col md={4}>
              <Form.Select
                value={filtroCategoria}
                onChange={(e) => setFiltroCategoria(e.target.value)}
              >
                <option value="">Todas las categorías</option>
                {categorias.map(cat => (
                  <option key={cat.id} value={cat.id}>{cat.nombre}</option>
                ))}
              </Form.Select>
            </Col>
            <Col md={4}>
              <Form.Select
                value={filtroActivo}
                onChange={(e) => setFiltroActivo(e.target.value)}
              >
                <option value="">Todos los estados</option>
                <option value="1">Solo activos</option>
                <option value="0">Solo inactivos</option>
              </Form.Select>
            </Col>
          </Row>
        </Card.Body>
      </Card>

      {/* Tabla de Productos */}
      <Card className="shadow-sm">
        <Card.Body>
          {productos.length === 0 ? (
            <Alert variant="info">No se encontraron productos</Alert>
          ) : (
            <Table responsive hover>
              <thead style={{ backgroundColor: '#f8f9fa' }}>
                <tr>
                  <th style={{ width: '80px' }}>Imagen</th>
                  <th>Nombre</th>
                  <th>Categoría</th>
                  <th>Precio</th>
                  <th>Stock</th>
                  <th style={{ width: '120px' }}>Estado</th>
                  <th style={{ width: '180px' }}>Acciones</th>
                </tr>
              </thead>
              <tbody>
                {productos.map(producto => (
                  <tr key={producto.id}>
                    <td>
                      <img
                        src={
                          producto.imagenes?.[0]?.url_imagen_completa
                            || producto.imagenes?.[0]?.url_imagen
                            || 'https://via.placeholder.com/60?text=Sin+Imagen'
                        }
                        alt={producto.nombre}
                        style={{ width: '60px', height: '60px', objectFit: 'cover', borderRadius: '4px' }}
                        onError={(e) => {
                          e.target.onerror = null;
                          e.target.src = 'https://via.placeholder.com/60?text=Error';
                        }}
                      />
                    </td>
                    <td>
                      <strong>{producto.nombre}</strong>
                      <br />
                      <small className="text-muted">{producto.presentacion}</small>
                      {producto.es_de_temporada && (
                        <Badge bg="warning" className="ms-2">Temporada</Badge>
                      )}
                    </td>
                    <td>{producto.categoria?.nombre || 'Sin categoría'}</td>
                    <td>
                      <strong>Bs. {parseFloat(producto.precio_minorista).toFixed(2)}</strong>
                      {producto.precio_mayorista && (
                        <>
                          <br />
                          <small className="text-muted">
                            Mayor: Bs. {parseFloat(producto.precio_mayorista).toFixed(2)}
                          </small>
                        </>
                      )}
                    </td>
                    <td>
                      {producto.limite_produccion ? (
                        <Badge bg="info">{producto.limite_produccion} max</Badge>
                      ) : (
                        <span className="text-muted">—</span>
                      )}
                    </td>
                    <td>
                      <Badge bg={producto.esta_activo ? 'success' : 'secondary'}>
                        {producto.esta_activo ? 'Activo' : 'Inactivo'}
                      </Badge>
                    </td>
                    <td>
                      <div className="d-flex gap-2">
                        <Button
                          size="sm"
                          variant="outline-primary"
                          onClick={() => handleEditarProducto(producto)}
                          title="Editar"
                        >
                          ✏️
                        </Button>
                        <Button
                          size="sm"
                          variant={producto.esta_activo ? 'outline-warning' : 'outline-success'}
                          onClick={() => handleToggleActive(producto.id, producto.nombre)}
                          title={producto.esta_activo ? 'Desactivar' : 'Activar'}
                        >
                          {producto.esta_activo ? '👁️' : '🔒'}
                        </Button>
                        <Button
                          size="sm"
                          variant="outline-danger"
                          onClick={() => handleEliminarProducto(producto.id, producto.nombre)}
                          title="Eliminar"
                        >
                          🗑️
                        </Button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </Table>
          )}
        </Card.Body>
      </Card>

  {/* Modal de Formulario */}
      <Modal 
        show={showModal} 
        onHide={() => setShowModal(false)} 
        size="xl"
        centered
      >
        <Modal.Header closeButton>
          <Modal.Title>
            {productoEditar ? 'Editar Producto' : 'Nuevo Producto'}
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <ProductoForm
            producto={productoEditar}
            categorias={categorias}
            onGuardar={handleGuardarProducto}
            onCancelar={() => setShowModal(false)}
          />
        </Modal.Body>
      </Modal>
        </>
      )}
    </Container>
  );
};

export default AdminPanel;
