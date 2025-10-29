import { useState, useEffect, useCallback, useMemo } from 'react';
import useDebounce from '../hooks/useDebounce';
import { Container, Row, Col, Card, Button, Table, Form, InputGroup, Badge, Modal, ListGroup } from 'react-bootstrap';
import { admin, getProductos, getMetodosPagoCached as getMetodosPago } from '../services/api';
import { toast } from 'react-toastify';
import { useAuth } from '../context/AuthContext';

export default function VendedorPanel() {
  const { user } = useAuth();
  const [productos, setProductos] = useState([]);
  const [carrito, setCarrito] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showExtrasModal, setShowExtrasModal] = useState(false);
  const [productoConExtras, setProductoConExtras] = useState(null);
  const [extrasSeleccionados, setExtrasSeleccionados] = useState({});
  const [searchTerm, setSearchTerm] = useState('');
  const [categoriaFilter, setCategoriaFilter] = useState('');
  const [categorias, setCategorias] = useState([]);
  const [metodosPago, setMetodosPago] = useState([]);
  
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
    fetchMetodosPago();
  }, []);

  const fetchMetodosPago = async () => {
    try {
      const metodos = await getMetodosPago();
      const list = Array.isArray(metodos) ? metodos : (metodos.data || []);
      setMetodosPago(list);
      if (list.length > 0) {
        // prefer an 'efectivo' named method, otherwise first
        const efectivo = list.find(m => /efectivo|cash/i.test(m.nombre || m.name || ''));
        setMetodoPago(efectivo ? efectivo.id : list[0].id);
      }
    } catch (err) {
      console.warn('No se pudieron cargar métodos de pago, usando valores por defecto', err?.message || err);
    }
  };

  // Establecer monto por defecto cuando se abre el modal o cambia el método de pago
  const isMetodoTipo = (metodoOrId, tipo) => {
    if (!metodoOrId) return false;
    // find in metodosPago if numeric or string id
    const found = metodosPago.find(m => m.id === metodoOrId || String(m.id) === String(metodoOrId) || m.codigo === metodoOrId || String(m.nombre || '').toLowerCase() === String(metodoOrId).toLowerCase());
    const nombre = (found ? (found.nombre || found.name || '') : String(metodoOrId || '')).toLowerCase();
    if (tipo === 'qr') return /qr|qrcode|codigo/i.test(nombre) || /qr/i.test(found?.codigo || '');
    if (tipo === 'efectivo') return /efectivo|cash/i.test(nombre) || (found?.codigo === 'efectivo');
    return false;
  };

  useEffect(() => {
    if (showPagoModal && isMetodoTipo(metodoPago, 'qr')) {
      setMontoPagado(calcularTotal().toString());
    } else if (showPagoModal && isMetodoTipo(metodoPago, 'efectivo') && !montoPagado) {
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

  const agregarAlCarrito = useCallback((producto, cantidad = 1, extras = {}) => {
    // Comprobar stock principal
    const prodStock = producto?.inventario?.stock_actual ?? producto?.stock_actual ?? producto?.stock ?? null;
    if (prodStock !== null && Number(prodStock) <= 0) {
      toast.error(`${producto.nombre} sin stock`);
      return;
    }
    if (prodStock !== null && cantidad > Number(prodStock)) {
      toast.error(`Cantidad solicitada supera stock disponible (${prodStock})`);
      return;
    }

    // Single functional update: add/update main product and extras together to avoid multiple state writes
    setCarrito(prev => {
      // start from current prev state
      const next = [...prev];

      // MAIN PRODUCT
      const mainIdx = next.findIndex(item => item.id === producto.id && !item.es_extra);
      if (mainIdx >= 0) {
        next[mainIdx] = { ...next[mainIdx], cantidad: next[mainIdx].cantidad + cantidad };
      } else {
        next.push({
          id: producto.id,
          nombre: producto.nombre,
          precio: parseFloat(producto.precio_minorista) || parseFloat(producto.precio) || 0,
          cantidad: cantidad,
          producto,
          es_extra: false
        });
      }

      // EXTRAS
      if (producto.extras_disponibles && Object.keys(extras).length > 0) {
        Object.entries(extras).forEach(([index, qty]) => {
          const extra = producto.extras_disponibles[parseInt(index)];
          if (!extra || !(parseInt(qty) > 0)) return;
          const extraStock = extra?.stock_actual ?? extra?.stock ?? null;
          const unidades = parseInt(qty) || 0;
          if (extraStock !== null && Number(extraStock) <= 0) {
            // Skip adding this extra and inform the user
            toast.info(`${extra.nombre} sin stock, se omitirá`);
            return;
          }
          if (extraStock !== null && unidades > Number(extraStock)) {
            toast.error(`Cantidad de extra supera stock (${extraStock})`);
            return;
          }

          const extraId = `${producto.id}-extra-${index}`;
          const existingIdx = next.findIndex(i => i.id === extraId);
          const precioUnitario = parseFloat(extra.precio_unitario ?? extra.precio ?? 0) || 0;
          const cantidadMinima = parseInt(extra.cantidad_minima ?? extra.cantidad ?? 1) || 1;

          if (existingIdx >= 0) {
            next[existingIdx] = { ...next[existingIdx], cantidad: next[existingIdx].cantidad + unidades };
          } else {
            next.push({
              id: extraId,
              nombre: `${producto.nombre} - ${extra.nombre}`,
              precio: precioUnitario * cantidadMinima,
              cantidad: unidades,
              producto: extra,
              es_extra: true,
              producto_padre_id: producto.id
            });
          }
        });
      }

      return next;
    });

    toast.success(`${producto.nombre} agregado`, { autoClose: 900 });
  }, []);

  // Fast-add logic: when user clicks a product in the POS grid
  const handleProductoClick = (producto) => {
    // If product has extras, open compact modal to choose extras
    if (producto.extras_disponibles && producto.extras_disponibles.length > 0) {
      setProductoConExtras(producto);
      // initialize extrasSeleccionados with zeros
      const init = {};
      producto.extras_disponibles.forEach((_, idx) => { init[idx] = 0; });
      setExtrasSeleccionados(init);
      setShowExtrasModal(true);
      return;
    }
    // Otherwise add immediately
    agregarAlCarrito(producto, 1, {});
  };

  const handleToggleExtra = (index) => {
    setExtrasSeleccionados(prev => ({ ...prev, [index]: (prev[index] || 0) > 0 ? 0 : 1 }));
  };

  const handleChangeExtraQty = (index, newQty) => {
    setExtrasSeleccionados(prev => ({ ...prev, [index]: Math.max(0, parseInt(newQty) || 0) }));
  };

  const handleConfirmExtras = () => {
    if (!productoConExtras) return;
    // Add main product + extras
    agregarAlCarrito(productoConExtras, 1, extrasSeleccionados);
    setShowExtrasModal(false);
    setProductoConExtras(null);
    setExtrasSeleccionados({});
  };

  const eliminarDelCarrito = (productoId) => {
    // Find the item being removed
    const item = carrito.find(i => i.id === productoId);
    if (!item) return;

    if (!item.es_extra) {
      // If removing a parent product, also remove its extras (by producto_padre_id or id prefix)
      setCarrito(prev => prev.filter(i => {
        if (i.es_extra && (i.producto_padre_id === item.id || String(i.id).startsWith(`${item.id}-extra-`))) return false;
        return i.id !== productoId;
      }));
    } else {
      // Removing an extra only removes that extra
      setCarrito(prev => prev.filter(i => i.id !== productoId));
    }
  };

  const cambiarCantidad = (productoId, nuevaCantidad) => {
    if (nuevaCantidad < 1) {
      eliminarDelCarrito(productoId);
      return;
    }

    setCarrito(prev => prev.map(item =>
      item.id === productoId
        ? { ...item, cantidad: nuevaCantidad }
        : item
    ));

    // If we decreased a parent product to 0 (handled above), extras are already removed by eliminarDelCarrito.
    // If we decreased parent to some lower positive number, keep extras unchanged.
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

  // Remove extras that don't have a parent product in the cart
  const cleanupOrphanExtras = (cart) => {
    const parentIds = new Set(cart.filter(i => !i.es_extra).map(i => i.id));
    const cleaned = cart.filter(i => {
      if (!i.es_extra) return true;
      // If extra carries producto_padre_id and it's present, keep
      if (i.producto_padre_id && parentIds.has(i.producto_padre_id)) return true;
      // Also accept id prefix pattern
      for (const pid of parentIds) {
        if (String(i.id).startsWith(`${pid}-extra-`)) return true;
      }
      return false;
    });
    return { cleaned, removed: cart.length - cleaned.length };
  };

  const procesarVenta = async () => {
    if (carrito.length === 0) {
      toast.error('El carrito está vacío');
      return;
    }

    // Cleanup orphan extras before processing
    const { cleaned, removed } = cleanupOrphanExtras(carrito);
    if (removed > 0) {
      toast.info(`Se eliminaron ${removed} extra(s) huérfano(s) antes de procesar la venta`);
      setCarrito(cleaned);
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
      const resolveMetodoPagoId = () => {
        // If metodoPago is already a numeric id
        if (typeof metodoPago === 'number') return metodoPago;
        // If metodoPago is a code or name, try to match
        if (metodosPago.length > 0) {
          const byId = metodosPago.find(m => String(m.id) === String(metodoPago));
          if (byId) return byId.id;
          const byCodigo = metodosPago.find(m => String(m.codigo) === String(metodoPago) || String(m.codigo).toLowerCase() === String(metodoPago).toLowerCase());
          if (byCodigo) return byCodigo.id;
          const byName = metodosPago.find(m => String(m.nombre || '').toLowerCase() === String(metodoPago).toLowerCase());
          if (byName) return byName.id;
        }
        // If still unresolved, return null to cause validation error client-side
        return null;
      };

      const metodoPagoIdResolved = resolveMetodoPagoId();
      if (!metodoPagoIdResolved) {
        toast.error('No se pudo resolver el método de pago seleccionado. Espera a que se carguen los métodos o recarga la página.');
        setLoading(false);
        return;
      }

      const pedidoData = {
        cliente_nombre: clienteNombre || 'Cliente Mostrador',
        cliente_email: `venta_${Date.now()}@local.panificadoranancy.com`,
        cliente_telefono: '00000000',
        metodos_pago_id: metodoPagoIdResolved,
        tipo_entrega: 'recoger', // Venta en mostrador
        es_venta_mostrador: true,
        estado: 'entregado', // Marcar como entregado inmediatamente
        descuento_bs: descuento,
  motivo_descuento: motivoDescuento || '',
        detalles: (cleanupOrphanExtras(carrito).cleaned).map(item => ({
            // For extras, send the real product id stored in item.producto.id; for main items use item.id
            producto_id: item.es_extra ? (item.producto?.id || item.id) : item.id,
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
    // Resolve display name for metodoPago (could be 'efectivo', 'qr' or numeric id)
    let displayMetodo = '';
    // Normalize displayMetodo safely to avoid calling string methods on numbers or objects
    if (typeof metodoPago === 'number') {
      const found = metodosPago.find(m => m.id === metodoPago || String(m.id) === String(metodoPago));
      if (found) displayMetodo = String(found.nombre || found.name || found.id).toUpperCase();
      else displayMetodo = String(metodoPago);
    } else {
      // Coerce anything else to string first (covers null, undefined, objects)
      displayMetodo = String(metodoPago || '').toUpperCase();
    }
    
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
          ${displayMetodo === 'EFECTIVO' ? `
            <div class="right">Pagado: Bs.${montoPagado.toFixed(2)}</div>
            <div class="right">Cambio: Bs.${cambio.toFixed(2)}</div>
          ` : `
            <div class="right">Método: ${displayMetodo}</div>
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

  const debouncedSearch = useDebounce(searchTerm, 300);

  const productosFiltrados = productos.filter(p => {
    const matchSearch = p.nombre.toLowerCase().includes(debouncedSearch.toLowerCase());
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
                {useMemo(() => {
                  return productosFiltrados.map(producto => {
                    const prodStock = producto?.inventario?.stock_actual ?? producto?.stock_actual ?? producto?.stock ?? null;
                    const isOutOfStock = prodStock !== null && Number(prodStock) <= 0;

                    return (
                      <Col key={producto.id} xs={6} md={4} lg={3} className="mb-3">
                        <Card 
                          className={"h-100 shadow-sm product-card-hover"} 
                          style={{ cursor: isOutOfStock ? 'not-allowed' : 'pointer' }}
                          onClick={() => { if (!isOutOfStock) handleProductoClick(producto); }}
                        >
                          <Card.Img
                              variant="top"
                              src={
                                producto.imagenes?.[0]?.url_imagen_completa 
                                  || producto.imagenes?.[0]?.url_imagen 
                                  || 'https://via.placeholder.com/150?text=Sin+Imagen'
                              }
                              srcSet={producto.imagenes?.[0] ? `${producto.imagenes[0].url_imagen || producto.imagenes[0].url_imagen_completa || ''} 300w, ${producto.imagenes[0].url_imagen_completa || producto.imagenes[0].url_imagen || ''} 800w` : undefined}
                              sizes="(max-width: 768px) 45vw, 150px"
                              loading="lazy"
                              decoding="async"
                              style={{ height: '120px', objectFit: 'cover' }}
                              onError={(e) => {
                                e.target.onerror = null;
                                e.target.src = 'https://via.placeholder.com/150?text=Sin+Imagen';
                              }}
                            />
                          <Card.Body className="p-2">
                            <div className="d-flex justify-content-between align-items-start">
                              <Card.Title style={{ fontSize: '0.9rem' }} className="mb-1">
                                {producto.nombre}
                              </Card.Title>
                              {isOutOfStock && <Badge bg="danger">Sin stock</Badge>}
                            </div>
                            <h5 className="text-success mb-0">
                              Bs. {(parseFloat(String(producto.precio_minorista ?? producto.precio ?? 0)) || 0).toFixed(2)}
                            </h5>
                          </Card.Body>
                        </Card>
                      </Col>
                    );
                  });
                }, [productosFiltrados, handleProductoClick])}
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
                  {(() => {
                    // Build a parent -> children map
                    const parents = carrito.filter(i => !i.es_extra);
                    const extrasMap = {};
                    carrito.filter(i => i.es_extra).forEach(ex => {
                      const pid = ex.producto_padre_id || String(ex.id).split('-extra-')[0];
                      if (!extrasMap[pid]) extrasMap[pid] = [];
                      extrasMap[pid].push(ex);
                    });

                    return parents.map(parent => (
                      <div key={parent.id}>
                        <ListGroup.Item className="px-0">
                          <Row className="align-items-center">
                            <Col xs={6}>
                              <small className="d-block">{parent.nombre}</small>
                              <strong className="text-success">
                                Bs. {parent.precio.toFixed(2)}
                              </strong>
                            </Col>
                            <Col xs={4}>
                              <InputGroup size="sm">
                                <Button variant="outline-secondary" size="sm" onClick={() => cambiarCantidad(parent.id, parent.cantidad - 1)}>-</Button>
                                <Form.Control type="number" min="1" value={parent.cantidad} onChange={(e) => cambiarCantidad(parent.id, parseInt(e.target.value))} className="text-center" style={{ maxWidth: '50px' }} />
                                <Button variant="outline-secondary" size="sm" onClick={() => cambiarCantidad(parent.id, parent.cantidad + 1)}>+</Button>
                              </InputGroup>
                            </Col>
                            <Col xs={2} className="text-end">
                              <Button variant="outline-danger" size="sm" onClick={() => eliminarDelCarrito(parent.id)}>🗑️</Button>
                            </Col>
                          </Row>
                          <div className="text-end mt-2"><strong>Subtotal: Bs. {(parent.precio * parent.cantidad).toFixed(2)}</strong></div>
                        </ListGroup.Item>

                        {/* Render children extras, if any */}
                        {(extrasMap[parent.id] || []).map(child => (
                          <ListGroup.Item key={child.id} className="px-0" style={{ paddingLeft: '24px', backgroundColor: '#fafafa' }}>
                            <Row className="align-items-center">
                              <Col xs={6}>
                                <small className="d-block">↳ {child.nombre}</small>
                                <small className="text-muted">(extra)</small>
                              </Col>
                              <Col xs={4}>
                                <InputGroup size="sm">
                                  <Button variant="outline-secondary" size="sm" onClick={() => cambiarCantidad(child.id, child.cantidad - 1)}>-</Button>
                                  <Form.Control type="number" min="1" value={child.cantidad} onChange={(e) => cambiarCantidad(child.id, parseInt(e.target.value))} className="text-center" style={{ maxWidth: '50px' }} />
                                  <Button variant="outline-secondary" size="sm" onClick={() => cambiarCantidad(child.id, child.cantidad + 1)}>+</Button>
                                </InputGroup>
                              </Col>
                              <Col xs={2} className="text-end">
                                <Button variant="outline-danger" size="sm" onClick={() => eliminarDelCarrito(child.id)}>🗑️</Button>
                              </Col>
                            </Row>
                            <div className="text-end mt-2"><strong>Subtotal: Bs. {(child.precio * child.cantidad).toFixed(2)}</strong></div>
                          </ListGroup.Item>
                        ))}
                      </div>
                    ));
                  })()}
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

      {/* Compact Extras Modal for quick POS additions */}
      <Modal show={showExtrasModal} onHide={() => setShowExtrasModal(false)} centered>
        <Modal.Header closeButton>
          <Modal.Title style={{ fontSize: '1rem' }}>{productoConExtras ? productoConExtras.nombre : 'Extras'}</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {productoConExtras && productoConExtras.extras_disponibles && productoConExtras.extras_disponibles.length > 0 ? (
            <div>
              <p className="mb-2"><small>Selecciona extras rápidos (toca para activar):</small></p>
              {productoConExtras.extras_disponibles.map((extra, idx) => (
                <div key={idx} className="d-flex align-items-center justify-content-between py-2 border-bottom">
                  <div className="d-flex align-items-center gap-2">
                    <input type="checkbox" checked={(extrasSeleccionados[idx] || 0) > 0} onChange={() => handleToggleExtra(idx)} />
                    <div>
                      <div className="fw-semibold">{extra.nombre}</div>
                      <div className="small text-muted">{extra.descripcion || ''}</div>
                    </div>
                  </div>
                  <div className="d-flex align-items-center gap-2">
                    <button className="btn btn-sm btn-outline-secondary" onClick={() => handleChangeExtraQty(idx, (extrasSeleccionados[idx] || 0) - 1)}>-</button>
                    <div style={{ minWidth: '28px', textAlign: 'center' }}>{extrasSeleccionados[idx] || 0}</div>
                    <button className="btn btn-sm btn-outline-secondary" onClick={() => handleChangeExtraQty(idx, (extrasSeleccionados[idx] || 0) + 1)}>+</button>
                    <div className="ms-2 fw-bold text-success">Bs {(parseFloat(extra.precio_unitario ?? extra.precio ?? 0) || 0).toFixed(2)}</div>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div>No hay extras disponibles para este producto.</div>
          )}
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setShowExtrasModal(false)}>Cancelar</Button>
          <Button variant="primary" onClick={handleConfirmExtras}>Agregar</Button>
        </Modal.Footer>
      </Modal>

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
                    onChange={(e) => {
                      // try to set numeric id when option value looks numeric
                      const val = e.target.value;
                      if (/^\d+$/.test(val)) setMetodoPago(Number(val));
                      else setMetodoPago(val);
                    }}
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
