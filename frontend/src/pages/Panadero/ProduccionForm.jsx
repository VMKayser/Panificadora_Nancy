import React, { useEffect, useState } from 'react';
import { Form, Button, Modal, Row, Col, Alert } from 'react-bootstrap';
import api, { getProductos, admin } from '../../services/api';
import { toast } from 'react-toastify';

const unidades = [
  { value: 'kg', label: 'Kg' },
  { value: 'unidades', label: 'Unidades' },
  { value: 'docenas', label: 'Docenas' },
];

export default function ProduccionForm() {
  const [productos, setProductos] = useState([]);
  const [loadingProductos, setLoadingProductos] = useState(true);
  const [errors, setErrors] = useState(null);

  const [form, setForm] = useState({
    producto_id: '',
    fecha_produccion: new Date().toISOString().slice(0, 10),
    hora_inicio: '',
    hora_fin: '',
    cantidad_producida: '',
    unidad: 'kg',
    harina_real_usada: '',
    observaciones: ''
  });

  useEffect(() => {
    let mounted = true;
    (async () => {
      try {
        const prods = await getProductos({ per_page: 200 });
        if (!mounted) return;
        setProductos(Array.isArray(prods) ? prods : (prods.data || prods));
      } catch (e) {
        console.error('Error cargando productos para producción', e);
        setProductos([]);
      } finally {
        setLoadingProductos(false);
      }
    })();
    return () => { mounted = false; };
  }, []);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setForm(prev => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors(null);

    // Basic client-side validation
    if (!form.producto_id) return setErrors({ producto_id: ['Seleccione un producto'] });
    if (!form.cantidad_producida || Number(form.cantidad_producida) <= 0) return setErrors({ cantidad_producida: ['Ingrese una cantidad válida'] });
    if (!form.harina_real_usada || Number(form.harina_real_usada) <= 0) return setErrors({ harina_real_usada: ['Ingrese harina usada (kg)'] });

    try {
      const payload = {
        producto_id: form.producto_id,
        fecha_produccion: form.fecha_produccion,
        hora_inicio: form.hora_inicio || null,
        hora_fin: form.hora_fin || null,
        cantidad_producida: Number(form.cantidad_producida),
        unidad: form.unidad,
        harina_real_usada: Number(form.harina_real_usada),
        observaciones: form.observaciones?.trim() || null
      };

      const res = await api.post('/producciones', payload);
      toast.success(res.data?.message || 'Producción registrada');
      // Reset form minimal
      setForm(prev => ({ ...prev, cantidad_producida: '', harina_real_usada: '', observaciones: '' }));
    } catch (err) {
      console.error('Error creando producción', err);
      if (err.response?.data?.errors) {
        setErrors(err.response.data.errors);
      } else if (err.response?.data?.message) {
        setErrors({ general: [err.response.data.message] });
      } else {
        setErrors({ general: ['Error de red o servidor'] });
      }
    }
  };

  return (
    <div className="container mt-3">
      <h3>Registrar Producción</h3>

      {errors && (
        <Alert variant="danger">
          <ul className="mb-0">
            {Object.keys(errors).map((k) => (
              <li key={k}>{errors[k].join ? errors[k].join(', ') : String(errors[k])}</li>
            ))}
          </ul>
        </Alert>
      )}

      <Form onSubmit={handleSubmit}>
        <Form.Group className="mb-2">
          <Form.Label>Producto</Form.Label>
          <Form.Control as="select" name="producto_id" value={form.producto_id} onChange={handleChange}>
            <option value="">-- Seleccionar --</option>
            {productos.map(p => (
              <option key={p.id} value={p.id}>{p.nombre}</option>
            ))}
          </Form.Control>
        </Form.Group>

        <Row>
          <Col md={4}>
            <Form.Group className="mb-2">
              <Form.Label>Fecha</Form.Label>
              <Form.Control type="date" name="fecha_produccion" value={form.fecha_produccion} onChange={handleChange} />
            </Form.Group>
          </Col>
          <Col md={4}>
            <Form.Group className="mb-2">
              <Form.Label>Hora inicio</Form.Label>
              <Form.Control type="time" name="hora_inicio" value={form.hora_inicio} onChange={handleChange} />
            </Form.Group>
          </Col>
          <Col md={4}>
            <Form.Group className="mb-2">
              <Form.Label>Hora fin</Form.Label>
              <Form.Control type="time" name="hora_fin" value={form.hora_fin} onChange={handleChange} />
            </Form.Group>
          </Col>
        </Row>

        <Row>
          <Col md={4}>
            <Form.Group className="mb-2">
              <Form.Label>Cantidad producida</Form.Label>
              <Form.Control type="number" step="0.01" name="cantidad_producida" value={form.cantidad_producida} onChange={handleChange} />
            </Form.Group>
          </Col>

          <Col md={4}>
            <Form.Group className="mb-2">
              <Form.Label>Unidad</Form.Label>
              <Form.Control as="select" name="unidad" value={form.unidad} onChange={handleChange}>
                {unidades.map(u => <option key={u.value} value={u.value}>{u.label}</option>)}
              </Form.Control>
            </Form.Group>
          </Col>

          <Col md={4}>
            <Form.Group className="mb-2">
              <Form.Label>Harina real usada (kg)</Form.Label>
              <Form.Control type="number" step="0.01" name="harina_real_usada" value={form.harina_real_usada} onChange={handleChange} />
            </Form.Group>
          </Col>
        </Row>

        <Form.Group className="mb-2">
          <Form.Label>Observaciones</Form.Label>
          <Form.Control as="textarea" rows={3} name="observaciones" value={form.observaciones} onChange={handleChange} />
        </Form.Group>

        <div className="d-flex justify-content-end">
          <Button type="submit">Registrar</Button>
        </div>
      </Form>
    </div>
  );
}
