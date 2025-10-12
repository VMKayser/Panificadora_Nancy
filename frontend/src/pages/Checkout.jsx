import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useSEO } from '../hooks/useSEO';
import { Container, Row, Col, Form, Button, Card, ListGroup, Image } from 'react-bootstrap';
import { useCart } from '../context/CartContext';
import { crearPedido, getMetodosPago } from '../services/api';
import { toast } from 'react-toastify';

const Checkout = () => {
  const navigate = useNavigate();
  const { cart, getTotal, getTotalItems, clearCart } = useCart();
  
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
    codigo_promocional: '',
  });

  const [metodosPago, setMetodosPago] = useState([]);
  const [loading, setLoading] = useState(false);
  const [descuento, setDescuento] = useState(0);
  const [fechaEntrega, setFechaEntrega] = useState('');

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
        setMetodosPago(metodos);
        // Seleccionar el primer método activo por defecto
        const primerMetodo = metodos.find(m => m.esta_activo);
        if (primerMetodo) {
          setFormData(prev => ({ ...prev, metodos_pago_id: primerMetodo.id }));
        }
      } catch (error) {
        console.error('Error al cargar métodos de pago:', error);
        toast.error('Error al cargar métodos de pago');
      }
    };
    fetchMetodosPago();
  }, []);

  // Calcular fecha mínima de entrega basada en productos
  useEffect(() => {
    if (cart.length === 0) return;
    
    // Encontrar el producto que requiere más anticipación
    let diasMaximos = 0;
    cart.forEach(item => {
      if (item.requiere_tiempo_anticipacion) {
        const dias = item.unidad_tiempo === 'dias' ? item.tiempo_anticipacion : 
                     item.unidad_tiempo === 'horas' ? Math.ceil(item.tiempo_anticipacion / 24) : 0;
        diasMaximos = Math.max(diasMaximos, dias);
      }
    });

    const fechaMinima = new Date();
    fechaMinima.setDate(fechaMinima.getDate() + diasMaximos);
    
    const fechaFormateada = fechaMinima.toISOString().split('T')[0];
    setFechaEntrega(fechaFormateada);
  }, [cart]);

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const handleAplicarCupon = () => {
    // Lógica de cupón (por ahora simulado)
    if (formData.codigo_promocional.toUpperCase() === 'PROMO10') {
      setDescuento(getTotal() * 0.1);
      toast.success('¡Cupón aplicado! 10% de descuento');
    } else if (formData.codigo_promocional) {
      toast.error('Código promocional no válido');
    }
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

    if (formData.tipo_entrega === 'delivery' && !formData.direccion_entrega) {
      toast.error('Ingresa la dirección de entrega');
      return;
    }

    setLoading(true);

    try {
      // Preparar datos del pedido
      const pedidoData = {
        ...formData,
        productos: cart.map(item => ({
          id: item.id,
          cantidad: item.cantidad,
        })),
        fecha_entrega: fechaEntrega,
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
      toast.error(error.response?.data?.message || 'Error al procesar el pedido');
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
                    >
                      Envío Nacional
                    </Button>
                  </div>
                </Form.Group>

                {formData.tipo_entrega === 'delivery' && (
                  <Form.Group className="mb-3">
                    <Form.Label>📍 Ingresa tu dirección</Form.Label>
                    <Form.Control
                      type="text"
                      name="direccion_entrega"
                      value={formData.direccion_entrega}
                      onChange={handleInputChange}
                      placeholder="Buscar dirección ..."
                    />
                  </Form.Group>
                )}
              </Card.Body>
            </Card>

            {/* Pago */}
            <Card className="mb-4 shadow-sm">
              <Card.Body>
                <h5 className="mb-3" style={{ fontWeight: 'bold' }}>Pago</h5>
                <Form.Label>Medios de pago:</Form.Label>
                
                {metodosPago.filter(m => m.esta_activo).map(metodo => (
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
                    {metodo.codigo === 'qr_simple' && (
                      <div className="d-flex align-items-center">
                        <div 
                          style={{ 
                            width: '50px', 
                            height: '50px', 
                            border: '2px solid black',
                            marginRight: '10px',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            fontSize: '10px',
                            textAlign: 'center'
                          }}
                        >
                          QR
                        </div>
                        <span>{metodo.nombre}</span>
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
                          <div className="d-flex justify-content-between">
                            <span>{item.cantidad} x {item.nombre}</span>
                            <strong>Bs {(item.precio_minorista * item.cantidad).toFixed(2)}</strong>
                          </div>
                        </div>
                      </div>
                    </ListGroup.Item>
                  ))}
                </ListGroup>

                {/* Código Promocional */}
                <div className="mb-3">
                  <div className="d-flex gap-2">
                    <Form.Control
                      type="text"
                      name="codigo_promocional"
                      value={formData.codigo_promocional}
                      onChange={handleInputChange}
                      placeholder="Código Promocional"
                    />
                    <Button
                      onClick={handleAplicarCupon}
                      style={{ backgroundColor: '#8b6f47', borderColor: '#8b6f47' }}
                    >
                      Aplicar
                    </Button>
                  </div>
                </div>

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
