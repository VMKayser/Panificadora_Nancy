import { useState, useEffect } from 'react';
import { Container, Row, Col, Card, Badge, Spinner, Alert, Tab, Tabs, ListGroup, Button, Modal } from 'react-bootstrap';
import { Package, Clock, CheckCircle, XCircle, Truck, Calendar, CreditCard, MapPin, FileText } from 'lucide-react';
import { auth } from '../services/api';
import { toast } from 'react-toastify';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';

const MisPedidos = () => {
  const [pedidos, setPedidos] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedPedido, setSelectedPedido] = useState(null);
  const [showDetalleModal, setShowDetalleModal] = useState(false);
  const [filtroEstado, setFiltroEstado] = useState('todos');

  useEffect(() => {
    cargarPedidos();
  }, []);

  const cargarPedidos = async () => {
    try {
      setLoading(true);
      const response = await auth.getMisPedidos();
      // La respuesta viene con paginación: { pedidos: { data: [...], current_page, ... } }
      const pedidosData = response.pedidos?.data || response.pedidos || [];
      setPedidos(Array.isArray(pedidosData) ? pedidosData : []);
    } catch (error) {
      console.error('Error al cargar pedidos:', error);
      toast.error('Error al cargar tus pedidos');
    } finally {
      setLoading(false);
    }
  };

  const verDetalle = async (pedido) => {
    try {
      const detalle = await auth.getMiPedidoDetalle(pedido.id);
      setSelectedPedido(detalle);
      setShowDetalleModal(true);
    } catch (error) {
      console.error('Error al cargar detalle:', error);
      toast.error('Error al cargar el detalle del pedido');
    }
  };

  const getEstadoBadge = (estado) => {
    const estados = {
      pendiente: { bg: 'warning', text: 'Pendiente', icon: <Clock size={14} /> },
      confirmado: { bg: 'info', text: 'Confirmado', icon: <CheckCircle size={14} /> },
      en_preparacion: { bg: 'primary', text: 'En Preparación', icon: <Package size={14} /> },
      listo: { bg: 'success', text: 'Listo', icon: <CheckCircle size={14} /> },
      en_camino: { bg: 'info', text: 'En Camino', icon: <Truck size={14} /> },
      entregado: { bg: 'success', text: 'Entregado', icon: <CheckCircle size={14} /> },
      cancelado: { bg: 'danger', text: 'Cancelado', icon: <XCircle size={14} /> },
    };
    const estado_info = estados[estado] || { bg: 'secondary', text: estado, icon: null };
    return (
      <Badge bg={estado_info.bg} className="d-flex align-items-center gap-1">
        {estado_info.icon} {estado_info.text}
      </Badge>
    );
  };

  const formatFecha = (fecha) => {
    if (!fecha) return 'No especificada';
    try {
      return format(new Date(fecha), "dd 'de' MMMM, yyyy 'a las' HH:mm", { locale: es });
    } catch {
      return fecha;
    }
  };

  const pedidosFiltrados = pedidos.filter(p => {
    if (filtroEstado === 'todos') return true;
    if (filtroEstado === 'activos') return !['entregado', 'cancelado'].includes(p.estado);
    if (filtroEstado === 'completados') return ['entregado', 'cancelado'].includes(p.estado);
    return p.estado === filtroEstado;
  });

  const contadores = {
    todos: pedidos.length,
    activos: pedidos.filter(p => !['entregado', 'cancelado'].includes(p.estado)).length,
    completados: pedidos.filter(p => ['entregado', 'cancelado'].includes(p.estado)).length,
  };

  if (loading) {
    return (
      <Container className="text-center py-5">
        <Spinner animation="border" style={{ color: '#8b6f47' }} />
        <p className="mt-3">Cargando tus pedidos...</p>
      </Container>
    );
  }

  return (
    <Container className="py-4">
      {/* Header */}
      <Row className="mb-4">
        <Col>
          <h1 style={{ color: '#534031', fontWeight: 'bold' }}>
            📦 Mis Pedidos
          </h1>
          <p className="text-muted">
            Consulta el estado y detalles de todos tus pedidos
          </p>
        </Col>
      </Row>

      {/* Filtros con Tabs */}
      <Tabs
        activeKey={filtroEstado}
        onSelect={(k) => setFiltroEstado(k)}
        className="mb-4"
        style={{ borderBottom: '2px solid #8b6f47' }}
      >
        <Tab 
          eventKey="todos" 
          title={
            <span>
              📋 Todos <Badge bg="secondary" pill>{contadores.todos}</Badge>
            </span>
          }
        />
        <Tab 
          eventKey="activos" 
          title={
            <span>
              🔄 En Proceso <Badge bg="primary" pill>{contadores.activos}</Badge>
            </span>
          }
        />
        <Tab 
          eventKey="completados" 
          title={
            <span>
              ✅ Completados <Badge bg="success" pill>{contadores.completados}</Badge>
            </span>
          }
        />
      </Tabs>

      {/* Lista de Pedidos */}
      {pedidosFiltrados.length === 0 ? (
        <Alert variant="info" className="text-center">
          <Package size={48} className="mb-3" />
          <h5>No tienes pedidos {filtroEstado !== 'todos' && `${filtroEstado}`}</h5>
          <p className="mb-0">Cuando realices un pedido, aparecerá aquí</p>
        </Alert>
      ) : (
        <Row>
          {pedidosFiltrados.map((pedido) => (
            <Col key={pedido.id} md={6} lg={4} className="mb-4">
              <Card className="h-100 shadow-sm border-0" style={{ borderTop: '4px solid #8b6f47' }}>
                <Card.Body>
                  {/* Header del pedido */}
                  <div className="d-flex justify-content-between align-items-start mb-3">
                    <div>
                      <h6 className="mb-1" style={{ color: '#534031', fontWeight: 'bold' }}>
                        {pedido.numero_pedido}
                      </h6>
                      <small className="text-muted">
                        {format(new Date(pedido.created_at), 'dd/MM/yyyy HH:mm')}
                      </small>
                    </div>
                    {getEstadoBadge(pedido.estado)}
                  </div>

                  {/* Información del pedido */}
                  <ListGroup variant="flush" className="mb-3">
                    <ListGroup.Item className="px-0 py-2 d-flex align-items-center gap-2">
                      <Package size={16} style={{ color: '#8b6f47' }} />
                      <small>
                        <strong>{pedido.detalles?.length || 0}</strong> producto(s)
                      </small>
                    </ListGroup.Item>

                    {pedido.fecha_entrega && (
                      <ListGroup.Item className="px-0 py-2 d-flex align-items-center gap-2">
                        <Calendar size={16} style={{ color: '#8b6f47' }} />
                        <small>{formatFecha(pedido.fecha_entrega)}</small>
                      </ListGroup.Item>
                    )}

                    <ListGroup.Item className="px-0 py-2 d-flex align-items-center gap-2">
                      <Truck size={16} style={{ color: '#8b6f47' }} />
                      <small className="text-capitalize">
                        {pedido.tipo_entrega === 'delivery' ? 'A Domicilio' : 
                         pedido.tipo_entrega === 'recoger' ? 'Recoger en Tienda' : 
                         pedido.tipo_entrega}
                      </small>
                    </ListGroup.Item>

                    <ListGroup.Item className="px-0 py-2 d-flex align-items-center gap-2">
                      <CreditCard size={16} style={{ color: '#8b6f47' }} />
                      <small>{pedido.metodo_pago?.nombre || 'No especificado'}</small>
                    </ListGroup.Item>
                  </ListGroup>

                  {/* Total */}
                  <div className="d-flex justify-content-between align-items-center mb-3 p-2 rounded" 
                       style={{ backgroundColor: '#f8f9fa' }}>
                    <span className="fw-bold" style={{ color: '#534031' }}>Total:</span>
                    <span className="fs-5 fw-bold" style={{ color: '#8b6f47' }}>
                      Bs. {parseFloat(pedido.total).toFixed(2)}
                    </span>
                  </div>

                  {/* Botón Ver Detalle */}
                  <Button
                    variant="outline-primary"
                    size="sm"
                    className="w-100"
                    onClick={() => verDetalle(pedido)}
                    style={{ borderColor: '#8b6f47', color: '#8b6f47' }}
                    onMouseEnter={(e) => {
                      e.target.style.backgroundColor = '#8b6f47';
                      e.target.style.color = 'white';
                    }}
                    onMouseLeave={(e) => {
                      e.target.style.backgroundColor = 'transparent';
                      e.target.style.color = '#8b6f47';
                    }}
                  >
                    Ver Detalle Completo
                  </Button>
                </Card.Body>
              </Card>
            </Col>
          ))}
        </Row>
      )}

      {/* Modal de Detalle */}
      <Modal 
        show={showDetalleModal} 
        onHide={() => setShowDetalleModal(false)} 
        size="lg"
        centered
      >
        <Modal.Header closeButton style={{ borderBottom: '2px solid #8b6f47' }}>
          <Modal.Title style={{ color: '#534031' }}>
            📦 Detalle del Pedido
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {selectedPedido && (
            <>
              {/* Información General */}
              <Row className="mb-4">
                <Col md={6}>
                  <h6 style={{ color: '#534031' }}>📋 Información del Pedido</h6>
                  <p className="mb-1"><strong>Número:</strong> {selectedPedido.numero_pedido}</p>
                  <p className="mb-1"><strong>Fecha:</strong> {formatFecha(selectedPedido.created_at)}</p>
                  <p className="mb-1"><strong>Estado:</strong> {getEstadoBadge(selectedPedido.estado)}</p>
                  {selectedPedido.fecha_entrega && (
                    <p className="mb-1"><strong>Entrega:</strong> {formatFecha(selectedPedido.fecha_entrega)}</p>
                  )}
                </Col>
                <Col md={6}>
                  <h6 style={{ color: '#534031' }}>👤 Información de Contacto</h6>
                  <p className="mb-1"><strong>Nombre:</strong> {selectedPedido.cliente_nombre} {selectedPedido.cliente_apellido}</p>
                  <p className="mb-1"><strong>Email:</strong> {selectedPedido.cliente_email}</p>
                  <p className="mb-1"><strong>Teléfono:</strong> {selectedPedido.cliente_telefono}</p>
                </Col>
              </Row>

              {/* Dirección de Entrega */}
              {selectedPedido.direccion_entrega && (
                <Row className="mb-4">
                  <Col>
                    <h6 style={{ color: '#534031' }}>
                      <MapPin size={18} className="me-2" />
                      Dirección de Entrega
                    </h6>
                    <p className="mb-1">{selectedPedido.direccion_entrega}</p>
                    {selectedPedido.indicaciones_especiales && (
                      <p className="mb-0 text-muted">
                        <small><strong>Indicaciones:</strong> {selectedPedido.indicaciones_especiales}</small>
                      </p>
                    )}
                  </Col>
                </Row>
              )}

              {/* Productos */}
              <Row className="mb-4">
                <Col>
                  <h6 style={{ color: '#534031' }}>🍞 Productos</h6>
                  <ListGroup>
                    {selectedPedido.detalles?.map((detalle, idx) => (
                      <ListGroup.Item key={idx} className="d-flex align-items-center gap-3">
                        {detalle.producto?.imagenes?.[0]?.url_imagen_completa && (
                          <img
                            src={detalle.producto.imagenes[0].url_imagen_completa}
                            alt={detalle.nombre_producto}
                            style={{ width: '60px', height: '60px', objectFit: 'cover', borderRadius: '8px' }}
                          />
                        )}
                        <div className="flex-grow-1">
                          <strong>{detalle.nombre_producto}</strong>
                          <br />
                          <small className="text-muted">
                            Cantidad: {detalle.cantidad} × Bs. {parseFloat(detalle.precio_unitario).toFixed(2)}
                          </small>
                        </div>
                        <div className="text-end">
                          <strong style={{ color: '#8b6f47' }}>
                            Bs. {parseFloat(detalle.subtotal).toFixed(2)}
                          </strong>
                        </div>
                      </ListGroup.Item>
                    ))}
                  </ListGroup>
                </Col>
              </Row>

              {/* Resumen de Pago */}
              <Row>
                <Col>
                  <Card style={{ backgroundColor: '#f8f9fa', border: 'none' }}>
                    <Card.Body>
                      <div className="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span>Bs. {parseFloat(selectedPedido.subtotal).toFixed(2)}</span>
                      </div>
                      {selectedPedido.descuento_bs > 0 && (
                        <div className="d-flex justify-content-between mb-2 text-success">
                          <span>Descuento:</span>
                          <span>- Bs. {parseFloat(selectedPedido.descuento_bs).toFixed(2)}</span>
                        </div>
                      )}
                      <hr />
                      <div className="d-flex justify-content-between">
                        <strong style={{ fontSize: '1.2rem', color: '#534031' }}>Total:</strong>
                        <strong style={{ fontSize: '1.2rem', color: '#8b6f47' }}>
                          Bs. {parseFloat(selectedPedido.total).toFixed(2)}
                        </strong>
                      </div>
                      <div className="mt-2 text-center">
                        <Badge bg="info">{selectedPedido.metodo_pago?.nombre || 'Método no especificado'}</Badge>
                      </div>
                    </Card.Body>
                  </Card>
                </Col>
              </Row>

              {/* Notas Administrativas */}
              {selectedPedido.notas_admin && (
                <Row className="mt-4">
                  <Col>
                    <Alert variant="info">
                      <FileText size={18} className="me-2" />
                      <strong>Notas:</strong> {selectedPedido.notas_admin}
                    </Alert>
                  </Col>
                </Row>
              )}
            </>
          )}
        </Modal.Body>
        <Modal.Footer>
          <Button 
            variant="secondary" 
            onClick={() => setShowDetalleModal(false)}
          >
            Cerrar
          </Button>
        </Modal.Footer>
      </Modal>
    </Container>
  );
};

export default MisPedidos;
