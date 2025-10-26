import { useState, useEffect } from 'react';
import { Form, Row, Col, Button, Card, Table, Alert, Badge } from 'react-bootstrap';
import { admin } from '../../services/api';
import { toast } from 'react-toastify';
import PropTypes from 'prop-types';

const RecetaForm = ({ productoId, productoNombre, receta, onGuardar, onCancelar }) => {
  const [materiasPrimas, setMateriasPrimas] = useState([]);
  const [ingredientes, setIngredientes] = useState([]);
  const [activa, setActiva] = useState(true);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    cargarMateriasPrimas();
    if (receta) {
      setIngredientes(receta.ingredientes || []);
      setActiva(receta.activa !== undefined ? receta.activa : true);
    }
  }, [receta]);

  const cargarMateriasPrimas = async () => {
    try {
      const data = await admin.getMateriasPrimas({ per_page: 1000 });
      setMateriasPrimas(Array.isArray(data) ? data : data.data || []);
    } catch (error) {
      console.error('Error cargando materias primas:', error);
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

    setLoading(true);
    try {
      const payload = {
        producto_id: productoId,
        activa: activa,
        ingredientes: ingredientesValidos.map(ing => ({
          materia_prima_id: parseInt(ing.materia_prima_id),
          cantidad_necesaria: parseFloat(ing.cantidad_necesaria),
          unidad_medida: ing.unidad_medida
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
      console.error('Error guardando receta:', error);
      toast.error(error.response?.data?.message || 'Error al guardar la receta');
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

          <h6 className="mb-3">Ingredientes</h6>
          
          {ingredientes.length === 0 ? (
            <Alert variant="info">
              No hay ingredientes. Haz clic en "Agregar ingrediente" para comenzar.
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
                              {mp.nombre} (Stock: {parseFloat(mp.stock_actual || 0).toFixed(2)} {mp.unidad_medida})
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
  productoId: PropTypes.number.isRequired,
  productoNombre: PropTypes.string.isRequired,
  receta: PropTypes.object,
  onGuardar: PropTypes.func,
  onCancelar: PropTypes.func,
};

export default RecetaForm;
