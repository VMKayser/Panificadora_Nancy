import { useState, useEffect } from 'react';
import { Container, Row, Col, Card, Form, Button, Alert, Spinner, Badge, ListGroup, Tab, Tabs } from 'react-bootstrap';
import { useAuth } from '../../context/AuthContext';
import { auth as authApi, admin } from '../../services/api';
import { toast } from 'react-toastify';

export default function PerfilPanel() {
  const { user, login } = useAuth();
  const [loading, setLoading] = useState(false);
  const [activeTab, setActiveTab] = useState('perfil');
  const [showPasswordForm, setShowPasswordForm] = useState(false);
  
  // Datos del perfil
  const [profileData, setProfileData] = useState({
    name: '',
    email: '',
    phone: '',
  });

  // Cambio de contraseña
  const [passwordData, setPasswordData] = useState({
    current_password: '',
    new_password: '',
    new_password_confirmation: '',
  });

  // Estadísticas específicas del rol
  const [roleStats, setRoleStats] = useState(null);
  const [loadingStats, setLoadingStats] = useState(false);

  useEffect(() => {
    if (user) {
      setProfileData({
        name: user.name || '',
        email: user.email || '',
        phone: user.phone || '',
      });
      
      // Cargar estadísticas si el usuario es empleado
      if (hasRole('vendedor') || hasRole('panadero')) {
        loadRoleStats();
      }
    }
  }, [user]);

  const hasRole = (roleName) => {
    return user?.roles?.some(role => role.name === roleName) || false;
  };

  const loadRoleStats = async () => {
    setLoadingStats(true);
    try {
      if (hasRole('vendedor')) {
        const stats = await admin.getVendedoresEstadisticas();
        // Encontrar las estadísticas del vendedor actual
        const vendedor = await admin.getVendedores({ user_id: user.id });
        if (vendedor && vendedor.length > 0) {
          setRoleStats(vendedor[0]);
        }
      } else if (hasRole('panadero')) {
        const stats = await admin.getPanaderosEstadisticas();
        const panaderos = await admin.getPanaderos();
        const panadero = panaderos.find(p => p.user_id === user.id);
        if (panadero) {
          setRoleStats(panadero);
        }
      }
    } catch (error) {
      console.error('Error al cargar estadísticas:', error);
    } finally {
      setLoadingStats(false);
    }
  };

  const handleUpdateProfile = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      const response = await authApi.updateProfile(profileData);
      toast.success('Perfil actualizado exitosamente');
      
      // Actualizar el usuario en el contexto y localStorage
      if (response.user) {
        localStorage.setItem('user', JSON.stringify(response.user));
        // Re-login silencioso para actualizar el contexto
        window.location.reload();
      }
    } catch (error) {
      console.error('Error al actualizar perfil:', error);
      toast.error(error.response?.data?.message || 'Error al actualizar el perfil');
    } finally {
      setLoading(false);
    }
  };

  const handleChangePassword = async (e) => {
    e.preventDefault();

    if (passwordData.new_password !== passwordData.new_password_confirmation) {
      toast.error('Las contraseñas no coinciden');
      return;
    }

    if (passwordData.new_password.length < 6) {
      toast.error('La nueva contraseña debe tener al menos 6 caracteres');
      return;
    }

    setLoading(true);

    try {
      await authApi.updateProfile({
        current_password: passwordData.current_password,
        new_password: passwordData.new_password,
        new_password_confirmation: passwordData.new_password_confirmation,
      });

      toast.success('Contraseña actualizada exitosamente');
      setPasswordData({
        current_password: '',
        new_password: '',
        new_password_confirmation: '',
      });
      setShowPasswordForm(false);
    } catch (error) {
      console.error('Error al cambiar contraseña:', error);
      toast.error(error.response?.data?.message || error.response?.data?.errors?.current_password?.[0] || 'Error al cambiar la contraseña');
    } finally {
      setLoading(false);
    }
  };

  const getRoleBadgeColor = (roleName) => {
    switch (roleName) {
      case 'admin':
        return 'danger';
      case 'vendedor':
        return 'primary';
      case 'panadero':
        return 'warning';
      case 'cliente':
        return 'success';
      default:
        return 'secondary';
    }
  };

  const getRoleDisplayName = (roleName) => {
    switch (roleName) {
      case 'admin':
        return 'Administrador';
      case 'vendedor':
        return 'Vendedor';
      case 'panadero':
        return 'Panadero';
      case 'cliente':
        return 'Cliente';
      default:
        return roleName;
    }
  };

  if (!user) {
    return (
      <Container className="text-center py-5">
        <Spinner animation="border" />
        <p className="mt-3">Cargando datos de usuario...</p>
      </Container>
    );
  }

  return (
    <Container fluid className="py-4">
      <Row className="mb-4">
        <Col>
          <h3>
            <i className="bi bi-person-circle me-2"></i>
            Mi Perfil y Configuración
          </h3>
          <p className="text-muted">Gestiona tu información personal y preferencias</p>
        </Col>
      </Row>

      <Row>
        {/* Columna izquierda - Información del usuario */}
        <Col md={4} className="mb-4">
          <Card className="shadow-sm">
            <Card.Body className="text-center">
              <div className="mb-3">
                <i className="bi bi-person-circle" style={{ fontSize: '5rem', color: '#8b6f47' }}></i>
              </div>
              <h4>{user.name}</h4>
              <p className="text-muted">{user.email}</p>
              
              <div className="mb-3">
                {user.roles && user.roles.map(role => (
                  <Badge 
                    key={role.id} 
                    bg={getRoleBadgeColor(role.name)} 
                    className="me-2 mb-2"
                    style={{ fontSize: '0.9rem', padding: '0.5rem 1rem' }}
                  >
                    {getRoleDisplayName(role.name)}
                  </Badge>
                ))}
              </div>

              {user.phone && (
                <p className="mb-2">
                  <i className="bi bi-telephone me-2"></i>
                  {user.phone}
                </p>
              )}

              <div className="mt-3 pt-3 border-top">
                <small className="text-muted">
                  <i className="bi bi-calendar-check me-2"></i>
                  Miembro desde {new Date(user.created_at || Date.now()).toLocaleDateString('es-ES', { 
                    year: 'numeric', 
                    month: 'long' 
                  })}
                </small>
              </div>
            </Card.Body>
          </Card>

          {/* Estadísticas del rol - solo para vendedores y panaderos */}
          {(hasRole('vendedor') || hasRole('panadero')) && (
            <Card className="shadow-sm mt-3">
              <Card.Header>
                <h6 className="mb-0">
                  <i className="bi bi-bar-chart me-2"></i>
                  Mis Estadísticas
                </h6>
              </Card.Header>
              <Card.Body>
                {loadingStats ? (
                  <div className="text-center py-3">
                    <Spinner animation="border" size="sm" />
                  </div>
                ) : roleStats ? (
                  <ListGroup variant="flush">
                    {hasRole('vendedor') && (
                      <>
                        <ListGroup.Item className="d-flex justify-content-between">
                          <span>Ventas realizadas:</span>
                          <strong>{roleStats.ventas_realizadas || 0}</strong>
                        </ListGroup.Item>
                        <ListGroup.Item className="d-flex justify-content-between">
                          <span>Total vendido:</span>
                          <strong>Bs. {(roleStats.total_vendido || 0).toLocaleString('es-BO', { minimumFractionDigits: 2 })}</strong>
                        </ListGroup.Item>
                        <ListGroup.Item className="d-flex justify-content-between">
                          <span>Comisión:</span>
                          <strong>{roleStats.comision_porcentaje || 0}%</strong>
                        </ListGroup.Item>
                        <ListGroup.Item className="d-flex justify-content-between">
                          <span>Estado:</span>
                          <Badge bg={roleStats.estado === 'activo' ? 'success' : 'secondary'}>
                            {roleStats.estado || 'N/A'}
                          </Badge>
                        </ListGroup.Item>
                      </>
                    )}
                    {hasRole('panadero') && (
                      <>
                        <ListGroup.Item className="d-flex justify-content-between">
                          <span>Especialidad:</span>
                          <strong>{roleStats.especialidad || 'N/A'}</strong>
                        </ListGroup.Item>
                        <ListGroup.Item className="d-flex justify-content-between">
                          <span>Turno:</span>
                          <strong>{roleStats.turno || 'N/A'}</strong>
                        </ListGroup.Item>
                        <ListGroup.Item className="d-flex justify-content-between">
                          <span>Unidades producidas:</span>
                          <strong>{(roleStats.total_unidades_producidas || 0).toLocaleString()}</strong>
                        </ListGroup.Item>
                        <ListGroup.Item className="d-flex justify-content-between">
                          <span>Kilos producidos:</span>
                          <strong>{(roleStats.total_kilos_producidos || 0).toLocaleString()} kg</strong>
                        </ListGroup.Item>
                      </>
                    )}
                  </ListGroup>
                ) : (
                  <p className="text-muted text-center mb-0">No hay estadísticas disponibles</p>
                )}
              </Card.Body>
            </Card>
          )}
        </Col>

        {/* Columna derecha - Formularios */}
        <Col md={8}>
          <Tabs activeKey={activeTab} onSelect={setActiveTab} className="mb-3">
            <Tab eventKey="perfil" title={<><i className="bi bi-person me-2"></i>Información Personal</>}>
              <Card className="shadow-sm">
                <Card.Header>
                  <h5 className="mb-0">Actualizar Información Personal</h5>
                </Card.Header>
                <Card.Body>
                  <Form onSubmit={handleUpdateProfile}>
                    <Form.Group className="mb-3">
                      <Form.Label>Nombre Completo *</Form.Label>
                      <Form.Control
                        type="text"
                        value={profileData.name}
                        onChange={(e) => setProfileData({ ...profileData, name: e.target.value })}
                        required
                        placeholder="Ej: Juan Pérez"
                      />
                    </Form.Group>

                    <Form.Group className="mb-3">
                      <Form.Label>Correo Electrónico</Form.Label>
                      <Form.Control
                        type="email"
                        value={profileData.email}
                        disabled
                        className="bg-light"
                      />
                      <Form.Text className="text-muted">
                        El correo no se puede modificar. Contacta al administrador si necesitas cambiarlo.
                      </Form.Text>
                    </Form.Group>

                    <Form.Group className="mb-3">
                      <Form.Label>Teléfono</Form.Label>
                      <Form.Control
                        type="tel"
                        value={profileData.phone}
                        onChange={(e) => setProfileData({ ...profileData, phone: e.target.value })}
                        placeholder="Ej: +591 70123456"
                      />
                    </Form.Group>

                    <div className="d-flex gap-2">
                      <Button 
                        type="submit" 
                        variant="primary" 
                        disabled={loading}
                        style={{ backgroundColor: '#8b6f47', borderColor: '#8b6f47' }}
                      >
                        {loading ? (
                          <>
                            <Spinner animation="border" size="sm" className="me-2" />
                            Guardando...
                          </>
                        ) : (
                          <>
                            <i className="bi bi-save me-2"></i>
                            Guardar Cambios
                          </>
                        )}
                      </Button>
                    </div>
                  </Form>
                </Card.Body>
              </Card>
            </Tab>

            <Tab eventKey="seguridad" title={<><i className="bi bi-shield-lock me-2"></i>Seguridad</>}>
              <Card className="shadow-sm">
                <Card.Header>
                  <h5 className="mb-0">Cambiar Contraseña</h5>
                </Card.Header>
                <Card.Body>
                  {!showPasswordForm ? (
                    <div className="text-center py-4">
                      <i className="bi bi-shield-lock" style={{ fontSize: '3rem', color: '#8b6f47' }}></i>
                      <p className="mt-3 mb-3">
                        Mantén tu cuenta segura actualizando tu contraseña regularmente.
                      </p>
                      <Button 
                        variant="outline-primary"
                        onClick={() => setShowPasswordForm(true)}
                      >
                        <i className="bi bi-key me-2"></i>
                        Cambiar Contraseña
                      </Button>
                    </div>
                  ) : (
                    <Form onSubmit={handleChangePassword}>
                      <Alert variant="info" className="mb-3">
                        <i className="bi bi-info-circle me-2"></i>
                        La nueva contraseña debe tener al menos 6 caracteres.
                      </Alert>

                      <Form.Group className="mb-3">
                        <Form.Label>Contraseña Actual *</Form.Label>
                        <Form.Control
                          type="password"
                          value={passwordData.current_password}
                          onChange={(e) => setPasswordData({ ...passwordData, current_password: e.target.value })}
                          required
                          placeholder="Ingresa tu contraseña actual"
                        />
                      </Form.Group>

                      <Form.Group className="mb-3">
                        <Form.Label>Nueva Contraseña *</Form.Label>
                        <Form.Control
                          type="password"
                          value={passwordData.new_password}
                          onChange={(e) => setPasswordData({ ...passwordData, new_password: e.target.value })}
                          required
                          minLength={6}
                          placeholder="Ingresa tu nueva contraseña"
                        />
                      </Form.Group>

                      <Form.Group className="mb-3">
                        <Form.Label>Confirmar Nueva Contraseña *</Form.Label>
                        <Form.Control
                          type="password"
                          value={passwordData.new_password_confirmation}
                          onChange={(e) => setPasswordData({ ...passwordData, new_password_confirmation: e.target.value })}
                          required
                          minLength={6}
                          placeholder="Confirma tu nueva contraseña"
                        />
                      </Form.Group>

                      <div className="d-flex gap-2">
                        <Button 
                          type="submit" 
                          variant="primary" 
                          disabled={loading}
                        >
                          {loading ? (
                            <>
                              <Spinner animation="border" size="sm" className="me-2" />
                              Actualizando...
                            </>
                          ) : (
                            <>
                              <i className="bi bi-check-circle me-2"></i>
                              Actualizar Contraseña
                            </>
                          )}
                        </Button>
                        <Button 
                          variant="outline-secondary" 
                          onClick={() => {
                            setShowPasswordForm(false);
                            setPasswordData({
                              current_password: '',
                              new_password: '',
                              new_password_confirmation: '',
                            });
                          }}
                          disabled={loading}
                        >
                          Cancelar
                        </Button>
                      </div>
                    </Form>
                  )}
                </Card.Body>
              </Card>
            </Tab>

            {/* Tab de preferencias - solo para admins */}
            {hasRole('admin') && (
              <Tab eventKey="preferencias" title={<><i className="bi bi-gear me-2"></i>Preferencias del Sistema</>}>
                <Card className="shadow-sm">
                  <Card.Header>
                    <h5 className="mb-0">Configuración del Sistema</h5>
                  </Card.Header>
                  <Card.Body>
                    <Alert variant="info">
                      <i className="bi bi-info-circle me-2"></i>
                      Como administrador, tienes acceso completo a todas las funcionalidades del sistema.
                    </Alert>

                    <ListGroup variant="flush">
                      <ListGroup.Item className="d-flex justify-content-between align-items-center">
                        <div>
                          <strong>Gestión de Productos</strong>
                          <br />
                          <small className="text-muted">Crear, editar y eliminar productos del catálogo</small>
                        </div>
                        <Badge bg="success">Activo</Badge>
                      </ListGroup.Item>
                      <ListGroup.Item className="d-flex justify-content-between align-items-center">
                        <div>
                          <strong>Gestión de Pedidos</strong>
                          <br />
                          <small className="text-muted">Administrar todos los pedidos del sistema</small>
                        </div>
                        <Badge bg="success">Activo</Badge>
                      </ListGroup.Item>
                      <ListGroup.Item className="d-flex justify-content-between align-items-center">
                        <div>
                          <strong>Gestión de Empleados</strong>
                          <br />
                          <small className="text-muted">Administrar panaderos y vendedores</small>
                        </div>
                        <Badge bg="success">Activo</Badge>
                      </ListGroup.Item>
                      <ListGroup.Item className="d-flex justify-content-between align-items-center">
                        <div>
                          <strong>Control de Inventario</strong>
                          <br />
                          <small className="text-muted">Gestionar materias primas y productos finales</small>
                        </div>
                        <Badge bg="success">Activo</Badge>
                      </ListGroup.Item>
                      <ListGroup.Item className="d-flex justify-content-between align-items-center">
                        <div>
                          <strong>Reportes y Estadísticas</strong>
                          <br />
                          <small className="text-muted">Acceso a reportes detallados del negocio</small>
                        </div>
                        <Badge bg="success">Activo</Badge>
                      </ListGroup.Item>
                    </ListGroup>
                  </Card.Body>
                </Card>
              </Tab>
            )}
          </Tabs>
        </Col>
      </Row>
    </Container>
  );
}
