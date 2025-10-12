import { useState, useEffect } from 'react';
import { Container, Row, Col, Card, Table, Button, Modal, Form, Badge, Tabs, Tab, Alert, Spinner } from 'react-bootstrap';
import { admin } from '../../services/api';
import { toast } from 'react-toastify';

export default function MovimientosInventarioPanel() {
  const [activeTab, setActiveTab] = useState('materias-primas');
  const [materiasPrimas, setMateriasPrimas] = useState([]);
  const [productosFinales, setProductosFinales] = useState([]);
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
    
    try {
      if (activeTab === 'materias-primas') {
        if (tipoMovimiento === 'entrada') {
          // Registrar compra
          await admin.registrarCompraMateriaPrima(selectedItem.id, {
            cantidad: parseFloat(formData.cantidad),
            costo_unitario: parseFloat(formData.costo_unitario),
            numero_factura: formData.numero_factura,
            observaciones: formData.observaciones
          });
          toast.success('Compra registrada exitosamente');
        } else {
          // Ajuste de stock (salida o corrección)
          const nuevoStock = selectedItem.stock_actual - parseFloat(formData.cantidad);
          await admin.ajustarStockMateriaPrima(selectedItem.id, {
            nuevo_stock: nuevoStock,
            motivo: formData.motivo || 'merma',
            observaciones: formData.observaciones
          });
          toast.success('Salida registrada exitosamente');
        }
      } else {
        // Productos finales - siempre es ajuste
        const cambio = tipoMovimiento === 'entrada' 
          ? parseFloat(formData.cantidad)
          : -parseFloat(formData.cantidad);
        const nuevoStock = selectedItem.stock_actual + cambio;
        
        await admin.ajustarInventarioProducto(selectedItem.producto_id, {
          nuevo_stock: nuevoStock,
          motivo: formData.motivo || 'ajuste_manual',
          observaciones: formData.observaciones
        });
        toast.success('Ajuste registrado exitosamente');
      }
      
      handleCloseModal();
      cargarDatos();
    } catch (error) {
      console.error('Error registrando movimiento:', error);
      toast.error(error.response?.data?.message || 'Error al registrar movimiento');
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
          {items.map(item => {
            const nombre = activeTab === 'materias-primas' 
              ? item.nombre 
              : item.producto?.nombre || 'N/A';
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
            
            return (
              <tr key={item.id}>
                <td>{item.id}</td>
                <td><strong>{nombre}</strong></td>
                <td>
                  <Badge bg={estadoBadge}>
                    {stockActual.toFixed(3)} {unidad}
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
                  : selectedItem.producto?.nombre}
              </div>
            )}
          </Modal.Title>
        </Modal.Header>
        <Form onSubmit={handleSubmit}>
          <Modal.Body>
            {selectedItem && (
              <Alert variant="info" className="mb-3">
                <strong>Stock actual:</strong> {parseFloat(selectedItem.stock_actual).toFixed(3)} {
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
                step="0.001"
                min="0.001"
                value={formData.cantidad}
                onChange={(e) => setFormData({...formData, cantidad: e.target.value})}
                required
                placeholder="0.000"
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
          </Modal.Body>
          <Modal.Footer>
            <Button variant="secondary" onClick={handleCloseModal}>
              Cancelar
            </Button>
            <Button variant="primary" type="submit">
              {tipoMovimiento === 'entrada' ? 'Registrar Entrada' : 'Registrar Salida'}
            </Button>
          </Modal.Footer>
        </Form>
      </Modal>
    </Container>
  );
}
