import { useLocation, useNavigate } from 'react-router-dom';
import { useSEO } from '../hooks/useSEO';
import { Container, Card, Button, ListGroup } from 'react-bootstrap';

const PedidoConfirmado = () => {
  const location = useLocation();
  const navigate = useNavigate();
  const pedido = location.state?.pedido;

  if (!pedido) {
    navigate('/');
    return null;
  }

  // SEO: no indexar página de confirmación de pedido
  useSEO({
    title: 'Pedido Confirmado - Panificadora Nancy',
    description: `Pedido ${pedido.numero_pedido} confirmado. Gracias por tu compra.`,
    noindex: true
  });

  return (
    <Container className="py-5">
      <Card className="shadow-lg" style={{ maxWidth: '600px', margin: '0 auto' }}>
        <Card.Body className="text-center p-5">
          {/* Icono de éxito */}
          <div style={{ fontSize: '80px', color: '#28a745' }}>✅</div>
          
          <h2 className="mb-3" style={{ color: '#8b6f47', fontWeight: 'bold' }}>
            ¡Pedido Confirmado!
          </h2>
          
          <p className="text-muted mb-4">
            Tu pedido ha sido recibido exitosamente
          </p>

          {/* Detalles del pedido */}
          <Card className="mb-4" style={{ backgroundColor: '#f8f9fa' }}>
            <Card.Body>
              <div className="mb-3">
                <strong>Número de Pedido:</strong>
                <div style={{ fontSize: '24px', color: '#8b6f47', fontWeight: 'bold' }}>
                  {pedido.numero_pedido}
                </div>
              </div>
              
              <hr />
              
              <div className="text-start">
                <p className="mb-2">
                  <strong>Cliente:</strong> {pedido.cliente_nombre} {pedido.cliente_apellido}
                </p>
                <p className="mb-2">
                  <strong>Email:</strong> {pedido.cliente_email}
                </p>
                <p className="mb-2">
                  <strong>Teléfono:</strong> {pedido.cliente_telefono}
                </p>
                <p className="mb-2">
                  <strong>Tipo de Entrega:</strong> {pedido.tipo_entrega === 'delivery' ? 'Envío a domicilio' : 'Retiro en tienda'}
                </p>
                {pedido.direccion_entrega && (
                  <p className="mb-2">
                    <strong>Dirección:</strong> {pedido.direccion_entrega}
                  </p>
                )}
              </div>

              <hr />

              <div className="d-flex justify-content-between align-items-center">
                <strong>Total Pagado:</strong>
                <h4 style={{ color: '#28a745', fontWeight: 'bold', margin: 0 }}>
                  Bs. {parseFloat(pedido.total).toFixed(2)}
                </h4>
              </div>
            </Card.Body>
          </Card>

          {/* Productos del pedido */}
          {pedido.detalles && pedido.detalles.length > 0 && (
            <Card className="mb-4">
              <Card.Header style={{ backgroundColor: '#8b6f47', color: 'white' }}>
                <strong>Productos Ordenados</strong>
              </Card.Header>
              <ListGroup variant="flush">
                {pedido.detalles.map((detalle, index) => (
                  <ListGroup.Item key={index}>
                    <div className="d-flex justify-content-between">
                      <span>{detalle.cantidad}x {detalle.nombre_producto}</span>
                      <strong>Bs. {parseFloat(detalle.subtotal).toFixed(2)}</strong>
                    </div>
                  </ListGroup.Item>
                ))}
              </ListGroup>
            </Card>
          )}

          {/* Información adicional */}
          <div className="alert alert-info">
            <strong>📧 Confirmación enviada</strong>
            <p className="mb-0 mt-2" style={{ fontSize: '14px' }}>
              Hemos enviado los detalles de tu pedido a <strong>{pedido.cliente_email}</strong>
            </p>
          </div>

          {pedido.requiere_anticipacion && (
            <div className="alert alert-warning">
              <strong>⏰ Nota importante</strong>
              <p className="mb-0 mt-2" style={{ fontSize: '14px' }}>
                Tu pedido incluye productos que requieren tiempo de preparación. 
                Nos pondremos en contacto contigo para confirmar la fecha de entrega.
              </p>
            </div>
          )}

          {/* Botones */}
          <div className="d-grid gap-2 mt-4">
            <Button
              size="lg"
              onClick={() => navigate('/')}
              style={{ backgroundColor: '#8b6f47', borderColor: '#8b6f47' }}
            >
              Volver al Inicio
            </Button>
            <Button
              variant="outline-secondary"
              onClick={() => window.print()}
            >
              🖨️ Imprimir Recibo
            </Button>
          </div>
        </Card.Body>
      </Card>
    </Container>
  );
};

export default PedidoConfirmado;
