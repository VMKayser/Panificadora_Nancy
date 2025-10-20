import { useEffect, useState, useRef } from 'react';
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

const ChartArea = ({ id, type, labels, datasets, options }) => {
  const canvasRef = useRef(null);

  useEffect(() => {
    if (!canvasRef.current) return;
    // Chart.js disponible globalmente via CDN
    if (typeof window.Chart === 'undefined') return;
    const ctx = canvasRef.current.getContext('2d');
    const defaultOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } };
    const chart = new window.Chart(ctx, { type, data: { labels, datasets }, options: Object.assign({}, defaultOptions, options || {}) });
    return () => { chart.destroy(); };
  }, [type, labels, datasets, options]);

  // Wrap canvas in fixed-height container so all charts match visually
  return (
    <div style={{ width: '100%', height: 220 }}>
      <canvas id={id} ref={canvasRef} style={{ width: '100%', height: '100%' }} />
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

  // Prepare data for charts
  const ventasLabels = data.ventas_por_temporada.map(v => v.fecha);
  const ventasValues = data.ventas_por_temporada.map(v => v.ventas);

  const topProducts = data.productos.slice().sort((a,b)=>b.ventas-a.ventas).slice(0,5);
  const prodLabels = topProducts.map(p=>p.nombre);
  const prodValues = topProducts.map(p=>p.ventas);

  const profitLabels = data.productos.slice().sort((a,b)=>b.profit-a.profit).slice(0,5).map(p=>p.nombre);
  const profitValues = data.productos.slice().sort((a,b)=>b.profit-a.profit).slice(0,5).map(p=>p.profit);

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
          <Card className="shadow-sm p-3" style={{ minHeight: 340 }}>
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
          <Card className="shadow-sm p-3" style={{ minHeight: 340, display: 'flex', flexDirection: 'column', justifyContent: 'space-between' }}>
            <h5>Productos top por rentabilidad</h5>
            <div style={{ flex: 1 }}>
              <ChartArea id="profitChart" type="bar" labels={profitLabels} datasets={[{ label: 'Profit (Bs.)', data: profitValues, backgroundColor: '#8b6f47' }]} />
            </div>
          </Card>
        </Col>
      </Row>

      <Row>
        <Col md={8}>
          <Card className="shadow-sm p-3" style={{ minHeight: 320 }}>
            <h5>Ventas por temporada (últimos 7 días)</h5>
            <ChartArea id="ventasLine" type="line" labels={ventasLabels} datasets={[{ label: 'Ventas', data: ventasValues, borderColor: '#8b6f47', backgroundColor: 'rgba(139,111,71,0.1)', fill: true }]} />
          </Card>
        </Col>
        <Col md={4}>
          <Card className="shadow-sm p-3" style={{ minHeight: 320, display: 'flex', flexDirection: 'column', justifyContent: 'center' }}>
            <h5>Top productos por ventas</h5>
            <ChartArea id="topProdPie" type="pie" labels={prodLabels} datasets={[{ data: prodValues, backgroundColor: ['#8b6f47','#c28f5b','#f3c9a6','#d9b79a','#e8e2d8'] }]} />
          </Card>
        </Col>
      </Row>
    </div>
  );
};

export default Dashboard;
