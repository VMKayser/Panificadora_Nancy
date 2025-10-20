import { useEffect, useState } from 'react';
import { Row, Col, Card, Table } from 'react-bootstrap';

const MiniBar = ({ values }) => {
  const max = Math.max(...values, 1);
  return (
    <div style={{ display: 'flex', gap: 6, alignItems: 'end', height: 80 }}>
      {values.map((v, i) => (
        <div key={i} title={v} style={{ width: 18, background: '#8b6f47', height: `${Math.round((v / max) * 100)}%`, borderRadius: 4 }} />
      ))}
    </div>
  );
};

const Dashboard = () => {
  const [data, setData] = useState(null);

  useEffect(() => {
    let mounted = true;
    fetch(`${import.meta.env.BASE_URL}sample-dashboard.json`).then(r => r.json()).then(d => { if (mounted) setData(d); }).catch(() => {});
    return () => { mounted = false; };
  }, []);

  if (!data) return (
    <Card className="shadow-sm p-4">
      Cargando dashboard...
    </Card>
  );

  return (
    <div>
      <Row className="mb-4">
        <Col md={3}>
          <Card className="shadow-sm p-3">
            <small className="text-muted">Pedidos hoy</small>
            <h3 style={{ color: '#8b6f47' }}>{data.pedidos_hoy}</h3>
          </Card>
        </Col>
        <Col md={3}>
          <Card className="shadow-sm p-3">
            <small className="text-muted">Ingresos hoy</small>
            <h3>Bs. {parseFloat(data.ingresos_hoy).toFixed(2)}</h3>
          </Card>
        </Col>
        <Col md={3}>
          <Card className="shadow-sm p-3">
            <small className="text-muted">Producción</small>
            <h3>{data.produccion_hoy} uds</h3>
          </Card>
        </Col>
        <Col md={3}>
          <Card className="shadow-sm p-3">
            <small className="text-muted">Productos con stock bajo</small>
            <h3 className="text-danger">{data.stock_bajo}</h3>
          </Card>
        </Col>
      </Row>

      <Row className="mb-4">
        <Col md={6}>
          <Card className="shadow-sm p-3">
            <h5>Panadero con más producción</h5>
            <Table size="sm" borderless>
              <tbody>
                {data.panaderos.sort((a,b)=>b.produccion-a.produccion).slice(0,3).map(p => (
                  <tr key={p.id}>
                    <td style={{ width: 200 }}>{p.nombre}</td>
                    <td style={{ width: 120 }}>{p.produccion} uds</td>
                    <td><MiniBar values={[p.produccion]} /></td>
                  </tr>
                ))}
              </tbody>
            </Table>
          </Card>
        </Col>
        <Col md={6}>
          <Card className="shadow-sm p-3">
            <h5>Productos top por rentabilidad</h5>
            <Table size="sm">
              <thead>
                <tr><th>Producto</th><th>Ventas</th><th>Profit</th></tr>
              </thead>
              <tbody>
                {data.productos.sort((a,b)=>b.profit-a.profit).slice(0,5).map(prod => (
                  <tr key={prod.id}><td>{prod.nombre}</td><td>{prod.ventas}</td><td>Bs. {prod.profit.toFixed(2)}</td></tr>
                ))}
              </tbody>
            </Table>
          </Card>
        </Col>
      </Row>

      <Row>
        <Col md={8}>
          <Card className="shadow-sm p-3">
            <h5>Ventas por temporada (últimos 7 días)</h5>
            <MiniBar values={data.ventas_por_temporada.map(v=>v.ventas)} />
            <small className="text-muted">(barra representa volumen diario)</small>
          </Card>
        </Col>
        <Col md={4}>
          <Card className="shadow-sm p-3">
            <h5>Productos más vendidos</h5>
            <ol>
              {data.productos.sort((a,b)=>b.ventas-a.ventas).slice(0,5).map(p=> <li key={p.id}>{p.nombre} ({p.ventas})</li>)}
            </ol>
          </Card>
        </Col>
      </Row>
    </div>
  );
};

export default Dashboard;
