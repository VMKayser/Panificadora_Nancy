import { useEffect, useState } from 'react';
import { Table, Button, Spinner, Row, Col, Form, Modal, Card } from 'react-bootstrap';
import { admin } from '../../services/api';
import { toast } from 'react-toastify';

export default function EmpleadoPagosPanel({ initialFilters = {}, openCreateFor = null }) {
  const [pagos, setPagos] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filtros, setFiltros] = useState({ empleado_tipo: '', empleado_id: '' });
  const [showModal, setShowModal] = useState(false);
  const [form, setForm] = useState({ empleado_tipo: 'panadero', empleado_id: '', monto: '', kilos_pagados: '', comision_pagada: '', tipo_pago: 'pago_produccion', notas: '' });

  const cargar = async () => {
    try {
      setLoading(true);
      const data = await admin.listarEmpleadoPagos(filtros);
      setPagos(data.data || data);
    } catch (err) {
      console.error(err);
      toast.error('Error cargando pagos');
    } finally {
      setLoading(false);
    }
  };

  // Apply initial filters once when component mounts or when initialFilters change
  useEffect(() => {
    if (initialFilters && Object.keys(initialFilters).length > 0) {
      setFiltros(prev => ({ ...prev, ...initialFilters }));
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [initialFilters]);

  useEffect(() => { cargar(); }, [filtros]);

  // If parent asks to open create modal for a specific employee, prefill and open
  useEffect(() => {
    if (openCreateFor && openCreateFor.empleado_tipo && openCreateFor.empleado_id) {
      setForm(prev => ({
        ...prev,
        empleado_tipo: openCreateFor.empleado_tipo,
        empleado_id: openCreateFor.empleado_id,
        monto: openCreateFor.monto ?? prev.monto,
      }));
      setShowModal(true);
    }
  }, [openCreateFor]);

  const handleFiltroChange = (k, v) => setFiltros(prev => ({ ...prev, [k]: v }));

  const openNew = () => { setShowModal(true); };
  const handleClose = () => setShowModal(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      const payload = {
        empleado_tipo: form.empleado_tipo,
        empleado_id: parseInt(form.empleado_id, 10),
        monto: parseFloat(form.monto) || 0,
        kilos_pagados: form.kilos_pagados ? parseFloat(form.kilos_pagados) : null,
        comision_pagada: form.comision_pagada ? parseFloat(form.comision_pagada) : null,
        tipo_pago: form.tipo_pago,
  notas: form.notas || '',
      };
      await admin.crearEmpleadoPago(payload);
      toast.success('Pago registrado');
      handleClose();
      cargar();
    } catch (err) {
      console.error(err);
      toast.error(err.response?.data?.message || 'Error registrando pago');
    }
  };

  if (loading) return <div className="text-center py-4"><Spinner animation="border" /></div>;

  return (
    <div>
      <Row className="mb-3">
        <Col><h4>Historial de Pagos de Empleados</h4></Col>
        <Col className="text-end"><Button onClick={openNew}>Nuevo Pago</Button></Col>
      </Row>

      <Row className="mb-3">
        <Col md={3}>
          <Form.Select value={filtros.empleado_tipo} onChange={e => handleFiltroChange('empleado_tipo', e.target.value)}>
            <option value="">Todos</option>
            <option value="panadero">Panadero</option>
            <option value="vendedor">Vendedor</option>
          </Form.Select>
        </Col>
        <Col md={3}>
          <Form.Control placeholder="Empleado ID" value={filtros.empleado_id} onChange={e => handleFiltroChange('empleado_id', e.target.value)} />
        </Col>
        <Col md={2}><Button onClick={cargar}>Filtrar</Button></Col>
      </Row>

      <Table hover responsive>
        <thead className="table-light">
          <tr>
            <th>ID</th>
            <th>Empleado</th>
            <th>Tipo</th>
            <th>Monto</th>
            <th>Kilos/Comisión</th>
            <th>Notas</th>
            <th>Fecha</th>
          </tr>
        </thead>
        <tbody>
          {pagos.map(p => (
            <tr key={p.id}>
              <td>{p.id}</td>
              <td>{p.empleado_tipo} #{p.empleado_id}</td>
              <td>{p.tipo_pago || 'N/A'}</td>
              <td>Bs. {parseFloat(p.monto || 0).toFixed(2)}</td>
              <td>{p.kilos_pagados ? `${p.kilos_pagados} kg` : p.comision_pagada ? `Bs. ${p.comision_pagada}` : '-'}</td>
              <td>{p.notas || ''}</td>
              <td>{new Date(p.created_at).toLocaleString()}</td>
            </tr>
          ))}
        </tbody>
      </Table>

      <Modal show={showModal} onHide={handleClose}>
        <Form onSubmit={handleSubmit}>
          <Modal.Header closeButton><Modal.Title>Registrar Pago</Modal.Title></Modal.Header>
          <Modal.Body>
            <Form.Group className="mb-2">
              <Form.Label>Tipo</Form.Label>
              <Form.Select value={form.empleado_tipo} onChange={e => setForm(prev => ({...prev, empleado_tipo: e.target.value }))}>
                <option value="panadero">Panadero</option>
                <option value="vendedor">Vendedor</option>
              </Form.Select>
            </Form.Group>
            <Form.Group className="mb-2">
              <Form.Label>Empleado ID</Form.Label>
              <Form.Control value={form.empleado_id} onChange={e => setForm(prev => ({...prev, empleado_id: e.target.value }))} required />
            </Form.Group>
            <Form.Group className="mb-2">
              <Form.Label>Monto (Bs)</Form.Label>
              <Form.Control type="number" step="0.01" value={form.monto} onChange={e => setForm(prev => ({...prev, monto: e.target.value }))} required />
            </Form.Group>
            <Form.Group className="mb-2">
              <Form.Label>Kilos pagados (si aplica)</Form.Label>
              <Form.Control type="number" step="0.01" value={form.kilos_pagados} onChange={e => setForm(prev => ({...prev, kilos_pagados: e.target.value }))} />
            </Form.Group>
            <Form.Group className="mb-2">
              <Form.Label>Comisión pagada (si aplica)</Form.Label>
              <Form.Control type="number" step="0.01" value={form.comision_pagada} onChange={e => setForm(prev => ({...prev, comision_pagada: e.target.value }))} />
            </Form.Group>
            <Form.Group className="mb-2">
              <Form.Label>Notas</Form.Label>
              <Form.Control as="textarea" rows={3} value={form.notas} onChange={e => setForm(prev => ({...prev, notas: e.target.value }))} />
            </Form.Group>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="secondary" onClick={handleClose}>Cancelar</Button>
            <Button variant="primary" type="submit">Registrar</Button>
          </Modal.Footer>
        </Form>
      </Modal>
    </div>
  );
}
