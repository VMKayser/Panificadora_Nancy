import { useState, useEffect } from 'react';
import { Container, Row, Col, Card, Button, Table, Form, InputGroup, Badge, Modal, ListGroup } from 'react-bootstrap';
import { admin, getProductos } from '../services/api';
import { toast } from 'react-toastify';
import { useAuth } from '../context/AuthContext';

export default function VendedorPanel() {
  const { user } = useAuth();
  const [productos, setProductos] = useState([]);
  const [carrito, setCarrito] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [categoriaFilter, setCategoriaFilter] = useState('');
  const [categorias, setCategorias] = useState([]);
  
  // Modal de pago
  const [showPagoModal, setShowPagoModal] = useState(false);
  const [montoPagado, setMontoPagado] = useState('');
  const [metodoPago, setMetodoPago] = useState('efectivo');
  const [clienteNombre, setClienteNombre] = useState('');
  const [descuentoBs, setDescuentoBs] = useState(0);
  const [motivoDescuento, setMotivoDescuento] = useState('');
  
  // Estadísticas del día
  const [statsHoy, setStatsHoy] = useState({
    ventas: 0,
    total: 0,
    productos_vendidos: 0
  });

  useEffect(() => {
    cargarProductos();
    cargarEstadisticas();
  }, []);

  // Establecer monto por defecto cuando se abre el modal o cambia el método de pago
  useEffect(() => {
    if (showPagoModal && metodoPago === 'qr') {
      // Para QR, establecer el monto exacto
      setMontoPagado(calcularTotal().toString());
    } else if (showPagoModal && metodoPago === 'efectivo' && !montoPagado) {
      // Para efectivo, establecer el total como sugerencia
      setMontoPagado(calcularTotal().toString());
    }
  }, [showPagoModal, metodoPago]);

  const cargarProductos = async () => {
    try {
      setLoading(true);
      const [productosData, categoriasData] = await Promise.all([
        getProductos({ activo: 1 }),
        admin.getCategorias()
      ]);
      
      setProductos(Array.isArray(productosData) ? productosData : productosData.data || []);
      setCategorias(Array.isArray(categoriasData) ? categoriasData : categoriasData.data || []);
    } catch (error) {
      console.error('Error cargando productos:', error);
      toast.error('Error al cargar productos');
    } finally {
      setLoading(false);
    }
  };

  const cargarEstadisticas = async () => {
    try {
      const stats = await admin.getPedidosStats({
        fecha_desde: new Date().toISOString().split('T')[0],
        fecha_hasta: new Date().toISOString().split('T')[0]
      });
      setStatsHoy(stats);
    } catch (error) {
      console.error('Error cargando estadísticas:', error);
    }
  };

  const agregarAlCarrito = (producto) => {
    const itemExistente = carrito.find(item => item.id === producto.id);
    
    if (itemExistente) {
      setCarrito(carrito.map(item =>
        item.id === producto.id
          ? { ...item, cantidad: item.cantidad + 1 }
          : item
      ));
    } else {
      setCarrito([...carrito, {
        id: producto.id,
        nombre: producto.nombre,
        precio: parseFloat(producto.precio_minorista),
        cantidad: 1,
        producto
      }]);
    }
    toast.success(`${producto.nombre} agregado`, { autoClose: 1000 });
  };

  const eliminarDelCarrito = (productoId) => {
    setCarrito(carrito.filter(item => item.id !== productoId));
  };

  const cambiarCantidad = (productoId, nuevaCantidad) => {
    if (nuevaCantidad < 1) {
      eliminarDelCarrito(productoId);
      return;
    }
    
    setCarrito(carrito.map(item =>
      item.id === productoId
        ? { ...item, cantidad: nuevaCantidad }
        : item
    ));
  };

  const calcularTotal = () => {
    const subtotal = carrito.reduce((sum, item) => sum + (item.precio * item.cantidad), 0);
    const descuento = parseFloat(descuentoBs) || 0;
    return Math.max(0, subtotal - descuento); // No permitir totales negativos
  };

  const calcularSubtotal = () => {
    return carrito.reduce((sum, item) => sum + (item.precio * item.cantidad), 0);
  };

  const calcularCambio = () => {
    const pagado = parseFloat(montoPagado) || 0;
    const total = calcularTotal();
    return pagado - total;
  };

  const procesarVenta = async () => {
    if (carrito.length === 0) {
      toast.error('El carrito está vacío');
      return;
    }

    const total = calcularTotal();
    const pagado = parseFloat(montoPagado) || 0;

    // Solo validar monto para efectivo
    if (metodoPago === 'efectivo' && pagado < total) {
      toast.error('El monto pagado es insuficiente');
      return;
    }

    // Para QR, el monto pagado es exactamente el total
    const montoPagadoFinal = metodoPago === 'efectivo' ? pagado : total;

    // Validar descuento
    const descuento = parseFloat(descuentoBs) || 0;
    if (descuento > 0 && !motivoDescuento.trim()) {
      toast.error('Debe ingresar un motivo para el descuento');
      return;
    }

    try {
      setLoading(true);

      const subtotal = calcularSubtotal();

      // Crear pedido como venta directa
      const pedidoData = {
        cliente_nombre: clienteNombre || 'Cliente Mostrador',
        cliente_email: `venta_${Date.now()}@local.panificadoranancy.com`,
        cliente_telefono: '00000000',
        metodo_pago_id: metodoPago === 'efectivo' ? 1 : 3, // 1: Efectivo, 3: QR
        tipo_entrega: 'recoger', // Venta en mostrador
        es_venta_mostrador: true,
        estado: 'entregado', // Marcar como entregado inmediatamente
        descuento_bs: descuento,
        motivo_descuento: motivoDescuento || null,
        detalles: carrito.map(item => ({
          producto_id: item.id,
          cantidad: item.cantidad,
          precio_unitario: item.precio,
          subtotal: item.precio * item.cantidad
        })),
        subtotal: subtotal,
        total: total
      };

      // Registrar venta (usar endpoint de pedidos)
      console.log('📤 Enviando pedido:', pedidoData);
      const response = await admin.createPedido(pedidoData);
      console.log('✅ Respuesta del servidor:', response);

      toast.success('¡Venta registrada exitosamente!', {
        autoClose: 2000
      });

      // Limpiar
      setCarrito([]);
      setClienteNombre('');
      setMontoPagado('');
      setDescuentoBs(0);
      setMotivoDescuento('');
      setShowPagoModal(false);
      cargarEstadisticas();

      // Opcional: imprimir ticket
      imprimirTicket(pedidoData, montoPagadoFinal);

    } catch (error) {
      console.error('Error procesando venta:', error);
      console.error('Error completo:', error.response?.data || error.message);
      toast.error(`Error: ${error.response?.data?.message || 'Error al registrar la venta'}`);
    } finally {
      setLoading(false);
    }
  };

  const imprimirTicket = (pedido, montoPagado) => {
    const ventana = window.open('', '_blank', 'width=300,height=600');
    const subtotal = calcularSubtotal();
    const total = calcularTotal();
    const descuento = parseFloat(descuentoBs) || 0;
    const cambio = montoPagado - total;
    
    ventana.document.write(`
      <html>
        <head>
          <title>Ticket de Venta</title>
          <style>
            body { font-family: 'Courier New', monospace; width: 280px; padding: 10px; }
            .center { text-align: center; }
            .right { text-align: right; }
            .bold { font-weight: bold; }
            hr { border: 1px dashed #000; }
            table { width: 100%; }
          </style>
        </head>
        <body>
          <div class="center bold">🥖 PANIFICADORA NANCY</div>
          <div class="center">La Paz, Bolivia</div>
          <hr>
          <div>Fecha: ${new Date().toLocaleString('es-BO')}</div>
          <div>Vendedor: ${user?.name}</div>
          <div>Cliente: ${pedido.cliente_nombre}</div>
          <hr>
          <table>
            ${carrito.map(item => `
              <tr>
                <td>${item.nombre}</td>
                <td class="right">${item.cantidad} x Bs.${item.precio.toFixed(2)}</td>
              </tr>
              <tr>
                <td colspan="2" class="right">Bs.${(item.cantidad * item.precio).toFixed(2)}</td>
              </tr>
            `).join('')}
          </table>
          <hr>
          <div class="right">SUBTOTAL: Bs.${subtotal.toFixed(2)}</div>
          ${descuento > 0 ? `
            <div class="right">DESCUENTO: -Bs.${descuento.toFixed(2)}</div>
            ${motivoDescuento ? `<div class="right"><small>${motivoDescuento}</small></div>` : ''}
          ` : ''}
          <div class="right bold">TOTAL: Bs.${total.toFixed(2)}</div>
          ${metodoPago === 'efectivo' ? `
            <div class="right">Pagado: Bs.${montoPagado.toFixed(2)}</div>
            <div class="right">Cambio: Bs.${cambio.toFixed(2)}</div>
          ` : `
            <div class="right">Método: ${metodoPago.toUpperCase()}</div>
          `}
          <hr>
          <div class="center">¡Gracias por su compra!</div>
          <div class="center">Vuelva pronto</div>
        </body>
      </html>
    `);
    
    setTimeout(() => {
      ventana.print();
      ventana.close();
    }, 250);
  };

  const productosFiltrados = productos.filter(p => {
    const matchSearch = p.nombre.toLowerCase().includes(searchTerm.toLowerCase());
    const matchCategoria = !categoriaFilter || p.categorias_id == categoriaFilter;
    return matchSearch && matchCategoria;
  });

  return (
    <Container fluid className="py-4">
      <Row className="mb-4">
        <Col>
          <h2 style={{ color: '#534031', fontWeight: 'bold' }}>
            🛒 Punto de Venta
          </h2>
          <p className="text-muted">Registro rápido de ventas en mostrador</p>
        </Col>
        <Col xs="auto">
          <Card className="shadow-sm">
            <Card.Body className="py-2 px-3">
              <small className="text-muted">Ventas Hoy</small>
              <h4 className="mb-0" style={{ color: '#8b6f47' }}>
                Bs. {statsHoy.total_ventas?.toFixed(2) || '0.00'}
              </h4>
            </Card.Body>
          </Card>
        </Col>
      </Row>

      <Row>
        {/* PRODUCTOS */}
        <Col md={8}>
          <Card className="shadow-sm mb-3">
            <Card.Body>
              <Row className="mb-3">
                <Col md={6}>
                  <InputGroup>
                    <InputGroup.Text>🔍</InputGroup.Text>
                    <Form.Control
                      placeholder="Buscar producto..."
                      value={searchTerm}
                      onChange={(e) => setSearchTerm(e.target.value)}
                    />
                  </InputGroup>
                </Col>
                <Col md={6}>
                  <Form.Select
                    value={categoriaFilter}
                    onChange={(e) => setCategoriaFilter(e.target.value)}
                  >
                    <option value="">Todas las categorías</option>
                    {categorias.map(cat => (
                      <option key={cat.id} value={cat.id}>{cat.nombre}</option>
                    ))}
                  </Form.Select>
                </Col>
              </Row>

              <Row>
                {productosFiltrados.map(producto => (
                  <Col key={producto.id} xs={6} md={4} lg={3} className="mb-3">
                    <Card 
                      className="h-100 shadow-sm" 
                      style={{ cursor: 'pointer', transition: 'transform 0.2s' }}
                      onClick={() => agregarAlCarrito(producto)}
                      onMouseOver={(e) => e.currentTarget.style.transform = 'scale(1.05)'}
                      onMouseOut={(e) => e.currentTarget.style.transform = 'scale(1)'}
                    >
                      <Card.Img
                        variant="top"
                        src={
                          producto.imagenes?.[0]?.url_imagen_completa 
                            || producto.imagenes?.[0]?.url_imagen 
                            || 'https://via.placeholder.com/150?text=Sin+Imagen'
                        }
                        style={{ height: '120px', objectFit: 'cover' }}
                        onError={(e) => {
                          e.target.onerror = null;
                          e.target.src = 'https://via.placeholder.com/150?text=Sin+Imagen';
                        }}
                      />
                      <Card.Body className="p-2">
                        <Card.Title style={{ fontSize: '0.9rem' }} className="mb-1">
                          {producto.nombre}
                        </Card.Title>
                        <h5 className="text-success mb-0">
                          Bs. {parseFloat(producto.precio_minorista).toFixed(2)}
                        </h5>
                      </Card.Body>
                    </Card>
                  </Col>
                ))}
              </Row>
            </Card.Body>
          </Card>
        </Col>

        {/* CARRITO Y PAGO */}
        <Col md={4}>
          <Card className="shadow-sm sticky-top" style={{ top: '20px' }}>
            <Card.Header style={{ backgroundColor: '#8b6f47', color: 'white' }}>
              <h5 className="mb-0">🛒 Carrito de Venta</h5>
            </Card.Header>
            <Card.Body style={{ maxHeight: '400px', overflowY: 'auto' }}>
              {carrito.length === 0 ? (
                <div className="text-center text-muted py-5">
                  <h1>🛒</h1>
                  <p>Carrito vacío</p>
                </div>
              ) : (
                <ListGroup variant="flush">
                  {carrito.map(item => (
                    <ListGroup.Item key={item.id} className="px-0">
                      <Row className="align-items-center">
                        <Col xs={6}>
                          <small className="d-block">{item.nombre}</small>
                          <strong className="text-success">
                            Bs. {item.precio.toFixed(2)}
                          </strong>
                        </Col>
                        <Col xs={4}>
                          <InputGroup size="sm">
                            <Button
                              variant="outline-secondary"
                              size="sm"
                              onClick={() => cambiarCantidad(item.id, item.cantidad - 1)}
                            >
                              -
                            </Button>
                            <Form.Control
                              type="number"
                              min="1"
                              value={item.cantidad}
                              onChange={(e) => cambiarCantidad(item.id, parseInt(e.target.value))}
                              className="text-center"
                              style={{ maxWidth: '50px' }}
                            />
                            <Button
                              variant="outline-secondary"
                              size="sm"
                              onClick={() => cambiarCantidad(item.id, item.cantidad + 1)}
                            >
                              +
                            </Button>
                          </InputGroup>
                        </Col>
                        <Col xs={2} className="text-end">
                          <Button
                            variant="outline-danger"
                            size="sm"
                            onClick={() => eliminarDelCarrito(item.id)}
                          >
                            🗑️
                          </Button>
                        </Col>
                      </Row>
                      <div className="text-end mt-2">
                        <strong>Subtotal: Bs. {(item.precio * item.cantidad).toFixed(2)}</strong>
                      </div>
                    </ListGroup.Item>
                  ))}
                </ListGroup>
              )}
            </Card.Body>
            
            {carrito.length > 0 && (
              <>
                <Card.Body className="border-top">
                  <Row className="mb-2">
                    <Col><strong>Subtotal:</strong></Col>
                    <Col className="text-end">
                      Bs. {calcularSubtotal().toFixed(2)}
                    </Col>
                  </Row>
                  
                  {descuentoBs > 0 && (
                    <Row className="mb-2 text-danger">
                      <Col><strong>Descuento:</strong></Col>
                      <Col className="text-end">
                        -Bs. {parseFloat(descuentoBs).toFixed(2)}
                      </Col>
                    </Row>
                  )}
                  
                  <Row className="mb-2 border-top pt-2">
                    <Col><strong>Total:</strong></Col>
                    <Col className="text-end">
                      <h4 className="text-success mb-0">
                        Bs. {calcularTotal().toFixed(2)}
                      </h4>
                    </Col>
                  </Row>
                </Card.Body>
                
                <Card.Footer>
                  <Button
                    variant="success"
                    size="lg"
                    className="w-100"
                    onClick={() => setShowPagoModal(true)}
                  >
                    💰 Procesar Pago
                  </Button>
                  <Button
                    variant="outline-danger"
                    size="sm"
                    className="w-100 mt-2"
                    onClick={() => setCarrito([])}
                  >
                    Limpiar Carrito
                  </Button>
                </Card.Footer>
              </>
            )}
          </Card>
        </Col>
      </Row>

      {/* MODAL DE PAGO */}
      <Modal show={showPagoModal} onHide={() => setShowPagoModal(false)} centered>
        <Modal.Header closeButton style={{ backgroundColor: '#8b6f47', color: 'white' }}>
          <Modal.Title>💰 Procesar Pago</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <Form>
            <Form.Group className="mb-3">
              <Form.Label>Nombre del Cliente (Opcional)</Form.Label>
              <Form.Control
                type="text"
                placeholder="Ej: Juan Pérez"
                value={clienteNombre}
                onChange={(e) => setClienteNombre(e.target.value)}
              />
            </Form.Group>

            {/* DESCUENTO EN BOLIVIANOS */}
            <Form.Group className="mb-3">
              <Form.Label>Descuento (Opcional)</Form.Label>
              <InputGroup>
                <InputGroup.Text>Bs.</InputGroup.Text>
                <Form.Control
                  type="number"
                  step="0.50"
                  min="0"
                  max={calcularSubtotal()}
                  value={descuentoBs}
                  onChange={(e) => setDescuentoBs(e.target.value)}
                  placeholder="0.00"
                />
              </InputGroup>
              {descuentoBs > 0 && (
                <>
                  <Form.Control
                    className="mt-2"
                    type="text"
                    placeholder="Motivo del descuento (requerido)"
                    value={motivoDescuento}
                    onChange={(e) => setMotivoDescuento(e.target.value)}
                    maxLength="100"
                  />
                  <Form.Text className="text-muted">
                    Ejemplo: Cliente frecuente, promoción, etc.
                  </Form.Text>
                </>
              )}
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>Método de Pago</Form.Label>
              <Form.Select
                value={metodoPago}
                onChange={(e) => setMetodoPago(e.target.value)}
              >
                <option value="efectivo">💵 Efectivo</option>
                <option value="qr">📱 QR</option>
              </Form.Select>
            </Form.Group>

            {/* Monto para EFECTIVO */}
            {metodoPago === 'efectivo' && (
              <Form.Group className="mb-3">
                <Form.Label>Monto Pagado</Form.Label>
                <InputGroup>
                  <InputGroup.Text>Bs.</InputGroup.Text>
                  <Form.Control
                    type="number"
                    step="0.01"
                    min={calcularTotal()}
                    value={montoPagado}
                    onChange={(e) => setMontoPagado(e.target.value)}
                    placeholder={calcularTotal().toFixed(2)}
                    autoFocus
                  />
                </InputGroup>
                {montoPagado && (
                  <Form.Text className={calcularCambio() >= 0 ? 'text-success' : 'text-danger'}>
                    {calcularCambio() >= 0 
                      ? `Cambio: Bs. ${calcularCambio().toFixed(2)}`
                      : `Falta: Bs. ${Math.abs(calcularCambio()).toFixed(2)}`
                    }
                  </Form.Text>
                )}
              </Form.Group>
            )}

            {/* Monto para QR (solo informativo) */}
            {metodoPago === 'qr' && (
              <Form.Group className="mb-3">
                <Form.Label>Monto a Cobrar</Form.Label>
                <InputGroup>
                  <InputGroup.Text>Bs.</InputGroup.Text>
                  <Form.Control
                    type="text"
                    value={calcularTotal().toFixed(2)}
                    disabled
                    className="bg-light"
                  />
                </InputGroup>
                <Form.Text className="text-muted">
                  📱 Escanear código QR para pagar
                </Form.Text>
              </Form.Group>
            )}

            <Card className="bg-light">
              <Card.Body>
                {descuentoBs > 0 && (
                  <>
                    <div className="d-flex justify-content-between">
                      <span>Subtotal:</span>
                      <span>Bs. {calcularSubtotal().toFixed(2)}</span>
                    </div>
                    <div className="d-flex justify-content-between text-danger">
                      <span>Descuento:</span>
                      <span>-Bs. {parseFloat(descuentoBs).toFixed(2)}</span>
                    </div>
                    <hr className="my-2" />
                  </>
                )}
                <h5 className="text-center mb-0">
                  TOTAL: <span className="text-success">Bs. {calcularTotal().toFixed(2)}</span>
                </h5>
              </Card.Body>
            </Card>
          </Form>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="outline-secondary" onClick={() => setShowPagoModal(false)}>
            Cancelar
          </Button>
          <Button
            variant="success"
            onClick={procesarVenta}
            disabled={loading || (metodoPago === 'efectivo' && calcularCambio() < 0)}
          >
            {loading ? 'Procesando...' : '✅ Confirmar Venta'}
          </Button>
        </Modal.Footer>
      </Modal>
    </Container>
  );
}
