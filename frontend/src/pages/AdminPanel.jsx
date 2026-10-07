import React, { useState, useEffect, Suspense, useCallback } from 'react';
import { Search, Pencil, Eye, EyeOff, Trash2 } from 'lucide-react';
import { Container, Row, Col, Card, Button, Table, Badge, Form, InputGroup, Modal, Spinner, Alert, Nav, Pagination } from 'react-bootstrap';
import { admin, getCategorias } from '../services/api';
import { formatCurrency } from '../utils/number';
import { toast } from 'react-toastify';
import ProductoForm from '../components/admin/ProductoForm';
import useDebounce from '../hooks/useDebounce';
import PedidosPanel from './admin/PedidosPanel';
import ClientesPanel from './admin/ClientesPanel';
const PanaderosPanel = React.lazy(() => import('./admin/PanaderosPanel'));
const VendedoresPanel = React.lazy(() => import('./admin/VendedoresPanel'));
import EmpleadoPagosPanel from './admin/EmpleadoPagosPanel';
import InventarioPanel from './admin/InventarioPanel';
import CategoriasPanel from './admin/CategoriasPanel';
import MovimientosInventarioPanel from './admin/MovimientosInventarioPanel';
import Dashboard from './admin/Dashboard';
import { IMAGEN_PLACEHOLDER, usarImagenRespaldo } from '../utils/imagen';

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
  const [openClienteSignal, setOpenClienteSignal] = useState(0);
  const [pagosFilters, setPagosFilters] = useState({});
  const [pagosOpenFor, setPagosOpenFor] = useState(null);
  
  // Paginación para productos
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [perPage] = useState(20);

  // debounce searchTerm to avoid firing requests on every keystroke
  const debouncedSearchTerm = useDebounce(searchTerm, 350);

  useEffect(() => {
    // Reset page when filters change
    setCurrentPage(1);
  }, [debouncedSearchTerm, filtroCategoria, filtroActivo, activeTab]);

  const cargarDatos = useCallback(async () => {
    try {
      setLoading(true);
      
      const params = {};
      if (debouncedSearchTerm) params.search = debouncedSearchTerm;
      if (filtroCategoria) params.categoria_id = filtroCategoria;
      if (filtroActivo !== '') params.activo = filtroActivo;
      
      // Agregar paginación
      params.per_page = perPage;
      params.page = currentPage;

      const [productosData, categoriasData, statsData] = await Promise.all([
        admin.getProductos(params),
        getCategorias(),
        admin.getStats()
      ]);

      setProductos(productosData.data || productosData);
      
      // Actualizar información de paginación
      if (productosData.last_page) {
        setTotalPages(productosData.last_page);
        if (import.meta.env.DEV) console.debug('[AdminPanel] Total pages:', productosData.last_page, 'Current:', currentPage);
      }
      
      setCategorias(categoriasData);
      setStats(statsData);
    } catch (error) {
      console.error('Error al cargar datos:', error);
      toast.error('Error al cargar los datos');
    } finally {
      setLoading(false);
    }
  }, [debouncedSearchTerm, filtroCategoria, filtroActivo, perPage, currentPage]);

  useEffect(() => {
    // Solo cargar productos cuando la pestaña activa sea 'productos'
    if (activeTab === 'productos') {
      cargarDatos();
    } else {
      // No cargar nada si no está en productos (optimización)
      setLoading(false);
    }
  }, [activeTab, cargarDatos]);

  const handleNuevoProducto = () => {
    setProductoEditar(null);
    setShowModal(true);
  };

  const handleEditarProducto = async (producto) => {
    // Open modal and fetch full product from backend to ensure all fields (extras, booleans, inventario) are present
    setProductoEditar(null);
    setShowModal(true);
    try {
      const full = await admin.getProducto(producto.id);
      setProductoEditar(full);
    } catch (error) {
      console.error('Error al obtener producto completo:', error);
      toast.error('No se pudo cargar el producto para editar');
      setShowModal(false);
    }
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

  // No desmontar toda la UI durante la carga para evitar perder foco en inputs (ej. buscador).
  // Mostramos un spinner inline en el header cuando esté cargando.

  return (
    <Container fluid className="py-4">
      {/* Header */}
      <Row className="mb-4">
        <Col>
          <h1 style={{ color: '#534031', fontWeight: 'bold' }}>
            Panel de Administración
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
            {activeTab === 'perfil' && 'Configura tu perfil y preferencias del sistema'}
          </p>
        </Col>
        {activeTab === 'productos' && (
          <>
            {totalPages > 1 && (
              <Col xs="auto">
                <small className="text-muted">
                  Página {currentPage} de {totalPages} | {productos.length} productos
                </small>
              </Col>
            )}
            <Col xs="auto">
              <Button
                size="lg"
                onClick={handleNuevoProducto}
                style={{ backgroundColor: '#8b6f47', borderColor: '#8b6f47' }}
              >
                + Nuevo Producto
              </Button>
            </Col>
          </>
        )}
        {activeTab === 'clientes' && (
          <Col xs="auto">
            <Button
              size="lg"
              onClick={() => setOpenClienteSignal((s) => s + 1)}
              style={{ backgroundColor: '#8b6f47', borderColor: '#8b6f47' }}
            >
              + Nuevo Usuario
            </Button>
          </Col>
        )}
      </Row>

      {/* Pestañas de navegación */}
      <Nav variant="tabs" activeKey={activeTab} onSelect={(k) => setActiveTab(k)} className="mb-4">
        <Nav.Item>
          <Nav.Link eventKey="dashboard">Dashboard</Nav.Link>
        </Nav.Item>
        <Nav.Item>
          <Nav.Link eventKey="productos">
            Productos
          </Nav.Link>
        </Nav.Item>
        <Nav.Item>
          <Nav.Link eventKey="pedidos">
            Pedidos
          </Nav.Link>
        </Nav.Item>
        <Nav.Item>
          <Nav.Link eventKey="clientes">
            Clientes
          </Nav.Link>
        </Nav.Item>
        <Nav.Item>
          <Nav.Link eventKey="panaderos">
            Panaderos
          </Nav.Link>
        </Nav.Item>
        <Nav.Item>
          <Nav.Link eventKey="vendedores">
            Vendedores
          </Nav.Link>
        </Nav.Item>
        <Nav.Item>
          <Nav.Link eventKey="inventario">
            Inventario
          </Nav.Link>
        </Nav.Item>
        <Nav.Item>
          <Nav.Link eventKey="categorias">
            Categorías
          </Nav.Link>
        </Nav.Item>
        <Nav.Item>
          <Nav.Link eventKey="movimientos">
            Movimientos
          </Nav.Link>
        </Nav.Item>
        <Nav.Item>
          <Nav.Link eventKey="pagos">
            Pagos
          </Nav.Link>
        </Nav.Item>
        {/* Perfil moved to global navbar */}
      </Nav>
      {/* Inline loading indicator to avoid unmounting the whole UI (keeps search input focused) */}
      {loading && activeTab === 'productos' && (
        <div className="mb-3 text-center">
          <Spinner animation="border" size="sm" style={{ color: '#8b6f47' }} /> <small className="text-muted ms-2">Cargando productos...</small>
        </div>
      )}
      {activeTab === 'pedidos' && <PedidosPanel />}
  {activeTab === 'dashboard' && <Dashboard />}
      {activeTab === 'clientes' && <ClientesPanel externalOpenCreate={openClienteSignal} />}
      {activeTab === 'panaderos' && (
        <Suspense fallback={<div className="d-flex justify-content-center py-4"><div className="spinner-border" role="status"><span className="visually-hidden">Cargando...</span></div></div>}>
          <PanaderosPanel onOpenPayments={(tipo, id, monto) => { setPagosFilters({ empleado_tipo: tipo, empleado_id: id }); setPagosOpenFor({ empleado_tipo: tipo, empleado_id: id, monto }); setActiveTab('pagos'); }} />
        </Suspense>
      )}
      {activeTab === 'vendedores' && (
        <Suspense fallback={<div className="d-flex justify-content-center py-4"><div className="spinner-border" role="status"><span className="visually-hidden">Cargando...</span></div></div>}>
          <VendedoresPanel onOpenPayments={(tipo, id, monto) => { setPagosFilters({ empleado_tipo: tipo, empleado_id: id }); setPagosOpenFor({ empleado_tipo: tipo, empleado_id: id, monto }); setActiveTab('pagos'); }} />
        </Suspense>
      )}
      {activeTab === 'pagos' && <EmpleadoPagosPanel initialFilters={pagosFilters} openCreateFor={pagosOpenFor} />}
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
                <InputGroup.Text><Search size={16} /></InputGroup.Text>
                <Form.Control
                  placeholder="Buscar producto..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  onKeyDown={(e) => { if (e.key === 'Enter') e.preventDefault(); }}
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
                            || IMAGEN_PLACEHOLDER
                        }
                        alt={producto.nombre}
                        style={{ width: '60px', height: '60px', objectFit: 'cover', borderRadius: '4px' }}
                        onError={usarImagenRespaldo}
                      />
                    </td>
                    <td>
                      <strong>{producto.nombre}</strong>
                      <br />
                      <small className="text-muted">{String(producto.presentacion ?? '').trim() !== '' ? producto.presentacion : '\u2014'}</small>
                      {producto.es_de_temporada && (
                        <Badge bg="warning" className="ms-2">Temporada</Badge>
                      )}
                    </td>
                    <td>{producto.categoria?.nombre || 'Sin categoría'}</td>
                    <td>
                      <strong>Bs. {formatCurrency(producto.precio_minorista ?? producto.precio ?? 0)}</strong>
                      {producto.precio_mayorista && (
                        <>
                          <br />
                          <small className="text-muted">
                            Mayor: Bs. {formatCurrency(producto.precio_mayorista)}
                          </small>
                        </>
                      )}
                    </td>
                    <td>
                      {producto.inventario && producto.inventario.stock_actual !== null ? (
                        // El stock de productos finales es entero por diseño
                        <span>{parseInt(producto.inventario.stock_actual, 10)}</span>
                      ) : (producto.limite_produccion ? (
                        <Badge bg="info">{producto.limite_produccion} max</Badge>
                      ) : (
                        <span className="text-muted">—</span>
                      ))}
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
                          <Pencil size={16} />
                        </Button>
                        <Button
                          size="sm"
                          variant={producto.esta_activo ? 'outline-warning' : 'outline-success'}
                          onClick={() => handleToggleActive(producto.id, producto.nombre)}
                          title={producto.esta_activo ? 'Desactivar' : 'Activar'}
                        >
                          {producto.esta_activo ? <Eye size={16} /> : <EyeOff size={16} />}
                        </Button>
                        <Button
                          size="sm"
                          variant="outline-danger"
                          onClick={() => handleEliminarProducto(producto.id, producto.nombre)}
                          title="Eliminar"
                        >
                          <Trash2 size={16} />
                        </Button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </Table>
          )}
          
          {/* Controles de Paginación */}
          {!loading && productos.length > 0 && totalPages > 1 && (
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
