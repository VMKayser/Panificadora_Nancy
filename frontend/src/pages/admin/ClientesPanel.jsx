import React, { useState, useEffect } from 'react';
import { admin } from '../../services/api';
import { toast } from 'react-toastify';
import {
  Table,
  Spinner,
  Card,
  Button,
  Badge,
  Row,
  Col,
  Form,
  InputGroup,
  Dropdown,
  ButtonGroup,
} from 'react-bootstrap';

export default function ClientesPanel() {
  const [usuarios, setUsuarios] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [filtroActivo, setFiltroActivo] = useState('');
  const [filtroRol, setFiltroRol] = useState('');

  useEffect(() => {
    cargarUsuarios();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [searchTerm, filtroActivo, filtroRol]);

  const cargarUsuarios = async () => {
    try {
      setLoading(true);
      const params = {};
      if (searchTerm) params.buscar = searchTerm;
      if (filtroActivo !== '') params.activo = filtroActivo;
      if (filtroRol) params.role = filtroRol;
      const data = await admin.getUsuarios(params);
      // Normalize response: backend may return an array, or a paginated object { data: [...], ... }
      const usuariosData = Array.isArray(data)
        ? data
        : Array.isArray(data?.data)
        ? data.data
        : [];
      setUsuarios(usuariosData);
    } catch (error) {
      console.error('Error cargando usuarios:', error);
      toast.error('Error al cargar usuarios');
    } finally {
      setLoading(false);
    }
  };

  const cambiarRol = async (id, nuevoRol, nombreUsuario) => {
    if (!window.confirm(`¿Estás seguro de cambiar el rol de "${nombreUsuario}" a "${nuevoRol}"?`)) return;
    try {
      await admin.actualizarRolUsuario(id, nuevoRol);
      toast.success(`Rol actualizado a ${nuevoRol} exitosamente`);
      cargarUsuarios();
    } catch (error) {
      // Log full response for debugging
      console.error('Error cambiando rol:', error);
      console.error('Error response data:', error.response?.data);
      const serverMsg = error.response?.data?.message || error.response?.data || error.message;
      toast.error(serverMsg || 'Error al cambiar rol');
    }
  };

  const formatFecha = (fecha) => {
    if (!fecha) return '-';
    return new Date(fecha).toLocaleDateString('es-BO');
  };

  const getRoleBadgeColor = (role) => {
    switch (role) {
      case 'admin':
        return 'danger';
      case 'vendedor':
        return 'primary';
      case 'panadero':
        return 'warning';
      case 'cliente':
        return 'secondary';
      default:
        return 'secondary';
    }
  };

  return (
    <div>
      <Card className="mb-4">
        <Card.Body>
          <Row>
            <Col md={4}>
              <InputGroup>
                <InputGroup.Text>🔍</InputGroup.Text>
                <Form.Control
                  placeholder="Buscar por nombre o email..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                />
              </InputGroup>
            </Col>
            <Col md={3}>
              <Form.Select value={filtroRol} onChange={(e) => setFiltroRol(e.target.value)}>
                <option value="">Todos los roles</option>
                <option value="cliente">Clientes</option>
                <option value="vendedor">Vendedores</option>
                <option value="panadero">Panaderos</option>
                <option value="admin">Administradores</option>
              </Form.Select>
            </Col>
            <Col md={3}>
              <Form.Select value={filtroActivo} onChange={(e) => setFiltroActivo(e.target.value)}>
                <option value="">Todos los estados</option>
                <option value="1">Solo activos</option>
                <option value="0">Solo inactivos</option>
              </Form.Select>
            </Col>
          </Row>
        </Card.Body>
      </Card>

      <Card>
        <Card.Body>
          {loading ? (
            <div className="text-center py-4">
              <Spinner animation="border" style={{ color: '#8b6f47' }} />
              <p className="mt-2 text-muted">Cargando usuarios...</p>
            </div>
          ) : usuarios.length === 0 ? (
            <div className="text-center py-5 text-muted">
              <h4>👥</h4>
              <p>No hay usuarios registrados</p>
            </div>
          ) : (
            <Table hover responsive>
              <thead className="table-light">
                <tr>
                  <th>ID</th>
                  <th>Usuario</th>
                  <th>Email</th>
                  <th>Rol</th>
                  <th>Fecha Registro</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                {usuarios.map((u) => (
                  <tr key={u.id}>
                    <td>{u.id}</td>
                    <td>
                      <strong>{u.name}</strong>
                    </td>
                    <td>
                      <small className="text-muted">📧 {u.email}</small>
                    </td>
                    <td>
                      <Badge bg={getRoleBadgeColor(u.role)}>{u.role}</Badge>
                    </td>
                    <td>{formatFecha(u.created_at)}</td>
                    <td>
                      <Dropdown as={ButtonGroup} size="sm">
                        <Button variant="outline-primary" size="sm">
                          Rol: {u.role}
                        </Button>
                        <Dropdown.Toggle split variant="outline-primary" id={`dropdown-${u.id}`} />
                        <Dropdown.Menu>
                          <Dropdown.Header>Cambiar rol a:</Dropdown.Header>
                          <Dropdown.Item onClick={() => cambiarRol(u.id, 'cliente', u.name)}>Cliente</Dropdown.Item>
                          <Dropdown.Item onClick={() => cambiarRol(u.id, 'vendedor', u.name)}>Vendedor</Dropdown.Item>
                          <Dropdown.Item onClick={() => cambiarRol(u.id, 'panadero', u.name)}>Panadero</Dropdown.Item>
                          <Dropdown.Item onClick={() => cambiarRol(u.id, 'admin', u.name)}>Administrador</Dropdown.Item>
                        </Dropdown.Menu>
                      </Dropdown>
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
}
