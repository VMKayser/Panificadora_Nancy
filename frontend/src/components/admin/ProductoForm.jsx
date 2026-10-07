import { useState, useEffect } from 'react';
import { Form, Row, Col, Button, Image, Alert, Badge, Modal } from 'react-bootstrap';
import { admin, assetBase } from '../../services/api';
import { toast } from 'react-toastify';
import PropTypes from 'prop-types';
import RecetaForm from './RecetaForm';
import { usarImagenRespaldo } from '../../utils/imagen';

const ProductoForm = ({ producto, categorias, onGuardar, onCancelar }) => {
  const [formData, setFormData] = useState({
    categorias_id: '',
    nombre: '',
    descripcion: '',
    descripcion_corta: '',
    precio_minorista: '',
    precio_mayorista: '',
    precio_por_confirmar: false,
    cantidad_minima_mayoreo: '10',
    unidad_medida: 'unidad',
    presentacion: '',
    es_de_temporada: false,
    esta_activo: true,
    permite_delivery: true,
    permite_envio_nacional: false,
    requiere_tiempo_anticipacion: false,
    tiempo_anticipacion: '',
    unidad_tiempo: 'horas',
    pedidos_hasta: '',
    etiqueta_personalizacion: '',
    limite_produccion: '',
    tiene_extras: false,
    extras_disponibles: [],
  });

  const [imagenes, setImagenes] = useState([]);
  const [imagenesPreview, setImagenesPreview] = useState([]);
  const [uploading, setUploading] = useState(false);
  const [loading, setLoading] = useState(false);
  const [showRecetaModal, setShowRecetaModal] = useState(false);
  const [recetaActual, setRecetaActual] = useState(null);
  const [hasReceta, setHasReceta] = useState(false);

  useEffect(() => {
    if (producto) {
      setFormData({
        categorias_id: producto.categorias_id || '',
        nombre: producto.nombre || '',
        descripcion: producto.descripcion || '',
        descripcion_corta: producto.descripcion_corta || '',
        precio_minorista: producto.precio_minorista || '',
        precio_mayorista: producto.precio_mayorista || '',
        precio_por_confirmar: Boolean(producto.precio_por_confirmar),
        cantidad_minima_mayoreo: producto.cantidad_minima_mayoreo || '10',
        unidad_medida: producto.unidad_medida || 'unidad',
        presentacion: producto.presentacion || '',
        es_de_temporada: producto.es_de_temporada || false,
        esta_activo: producto.esta_activo !== undefined ? producto.esta_activo : true,
        permite_delivery: producto.permite_delivery !== undefined ? producto.permite_delivery : true,
        permite_envio_nacional: producto.permite_envio_nacional || false,
        requiere_tiempo_anticipacion: producto.requiere_tiempo_anticipacion || false,
        tiempo_anticipacion: producto.tiempo_anticipacion || '',
        unidad_tiempo: producto.unidad_tiempo || 'horas',
        pedidos_hasta: producto.pedidos_hasta ? String(producto.pedidos_hasta).slice(0, 10) : '',
        etiqueta_personalizacion: producto.etiqueta_personalizacion || '',
        limite_produccion: producto.limite_produccion || '',
        tiene_extras: producto.tiene_extras || false,
        extras_disponibles: producto.extras_disponibles || [],
      });

      if (producto.imagenes && producto.imagenes.length > 0) {
        const urls = producto.imagenes.map(img => {
          // Priorizar url_imagen_completa, luego url_imagen
          const url = img.url_imagen_completa || img.url_imagen;
          // Si la URL no comienza con http, agregar el prefijo del backend (assetBase())
          return url.startsWith('http') ? url : `${assetBase()}${url}`;
        });
        setImagenes(urls);
        setImagenesPreview(urls);
      }
      // Determine quickly if producto has a receta to avoid label flicker.
      // If the API already included relation `producto.receta` or `producto.receta_id`, use that.
      if (producto.receta || producto.receta_id) {
        setHasReceta(true);
        // If receta object is present, keep it in state so modal opens immediately for edit
        if (producto.receta) setRecetaActual(producto.receta);
      } else {
        // If not present, try a lightweight existence check in background
        (async () => {
          try {
            const resp = await admin.getRecetas({ producto_id: producto.id, per_page: 1 });
            const found = resp?.data?.[0] || (Array.isArray(resp) ? resp[0] : null);
            if (found) {
              setHasReceta(true);
              setRecetaActual(found);
            } else {
              setHasReceta(false);
            }
          } catch (err) {
            if (import.meta.env.DEV) console.debug('No se pudo comprobar existencia de receta:', err?.message || err);
            // leave hasReceta false — user can still create
          }
        })();
      }
    }
  }, [producto]);

  const handleInputChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value
    }));
  };

  const handleImageUpload = async (e) => {
    const files = Array.from(e.target.files);
    
    if (files.length === 0) return;

    setUploading(true);

    try {
  if (import.meta.env.DEV) console.debug('Iniciando subida de imágenes:', files.length);
      
      const uploadPromises = files.map(async (file) => {
  if (import.meta.env.DEV) console.debug('Subiendo archivo:', file.name, 'Tamaño:', file.size, 'bytes');
  const result = await admin.uploadImage(file);
  if (import.meta.env.DEV) console.debug('Resultado de subida:', result);
        return result;
      });
      
      const results = await Promise.all(uploadPromises);
      
  const urls = results.map(res => res.url);
  if (import.meta.env.DEV) console.debug('URLs recibidas:', urls);
      
      setImagenes(prev => [...prev, ...urls]);
      setImagenesPreview(prev => [...prev, ...urls]);
      
      toast.success(`${files.length} imagen(es) subida(s) exitosamente`);
    } catch (error) {
      console.error('Error completo:', error);
      if (import.meta.env.DEV) console.error('Respuesta del error:', error.response);
      const errorMessage = error.response?.data?.message 
        || error.response?.data?.error 
        || error.message 
        || 'Error al subir imágenes';
      toast.error(errorMessage);
    } finally {
      setUploading(false);
    }
  };

  const handleRemoveImage = (index) => {
    setImagenes(prev => prev.filter((_, i) => i !== index));
    setImagenesPreview(prev => prev.filter((_, i) => i !== index));
  };

  const handleAddExtra = () => {
    const nuevoExtra = { nombre: '', precio: '' };
    setFormData(prev => ({
      ...prev,
      extras_disponibles: [...prev.extras_disponibles, nuevoExtra]
    }));
  };

  const handleRemoveExtra = (index) => {
    setFormData(prev => ({
      ...prev,
      extras_disponibles: prev.extras_disponibles.filter((_, i) => i !== index)
    }));
  };

  const handleExtraChange = (index, field, value) => {
    setFormData(prev => ({
      ...prev,
      extras_disponibles: prev.extras_disponibles.map((extra, i) => 
        i === index ? { ...extra, [field]: value } : extra
      )
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!formData.nombre || !formData.precio_minorista || !formData.categorias_id) {
      toast.error('Por favor completa los campos obligatorios');
      return;
    }

    setLoading(true);

    try {
      // Procesar extras para asegurar que los precios sean números
      const extrasProcesados = formData.tiene_extras 
        ? formData.extras_disponibles.map(extra => ({
            // La descripción y la foto del extra no se editan aquí, pero se conservan
            ...(extra.descripcion ? { descripcion: extra.descripcion } : {}),
            ...(extra.imagen_url ? { imagen_url: extra.imagen_url } : {}),
            nombre: (extra.nombre || '').trim(),
            precio: parseFloat(extra.precio) || 0
          })).filter(e => e.nombre !== '') // remove empty-named extras
        : null; // send null when not applicable so backend can leave existing value

      const dataToSend = {
        ...formData,
        imagenes: imagenes,
        precio_minorista: parseFloat(formData.precio_minorista),
        precio_mayorista: formData.precio_mayorista ? parseFloat(formData.precio_mayorista) : null,
        limite_produccion: formData.limite_produccion && parseInt(formData.limite_produccion) > 0 
          ? parseInt(formData.limite_produccion) 
          : null,
        tiempo_anticipacion: formData.tiempo_anticipacion ? parseInt(formData.tiempo_anticipacion) : null,
        pedidos_hasta: formData.pedidos_hasta || null,
        etiqueta_personalizacion: formData.etiqueta_personalizacion.trim() || null,
  // Only include extras_disponibles when tiene_extras is true (or explicitly empty array when enabled)
  ...(formData.tiene_extras ? { extras_disponibles: extrasProcesados || [] } : { tiene_extras: false, extras_disponibles: null }),
      };

      if (producto) {
        await admin.actualizarProducto(producto.id, dataToSend);
        toast.success('Producto actualizado exitosamente');
      } else {
        await admin.crearProducto(dataToSend);
        toast.success('Producto creado exitosamente');
      }

      onGuardar();
    } catch (error) {
      toast.error(error.response?.data?.message || 'Error al guardar producto');
      console.error(error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <Form onSubmit={handleSubmit}>
      <Row>
        {/* Información Básica */}
        <Col md={6}>
          <h5 className="mb-3">Información Básica</h5>
          
          <Form.Group className="mb-3">
            <Form.Label>Categoría *</Form.Label>
            <Form.Select
              name="categorias_id"
              value={formData.categorias_id}
              onChange={handleInputChange}
              required
            >
              <option value="">Selecciona una categoría</option>
              {categorias.map(cat => (
                <option key={cat.id} value={cat.id}>{cat.nombre}</option>
              ))}
            </Form.Select>
          </Form.Group>

          <Form.Group className="mb-3">
            <Form.Label>Nombre del Producto *</Form.Label>
            <Form.Control
              type="text"
              name="nombre"
              value={formData.nombre}
              onChange={handleInputChange}
              placeholder="Ej: Pan de Maíz"
              required
            />
          </Form.Group>

          <Form.Group className="mb-3">
            <Form.Label>Descripción Corta</Form.Label>
            <Form.Control
              as="textarea"
              rows={2}
              name="descripcion_corta"
              value={formData.descripcion_corta}
              onChange={handleInputChange}
              placeholder="Breve descripción para tarjetas"
            />
          </Form.Group>

          <Form.Group className="mb-3">
            <Form.Label>Descripción Completa</Form.Label>
            <Form.Control
              as="textarea"
              rows={4}
              name="descripcion"
              value={formData.descripcion}
              onChange={handleInputChange}
              placeholder="Descripción detallada del producto"
            />
          </Form.Group>
        </Col>

        {/* Precios y Cantidades */}
        <Col md={6}>
          <h5 className="mb-3">Precios y Cantidades</h5>
          
          <Row>
            <Col md={6}>
              <Form.Group className="mb-3">
                <Form.Label>Precio Minorista (Bs.) *</Form.Label>
                <Form.Control
                  type="number"
                  step="0.01"
                  name="precio_minorista"
                  value={formData.precio_minorista}
                  onChange={handleInputChange}
                  placeholder="0.00"
                  required
                />
              </Form.Group>
            </Col>
            <Col md={6}>
              <Form.Group className="mb-3">
                <Form.Label>Precio Mayorista (Bs.)</Form.Label>
                <Form.Control
                  type="number"
                  step="0.01"
                  name="precio_mayorista"
                  value={formData.precio_mayorista}
                  onChange={handleInputChange}
                  placeholder="0.00"
                />
              </Form.Group>
            </Col>
          </Row>

          <Form.Group className="mb-3">
            <Form.Check
              type="checkbox"
              id="precio_por_confirmar"
              name="precio_por_confirmar"
              label="Precio por confirmar"
              checked={formData.precio_por_confirmar}
              onChange={handleInputChange}
            />
            <Form.Text className="text-muted">
              La tienda muestra «Precio por confirmar» y el cliente consulta por WhatsApp: no se puede pedir por la web.
            </Form.Text>
          </Form.Group>

          <Row>
            <Col md={6}>
              <Form.Group className="mb-3">
                <Form.Label>Unidad de Medida</Form.Label>
                <Form.Select
                  name="unidad_medida"
                  value={formData.unidad_medida}
                  onChange={handleInputChange}
                >
                  <option value="unidad">Unidad</option>
                  <option value="cm">Centímetro (cm)</option>
                  <option value="docena">Docena</option>
                  <option value="paquete">Paquete</option>
                  <option value="gramos">Gramos</option>
                  <option value="kilogramos">Kilogramos</option>
                  <option value="arroba">Arroba</option>
                  <option value="porcion">Porción</option>
                </Form.Select>
              </Form.Group>
            </Col>
            {/* El stock no se edita aquí: se maneja con producción y ajustes de inventario (quedan en el kardex). */}
          </Row>

          <Form.Group className="mb-3">
            <Form.Label>Presentación</Form.Label>
            <Form.Control
              type="text"
              name="presentacion"
              value={formData.presentacion}
              onChange={handleInputChange}
              placeholder="Ej: 1 Bolsa de 6 unidades"
            />
          </Form.Group>

          <Form.Group className="mb-3">
            <Form.Label>Límite de Producción Diaria</Form.Label>
            <Form.Control
              type="number"
              name="limite_produccion"
              value={formData.limite_produccion}
              onChange={handleInputChange}
              placeholder="Opcional"
              min="0"
            />
            <Form.Text className="text-muted">
              Cantidad máxima que se puede producir por día (dejar vacío si es ilimitado)
            </Form.Text>
          </Form.Group>
        </Col>
      </Row>

      {/* Opciones Adicionales */}
      <Row className="mt-3">
        <Col>
          <h5 className="mb-3">Opciones</h5>
          
          <Row>
            <Col md={3}>
              <Form.Check
                type="checkbox"
                name="esta_activo"
                label="Producto Activo"
                checked={formData.esta_activo}
                onChange={handleInputChange}
              />
            </Col>
            <Col md={3}>
              <Form.Check
                type="checkbox"
                name="es_de_temporada"
                label="Producto de Temporada"
                checked={formData.es_de_temporada}
                onChange={handleInputChange}
              />
            </Col>
            <Col md={6}>
              <Form.Check
                type="checkbox"
                name="requiere_tiempo_anticipacion"
                label="Requiere Tiempo de Anticipación"
                checked={formData.requiere_tiempo_anticipacion}
                onChange={handleInputChange}
              />
            </Col>
          </Row>

          <hr className="my-3" />

          <h6 className="mb-3">Opciones de Entrega</h6>
          <Alert variant="info" className="mb-3">
            <small>
              <strong>Recojo en sucursal:</strong> Siempre disponible para todos los productos<br/>
              <strong>Delivery local:</strong> Activar si se puede entregar a domicilio en la zona<br/>
              <strong>Envío nacional:</strong> Activar solo para productos que se pueden enviar a todo el país
            </small>
          </Alert>

          <Row>
            <Col md={6}>
              <Form.Check
                type="checkbox"
                name="permite_delivery"
                label="Permite Delivery Local"
                checked={formData.permite_delivery}
                onChange={handleInputChange}
              />
            </Col>
            <Col md={6}>
              <Form.Check
                type="checkbox"
                name="permite_envio_nacional"
                label="Permite Envío Nacional"
                checked={formData.permite_envio_nacional}
                onChange={handleInputChange}
              />
            </Col>
          </Row>

          {formData.requiere_tiempo_anticipacion && (
            <Row className="mt-3">
              <Col md={6}>
                <Form.Group>
                  <Form.Label>Tiempo de Anticipación</Form.Label>
                  <Form.Control
                    type="number"
                    name="tiempo_anticipacion"
                    value={formData.tiempo_anticipacion}
                    onChange={handleInputChange}
                    placeholder="Ej: 24"
                  />
                </Form.Group>
              </Col>
              <Col md={6}>
                <Form.Group>
                  <Form.Label>Unidad de Tiempo</Form.Label>
                  <Form.Select
                    name="unidad_tiempo"
                    value={formData.unidad_tiempo}
                    onChange={handleInputChange}
                  >
                    <option value="horas">Horas</option>
                    <option value="dias">Días</option>
                    <option value="semanas">Semanas</option>
                  </Form.Select>
                </Form.Group>
              </Col>
            </Row>
          )}

          <Row className="mt-3">
            <Col md={6}>
              <Form.Group className="mb-3">
                <Form.Label htmlFor="pedidos_hasta">Recibir pedidos hasta</Form.Label>
                <Form.Control
                  type="date"
                  id="pedidos_hasta"
                  name="pedidos_hasta"
                  value={formData.pedidos_hasta}
                  onChange={handleInputChange}
                />
                <Form.Text className="text-muted">
                  Último día en que la web acepta pedidos. Vacío: sin fecha límite.
                </Form.Text>
              </Form.Group>
            </Col>
            <Col md={6}>
              <Form.Group className="mb-3">
                <Form.Label htmlFor="etiqueta_personalizacion">Pedir al cliente</Form.Label>
                <Form.Control
                  type="text"
                  id="etiqueta_personalizacion"
                  name="etiqueta_personalizacion"
                  value={formData.etiqueta_personalizacion}
                  onChange={handleInputChange}
                  maxLength={60}
                  placeholder="Ej: Nombre del difunto"
                />
                <Form.Text className="text-muted">
                  El cliente debe escribir este dato al pedir. Vacío: no se pide nada.
                </Form.Text>
              </Form.Group>
            </Col>
          </Row>
        </Col>
      </Row>

      {/* Extras Disponibles */}
      <Row className="mt-4">
        <Col>
          <h5 className="mb-3">Extras del Producto</h5>
          
          <Form.Check
            type="checkbox"
            name="tiene_extras"
            label="Este producto tiene extras disponibles"
            checked={formData.tiene_extras}
            onChange={handleInputChange}
            className="mb-3"
          />

          {formData.tiene_extras && (
            <>
              <Alert variant="info" className="mb-3">
                <small>
                  Los extras son opciones adicionales que el cliente puede agregar al producto (ej: extra de queso, tamaño grande, decoración especial, etc.)
                </small>
              </Alert>

              {formData.extras_disponibles.map((extra, index) => (
                <Row key={index} className="mb-2 align-items-end">
                  <Col md={6}>
                    <Form.Group>
                      <Form.Label>Nombre del Extra</Form.Label>
                      <Form.Control
                        type="text"
                        value={extra.nombre}
                        onChange={(e) => handleExtraChange(index, 'nombre', e.target.value)}
                        placeholder="Ej: Extra de Queso"
                      />
                    </Form.Group>
                  </Col>
                  <Col md={4}>
                    <Form.Group>
                      <Form.Label>Precio Extra (Bs.)</Form.Label>
                      <Form.Control
                        type="number"
                        step="0.01"
                        min="0"
                        value={extra.precio}
                        onChange={(e) => handleExtraChange(index, 'precio', e.target.value)}
                        placeholder="0.00"
                      />
                    </Form.Group>
                  </Col>
                  <Col md={2}>
                    <Button 
                      variant="danger" 
                      size="sm"
                      onClick={() => handleRemoveExtra(index)}
                      className="w-100"
                    >
                      Eliminar
                    </Button>
                  </Col>
                </Row>
              ))}

              <Button 
                variant="outline-primary" 
                size="sm"
                onClick={handleAddExtra}
                className="mt-2"
              >
                + Agregar Extra
              </Button>
            </>
          )}
        </Col>
      </Row>

      {/* Imágenes */}
      <Row className="mt-4">
        <Col>
          <h5 className="mb-3">Imágenes del Producto</h5>
          
          <Form.Group className="mb-3">
            <Form.Label>Subir Imágenes</Form.Label>
            <Form.Control
              type="file"
              accept="image/*"
              multiple
              onChange={handleImageUpload}
              disabled={uploading}
            />
            <Form.Text className="text-muted">
              Puedes subir múltiples imágenes. La primera será la imagen principal.
            </Form.Text>
          </Form.Group>

          {uploading && <Alert variant="info">Subiendo imágenes...</Alert>}

          {imagenesPreview.length > 0 && (
            <Row>
              {imagenesPreview.map((url, index) => (
                <Col key={index} xs={6} md={3} className="mb-3">
                  <div className="position-relative">
                    <Image 
                      src={url} 
                      rounded 
                      loading="lazy"
                      decoding="async"
                      style={{ width: '100%', height: '150px', objectFit: 'cover' }}
                      onError={usarImagenRespaldo}
                    />
                    {index === 0 && (
                      <Badge 
                        bg="primary" 
                        className="position-absolute top-0 start-0 m-2"
                      >
                        Principal
                      </Badge>
                    )}
                    <Button
                      variant="danger"
                      size="sm"
                      className="position-absolute top-0 end-0 m-2"
                      onClick={() => handleRemoveImage(index)}
                    >
                      ✕
                    </Button>
                  </div>
                </Col>
              ))}
            </Row>
          )}
        </Col>
      </Row>

      {/* Botones */}
      <Row className="mt-4">
        <Col className="d-flex justify-content-between align-items-center">
          {/* Botón de receta (solo para productos existentes) */}
          {producto && producto.id && (
            <Button
              variant="outline-success"
              onClick={async () => {
                // If producto.receta is not present, attempt to fetch it from the inventory/recetas endpoint
                if (!producto.receta) {
                  try {
                    // dynamic import to avoid circular requires in some bundlers
                    const { admin } = await import('../../services/api');
                    const resp = await admin.getRecetas({ producto_id: producto.id, per_page: 1 });
                    // resp may be a paginator { data: [...] } or an array
                    const found = resp?.data?.[0] || (Array.isArray(resp) ? resp[0] : null);
                    setRecetaActual(found || null);
                  } catch (err) {
                    // ignore — we'll open modal to create a new receta
                    if (import.meta.env.DEV) console.debug('No se pudo cargar receta al abrir modal:', err?.message || err);
                    setRecetaActual(null);
                  }
                } else {
                  setRecetaActual(producto.receta);
                }
                setShowRecetaModal(true);
              }}
            >
              <i className="bi bi-book me-2"></i>
              {/* Mostrar 'Editar Receta' si ya tenemos receta cargada en estado (recetaActual)
                  o si el objeto producto contiene la relación/clave `receta` o `receta_id`. Esto
                  cubre casos donde la relación no fue eager-loaded pero existe en la BD. */}
              {(recetaActual || hasReceta || (producto && (producto.receta || producto.receta_id))) ? 'Editar Receta' : 'Crear Receta'}
            </Button>
          )}
          
          <div className="d-flex gap-2 ms-auto">
            <Button 
              variant="secondary" 
              onClick={onCancelar}
              disabled={loading}
            >
              Cancelar
            </Button>
            <Button 
              type="submit" 
              disabled={loading || uploading}
              style={{ backgroundColor: '#8b6f47', borderColor: '#8b6f47' }}
            >
              {loading ? 'Guardando...' : (producto ? 'Actualizar' : 'Crear Producto')}
            </Button>
          </div>
        </Col>
      </Row>

      {/* Modal de Receta */}
      <Modal show={showRecetaModal} onHide={() => setShowRecetaModal(false)} size="lg">
        <Modal.Header closeButton>
          <Modal.Title>Gestionar Receta</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {producto && producto.id ? (
            <RecetaForm
              productoId={producto.id}
              productoNombre={producto.nombre || ''}
              receta={recetaActual}
              onGuardar={(result) => {
                setShowRecetaModal(false);
                toast.success('Receta guardada correctamente');
                // Recargar producto para actualizar receta
                if (onGuardar) {
                  onGuardar(result);
                }
              }}
              onCancelar={() => setShowRecetaModal(false)}
            />
          ) : (
            <div className="text-center p-4">Cargando información del producto...</div>
          )}
        </Modal.Body>
      </Modal>
    </Form>
  );
};

ProductoForm.propTypes = {
  producto: PropTypes.object,
  categorias: PropTypes.array.isRequired,
  onGuardar: PropTypes.func.isRequired,
  onCancelar: PropTypes.func.isRequired,
};

export default ProductoForm;
