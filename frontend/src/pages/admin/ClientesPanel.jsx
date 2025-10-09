import { useState, useEffect } from 'react';
import { admin } from '../../services/api';
import { toast } from 'react-toastify';
import { Table, Spinner, Card, Button, Badge, Row, Col, Form, InputGroup } from 'react-bootstrap';

const ClientesPanel = () => {
  const [clientes, setClientes] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [filtroActivo, setFiltroActivo] = useState('');

  useEffect(() => {
    cargarClientes();
  }, [searchTerm, filtroActivo]);

  const cargarClientes = async () => {
    try {
      setLoading(true);
      const params = {};
      if (searchTerm) params.search = searchTerm;
      if (filtroActivo !== '') params.activo = filtroActivo;

      console.log('🔍 Cargando clientes con params:', params);
      const data = await admin.getClientes(params);
      console.log('✅ Respuesta clientes:', data);
      
      // La API devuelve paginación, extraer correctamente
      const clientesData = data.data || data;
      console.log('📊 Clientes extraídos:', clientesData);
      
      setClientes(clientesData);
    } catch (error) {
      console.error('❌ Error cargando clientes:', error);
      console.error('Detalles del error:', error.response?.data);
      toast.error('Error al cargar clientes: ' + (error.response?.data?.message || error.message));
    } finally {
      setLoading(false);
    }
  };

  const toggleActivo = async (id) => {
    try {
      await admin.toggleActiveCliente(id);
      toast.success('Estado actualizado');
      cargarClientes();
    } catch (error) {
      toast.error('Error al actualizar estado');
    }
  };

  const formatFecha = (fecha) => {
    if (!fecha) return '-';
    return new Date(fecha).toLocaleDateString('es-BO', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    });
  };

  return (
    <div>
      {/* Filtros */}
      <Card className="mb-4">
        <Card.Body>
          <Row>
            <Col md={6}>
              <InputGroup>
                <InputGroup.Text>�</InputGroup.Text>
                <Form.Control
                  placeholder="Buscar por nombre, email, teléfono..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                />
              </InputGroup>
            </Col>
            <Col md={3}>
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

      {/* Tabla de clientes */}
      <Card>
        <Card.Body>
          {loading ? (
            <div className="text-center py-4">
              <Spinner animation="border" style={{ color: '#8b6f47' }} />
              <p className="mt-2 text-muted">Cargando clientes...</p>
            </div>
          ) : clientes.length === 0 ? (
            <div className="text-center py-5 text-muted">
              <h4>👥</h4>
              <p>No hay clientes registrados</p>
            </div>
          ) : (
            <Table hover responsive>
              <thead style={{ backgroundColor: '#f8f9fa' }}>
                <tr>
                  <th>Cliente</th>
                  <th>Contacto</th>
                  <th>Pedidos</th>
                  <th>Total Gastado</th>
                  <th>Último Pedido</th>
                  <th>Estado</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                {clientes.map(c => (
                  <tr key={c.id}>
                    <td>
                      <strong>{c.nombre} {c.apellido}</strong>
                      {c.tipo_cliente && (
                        <Badge bg="info" className="ms-2">{c.tipo_cliente}</Badge>
                      )}
                    </td>
                    <td>
                      <div>
                        <small className="text-muted d-block">📧 {c.email}</small>
                        {c.telefono && <small className="text-muted d-block">📱 {c.telefono}</small>}
                      </div>
                    </td>
                    <td className="text-center">
                      <Badge bg="primary">{c.total_pedidos || 0}</Badge>
                    </td>
                    <td>
                      <strong style={{ color: '#8b6f47' }}>
                        Bs. {parseFloat(c.total_gastado || 0).toFixed(2)}
                      </strong>
                    </td>
                    <td>{formatFecha(c.fecha_ultimo_pedido)}</td>
                    <td>
                      <Badge bg={c.activo ? 'success' : 'secondary'}>
                        {c.activo ? 'Activo' : 'Inactivo'}
                      </Badge>
                    </td>
                    <td>
                      <Button 
                        size="sm" 
                        variant={c.activo ? 'outline-warning' : 'outline-success'} 
                        onClick={() => toggleActivo(c.id)}
                      >
                        {c.activo ? '🔒 Desactivar' : '✓ Activar'}
                      </Button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </Table>
          )}
        </Card.Body>
      </Card>
    </div>
  );
};

export default ClientesPanel;
