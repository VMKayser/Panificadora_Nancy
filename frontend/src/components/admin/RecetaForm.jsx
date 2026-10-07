import { useState, useEffect } from 'react';
import { Form, Row, Col, Button, Card, Table, Alert, Badge } from 'react-bootstrap';
import { admin } from '../../services/api';
import { toast } from 'react-toastify';
import { formatCurrency } from '../../utils/number';
import PropTypes from 'prop-types';

const RecetaForm = ({ productoId, productoNombre, receta, onGuardar, onCancelar }) => {
  const [materiasPrimas, setMateriasPrimas] = useState([]);
  const [ingredientes, setIngredientes] = useState([]);
  const [activa, setActiva] = useState(true);
  const [nombreReceta, setNombreReceta] = useState('');
  const [descripcionReceta, setDescripcionReceta] = useState('');
  const [rendimiento, setRendimiento] = useState(1);
  const [unidadRendimiento, setUnidadRendimiento] = useState('unidades');
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    cargarMateriasPrimas();
    if (receta) {
      setIngredientes(receta.ingredientes || []);
      setActiva(receta.activa !== undefined ? receta.activa : true);
      setNombreReceta(receta.nombre_receta || receta.nombre || 'Receta');
      setDescripcionReceta(receta.descripcion || '');
      setRendimiento(receta.rendimiento || 1);
      setUnidadRendimiento(receta.unidad_rendimiento || 'unidades');
    }
  }, [receta]);

  // Si estamos creando una receta nueva, autogenerar un nombre basado en el producto
  useEffect(() => {
    if (!receta && productoNombre && (!nombreReceta || String(nombreReceta).trim() === '')) {
      setNombreReceta(`Receta: ${productoNombre}`);
    }
  }, [receta, productoNombre, nombreReceta]);

  const cargarMateriasPrimas = async () => {
    try {
      const data = await admin.getMateriasPrimas({ per_page: 1000 });
      setMateriasPrimas(Array.isArray(data) ? data : data.data || []);
    } catch (error) {
      if (import.meta.env.DEV) console.error('Error cargando materias primas:', error);
      toast.error('Error al cargar materias primas');
    }
  };

  const agregarIngrediente = () => {
    setIngredientes([...ingredientes, {
      materia_prima_id: '',
      cantidad_necesaria: '',
      unidad_medida: 'kg'
    }]);
  };

  const eliminarIngrediente = (index) => {
    setIngredientes(ingredientes.filter((_, i) => i !== index));
  };

  const actualizarIngrediente = (index, campo, valor) => {
    const nuevosIngredientes = [...ingredientes];
    nuevosIngredientes[index][campo] = valor;
    
    // Si cambió la materia prima, actualizar la unidad de medida
    if (campo === 'materia_prima_id') {
      const mp = materiasPrimas.find(m => m.id === parseInt(valor));
      if (mp) {
        nuevosIngredientes[index].unidad_medida = mp.unidad_medida;
      }
    }
    
    setIngredientes(nuevosIngredientes);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (ingredientes.length === 0) {
      toast.warning('Debes agregar al menos un ingrediente');
      return;
    }

    const ingredientesValidos = ingredientes.filter(ing => 
      ing.materia_prima_id && ing.cantidad_necesaria && parseFloat(ing.cantidad_necesaria) > 0
    );

    if (ingredientesValidos.length === 0) {
      toast.warning('Debes completar al menos un ingrediente correctamente');
      return;
    }

    // Validar nombre de receta y rendimiento (requeridos por el backend)
    if (!nombreReceta || String(nombreReceta).trim().length === 0) {
      toast.error('El nombre de la receta es obligatorio');
      return;
    }
    if (!rendimiento || Number(rendimiento) <= 0) {
      toast.error('El rendimiento debe ser mayor a 0');
      return;
    }

    setLoading(true);
    try {
      // Convertir productoId a número de forma segura antes de enviar al backend
      const productoIdNum = Number(productoId);
      if (Number.isNaN(productoIdNum)) {
        toast.error('Producto inválido');
        setLoading(false);
        return;
      }

      const payload = {
        producto_id: productoIdNum,
        activa: activa,
        nombre_receta: String(nombreReceta).trim(),
        descripcion: String(descripcionReceta || ''),
        rendimiento: parseFloat(rendimiento) || 1,
        unidad_rendimiento: unidadRendimiento || 'unidades',
        // Map ingredients to the shape the backend expects: cantidad and unidad
        ingredientes: ingredientesValidos.map((ing, idx) => ({
          materia_prima_id: parseInt(ing.materia_prima_id),
          cantidad: parseFloat(ing.cantidad_necesaria),
          unidad: ing.unidad_medida || ing.unidad || 'unidades',
          orden: idx + 1
        }))
      };

      let result;
      if (receta && receta.id) {
        // Actualizar receta existente
        result = await admin.actualizarReceta(receta.id, payload);
      } else {
        // Crear nueva receta
        result = await admin.crearReceta(payload);
      }

      toast.success(receta ? 'Receta actualizada' : 'Receta creada exitosamente');
      if (onGuardar) onGuardar(result);
    } catch (error) {
      // Provide richer debug info in dev and show validation messages when available
      if (import.meta.env.DEV) {
        console.error('Error guardando receta:', error);
        console.error('Error response data:', error.response?.data);
      }

      // Prefer backend validation message -> full errors -> fallback to error.message
      const serverMessage = error.response?.data?.message;
      const serverErrors = error.response?.data?.errors;

      if (serverMessage) {
        // If there are structured validation errors, include them in the toast
        if (serverErrors && typeof serverErrors === 'object') {
          // Flatten validation errors to a single string
          const flat = Object.values(serverErrors).flat().join(' — ');
          toast.error(`${serverMessage}: ${flat}`);
        } else {
          toast.error(serverMessage);
        }
      } else {
        toast.error(error.response?.data?.error || error.message || 'Error al guardar la receta');
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <Form onSubmit={handleSubmit}>
      <Card className="mb-3">
        <Card.Header className="bg-light">
          <h6 className="mb-0">
            <i className="bi bi-book me-2"></i>
            {receta ? 'Editar Receta' : 'Crear Receta'} - {productoNombre}
          </h6>
        </Card.Header>
        <Card.Body>
          <Row className="mb-3">
            <Col md={6}>
              <Form.Check
                type="switch"
                id="activa-switch"
                label="Receta activa"
                checked={activa}
                onChange={(e) => setActiva(e.target.checked)}
              />
              <Form.Text className="text-muted">
                Solo puede haber una receta activa por producto
              </Form.Text>
            </Col>
          </Row>

          <Row className="mb-3">
            <Col md={8}>
              <Form.Group className="mb-2">
                <Form.Label>Nombre de la Receta *</Form.Label>
                <Form.Control
                  type="text"
                  value={nombreReceta}
                  onChange={(e) => setNombreReceta(e.target.value)}
                  placeholder="Ej: Receta Base - Pan de Agua"
                  required
                />
              </Form.Group>
            </Col>
            <Col md={4}>
              <Form.Group className="mb-2">
                <Form.Label>Rendimiento *</Form.Label>
                <Form.Control
                  type="number"
                  step="0.01"
                  min="0.01"
                  value={rendimiento}
                  onChange={(e) => setRendimiento(e.target.value)}
                />
                <Form.Text className="text-muted">Cantidad de unidades que produce la receta</Form.Text>
              </Form.Group>
            </Col>
          </Row>

          <Row className="mb-3">
            <Col md={6}>
              <Form.Group className="mb-2">
                <Form.Label>Unidad de Rendimiento</Form.Label>
                <Form.Select value={unidadRendimiento} onChange={(e) => setUnidadRendimiento(e.target.value)}>
                  <option value="unidades">Unidades</option>
                  <option value="kg">Kg</option>
                  <option value="docenas">Docenas</option>
                </Form.Select>
              </Form.Group>
            </Col>
            <Col md={6}>
              <Form.Group className="mb-2">
                <Form.Label>Descripción (opcional)</Form.Label>
                <Form.Control
                  as="textarea"
                  rows={2}
                  value={descripcionReceta}
                  onChange={(e) => setDescripcionReceta(e.target.value)}
                  placeholder="Información adicional sobre la receta"
                />
              </Form.Group>
            </Col>
          </Row>

          <h6 className="mb-3">Ingredientes</h6>
          
          {ingredientes.length === 0 ? (
            <Alert variant="info">
              No hay ingredientes. Haz clic en &quot;Agregar ingrediente&quot; para comenzar.
            </Alert>
          ) : (
            <Table striped bordered hover responsive>
              <thead>
                <tr>
                  <th style={{ width: '40%' }}>Materia Prima</th>
                  <th style={{ width: '25%' }}>Cantidad</th>
                  <th style={{ width: '20%' }}>Unidad</th>
                  <th style={{ width: '15%' }}>Acciones</th>
                </tr>
              </thead>
              <tbody>
                {ingredientes.map((ing, index) => {
                  const mpSeleccionada = materiasPrimas.find(mp => mp.id === parseInt(ing.materia_prima_id));
                  return (
                    <tr key={index}>
                      <td>
                        <Form.Select
                          value={ing.materia_prima_id}
                          onChange={(e) => actualizarIngrediente(index, 'materia_prima_id', e.target.value)}
                          required
                        >
                          <option value="">Seleccione...</option>
                          {materiasPrimas.map(mp => (
                                  <option key={mp.id} value={mp.id}>
                              {mp.nombre} (Stock: {formatCurrency(mp.stock_actual || 0)} {mp.unidad_medida})
                            </option>
                          ))}
                        </Form.Select>
                      </td>
                      <td>
                        <Form.Control
                          type="number"
                          step="0.01"
                          min="0.01"
                          value={ing.cantidad_necesaria}
                          onChange={(e) => actualizarIngrediente(index, 'cantidad_necesaria', e.target.value)}
                          placeholder="0.00"
                          required
                        />
                      </td>
                      <td>
                        <Badge bg="secondary" className="w-100 p-2">
                          {mpSeleccionada ? mpSeleccionada.unidad_medida : ing.unidad_medida || '-'}
                        </Badge>
                      </td>
                      <td>
                        <Button
                          variant="outline-danger"
                          size="sm"
                          onClick={() => eliminarIngrediente(index)}
                        >
                          <i className="bi bi-trash"></i>
                        </Button>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </Table>
          )}

          <Button
            variant="outline-primary"
            size="sm"
            onClick={agregarIngrediente}
            className="mb-3"
          >
            <i className="bi bi-plus-circle me-2"></i>
            Agregar ingrediente
          </Button>

          {ingredientes.length > 0 && (
            <Alert variant="secondary" className="mt-3">
              <strong>Total de ingredientes:</strong> {ingredientes.filter(ing => ing.materia_prima_id && ing.cantidad_necesaria).length}
            </Alert>
          )}
        </Card.Body>
      </Card>

      <div className="d-flex justify-content-end gap-2">
        <Button variant="secondary" onClick={onCancelar} disabled={loading}>
          Cancelar
        </Button>
        <Button variant="primary" type="submit" disabled={loading}>
          {loading ? (
            <>
              <span className="spinner-border spinner-border-sm me-2" />
              Guardando...
            </>
          ) : (
            <>
              <i className="bi bi-save me-2"></i>
              {receta ? 'Actualizar Receta' : 'Crear Receta'}
            </>
          )}
        </Button>
      </div>
    </Form>
  );
};

RecetaForm.propTypes = {
  // productoId may come as number or string depending on backend; accept both
  productoId: PropTypes.oneOfType([PropTypes.number, PropTypes.string]).isRequired,
  productoNombre: PropTypes.string.isRequired,
  receta: PropTypes.object,
  onGuardar: PropTypes.func,
  onCancelar: PropTypes.func,
};

export default RecetaForm;
