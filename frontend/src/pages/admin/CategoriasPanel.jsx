import { useState, useEffect } from 'react';
import { Table, Button, Modal, Form, Badge, Alert, Spinner } from 'react-bootstrap';
import { admin } from '../../services/api';
import { toast } from 'react-toastify';

export default function CategoriasPanel() {
  const [categorias, setCategorias] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [editando, setEditando] = useState(null);
  const [formData, setFormData] = useState({
    nombre: '',
    descripcion: '',
    imagen: '',
    esta_activo: true,
    order: ''
  });

  useEffect(() => {
    cargarCategorias();
  }, []);

  const cargarCategorias = async () => {
    try {
      setLoading(true);
      const data = await admin.getCategorias();
      setCategorias(data);
    } catch (error) {
      console.error('Error cargando categorías:', error);
      toast.error('Error al cargar categorías');
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    try {
      if (editando) {
        await admin.updateCategoria(editando.id, formData);
        toast.success('Categoría actualizada exitosamente');
      } else {
        await admin.createCategoria(formData);
        toast.success('Categoría creada exitosamente');
      }
      
      handleCloseModal();
      cargarCategorias();
    } catch (error) {
      console.error('Error guardando categoría:', error);
      toast.error(error.response?.data?.message || 'Error al guardar categoría');
    }
  };

  const handleEditar = (categoria) => {
    setEditando(categoria);
    setFormData({
      nombre: categoria.nombre,
      descripcion: categoria.descripcion || '',
      imagen: categoria.imagen || '',
      esta_activo: categoria.esta_activo,
      order: categoria.order || ''
    });
    setShowModal(true);
  };

  const handleEliminar = async (id, nombre) => {
    if (!window.confirm(`¿Está seguro de eliminar la categoría "${nombre}"?`)) return;
    
    try {
      await admin.deleteCategoria(id);
      toast.success('Categoría eliminada exitosamente');
      cargarCategorias();
    } catch (error) {
      console.error('Error eliminando categoría:', error);
      toast.error(error.response?.data?.message || 'Error al eliminar categoría');
    }
  };

  const handleToggleActive = async (id) => {
    try {
      await admin.toggleCategoriaActive(id);
      toast.success('Estado actualizado');
      cargarCategorias();
    } catch (error) {
      console.error('Error actualizando estado:', error);
      toast.error('Error al actualizar estado');
    }
  };

  const handleCloseModal = () => {
    setShowModal(false);
    setEditando(null);
    setFormData({
      nombre: '',
      descripcion: '',
      imagen: '',
      esta_activo: true,
      order: ''
    });
  };

  if (loading) {
    return (
      <div className="text-center py-5">
        <Spinner animation="border" />
        <p className="mt-3">Cargando categorías...</p>
      </div>
    );
  }

  return (
    <div>
      <div className="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h4>Gestión de Categorías</h4>
          <p className="text-muted mb-0">Administra las categorías de productos</p>
        </div>
        <Button variant="primary" onClick={() => setShowModal(true)}>
          <i className="bi bi-plus-circle me-2"></i>
          Nueva Categoría
        </Button>
      </div>

      {categorias.length === 0 ? (
        <Alert variant="info">
          No hay categorías creadas. Crea la primera categoría para empezar.
        </Alert>
      ) : (
        <Table striped bordered hover responsive>
          <thead>
            <tr>
              <th>Orden</th>
              <th>Nombre</th>
              <th>URL</th>
              <th>Descripción</th>
              <th>Productos</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            {categorias.map(cat => (
              <tr key={cat.id}>
                <td>{cat.order}</td>
                <td><strong>{cat.nombre}</strong></td>
                <td><code>{cat.url}</code></td>
                <td>{cat.descripcion || '-'}</td>
                <td>
                  <Badge bg="info">{cat.productos_count || 0}</Badge>
                </td>
                <td>
                  <Badge bg={cat.esta_activo ? 'success' : 'secondary'}>
                    {cat.esta_activo ? 'Activa' : 'Inactiva'}
                  </Badge>
                </td>
                                  <td>
                    <Button
                      variant="outline-primary"
                      size="sm"
                      className="me-2"
                      onClick={() => handleEditar(cat)}
                      title="Editar"
                    >
                      <i className="bi bi-pencil me-1"></i>
                      Editar
                    </Button>
                    <Button
                      variant={cat.esta_activo ? 'outline-warning' : 'outline-success'}
                      size="sm"
                      className="me-2"
                      onClick={() => handleToggleActive(cat.id)}
                      title={cat.esta_activo ? 'Desactivar' : 'Activar'}
                    >
                      <i className={`bi bi-${cat.esta_activo ? 'x-circle' : 'check-circle'} me-1`}></i>
                      {cat.esta_activo ? 'Desactivar' : 'Activar'}
                    </Button>
                    <Button
                      variant="outline-danger"
                      size="sm"
                      onClick={() => handleEliminar(cat.id, cat.nombre)}
                      disabled={cat.productos_count > 0}
                      title="Eliminar"
                    >
                      <i className="bi bi-trash me-1"></i>
                      Eliminar
                    </Button>
                  </td>
              </tr>
            ))}
          </tbody>
        </Table>
      )}

      {/* Modal para crear/editar */}
      <Modal show={showModal} onHide={handleCloseModal} size="lg">
        <Modal.Header closeButton>
          <Modal.Title>{editando ? 'Editar Categoría' : 'Nueva Categoría'}</Modal.Title>
        </Modal.Header>
        <Form onSubmit={handleSubmit}>
          <Modal.Body>
            <Form.Group className="mb-3">
              <Form.Label>Nombre *</Form.Label>
              <Form.Control
                type="text"
                value={formData.nombre}
                onChange={(e) => setFormData({...formData, nombre: e.target.value})}
                required
                placeholder="Ej: Panes, Tortas, Empanadas"
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>Descripción</Form.Label>
              <Form.Control
                as="textarea"
                rows={3}
                value={formData.descripcion}
                onChange={(e) => setFormData({...formData, descripcion: e.target.value})}
                placeholder="Descripción de la categoría"
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>URL de Imagen</Form.Label>
              <Form.Control
                type="text"
                value={formData.imagen}
                onChange={(e) => setFormData({...formData, imagen: e.target.value})}
                placeholder="https://..."
              />
              <Form.Text className="text-muted">
                URL de la imagen para la categoría
              </Form.Text>
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>Orden</Form.Label>
              <Form.Control
                type="number"
                min="0"
                value={formData.order}
                onChange={(e) => setFormData({...formData, order: e.target.value})}
                placeholder="Orden de visualización"
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Check
                type="checkbox"
                label="Categoría activa"
                checked={formData.esta_activo}
                onChange={(e) => setFormData({...formData, esta_activo: e.target.checked})}
              />
            </Form.Group>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="secondary" onClick={handleCloseModal}>
              Cancelar
            </Button>
            <Button variant="primary" type="submit">
              {editando ? 'Actualizar' : 'Crear'}
            </Button>
          </Modal.Footer>
        </Form>
      </Modal>
    </div>
  );
}
