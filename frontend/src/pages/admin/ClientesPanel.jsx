import React, { useState, useEffect } from 'react';
import useDebounce from '../../hooks/useDebounce';
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
  Modal,
  InputGroup,
  Dropdown,
  ButtonGroup,
} from 'react-bootstrap';

export default function ClientesPanel({ externalOpenCreate = 0 }) {
  const [usuarios, setUsuarios] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [filtroActivo, setFiltroActivo] = useState('');
  const [filtroRol, setFiltroRol] = useState('');
  const [showCrear, setShowCrear] = useState(false);
  const [crearComo, setCrearComo] = useState('cliente'); // 'cliente' or 'usuario'
  const [nuevoCliente, setNuevoCliente] = useState({ nombre: '', apellido: '', email: '', telefono: '', password: '', password_confirmation: '', mark_verified: false });
  const [editingCliente, setEditingCliente] = useState(null);
  const [creating, setCreating] = useState(false);
  
  // Modal para completar datos al cambiar rol
  const [showRoleChangeModal, setShowRoleChangeModal] = useState(false);
  const [roleChangeData, setRoleChangeData] = useState({ userId: null, newRole: '', userName: '', userEmail: '', extraData: {} });

  const debouncedSearch = useDebounce(searchTerm, 350);

  useEffect(() => {
    cargarUsuarios();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [debouncedSearch, filtroActivo, filtroRol]);

  // Open create modal when parent header button toggles the signal (number > 0)
  useEffect(() => {
    if (typeof externalOpenCreate === 'number' && externalOpenCreate > 0) {
      abrirCrear('usuario');
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [externalOpenCreate]);

  const cargarUsuarios = async () => {
    try {
      setLoading(true);
      const params = {};
  if (debouncedSearch) params.buscar = debouncedSearch;
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

  const cambiarRol = async (id, nuevoRol, nombreUsuario, userEmail) => {
    // Si cambio a panadero o vendedor, pedir datos extra primero
    if (nuevoRol === 'panadero' || nuevoRol === 'vendedor') {
      setRoleChangeData({
        userId: id,
        newRole: nuevoRol,
        userName: nombreUsuario,
        userEmail: userEmail || '',
        extraData: nuevoRol === 'panadero' ? {
          telefono: '',
          ci: '',
          fecha_ingreso: new Date().toISOString().split('T')[0],
          turno: 'mañana',
          especialidad: 'pan',
          salario_base: '0',
          salario_por_kilo: '0',
          direccion: '',
          observaciones: ''
        } : {
          telefono: '',
          direccion: '',
          fecha_ingreso: new Date().toISOString().split('T')[0],
          turno: 'mañana',
          salario_base: '0',
          comision_porcentaje: '0',
          observaciones: ''
        }
      });
      setShowRoleChangeModal(true);
      return;
    }

    // Para cliente o admin, cambiar directo
    if (!window.confirm(`¿Estás seguro de cambiar el rol de "${nombreUsuario}" a "${nuevoRol}"?`)) return;
    try {
      await admin.actualizarRolUsuario(id, nuevoRol);
      toast.success(`Rol actualizado a ${nuevoRol} exitosamente`);
      cargarUsuarios();
    } catch (error) {
      console.error('Error cambiando rol:', error);
      console.error('Error response data:', error.response?.data);
      const serverMsg = error.response?.data?.message || error.response?.data || error.message;
      toast.error(serverMsg || 'Error al cambiar rol');
    }
  };

  const confirmarCambioRolConDatos = async () => {
    const { userId, newRole, extraData } = roleChangeData;
    
    try {
      setCreating(true);
      // Primero cambiar el rol del usuario
      await admin.actualizarRolUsuario(userId, newRole);
      
      // Luego crear la fila en la tabla correspondiente con los datos extra
      // SOLO si no existe ya (el backend debe manejar duplicados pero añadimos protección)
      if (newRole === 'panadero') {
        const [nombre, ...apellidoParts] = roleChangeData.userName.split(' ');
        const payload = {
          nombre: nombre || 'Sin nombre',
          apellido: apellidoParts.join(' ') || '',
          email: roleChangeData.userEmail,
          ...extraData
        };
        // Omitir observaciones si está vacío para no forzar campo en backend
        if (!payload.observaciones) delete payload.observaciones;
        try {
          await admin.crearPanadero(payload);
        } catch (err) {
          // Si ya existe (email duplicado), solo mostrar advertencia pero no fallar
          if (err.response?.status === 422 && err.response?.data?.errors?.email) {
            console.warn('Panadero ya existe para este email, solo se actualizó el rol');
            toast.warning('Rol actualizado. El perfil de panadero ya existía.');
          } else {
            throw err; // re-lanzar si es otro error
          }
        }
      } else if (newRole === 'vendedor') {
        const [nombre, ...apellidoParts] = roleChangeData.userName.split(' ');
        const payload = {
          nombre: nombre || 'Sin nombre',
          apellido: apellidoParts.join(' ') || '',
          email: roleChangeData.userEmail,
          ...extraData
        };
        // Omitir observaciones si está vacío
        if (!payload.observaciones) delete payload.observaciones;
        try {
          await admin.crearVendedor(payload);
        } catch (err) {
          if (err.response?.status === 422 && err.response?.data?.errors?.email) {
            console.warn('Vendedor ya existe para este email, solo se actualizó el rol');
            toast.warning('Rol actualizado. El perfil de vendedor ya existía.');
          } else {
            throw err;
          }
        }
      }
      
      toast.success(`Rol cambiado y perfil de ${newRole} creado exitosamente`);
      setShowRoleChangeModal(false);
      cargarUsuarios();
    } catch (error) {
      console.error('Error en cambio de rol:', error);
      // Extraer mensaje específico de validación si existe
      const validationErrors = error.response?.data?.errors;
      if (validationErrors) {
        const firstError = Object.values(validationErrors)[0];
        const errorMsg = Array.isArray(firstError) ? firstError[0] : firstError;
        toast.error(`Error de validación: ${errorMsg}`);
      } else {
        const msg = error.response?.data?.message || error.message;
        toast.error(msg || 'Error al cambiar rol y crear perfil');
      }
    } finally {
      setCreating(false);
    }
  };

  const abrirCrear = (modo = 'cliente') => { 
    setCrearComo(modo);
    setNuevoCliente({ nombre: '', apellido: '', email: '', telefono: '', password: '', password_confirmation: '', mark_verified: false }); 
    setEditingCliente(null);
    setShowCrear(true); 
  };

  const handleEditarCliente = (cliente) => {
    setEditingCliente(cliente);
    setNuevoCliente({
      nombre: cliente.name ? (cliente.name.split(' ')[0] || '') : (cliente.nombre || ''),
      apellido: cliente.name ? cliente.name.split(' ').slice(1).join(' ') : (cliente.apellido || ''),
      email: cliente.email || '',
      telefono: cliente.phone || cliente.telefono || '',
      password: '',
      password_confirmation: '',
      mark_verified: false
    });
    setShowCrear(true);
  };

  const handleEliminarCliente = async (id, nombre, record) => {
    if (!window.confirm(`¿Eliminar ${record?.role ? 'usuario' : 'cliente'} ${nombre}?`)) return;
    try {
      if (record?.role) {
        await admin.eliminarUsuario(id);
      } else {
        await admin.eliminarCliente(id);
      }
      toast.success('Registro eliminado');
      cargarUsuarios();
    } catch (err) {
      console.error('Error eliminando registro', err, err.response?.data);
      const msg = err.response?.data?.message || 'Error al eliminar';
      toast.error(msg);
    }
  };

  const crearCliente = async (e) => {
    e.preventDefault();
    if (nuevoCliente.password && nuevoCliente.password !== nuevoCliente.password_confirmation) {
      toast.error('Las contraseñas no coinciden');
      return;
    }
    try {
      setCreating(true);
      // build payload: only send password/mark_verified if provided to avoid extra work server-side
      const payload = {
        nombre: nuevoCliente.nombre,
        apellido: nuevoCliente.apellido,
        email: nuevoCliente.email,
        telefono: nuevoCliente.telefono,
      };
      if (nuevoCliente.password) payload.password = nuevoCliente.password;
      if (nuevoCliente.mark_verified) payload.mark_verified = true;

      let res;
      if (editingCliente) {
        // If the editing record comes from the users table (has role) use the usuarios endpoint
        if (editingCliente.role) {
          const userPayload = {};
          // backend UserController expects 'name' as full name
          userPayload.name = `${nuevoCliente.nombre || ''} ${nuevoCliente.apellido || ''}`.trim();
          if (nuevoCliente.email) userPayload.email = nuevoCliente.email;
          if (nuevoCliente.password) userPayload.password = nuevoCliente.password;
          // allow role change from edit if admin selected it in the form
          if (nuevoCliente.role) userPayload.role = nuevoCliente.role;
          res = await admin.actualizarUsuario(editingCliente.id, userPayload);
        } else {
          res = await admin.actualizarCliente(editingCliente.id, payload);
        }
        toast.success(res.message || 'Cambios guardados');
        setShowCrear(false);
        cargarUsuarios();
      } else {
        // Creating new record
        if (crearComo === 'usuario') {
          // Create user first. If the selected role requires extra profile data (vendedor/panadero)
          // we'll open the role-change modal pre-filled so the admin can complete the extra data
          // and create the corresponding empleado record.
          // build payload expected by UserController: 'name', 'email', 'password', 'role'
          const userPayload = {
            name: `${nuevoCliente.nombre || ''} ${nuevoCliente.apellido || ''}`.trim(),
            email: nuevoCliente.email,
          };
          if (nuevoCliente.password) userPayload.password = nuevoCliente.password;
          if (nuevoCliente.role) userPayload.role = nuevoCliente.role;

          res = await admin.crearUsuario(userPayload);

          // Try to find created user object in response (backend may return different shapes)
          const createdUser = res?.data || res?.user || res?.usuario || res;
          const userId = createdUser?.id || createdUser?.user?.id || createdUser?.usuario?.id;
          const userName = createdUser?.name || `${createdUser?.nombre || ''} ${createdUser?.apellido || ''}`.trim() || nuevoCliente.nombre;
          const userEmail = createdUser?.email || nuevoCliente.email;

          // If role requires extra data, open modal to complete it and then create panadero/vendedor
          if (nuevoCliente.role === 'panadero' || nuevoCliente.role === 'vendedor') {
            toast.success('Usuario creado. Complete los datos del perfil de ' + nuevoCliente.role);
            setShowCrear(false);
            setRoleChangeData({
              userId: userId || null,
              newRole: nuevoCliente.role,
              userName: userName,
              userEmail: userEmail,
              extraData: nuevoCliente.role === 'panadero' ? {
                telefono: nuevoCliente.telefono || '',
                ci: '',
                fecha_ingreso: new Date().toISOString().split('T')[0],
                turno: 'mañana',
                especialidad: 'pan',
                salario_base: '0',
                salario_por_kilo: '0',
                direccion: '',
                observaciones: ''
              } : {
                telefono: nuevoCliente.telefono || '',
                direccion: '',
                fecha_ingreso: new Date().toISOString().split('T')[0],
                turno: 'mañana',
                salario_base: '0',
                comision_porcentaje: '0',
                observaciones: ''
              }
            });
            setShowRoleChangeModal(true);
          } else {
            // Role is cliente or admin: nothing else to do
            toast.success(res.message || 'Usuario creado');
            setShowCrear(false);
            cargarUsuarios();
          }
        } else {
          // Create as a cliente model by default
          res = await admin.crearCliente(payload);
          toast.success(res.message || 'Cliente creado');
          setShowCrear(false);
          cargarUsuarios();
        }
      }
    } catch (error) {
      console.error('Error creando cliente:', error, error.response?.data);
      const msg = error.response?.data?.message || error.response?.data || error.message;
      toast.error(msg || 'Error al crear cliente');
    } finally {
      setCreating(false);
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
            {/* Button moved to AdminPanel header to match Producto button placement */}
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
                          <Dropdown.Item onClick={() => cambiarRol(u.id, 'cliente', u.name, u.email)}>Cliente</Dropdown.Item>
                          <Dropdown.Item onClick={() => cambiarRol(u.id, 'vendedor', u.name, u.email)}>Vendedor</Dropdown.Item>
                          <Dropdown.Item onClick={() => cambiarRol(u.id, 'panadero', u.name, u.email)}>Panadero</Dropdown.Item>
                          <Dropdown.Item onClick={() => cambiarRol(u.id, 'admin', u.name, u.email)}>Administrador</Dropdown.Item>
                        </Dropdown.Menu>
                      </Dropdown>
                      {u.role === 'cliente' && (
                        <div className="d-inline-block ms-2">
                          <Button size="sm" variant="outline-secondary" onClick={() => handleEditarCliente(u)} className="me-1">Editar</Button>
                          <Button size="sm" variant="outline-danger" onClick={() => handleEliminarCliente(u.id, u.name, u)}>Eliminar</Button>
                        </div>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </Table>
          )}
        </Card.Body>
      </Card>

      <Modal show={showCrear} onHide={() => setShowCrear(false)}>
        <Form onSubmit={crearCliente}>
          <Modal.Header closeButton>
            <Modal.Title>
              {editingCliente ? (editingCliente.role ? 'Editar Usuario' : 'Editar Cliente') : (crearComo === 'usuario' ? 'Crear Usuario' : 'Crear Cliente')}
            </Modal.Title>
          </Modal.Header>
          <Modal.Body>
            <Row className="g-2">
              <Col md={6}>
                <Form.Group>
                  <Form.Label>Nombre</Form.Label>
                  <Form.Control value={nuevoCliente.nombre} onChange={(e)=>setNuevoCliente({...nuevoCliente,nombre:e.target.value})} required />
                </Form.Group>
              </Col>
              <Col md={6}>
                <Form.Group>
                  <Form.Label>Apellido</Form.Label>
                  <Form.Control value={nuevoCliente.apellido} onChange={(e)=>setNuevoCliente({...nuevoCliente,apellido:e.target.value})} />
                </Form.Group>
              </Col>
              <Col md={12} className="mt-2">
                <Form.Group>
                  <Form.Label>Email</Form.Label>
                  <Form.Control type="email" value={nuevoCliente.email} onChange={(e)=>setNuevoCliente({...nuevoCliente,email:e.target.value})} required />
                </Form.Group>
              </Col>
              <Col md={6} className="mt-2">
                <Form.Group>
                  <Form.Label>Teléfono (opcional)</Form.Label>
                  <Form.Control value={nuevoCliente.telefono} onChange={(e)=>setNuevoCliente({...nuevoCliente,telefono:e.target.value})} />
                </Form.Group>
              </Col>
              <Col md={6} className="mt-2">
                <Form.Group>
                  <Form.Label>Contraseña (opcional)</Form.Label>
                  <Form.Control type="password" value={nuevoCliente.password} onChange={(e)=>setNuevoCliente({...nuevoCliente,password:e.target.value})} placeholder="Dejar vacío para generar" />
                </Form.Group>
              </Col>
              <Col md={6} className="mt-2">
                <Form.Group>
                  <Form.Label>Confirmar contraseña</Form.Label>
                  <Form.Control type="password" value={nuevoCliente.password_confirmation} onChange={(e)=>setNuevoCliente({...nuevoCliente,password_confirmation:e.target.value})} />
                </Form.Group>
              </Col>
              <Col md={6} className="mt-3 d-flex align-items-center">
                <Form.Check type="checkbox" label="Marcar email como verificado" checked={nuevoCliente.mark_verified} onChange={(e)=>setNuevoCliente({...nuevoCliente,mark_verified:e.target.checked})} />
              </Col>
              { !editingCliente && crearComo === 'usuario' && (
                <Col md={12} className="mt-2">
                  <Form.Group>
                    <Form.Label>Rol</Form.Label>
                    <Form.Select value={nuevoCliente.role || 'cliente'} onChange={(e)=>setNuevoCliente({...nuevoCliente, role: e.target.value})}>
                      <option value="cliente">Cliente</option>
                      <option value="vendedor">Vendedor</option>
                      <option value="panadero">Panadero</option>
                      <option value="admin">Administrador</option>
                    </Form.Select>
                  </Form.Group>
                </Col>
              )}
            </Row>
          </Modal.Body>
            <Modal.Footer>
            <Button variant="secondary" onClick={()=>setShowCrear(false)}>Cancelar</Button>
            <Button type="submit" variant="primary" disabled={creating}>{creating? (editingCliente ? 'Guardando...' : 'Creando...') : (editingCliente ? 'Guardar cambios' : (crearComo === 'usuario' ? 'Crear Usuario' : 'Crear Cliente'))}</Button>
          </Modal.Footer>
        </Form>
      </Modal>

      {/* Modal para completar datos al cambiar a panadero/vendedor */}
      <Modal show={showRoleChangeModal} onHide={() => setShowRoleChangeModal(false)} size="lg">
        <Modal.Header closeButton>
          <Modal.Title>Completar datos de {roleChangeData.newRole}</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <p className="text-muted">Usuario: <strong>{roleChangeData.userName}</strong> ({roleChangeData.userEmail})</p>
          <p className="mb-3">Completa la información adicional requerida para el rol de <strong>{roleChangeData.newRole}</strong>:</p>
          
          {roleChangeData.newRole === 'panadero' && (
            <Row className="g-2">
              <Col md={6}>
                <Form.Group>
                  <Form.Label>Teléfono *</Form.Label>
                  <Form.Control 
                    value={roleChangeData.extraData.telefono} 
                    onChange={(e)=>setRoleChangeData({...roleChangeData, extraData: {...roleChangeData.extraData, telefono: e.target.value}})} 
                    required 
                  />
                </Form.Group>
              </Col>
              <Col md={6}>
                <Form.Group>
                  <Form.Label>CI *</Form.Label>
                  <Form.Control 
                    value={roleChangeData.extraData.ci} 
                    onChange={(e)=>setRoleChangeData({...roleChangeData, extraData: {...roleChangeData.extraData, ci: e.target.value}})} 
                    required 
                  />
                </Form.Group>
              </Col>
              <Col md={4}>
                <Form.Group>
                  <Form.Label>Fecha Ingreso *</Form.Label>
                  <Form.Control 
                    type="date"
                    value={roleChangeData.extraData.fecha_ingreso} 
                    onChange={(e)=>setRoleChangeData({...roleChangeData, extraData: {...roleChangeData.extraData, fecha_ingreso: e.target.value}})} 
                    required 
                  />
                </Form.Group>
              </Col>
              <Col md={4}>
                <Form.Group>
                  <Form.Label>Turno *</Form.Label>
                  <Form.Select 
                    value={roleChangeData.extraData.turno} 
                    onChange={(e)=>setRoleChangeData({...roleChangeData, extraData: {...roleChangeData.extraData, turno: e.target.value}})}
                  >
                    <option value="mañana">Mañana</option>
                    <option value="tarde">Tarde</option>
                    <option value="noche">Noche</option>
                    <option value="rotativo">Rotativo</option>
                  </Form.Select>
                </Form.Group>
              </Col>
              <Col md={4}>
                <Form.Group>
                  <Form.Label>Especialidad *</Form.Label>
                  <Form.Select 
                    value={roleChangeData.extraData.especialidad} 
                    onChange={(e)=>setRoleChangeData({...roleChangeData, extraData: {...roleChangeData.extraData, especialidad: e.target.value}})}
                  >
                    <option value="pan">Pan</option>
                    <option value="reposteria">Repostería</option>
                    <option value="ambos">Ambos</option>
                  </Form.Select>
                </Form.Group>
              </Col>
              <Col md={6}>
                <Form.Group>
                  <Form.Label>Salario Base (Bs) *</Form.Label>
                  <Form.Control 
                    type="number"
                    step="0.01"
                    value={roleChangeData.extraData.salario_base} 
                    onChange={(e)=>setRoleChangeData({...roleChangeData, extraData: {...roleChangeData.extraData, salario_base: e.target.value}})} 
                    required 
                  />
                </Form.Group>
              </Col>
              <Col md={6}>
                <Form.Group>
                  <Form.Label>Salario por Kilo (Bs/kg)</Form.Label>
                  <Form.Control 
                    type="number"
                    step="0.01"
                    value={roleChangeData.extraData.salario_por_kilo} 
                    onChange={(e)=>setRoleChangeData({...roleChangeData, extraData: {...roleChangeData.extraData, salario_por_kilo: e.target.value}})} 
                  />
                </Form.Group>
              </Col>
              <Col md={12}>
                <Form.Group>
                  <Form.Label>Dirección</Form.Label>
                  <Form.Control 
                    value={roleChangeData.extraData.direccion} 
                    onChange={(e)=>setRoleChangeData({...roleChangeData, extraData: {...roleChangeData.extraData, direccion: e.target.value}})} 
                  />
                </Form.Group>
              </Col>
              <Col md={12}>
                <Form.Group>
                  <Form.Label>Observaciones</Form.Label>
                  <Form.Control 
                    as="textarea"
                    rows={2}
                    value={roleChangeData.extraData.observaciones} 
                    onChange={(e)=>setRoleChangeData({...roleChangeData, extraData: {...roleChangeData.extraData, observaciones: e.target.value}})} 
                  />
                </Form.Group>
              </Col>
            </Row>
          )}

          {roleChangeData.newRole === 'vendedor' && (
            <Row className="g-2">
              <Col md={6}>
                <Form.Group>
                  <Form.Label>Teléfono *</Form.Label>
                  <Form.Control 
                    value={roleChangeData.extraData.telefono} 
                    onChange={(e)=>setRoleChangeData({...roleChangeData, extraData: {...roleChangeData.extraData, telefono: e.target.value}})} 
                    required 
                  />
                </Form.Group>
              </Col>
              <Col md={6}>
                <Form.Group>
                  <Form.Label>Fecha Ingreso *</Form.Label>
                  <Form.Control 
                    type="date"
                    value={roleChangeData.extraData.fecha_ingreso} 
                    onChange={(e)=>setRoleChangeData({...roleChangeData, extraData: {...roleChangeData.extraData, fecha_ingreso: e.target.value}})} 
                    required 
                  />
                </Form.Group>
              </Col>
              <Col md={4}>
                <Form.Group>
                  <Form.Label>Turno *</Form.Label>
                  <Form.Select 
                    value={roleChangeData.extraData.turno} 
                    onChange={(e)=>setRoleChangeData({...roleChangeData, extraData: {...roleChangeData.extraData, turno: e.target.value}})}
                  >
                    <option value="mañana">Mañana</option>
                    <option value="tarde">Tarde</option>
                    <option value="noche">Noche</option>
                    <option value="completo">Completo</option>
                  </Form.Select>
                </Form.Group>
              </Col>
              <Col md={4}>
                <Form.Group>
                  <Form.Label>Salario Base (Bs) *</Form.Label>
                  <Form.Control 
                    type="number"
                    step="0.01"
                    value={roleChangeData.extraData.salario_base} 
                    onChange={(e)=>setRoleChangeData({...roleChangeData, extraData: {...roleChangeData.extraData, salario_base: e.target.value}})} 
                    required 
                  />
                </Form.Group>
              </Col>
              <Col md={4}>
                <Form.Group>
                  <Form.Label>Comisión (%)</Form.Label>
                  <Form.Control 
                    type="number"
                    step="0.01"
                    value={roleChangeData.extraData.comision_porcentaje} 
                    onChange={(e)=>setRoleChangeData({...roleChangeData, extraData: {...roleChangeData.extraData, comision_porcentaje: e.target.value}})} 
                  />
                </Form.Group>
              </Col>
              <Col md={12}>
                <Form.Group>
                  <Form.Label>Dirección</Form.Label>
                  <Form.Control 
                    value={roleChangeData.extraData.direccion} 
                    onChange={(e)=>setRoleChangeData({...roleChangeData, extraData: {...roleChangeData.extraData, direccion: e.target.value}})} 
                  />
                </Form.Group>
              </Col>
              <Col md={12}>
                <Form.Group>
                  <Form.Label>Observaciones</Form.Label>
                  <Form.Control 
                    as="textarea"
                    rows={2}
                    value={roleChangeData.extraData.observaciones} 
                    onChange={(e)=>setRoleChangeData({...roleChangeData, extraData: {...roleChangeData.extraData, observaciones: e.target.value}})} 
                  />
                </Form.Group>
              </Col>
            </Row>
          )}
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={()=>setShowRoleChangeModal(false)}>Cancelar</Button>
          <Button variant="primary" onClick={confirmarCambioRolConDatos} disabled={creating}>
            {creating ? 'Guardando...' : 'Confirmar cambio de rol'}
          </Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}
