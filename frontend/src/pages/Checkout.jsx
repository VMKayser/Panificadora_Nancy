import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useSEO } from '../hooks/useSEO';
import { Container, Row, Col, Form, Button, Card, ListGroup, Image } from 'react-bootstrap';
import { useCart } from '../context/CartContext';
import { useAuth } from '../context/AuthContext';
import { crearPedido, getMetodosPago, assetBase } from '../services/api';
import { toast } from 'react-toastify';

const Checkout = () => {
  const navigate = useNavigate();
  const { cart, getTotal, getTotalItems, clearCart, updateQuantity, removeFromCart } = useCart();
  const { user } = useAuth();
  
  // Estados del formulario
  const [formData, setFormData] = useState({
    cliente_nombre: '',
    cliente_apellido: '',
    cliente_email: '',
    cliente_telefono: '',
  tipo_entrega: 'recoger',
    direccion_entrega: '',
    indicaciones_especiales: '',
    metodos_pago_id: null,
    direccion_lat: null,
    direccion_lng: null,
  });
  const [metodosPago, setMetodosPago] = useState([]);
  const [loading, setLoading] = useState(false);
  const [descuento, setDescuento] = useState(0);
  // Flag: true if any cart item does NOT allow national shipping
  const [hasNonShippableNationalItem, setHasNonShippableNationalItem] = useState(false);
  const [direccionValida, setDireccionValida] = useState(false);
  const [fechaEntrega, setFechaEntrega] = useState('');
  const [horaEntrega, setHoraEntrega] = useState('');
  const [minFecha, setMinFecha] = useState('');

  // SEO: no indexar la página de checkout
  useSEO({
    title: 'Checkout - Panificadora Nancy',
    description: 'Completa tu pedido de pan y repostería. Pago seguro y envío rápido.',
    noindex: true
  });

  // Cargar métodos de pago
  useEffect(() => {
    const fetchMetodosPago = async () => {
      try {
        const metodos = await getMetodosPago();
      // En checkout sólo aceptamos métodos QR. Aceptamos códigos 'qr', 'qr_simple' o similares
        const qrMetodos = metodos.filter(m => m.esta_activo && /qr/i.test(m.codigo || ''));
        if (qrMetodos.length > 0) {
          setMetodosPago(qrMetodos);
          setFormData(prev => ({ ...prev, metodos_pago_id: qrMetodos[0].id }));
        } else {
          // No hay QR en backend: bloquear checkout y avisar al usuario
          setMetodosPago([]);
          setFormData(prev => ({ ...prev, metodos_pago_id: null }));
          toast.warn('No hay métodos QR activos disponibles en este momento. Por favor contacta a la tienda.');
        }
      } catch (error) {
        console.error('Error al cargar métodos de pago:', error);
        toast.error('Error al cargar métodos de pago');
      }
    };
    fetchMetodosPago();
  }, []);

  // Prefill form when user is logged in
  useEffect(() => {
    if (!user) return;
    setFormData(prev => ({
      ...prev,
      cliente_nombre: prev.cliente_nombre || user.name || '',
      cliente_apellido: prev.cliente_apellido || user.last_name || user.apellido || '',
      cliente_email: prev.cliente_email || user.email || '',
      cliente_telefono: prev.cliente_telefono || user.telefono || user.phone || '',
    }));
  }, [user]);

  // Calcular fecha mínima de entrega basada en productos (en minutos)
  useEffect(() => {
    if (cart.length === 0) return;

    // Encontrar el mayor tiempo de anticipación en minutos
    let minutosMaximos = 0;
    cart.forEach(item => {
      if (item.requiere_tiempo_anticipacion) {
        let minutos = 0;
        if (item.unidad_tiempo === 'dias') {
          minutos = (Number(item.tiempo_anticipacion) || 0) * 24 * 60;
        } else if (item.unidad_tiempo === 'horas') {
          minutos = (Number(item.tiempo_anticipacion) || 0) * 60;
        } else {
          // Por defecto tratamos la unidad como minutos
          minutos = (Number(item.tiempo_anticipacion) || 0);
        }
        minutosMaximos = Math.max(minutosMaximos, minutos);
      }
    });

    // Añadimos un buffer mínimo (30 minutos) además del tiempo de anticipación
    const bufferMinutos = 30;
    const totalMinutos = (minutosMaximos || 0) + bufferMinutos;

    const now = new Date();
    const fechaMinima = new Date(now.getTime() + totalMinutos * 60 * 1000);

    const pad = (n) => String(n).padStart(2, '0');
    const fechaFormateada = fechaMinima.toISOString().split('T')[0];
    const horaFormateada = `${pad(fechaMinima.getHours())}:${pad(fechaMinima.getMinutes())}`;

    // Guardamos la fecha mínima para usar en el atributo min del input date
    setMinFecha(fechaFormateada);

    // Solo prefill si el usuario/admin no ha seleccionado ya una fecha/hora
    if (!fechaEntrega) setFechaEntrega(fechaFormateada);
    if (!horaEntrega) setHoraEntrega(horaFormateada);
  }, [cart]);

  // Helper: quick set for fecha/hora (minutes from now)
  const setQuick = (minutes) => {
    const dt = new Date(Date.now() + minutes * 60 * 1000);
    const pad = (n) => String(n).padStart(2, '0');
    setFechaEntrega(dt.toISOString().split('T')[0]);
    setHoraEntrega(`${pad(dt.getHours())}:${pad(dt.getMinutes())}`);
  };

  // Detectar si hay productos que NO permiten envío nacional
  useEffect(() => {
    if (!cart || cart.length === 0) {
      setHasNonShippableNationalItem(false);
      return;
    }
    const hasNon = cart.some(item => item.permite_envio_nacional === false);
    setHasNonShippableNationalItem(hasNon);
    // Si el usuario había elegido Envío Nacional pero ahora hay productos no-enviables, forzamos recoger
    if (hasNon && formData.tipo_entrega === 'envio_nacional') {
      setFormData(prev => ({ ...prev, tipo_entrega: 'recoger' }));
      toast.info('Delivery deshabilitado porque hay productos que no permiten envío nacional. Elige Retiro o elimina los productos listados.');
    }
  }, [cart]);

  // Validar dirección básica: por ahora comprobamos que contenga la palabra 'Quillacollo'
  const validarDireccionBasica = (direccion) => {
    if (!direccion) return false;
    return /quillacollo/i.test(direccion);
  }

  // Bounding box client-side quick check for Quillacollo (approximate). Adjust values in backend if needed.
  const isWithinQuillacollo = (lat, lng) => {
    if (lat === null || lng === null) return false;
    // Approximate bounding box for Quillacollo, Cochabamba (minLat, maxLat, minLng, maxLng)
    const minLat = -17.45;
    const maxLat = -17.22;
    const minLng = -66.35;
    const maxLng = -66.10;
    return lat >= minLat && lat <= maxLat && lng >= minLng && lng <= maxLng;
  }

  // Helpers numéricos seguros
  const toNumber = (v) => {
    if (v === null || v === undefined) return 0;
    if (typeof v === 'number') return isNaN(v) ? 0 : v;
    const n = Number(v);
    return isNaN(n) ? 0 : n;
  };

  const formatPrice = (v) => {
    return (toNumber(v)).toFixed(2);
  };

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const handleAplicarCupon = () => {
    // Coupons temporarily disabled in checkout UI.
    toast.info('Códigos promocionales deshabilitados por el momento');
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    // Validaciones
    if (cart.length === 0) {
      toast.error('El carrito está vacío');
      return;
    }

    if (!formData.cliente_nombre || !formData.cliente_apellido) {
      toast.error('Por favor, completa todos los campos obligatorios');
      return;
    }

    if (!formData.metodos_pago_id) {
      toast.error('Selecciona un método de pago');
      return;
    }

    if ((formData.tipo_entrega === 'delivery' || formData.tipo_entrega === 'envio_nacional') && !formData.direccion_entrega) {
      toast.error('Ingresa la dirección de entrega');
      return;
    }

    if (formData.tipo_entrega === 'delivery') {
      // Validación básica local: dirección debe pertenecer a Quillacollo (mejorar con geocodificación en backend)
      const ok = validarDireccionBasica(formData.direccion_entrega) || direccionValida || isWithinQuillacollo(formData.direccion_lat, formData.direccion_lng);
      if (!ok) {
        toast.error('La dirección debe estar dentro de Quillacollo, Cochabamba. Usa "Validar dirección" o elige Retiro.');
        return;
      }
    }

    setLoading(true);

    try {
      // Última validación de stock en cliente: evitar crear pedido con items sin stock
      const outOfStock = [];
      cart.forEach(item => {
        const available = item?.producto?.inventario?.stock_actual ?? item?.producto?.stock_actual ?? item?.inventario?.stock_actual ?? item?.stock_actual ?? item?.stock ?? null;
        if (available !== null) {
          if (Number(available) <= 0) {
            outOfStock.push({ nombre: item.nombre || item.producto?.nombre || 'Producto', disponible: 0, solicitado: item.cantidad });
          } else if (Number(item.cantidad) > Number(available)) {
            outOfStock.push({ nombre: item.nombre || item.producto?.nombre || 'Producto', disponible: available, solicitado: item.cantidad });
          }
        }
      });

      if (outOfStock.length > 0) {
        const list = outOfStock.map(p => `${p.nombre} — disponible: ${p.disponible}, solicitado: ${p.solicitado}`).join('\n');
        toast.error('No se puede crear el pedido por problemas de stock:\n' + list, { autoClose: 8000 });
        setLoading(false);
        return;
      }

      // Preparar datos del pedido
      const pedidoData = {
        ...formData,
        productos: cart.map(item => ({
          id: item.id,
          cantidad: item.cantidad,
        })),
          // Enviar un único campo datetime combinando fecha+hora (si se proporcionan)
          entrega_datetime: (fechaEntrega && horaEntrega) ? `${fechaEntrega} ${horaEntrega}:00` : null,
        subtotal: getTotal(),
        descuento: descuento,
        total: getTotal() - descuento,
      };

      const response = await crearPedido(pedidoData);
      
      toast.success('¡Pedido creado exitosamente!');
      clearCart();
      
      // Redirigir a página de confirmación
      setTimeout(() => {
        navigate('/pedido-confirmado', { state: { pedido: response.pedido } });
      }, 1500);

    } catch (error) {
      console.error('Error al crear pedido:', error);
      const status = error.response?.status;
      if (status === 422 && error.response?.data?.blocking_products) {
        const list = error.response.data.blocking_products.map(p => `${p.nombre} (x${p.cantidad})`).join('\n');
        toast.error('No es posible realizar delivery por los siguientes productos:\n' + list, { autoClose: 8000 });
      } else {
        toast.error(error.response?.data?.message || 'Error al procesar el pedido');
      }
    } finally {
      setLoading(false);
    }
  };

  if (cart.length === 0) {
    return (
      <Container className="text-center py-5">
        <h2>🛒 Tu carrito está vacío</h2>
        <p className="text-muted">Agrega productos para realizar un pedido</p>
        <Button 
          onClick={() => navigate('/')}
          style={{ backgroundColor: '#8b6f47', borderColor: '#8b6f47' }}
        >
          Ver Productos
        </Button>
      </Container>
    );
  }

  return (
    <Container className="py-4" style={{ maxWidth: '1200px' }}>
      <Form onSubmit={handleSubmit}>
        <Row>
          {/* Columna Izquierda - Formulario */}
          <Col lg={7}>
            {/* Contacto */}
            <Card className="mb-4 shadow-sm">
              <Card.Body>
                <h5 className="mb-3" style={{ fontWeight: 'bold' }}>Contacto</h5>
                <Row>
                  <Col md={6}>
                    <Form.Group className="mb-3">
                      <Form.Label>Email</Form.Label>
                      <Form.Control
                        type="email"
                        name="cliente_email"
                        value={formData.cliente_email}
                        onChange={handleInputChange}
                        placeholder="tucorreo@gmail.com"
                        required
                      />
                    </Form.Group>
                  </Col>
                  <Col md={6}>
                    <Form.Group className="mb-3">
                      <Form.Label>Tu teléfono</Form.Label>
                      <Form.Control
                        type="tel"
                        name="cliente_telefono"
                        value={formData.cliente_telefono}
                        onChange={handleInputChange}
                        placeholder="+591 --------"
                        required
                      />
                    </Form.Group>
                  </Col>
                </Row>
                <Row>
                  <Col md={6}>
                    <Form.Group className="mb-3">
                      <Form.Label>Nombre</Form.Label>
                      <Form.Control
                        type="text"
                        name="cliente_nombre"
                        value={formData.cliente_nombre}
                        onChange={handleInputChange}
                        placeholder="Nombre"
                        required
                      />
                    </Form.Group>
                  </Col>
                  <Col md={6}>
                    <Form.Group className="mb-3">
                      <Form.Label>Apellido</Form.Label>
                      <Form.Control
                        type="text"
                        name="cliente_apellido"
                        value={formData.cliente_apellido}
                        onChange={handleInputChange}
                        placeholder="Apellido"
                        required
                      />
                    </Form.Group>
                  </Col>
                </Row>
              </Card.Body>
            </Card>

            {/* Entrega */}
            <Card className="mb-4 shadow-sm">
              <Card.Body>
                <h5 className="mb-3" style={{ fontWeight: 'bold' }}>
                  Entrega <span style={{ fontSize: '14px', fontWeight: 'normal' }}>Hoy, 10:30 📅</span>
                </h5>
                
                <Form.Group className="mb-3">
                  <Form.Label>¿Cómo quieres tu pedido?</Form.Label>
                  <div className="d-flex gap-2">
                    <Button
                      variant={formData.tipo_entrega === 'recoger' ? 'primary' : 'outline-secondary'}
                      onClick={() => setFormData(prev => ({ ...prev, tipo_entrega: 'recoger' }))}
                      style={formData.tipo_entrega === 'recoger' ? { backgroundColor: '#6c757d', borderColor: '#6c757d' } : {}}
                    >
                      Retiro
                    </Button>

                    <Button
                      variant={formData.tipo_entrega === 'delivery' ? 'primary' : 'outline-secondary'}
                      onClick={() => setFormData(prev => ({ ...prev, tipo_entrega: 'delivery' }))}
                      style={formData.tipo_entrega === 'delivery' ? { backgroundColor: '#8b6f47', borderColor: '#8b6f47' } : {}}
                      title={'Delivery local (Quillacollo)'}
                    >
                      Delivery (local)
                    </Button>

                    <Button
                      variant={formData.tipo_entrega === 'envio_nacional' ? 'primary' : 'outline-secondary'}
                      onClick={() => { if (!hasNonShippableNationalItem) setFormData(prev => ({ ...prev, tipo_entrega: 'envio_nacional' })); }}
                      disabled={hasNonShippableNationalItem}
                      title={hasNonShippableNationalItem ? 'Envío nacional deshabilitado por productos no-enviables' : 'Envío a todo Bolivia'}
                    >
                      Envío Nacional
                    </Button>
                  </div>
                </Form.Group>

                {/* Fecha y hora de entrega (opcional) */}
                <Form.Group className="mb-3">
                  <Form.Label>Fecha y hora de entrega (opcional)</Form.Label>
                  <div className="d-flex gap-2 align-items-center">
                    <Form.Control
                      type="date"
                      value={fechaEntrega}
                      onChange={(e) => setFechaEntrega(e.target.value)}
                      min={minFecha || fechaEntrega}
                      style={{ maxWidth: 180 }}
                    />
                    <Form.Control
                      type="time"
                      value={horaEntrega}
                      onChange={(e) => setHoraEntrega(e.target.value)}
                      style={{ maxWidth: 140 }}
                    />
                    <div style={{ display: 'flex', gap: 6 }}>
                      <Button size="sm" variant="outline-secondary" onClick={() => setQuick(0)}>Ahora</Button>
                      <Button size="sm" variant="outline-secondary" onClick={() => setQuick(30)}>+30m</Button>
                      <Button size="sm" variant="outline-secondary" onClick={() => setQuick(60)}>+1h</Button>
                      <Button size="sm" variant="outline-secondary" onClick={() => setQuick(24*60)}>Mañana</Button>
                    </div>
                  </div>
                  <Form.Text className="text-muted">Si no lo completas, se procesará lo antes posible según la disponibilidad.</Form.Text>
                </Form.Group>

                {(formData.tipo_entrega === 'delivery' || formData.tipo_entrega === 'envio_nacional') && (
                  <Form.Group className="mb-3">
                    <Form.Label>📍 Ingresa tu dirección</Form.Label>
                    <div className="d-flex gap-2">
                      <Form.Control
                        type="text"
                        name="direccion_entrega"
                        value={formData.direccion_entrega}
                        onChange={(e) => {
                          const val = e.target.value;
                          setFormData(prev => ({ ...prev, direccion_entrega: val }));
                          setDireccionValida(false);

                          // Only attempt coord parsing for delivery (users may paste coords)
                          if (formData.tipo_entrega === 'delivery') {
                            const coordMatch = val.match(/(-?\d+\.\d+)\s*,\s*(-?\d+\.\d+)/);
                            if (coordMatch) {
                              const lat = parseFloat(coordMatch[1]);
                              const lng = parseFloat(coordMatch[2]);
                              setFormData(prev => ({ ...prev, direccion_lat: lat, direccion_lng: lng }));
                              const ok = isWithinQuillacollo(lat, lng);
                              setDireccionValida(ok);
                              if (ok) {
                                toast.success('Coordenadas detectadas dentro de Quillacollo');
                              } else {
                                toast.error('Coordenadas detectadas, pero parecen estar fuera de Quillacollo');
                              }
                            } else {
                              setFormData(prev => ({ ...prev, direccion_lat: null, direccion_lng: null }));
                            }
                          } else {
                            // For envio_nacional we do not parse coords and clear them
                            setFormData(prev => ({ ...prev, direccion_lat: null, direccion_lng: null }));
                          }
                        }}
                        placeholder={formData.tipo_entrega === 'delivery' ? "Introduzca coordenadas (lat, lng) o use 'Usar mi ubicación'" : 'Departamento o ciudad (ej. Cochabamba)'}
                      />

                      {/* Buttons: only show validate + geolocation for delivery; for envio_nacional hide them */}
                      {formData.tipo_entrega === 'delivery' ? (
                        <div className="d-flex gap-2">
                          <Button variant="outline-primary" onClick={() => {
                            // If coordinates present, validate by coords first; otherwise fallback to text check
                            const lat = formData.direccion_lat;
                            const lng = formData.direccion_lng;
                            let ok = false;
                            if (lat !== null && lng !== null) {
                              ok = isWithinQuillacollo(lat, lng);
                            } else {
                              ok = validarDireccionBasica(formData.direccion_entrega);
                            }
                            setDireccionValida(ok);
                            if (ok) toast.success('Dirección válida dentro de Quillacollo (validación).');
                            else toast.error('Dirección no válida para Quillacollo.');
                          }}>Validar dirección</Button>
                          <Button variant="outline-secondary" onClick={() => {
                            if (!navigator.geolocation) {
                              toast.error('Geolocalización no soportada por tu navegador');
                              return;
                            }
                            navigator.geolocation.getCurrentPosition((pos) => {
                              const lat = pos.coords.latitude;
                              const lng = pos.coords.longitude;
                              setFormData(prev => ({ ...prev, direccion_lat: lat, direccion_lng: lng }));
                              const ok = isWithinQuillacollo(lat, lng);
                              setDireccionValida(ok);
                              if (ok) toast.success('Ubicación detectada dentro de Quillacollo');
                              else toast.error('Tu ubicación parece estar fuera de Quillacollo');
                            }, (err) => {
                              toast.error('No se pudo obtener la ubicación: ' + err.message);
                            }, { enableHighAccuracy: true, timeout: 8000 });
                          }}>Usar mi ubicación</Button>
                        </div>
                      ) : (
                        <Form.Text className="text-muted">Introduce el departamento o la ciudad para envío nacional (ej. Cochabamba).</Form.Text>
                      )}

                    </div>
                    {formData.tipo_entrega === 'delivery' && <Form.Text className="text-muted">Puedes pegar coordenadas como -17.39, -66.26 o usar el botón "Usar mi ubicación".</Form.Text>}
                    {direccionValida && <div className="text-success mt-2">Dirección validada (básico)</div>}
                  </Form.Group>
                )}
              </Card.Body>
            </Card>

            {/* Pago */}
            <Card className="mb-4 shadow-sm">
              <Card.Body>
                <h5 className="mb-3" style={{ fontWeight: 'bold' }}>Pago</h5>
                <Form.Label>Medios de pago:</Form.Label>

                {metodosPago.map(metodo => (
                  <div 
                    key={metodo.id} 
                    className="border rounded p-3 mb-2 d-flex align-items-center"
                    style={{ cursor: 'pointer', backgroundColor: formData.metodos_pago_id === metodo.id ? '#f8f9fa' : 'white' }}
                    onClick={() => setFormData(prev => ({ ...prev, metodos_pago_id: metodo.id }))}
                  >
                    <Form.Check
                      type="radio"
                      name="metodos_pago_id"
                      checked={formData.metodos_pago_id === metodo.id}
                      onChange={() => setFormData(prev => ({ ...prev, metodos_pago_id: metodo.id }))}
                      label=""
                      className="me-3"
                    />
                    {( /qr/i.test(metodo.codigo || '')) && (
                      <div className="d-flex align-items-center w-100">
                        <div style={{ display: 'flex', alignItems: 'center', gap: '12px', width: '100%' }}>
                          <div style={{
                            width: '120px',
                            height: '120px',
                            border: '2px solid #ddd',
                            marginRight: '12px',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            background: '#fff'
                          }}>
                            {/* Mostrar icono subido por admin si existe */}
                            {metodo.icono_url ? (
                              <Image
                                src={metodo.icono_url}
                                alt={metodo.nombre}
                                rounded
                                style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                              />
                            ) : metodo.icono ? (
                              <Image
                                src={metodo.icono.startsWith('http') ? metodo.icono : `${assetBase().replace(/\/$/, '')}/${metodo.icono.replace(/^\//, '')}`}
                                alt={metodo.nombre}
                                rounded
                                style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                              />
                            ) : (
                              <span style={{ fontSize: '14px', color: '#333' }}>QR</span>
                            )}
                          </div>
                          <div>
                            <div style={{ fontWeight: '600' }}>{metodo.nombre}</div>
                            <div style={{ fontSize: '14px', color: '#555' }}>
                              Escanea el QR y envía el comprobante por WhatsApp al número de la empresa.
                            </div>
                            <div style={{ marginTop: '6px' }}>
                              <a href={`https://wa.me/59176490687`} target="_blank" rel="noreferrer">Enviar comprobante por WhatsApp: +591 764 90687</a>
                            </div>
                          </div>
                        </div>
                      </div>
                    )}
                    {metodo.codigo === 'transferencia' && (
                      <div className="d-flex align-items-center">
                        <span style={{ fontSize: '30px', marginRight: '10px' }}>💱</span>
                        <span>{metodo.nombre}</span>
                      </div>
                    )}
                    {metodo.codigo === 'efectivo' && (
                      <div className="d-flex align-items-center">
                        <span style={{ fontSize: '30px', marginRight: '10px' }}>💵</span>
                        <span>{metodo.nombre}</span>
                      </div>
                    )}
                  </div>
                ))}
              </Card.Body>
            </Card>

            {/* Indicaciones Especiales */}
            <Card className="mb-4 shadow-sm">
              <Card.Body>
                <h5 className="mb-3" style={{ fontWeight: 'bold' }}>Indicaciones Especiales</h5>
                <Form.Group>
                  <Form.Control
                    as="textarea"
                    rows={3}
                    name="indicaciones_especiales"
                    value={formData.indicaciones_especiales}
                    onChange={handleInputChange}
                    placeholder="Ej: Torta para cumpleaños de Juan Díaz"
                  />
                </Form.Group>
              </Card.Body>
            </Card>

            {/* Botón Pagar */}
            <Button
              type="submit"
              size="lg"
              className="w-100 mb-4"
              disabled={loading}
              style={{ 
                backgroundColor: '#8b6f47', 
                borderColor: '#8b6f47',
                fontWeight: 'bold',
                padding: '12px'
              }}
            >
              {loading ? 'Procesando...' : 'Pagar Ahora'}
            </Button>
          </Col>

          {/* Columna Derecha - Resumen */}
          <Col lg={5}>
            <Card className="shadow-sm sticky-top" style={{ top: '20px' }}>
              <Card.Body>
                {/* Productos */}
                <ListGroup variant="flush" className="mb-3">
                  {cart.map(item => (
                    <ListGroup.Item key={item.id} className="px-0">
                      <div className="d-flex align-items-center">
                        <Image
                          src={item.imagenes?.[0]?.url_imagen_completa || item.imagenes?.[0]?.url_imagen || 'https://picsum.photos/80/80'}
                          rounded
                          style={{ width: '60px', height: '60px', objectFit: 'cover', marginRight: '12px' }}
                        />
                        <div className="flex-grow-1">
                          <div className="d-flex justify-content-between align-items-start">
                            <div>
                              <div style={{ fontWeight: '600' }}>{item.nombre}</div>
                              <div className="text-muted" style={{ fontSize: '13px' }}>Bs {formatPrice(item.precio_minorista)} c/u</div>
                              {item.permite_envio_nacional === false && (
                                <div className="badge bg-warning text-dark mt-1">No envíos nacionales</div>
                              )}
                            </div>
                            <div className="text-end">
                              <div className="d-flex align-items-center mb-2">
                                <Button size="sm" variant="light" onClick={() => updateQuantity(item.id, item.cantidad - 1)}>-</Button>
                                <div style={{ width: '40px', textAlign: 'center' }}>{item.cantidad}</div>
                                <Button size="sm" variant="light" onClick={() => updateQuantity(item.id, item.cantidad + 1)}>+</Button>
                              </div>
                              <div>
                                <strong>Bs {(toNumber(item.precio_minorista) * toNumber(item.cantidad)).toFixed(2)}</strong>
                              </div>
                              <div className="mt-2">
                                <Button size="sm" variant="outline-danger" onClick={() => removeFromCart(item.id)}>Eliminar</Button>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </ListGroup.Item>
                  ))}
                </ListGroup>

                {/* Nota: los códigos promocionales están temporalmente deshabilitados */}
                {hasNonShippableNationalItem && (
                  <div className="alert alert-warning" role="alert">
                    Algunos productos en tu carrito no permiten envío nacional. Si deseas envío, elimina los siguientes productos o elige Retiro:
                    <ul className="mt-2 mb-0">
                      {cart.filter(i => i.permite_envio_nacional === false).map(i => (
                        <li key={i.id}>{i.nombre} (cantidad: {i.cantidad})</li>
                      ))}
                    </ul>
                  </div>
                )}

                {formData.tipo_entrega === 'recoger' && (
                  <div className="mb-3">
                    <div className="bg-light p-2 rounded">
                      <strong>Retiro en sucursal:</strong>
                      <div>HPW9+J94, Av. Martín Cardenas, Quillacollo</div>
                      <div>
                        <a href="https://www.google.com/maps/search/?api=1&query=-17.403381642688004,-66.2815992191286" target="_blank" rel="noreferrer">Ver en mapa</a>
                      </div>
                    </div>
                  </div>
                )}

                {/* Totales */}
                <hr />
                <div className="d-flex justify-content-between mb-2">
                  <span>Total Productos:</span>
                  <strong>Bs. {getTotal().toFixed(2)}</strong>
                </div>
                <div className="d-flex justify-content-between mb-2">
                  <span>Total Descuentos:</span>
                  <strong className="text-danger">Bs. {descuento.toFixed(2)}</strong>
                </div>
                <hr />
                <div className="d-flex justify-content-between mb-3">
                  <h5 style={{ fontWeight: 'bold' }}>Total a Pagar:</h5>
                  <h4 style={{ fontWeight: 'bold', color: '#8b6f47' }}>
                    Bs. {(getTotal() - descuento).toFixed(2)}
                  </h4>
                </div>

                {/* Botón Volver */}
                <Button
                  variant="outline-secondary"
                  className="w-100"
                  onClick={() => navigate('/carrito')}
                  style={{ backgroundColor: '#8b6f47', borderColor: '#8b6f47', color: 'white' }}
                >
                  Volver
                </Button>
              </Card.Body>
            </Card>
          </Col>
        </Row>
      </Form>
    </Container>
  );
};

export default Checkout;
