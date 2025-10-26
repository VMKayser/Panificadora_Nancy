import { useState, useEffect } from 'react';
import { Container, Row, Col, Card, Table, Button, Modal, Form, Badge, Tabs, Tab, Alert, Spinner } from 'react-bootstrap';
import { admin } from '../../services/api';
import { toast } from 'react-toastify';

export default function MovimientosInventarioPanel() {
  const [activeTab, setActiveTab] = useState('materias-primas');
  const [materiasPrimas, setMateriasPrimas] = useState([]);
  const [productosFinales, setProductosFinales] = useState([]);
  const [panaderos, setPanaderos] = useState([]);
  const [crearProduccionAsignada, setCrearProduccionAsignada] = useState(false);
  const [multiMode, setMultiMode] = useState(false);
  const [multiLines, setMultiLines] = useState([]);
  const [stopOnError, setStopOnError] = useState(false);
  const [multiResults, setMultiResults] = useState([]);
  const [produccionForm, setProduccionForm] = useState({
    panadero_id: '',
    fecha_produccion: '',
    hora_inicio: '',
    hora_fin: '',
    unidad: 'unidades'
    // cantidad_producida y harina_real_usada eliminados - se calcula automáticamente
  });
  const [produccionExtras, setProduccionExtras] = useState([]); // extra ingredientes for single production
  const [produccionErrorDetails, setProduccionErrorDetails] = useState([]);
  const [produccionErrorMessage, setProduccionErrorMessage] = useState('');
  const [movimientos, setMovimientos] = useState([]);
  const [loading, setLoading] = useState(false);
  const [showModal, setShowModal] = useState(false);
  const [tipoMovimiento, setTipoMovimiento] = useState('entrada');
  const [selectedItem, setSelectedItem] = useState(null);
  
  const [formData, setFormData] = useState({
    cantidad: '',
    costo_unitario: '',
    numero_factura: '',
    motivo: '',
    observaciones: ''
  });
  const [isSubmitting, setIsSubmitting] = useState(false);

  useEffect(() => {
    cargarDatos();
  }, [activeTab]);

  const cargarDatos = async () => {
    setLoading(true);
    try {
      if (activeTab === 'materias-primas') {
        const data = await admin.getMateriasPrimas();
        setMateriasPrimas(Array.isArray(data) ? data : data.data || []);
      } else {
        const data = await admin.getProductosFinales();
        setProductosFinales(Array.isArray(data) ? data : []);
      }
      // Cargar panaderos para el select (si no están ya cargados)
      if (panaderos.length === 0) {
        try {
          const p = await admin.getPanaderos({ activo: 1, per_page: 100 });
          setPanaderos(Array.isArray(p) ? p : (p.data || p));
        } catch (err) {
          console.warn('No se pudieron cargar panaderos:', err.message || err);
        }
      }
    } catch (error) {
      console.error('Error cargando datos:', error);
      toast.error('Error al cargar datos');
    } finally {
      setLoading(false);
    }
  };

  const handleOpenModal = (item, tipo) => {
    setSelectedItem(item);
    setTipoMovimiento(tipo);
    setFormData({
      cantidad: '',
      costo_unitario: tipo === 'entrada' ? (item.costo_unitario || '') : '',
      numero_factura: '',
      motivo: '',
      observaciones: ''
    });
    // reset producción asignada
    setCrearProduccionAsignada(false);
    setMultiMode(false);
    setMultiLines([]);
    setMultiResults([]);
    setStopOnError(false);
    setProduccionForm({
      panadero_id: '',
      fecha_produccion: new Date().toISOString().slice(0,10),
      hora_inicio: '',
      hora_fin: '',
      unidad: 'unidades'
      // cantidad_producida y harina_real_usada eliminados
    });
    setIsSubmitting(false);
    setProduccionErrorDetails([]);
    setProduccionErrorMessage('');
    setShowModal(true);
  };

  const handleCloseModal = () => {
    setShowModal(false);
    setSelectedItem(null);
    setFormData({
      cantidad: '',
      costo_unitario: '',
      numero_factura: '',
      motivo: '',
      observaciones: ''
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setIsSubmitting(true);
    
    try {
      if (activeTab === 'materias-primas') {
        if (tipoMovimiento === 'entrada') {
          // Registrar compra
          await admin.registrarCompraMateriaPrima(selectedItem.id, {
            cantidad: parseFloat(formData.cantidad),
            costo_unitario: parseFloat(formData.costo_unitario),
            numero_factura: formData.numero_factura,
            observaciones: formData.observaciones?.trim() || null
          });
          toast.success('Compra registrada exitosamente');
        } else {
          // Ajuste de stock (salida o corrección)
          const nuevoStock = selectedItem.stock_actual - parseFloat(formData.cantidad);
          await admin.ajustarStockMateriaPrima(selectedItem.id, {
            nuevo_stock: nuevoStock,
            motivo: formData.motivo || 'merma',
            observaciones: formData.observaciones?.trim() || null
          });
          toast.success('Salida registrada exitosamente');
        }
      } else {
        // Productos finales - ajuste simple o entrada que puede crear producción
        const cambio = tipoMovimiento === 'entrada' 
          ? parseFloat(formData.cantidad)
          : -parseFloat(formData.cantidad);
        const nuevoStock = (parseFloat(selectedItem.stock_actual || 0) || 0) + cambio;

        // Si es entrada y se solicita crear producción asignada, intentamos crear una producción
        if (tipoMovimiento === 'entrada' && (crearProduccionAsignada || multiMode)) {
          // Validaciones mínimas
          // Multi-mode: procesar varias líneas secuencialmente
          const { crearProduccion } = await import('../../services/api');

          // In multiMode we require a panadero selected globally or per-line (here global)
          if (multiMode && !produccionForm.panadero_id) {
            throw new Error('Seleccione un panadero para asignar las producciones (modo multi)');
          }

          if (multiMode) {
            // Limit lines to a small batch (2-5) to keep operations safe
            const maxLines = 5;
            if (multiLines.length === 0) {
              throw new Error('Agregue al menos una línea de producción en modo multi-producto');
            }
            if (multiLines.length > maxLines) {
              throw new Error('Máximo 5 líneas permitidas en modo multi-producto');
            }

            const results = [];
            for (let i = 0; i < multiLines.length; i++) {
              const line = multiLines[i];
              // build payload for this line
              const payload = {
                producto_id: line.producto_id,
                fecha_produccion: line.fecha_produccion,
                hora_inicio: line.hora_inicio || null,
                hora_fin: line.hora_fin || null,
                harina_real_usada: 0,
                cantidad_producida: line.cantidad_producida,
                unidad: line.unidad || 'unidades',
                panadero_id: line.panadero_id || produccionForm.panadero_id,
                observaciones: (line.observaciones || formData.observaciones)?.trim() || null
              };

              try {
                const res = await crearProduccion(payload);
                results.push({ success: true, data: res?.data || res, mensaje: res?.message || 'OK' });
                // small delay could be added if rate-limiting is a concern
              } catch (err) {
                if (err.response?.status === 422) {
                  const data = err.response.data || {};
                  const faltantes = data.ingredientes_faltantes || data.errors?.ingredientes_faltantes || [];
                  results.push({ success: false, mensaje: data.message || 'Ingredientes faltantes', detalles: Array.isArray(faltantes) ? faltantes : [] });
                } else {
                  results.push({ success: false, mensaje: err.message || 'Error desconocido' });
                }
                if (stopOnError) {
                  break;
                }
              }
            }
            setMultiResults(results);

            // Show summary toast
            const succ = results.filter(r => r.success).length;
            const fail = results.length - succ;
            if (succ > 0) toast.success(`${succ} producciones creadas correctamente`);
            if (fail > 0) toast.error(`${fail} producciones fallaron. Revisa los detalles en el modal.`);

            // If all succeeded, close modal
            const allOk = results.length > 0 && results.every(r => r.success);
            if (allOk) {
              handleCloseModal();
            }
            // refresh data so stocks are up-to-date for succeeded lines
            cargarDatos();
            setIsSubmitting(false);
            return;
          } else {
            // single production path (backwards compatible)
            if (!produccionForm.panadero_id) {
              throw new Error('Seleccione un panadero para asignar la producción');
            }
            const payload = {
              producto_id: selectedItem.producto_id || selectedItem.producto?.id,
              fecha_produccion: produccionForm.fecha_produccion,
              hora_inicio: produccionForm.hora_inicio || null,
              hora_fin: produccionForm.hora_fin || null,
              harina_real_usada: 0, // Calculado automáticamente por el backend
              cantidad_producida: formData.cantidad, // Usar la cantidad del formulario principal
              unidad: produccionForm.unidad || 'unidades',
              panadero_id: produccionForm.panadero_id,
              observaciones: formData.observaciones?.trim() || null,
              ingredientes: produccionExtras.filter(i => i.materia_prima_id && i.cantidad).map(i => ({ materia_prima_id: i.materia_prima_id, cantidad: parseFloat(i.cantidad) }))
            };
            let res;
            let produccionId = null;
            try {
              res = await crearProduccion(payload);
              produccionId = res?.data?.id || res?.id || null;
              toast.success(res?.message || 'Producción creada y asignada al panadero');
            } catch (err) {
              if (err.response?.status === 422) {
                const data = err.response.data || {};
                setProduccionErrorMessage(data.message || 'Error validación');
                const faltantes = data.ingredientes_faltantes || data.errors?.ingredientes_faltantes || [];
                setProduccionErrorDetails(Array.isArray(faltantes) ? faltantes : []);
                setIsSubmitting(false);
                return;
              }
              throw err;
            }
            
            // Si la producción fue exitosa, cerrar modal y recargar
            if (produccionId) {
              handleCloseModal();
              cargarDatos();
              setIsSubmitting(false);
              return;
            }
          }
        }

        // Registrar ajuste de inventario del producto
        // Solo llegar aquí si NO se creó producción o si fue modo multi (ya manejado arriba)
        if (tipoMovimiento === 'entrada' && crearProduccionAsignada) {
          // Ya procesado arriba
          toast.success('Producción creada y stock actualizado por backend');
        } else {
          // Ajuste normal (no creación de producción)
          const ajustePayload = {
            nuevo_stock: nuevoStock,
            motivo: formData.motivo || 'ajuste_manual',
            observaciones: formData.observaciones?.trim() || null
          };
          await admin.ajustarInventarioProducto(selectedItem.producto_id, ajustePayload);
          toast.success('Ajuste registrado exitosamente');
        }
      }
      
      handleCloseModal();
      cargarDatos();
    } catch (error) {
      console.error('Error registrando movimiento:', error);
      console.error('Detalle del error:', error.response?.data);
      const errorMsg = error.response?.data?.message || error.message || 'Error al registrar movimiento';
      const errors = error.response?.data?.errors;
      if (errors) {
        const errorList = Object.values(errors).flat().join(', ');
        toast.error(`${errorMsg}: ${errorList}`);
      } else {
        toast.error(errorMsg);
      }
    }
    finally {
      setIsSubmitting(false);
    }
  };

  const renderTablaInventario = () => {
    const items = activeTab === 'materias-primas' ? materiasPrimas : productosFinales;
    
    if (loading) {
      return (
        <div className="text-center py-5">
          <Spinner animation="border" />
          <p className="mt-3">Cargando inventario...</p>
        </div>
      );
    }

    if (items.length === 0) {
      return (
        <Alert variant="info">
          No hay items en inventario. {activeTab === 'materias-primas' 
            ? 'Crea primero algunas materias primas.' 
            : 'Aún no hay productos finales en inventario.'}
        </Alert>
      );
    }

    return (
      <Table striped bordered hover responsive>
        <thead>
          <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Stock Actual</th>
            <th>Unidad</th>
            {activeTab === 'materias-primas' && <th>Costo Unit.</th>}
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          {items.map((item, index) => {
            const nombre = activeTab === 'materias-primas'
              ? item.nombre
              : (item.producto?.nombre || item.producto || 'N/A');
            const stockActual = parseFloat(item.stock_actual || 0);
            const stockMinimo = parseFloat(item.stock_minimo || 0);
            const unidad = activeTab === 'materias-primas' 
              ? item.unidad_medida 
              : item.producto?.unidad_medida || 'unidad';
            
            let estadoBadge = 'success';
            let estadoTexto = 'Normal';
            
            if (stockActual === 0) {
              estadoBadge = 'danger';
              estadoTexto = 'Agotado';
            } else if (stockActual <= stockMinimo) {
              estadoBadge = 'warning';
              estadoTexto = 'Stock Bajo';
            }
            
            const rowKey = item.id ?? item.producto_id ?? item.producto?.id ?? `${activeTab}-${index}`;

            // Decide how to display stock: materias primas -> max 2 decimals; productos finales -> show full value
            const stockDisplay = activeTab === 'materias-primas'
              ? stockActual.toFixed(2)
              : (Number.isInteger(stockActual) ? stockActual.toString() : stockActual.toString());

            return (
              <tr key={rowKey}>
                <td>{activeTab === 'materias-primas' ? item.id : (item.producto_id ?? '—')}</td>
                <td><strong>{nombre}</strong></td>
                <td>
                  <Badge bg={estadoBadge}>
                    {stockDisplay} {unidad}
                  </Badge>
                </td>
                <td>{unidad}</td>
                {activeTab === 'materias-primas' && (
                  <td>Bs. {parseFloat(item.costo_unitario || 0).toFixed(2)}</td>
                )}
                <td>
                  <Badge bg={estadoBadge}>{estadoTexto}</Badge>
                </td>
                <td>
                  <Button
                    variant="success"
                    size="sm"
                    className="me-2"
                    onClick={() => handleOpenModal(item, 'entrada')}
                  >
                    <i className="bi bi-plus-circle"></i> Entrada
                  </Button>
                  <Button
                    variant="danger"
                    size="sm"
                    onClick={() => handleOpenModal(item, 'salida')}
                  >
                    <i className="bi bi-dash-circle"></i> Salida
                  </Button>
                </td>
              </tr>
            );
          })}
        </tbody>
      </Table>
    );
  };

  return (
    <Container fluid className="py-4">
      <Row className="mb-4">
        <Col>
          <h3>
            <i className="bi bi-arrow-left-right me-2"></i>
            Movimientos de Inventario
          </h3>
          <p className="text-muted">Registra entradas y salidas de stock</p>
        </Col>
      </Row>

      <Tabs activeKey={activeTab} onSelect={(k) => setActiveTab(k)} className="mb-4">
        <Tab eventKey="materias-primas" title="📦 Materias Primas">
          {renderTablaInventario()}
        </Tab>
        <Tab eventKey="productos-finales" title="🍞 Productos Finales">
          {renderTablaInventario()}
        </Tab>
      </Tabs>

      {/* Modal para registrar entrada/salida */}
      <Modal show={showModal} onHide={handleCloseModal}>
        <Modal.Header closeButton>
          <Modal.Title>
            {tipoMovimiento === 'entrada' ? '➕ Registrar Entrada' : '➖ Registrar Salida'}
            {selectedItem && (
              <div className="text-muted fs-6 mt-1">
                {activeTab === 'materias-primas'
                  ? selectedItem.nombre
                  : (selectedItem.producto?.nombre || selectedItem.producto || '')}
              </div>
            )}
          </Modal.Title>
        </Modal.Header>
        <Form onSubmit={handleSubmit}>
          <Modal.Body>
            {selectedItem && (
              <Alert variant="info" className="mb-3">
                <strong>Stock actual:</strong> {
                  activeTab === 'materias-primas'
                    ? Number(parseFloat(selectedItem.stock_actual || 0)).toFixed(2)
                    : (selectedItem.stock_actual ?? selectedItem.stock ?? '0')
                } {
                  activeTab === 'materias-primas' 
                    ? selectedItem.unidad_medida 
                    : selectedItem.producto?.unidad_medida
                }
              </Alert>
            )}

            <Form.Group className="mb-3">
              <Form.Label>Cantidad *</Form.Label>
              <Form.Control
                type="number"
                step={activeTab === 'materias-primas' ? '0.01' : 'any'}
                min={activeTab === 'materias-primas' ? '0.01' : '0'}
                value={formData.cantidad}
                onChange={(e) => setFormData({...formData, cantidad: e.target.value})}
                required
                placeholder={activeTab === 'materias-primas' ? '0.00' : 'Ej: 1'}
              />
            </Form.Group>

            {tipoMovimiento === 'entrada' && activeTab === 'materias-primas' && (
              <>
                <Form.Group className="mb-3">
                  <Form.Label>Costo Unitario *</Form.Label>
                  <Form.Control
                    type="number"
                    step="0.01"
                    min="0"
                    value={formData.costo_unitario}
                    onChange={(e) => setFormData({...formData, costo_unitario: e.target.value})}
                    required
                    placeholder="0.00"
                  />
                </Form.Group>

                <Form.Group className="mb-3">
                  <Form.Label>Número de Factura</Form.Label>
                  <Form.Control
                    type="text"
                    value={formData.numero_factura}
                    onChange={(e) => setFormData({...formData, numero_factura: e.target.value})}
                    placeholder="Ej: FAC-001"
                  />
                </Form.Group>
              </>
            )}

            {tipoMovimiento === 'salida' && (
              <Form.Group className="mb-3">
                <Form.Label>Motivo *</Form.Label>
                <Form.Select
                  value={formData.motivo}
                  onChange={(e) => setFormData({...formData, motivo: e.target.value})}
                  required
                >
                  <option value="">Seleccione...</option>
                  <option value="merma">Merma / Desperdicio</option>
                  <option value="inventario_fisico">Ajuste por inventario físico</option>
                  <option value="correccion">Corrección</option>
                  <option value="devolucion">Devolución</option>
                  <option value="degustacion">Degustación / Muestra</option>
                </Form.Select>
              </Form.Group>
            )}

            <Form.Group className="mb-3">
              <Form.Label>Observaciones</Form.Label>
              <Form.Control
                as="textarea"
                rows={3}
                value={formData.observaciones}
                onChange={(e) => setFormData({...formData, observaciones: e.target.value})}
                placeholder="Detalles adicionales del movimiento..."
              />
            </Form.Group>

            {/* Opción para crear producción asignada cuando es entrada de producto final */}
            {tipoMovimiento === 'entrada' && activeTab === 'productos-finales' && (
              <>
                <Form.Group className="mb-3 d-flex align-items-center">
                  <Form.Check
                    type="checkbox"
                    id="crear-produccion-asignada"
                    label="Crear producción y asignar a panadero"
                    checked={crearProduccionAsignada}
                    onChange={(e) => {
                      setCrearProduccionAsignada(e.target.checked);
                      // La cantidad ya está en formData.cantidad
                    }}
                  />
                </Form.Group>

                {crearProduccionAsignada && (
                  <Card className="mb-3 p-2">
                        <div className="mb-2 text-muted small">Al crear la producción se intentará procesar la receta y descontar las materias primas. Si falta stock de ingredientes, la API devolverá un error y no se completará la operación.</div>
                        {produccionErrorMessage && (
                          <Alert variant="danger">
                            <strong>{produccionErrorMessage}</strong>
                            {produccionErrorDetails.length > 0 && (
                              <div className="mt-2">
                                <small>Ingredientes faltantes:</small>
                                <ul className="mb-0">
                                  {produccionErrorDetails.map((ing, idx) => (
                                    <li key={idx}>{ing.nombre || ing.ingrediente || ing.nombre_ingrediente} - necesario: {ing.necesario} - disponible: {ing.disponible}</li>
                                  ))}
                                </ul>
                              </div>
                            )}
                          </Alert>
                        )}
                    <Form.Group className="mb-2">
                      <Form.Label>Panadero *</Form.Label>
                      <Form.Select
                        value={produccionForm.panadero_id}
                        onChange={(e) => setProduccionForm({...produccionForm, panadero_id: e.target.value})}
                        required
                      >
                        <option value="">Seleccione...</option>
                        {panaderos.map(p => (
                          <option key={p.id} value={p.id}>{p.user?.name || p.name || p.nombre || p.id}</option>
                        ))}
                      </Form.Select>
                    </Form.Group>

                    {/* Extra ingredientes UI for single production */}
                    <Card className="mb-2 p-2 border-info">
                      <div className="mb-2 text-muted small">Ingredientes adicionales (opcional). Se descontarán del inventario junto a los de la receta. La harina sigue siendo la que determina el salario.</div>
                      {produccionExtras.map((ing, idx) => (
                        <Row key={idx} className="align-items-center mb-2">
                          <Col md={6} className="mb-2">
                            <Form.Select value={ing.materia_prima_id || ''} onChange={(e) => {
                              const clone = [...produccionExtras]; clone[idx].materia_prima_id = e.target.value; setProduccionExtras(clone);
                            }}>
                              <option value="">Seleccione materia prima...</option>
                              {materiasPrimas.map(mp => (
                                <option key={mp.id} value={mp.id}>{mp.nombre}</option>
                              ))}
                            </Form.Select>
                          </Col>
                          <Col md={4} className="mb-2">
                            <Form.Control type="number" step="0.01" value={ing.cantidad || ''} onChange={(e) => { const clone = [...produccionExtras]; clone[idx].cantidad = e.target.value; setProduccionExtras(clone); }} placeholder="Cantidad" />
                          </Col>
                          <Col md={2} className="text-end">
                            <Button size="sm" variant="outline-danger" onClick={() => { setProduccionExtras(produccionExtras.filter((_, i) => i !== idx)); }}>Eliminar</Button>
                          </Col>
                        </Row>
                      ))}
                      <Button size="sm" variant="outline-primary" onClick={() => setProduccionExtras([...produccionExtras, { materia_prima_id: '', cantidad: '' }])}>
                        <i className="bi bi-plus"></i> Añadir ingrediente
                      </Button>
                    </Card>

                    <Form.Group className="mb-2 d-flex align-items-center">
                      <Form.Check
                        type="checkbox"
                        id="multi-mode"
                        label="Modo multi-producto (2-5 líneas)"
                        checked={multiMode}
                        onChange={(e) => setMultiMode(e.target.checked)}
                      />
                      <Form.Text className="text-muted ms-3">Permite crear varias producciones en una sola operación.</Form.Text>
                    </Form.Group>

                    {multiMode && (
                      <Card className="mb-2 p-2 border-secondary">
                        <div className="mb-2 text-muted small">Agrega hasta 5 líneas. Cada línea crea una producción independiente. Puedes usar el mismo panadero para todas o seleccionar por línea.</div>
                        <div className="mb-2">
                          <Button size="sm" variant="outline-primary" onClick={() => {
                            if (multiLines.length >= 5) return toast.info('Máximo 5 líneas');
                            setMultiLines([...multiLines, {
                              producto_id: selectedItem.producto_id || selectedItem.producto?.id,
                              producto_nombre: selectedItem.producto?.nombre || selectedItem.producto || selectedItem.nombre,
                              cantidad_producida: formData.cantidad || '',
                              unidad: produccionForm.unidad || 'unidades',
                              harina_real_usada: produccionForm.harina_real_usada || '',
                              panadero_id: produccionForm.panadero_id || ''
                            }]);
                          }}>
                            <i className="bi bi-plus"></i> Añadir línea
                          </Button>
                          <Form.Check className="ms-3 d-inline-block" type="checkbox" id="stop-on-error" label="Detener en primer error" checked={stopOnError} onChange={(e) => setStopOnError(e.target.checked)} />
                        </div>

                        {multiLines.map((line, idx) => (
                          <Card key={idx} className="mb-2 p-2">
                            <Row className="align-items-center">
                              <Col md={4} className="mb-2">
                                <Form.Label>Producto</Form.Label>
                                <Form.Control type="text" value={line.producto_nombre} readOnly />
                              </Col>
                              <Col md={2} className="mb-2">
                                <Form.Label>Cantidad</Form.Label>
                                <Form.Control type="number" step="any" value={line.cantidad_producida} onChange={(e) => {
                                  const clone = [...multiLines]; clone[idx].cantidad_producida = e.target.value; setMultiLines(clone);
                                }} />
                              </Col>
                              <Col md={2} className="mb-2">
                                <Form.Label>Unidad</Form.Label>
                                <Form.Control type="text" value={line.unidad} onChange={(e) => { const clone = [...multiLines]; clone[idx].unidad = e.target.value; setMultiLines(clone); }} />
                              </Col>
                              <Col md={2} className="mb-2">
                                <Form.Label>Harina (kg)</Form.Label>
                                <Form.Control type="number" step="0.01" value={line.harina_real_usada} onChange={(e) => { const clone = [...multiLines]; clone[idx].harina_real_usada = e.target.value; setMultiLines(clone); }} />
                              </Col>
                              <Col md={2} className="mb-2 text-end">
                                <Button variant="outline-danger" size="sm" onClick={() => { const clone = multiLines.filter((_, i) => i !== idx); setMultiLines(clone); }}>Eliminar</Button>
                              </Col>
                              <Col md={12} className="mt-2">
                                <Form.Label>Observaciones (opcional)</Form.Label>
                                <Form.Control type="text" value={line.observaciones || ''} onChange={(e) => { const clone = [...multiLines]; clone[idx].observaciones = e.target.value; setMultiLines(clone); }} />
                              </Col>
                              <Col md={12} className="mt-2">
                                <Form.Label>Panadero (opcional, si vacío se usa el panadero global)</Form.Label>
                                <Form.Select value={line.panadero_id || ''} onChange={(e) => { const clone = [...multiLines]; clone[idx].panadero_id = e.target.value; setMultiLines(clone); }}>
                                  <option value="">Usar panadero global</option>
                                  {panaderos.map(p => (
                                    <option key={p.id} value={p.id}>{p.user?.name || p.name || p.nombre || p.id}</option>
                                  ))}
                                </Form.Select>
                              </Col>
                            </Row>
                            {/* show per-line result if exists */}
                            {multiResults[idx] && (
                              <Alert variant={multiResults[idx].success ? 'success' : 'danger'} className="mt-2">
                                {multiResults[idx].success ? `OK: ${multiResults[idx].mensaje}` : `Error: ${multiResults[idx].mensaje}`}
                                {multiResults[idx].detalles && multiResults[idx].detalles.length > 0 && (
                                  <ul className="mb-0 mt-2 small">
                                    {multiResults[idx].detalles.map((d, di) => (
                                      <li key={di}>{d.nombre || d.ingrediente || d.nombre_ingrediente} — necesario: {d.necesario} — disponible: {d.disponible}</li>
                                    ))}
                                  </ul>
                                )}
                              </Alert>
                            )}
                          </Card>
                        ))}
                      </Card>
                    )}
                    <Row>
                      <Col md={6} className="mb-2">
                        <Form.Label>Fecha producción</Form.Label>
                        <Form.Control
                          type="date"
                          value={produccionForm.fecha_produccion}
                          onChange={(e) => setProduccionForm({...produccionForm, fecha_produccion: e.target.value})}
                        />
                      </Col>
                    </Row>

                    {/* Nota: La cantidad ya se especifica arriba en el campo "Cantidad" principal */}
                  </Card>
                )}
              </>
            )}
          </Modal.Body>
          <Modal.Footer>
            <Button variant="secondary" onClick={handleCloseModal}>
              Cancelar
            </Button>
            {/* Retry failed lines button */}
            {multiResults && multiResults.some(r => !r.success) && (
              <Button variant="warning" size="sm" onClick={async () => {
                // Reintentar solo las líneas fallidas
                const failedIndexes = multiResults.map((r, i) => (!r.success ? i : -1)).filter(i => i >= 0);
                if (failedIndexes.length === 0) return toast.info('No hay líneas fallidas para reintentar');
                setIsSubmitting(true);
                const { crearProduccion } = await import('../../services/api');
                const newResults = [...multiResults];
                for (const idx of failedIndexes) {
                  const line = multiLines[idx];
                  const payload = {
                    producto_id: line.producto_id,
                    fecha_produccion: line.fecha_produccion || produccionForm.fecha_produccion,
                    hora_inicio: line.hora_inicio || produccionForm.hora_inicio || null,
                    hora_fin: line.hora_fin || produccionForm.hora_fin || null,
                    harina_real_usada: line.harina_real_usada || produccionForm.harina_real_usada || 0,
                    cantidad_producida: line.cantidad_producida,
                    unidad: line.unidad || produccionForm.unidad || 'unidades',
                    panadero_id: line.panadero_id || produccionForm.panadero_id,
                    observaciones: (line.observaciones || formData.observaciones)?.trim() || null
                  };
                  try {
                    const res = await crearProduccion(payload);
                    newResults[idx] = { success: true, data: res?.data || res, mensaje: res?.message || 'OK' };
                  } catch (err) {
                    if (err.response?.status === 422) {
                      const data = err.response.data || {};
                      const faltantes = data.ingredientes_faltantes || data.errors?.ingredientes_faltantes || [];
                      newResults[idx] = { success: false, mensaje: data.message || 'Ingredientes faltantes', detalles: Array.isArray(faltantes) ? faltantes : [] };
                    } else {
                      newResults[idx] = { success: false, mensaje: err.message || 'Error desconocido' };
                    }
                  }
                }
                setMultiResults(newResults);
                const succ = newResults.filter(r => r.success).length;
                const fail = newResults.length - succ;
                if (succ > 0) toast.success(`${succ} producciones creadas correctamente (reintento)`);
                if (fail > 0) toast.error(`${fail} producciones siguen fallando`);
                cargarDatos();
                setIsSubmitting(false);
              }}>Reintentar fallidas</Button>
            )}
            <Button variant="primary" type="submit" disabled={isSubmitting}>
              {isSubmitting ? (
                <>
                  <Spinner animation="border" size="sm" className="me-2" />
                  Procesando...
                </>
              ) : (
                (tipoMovimiento === 'entrada' ? 'Registrar Entrada' : 'Registrar Salida')
              )}
            </Button>
          </Modal.Footer>
        </Form>
      </Modal>
    </Container>
  );
}
