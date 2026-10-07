import { useEffect, useState, useRef } from 'react';
import { admin } from '../../services/api';
import { formatCurrency } from '../../utils/number';
import { Row, Col, Card, Table, Spinner } from 'react-bootstrap';

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

// Chart.js se carga desde el CDN solo cuando se abre el panel (antes venía en
// index.html y lo descargaba también la tienda pública).
let cargaChartJs = null;
const cargarChartJs = () => {
  if (window.Chart) return Promise.resolve(window.Chart);
  if (!cargaChartJs) {
    cargaChartJs = new Promise((resolve, reject) => {
      const script = document.createElement('script');
      script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4';
      script.async = true;
      script.onload = () => resolve(window.Chart);
      script.onerror = () => { cargaChartJs = null; reject(new Error('No se pudo cargar Chart.js')); };
      document.head.appendChild(script);
    });
  }
  return cargaChartJs;
};

const ChartArea = ({ id, type, labels, datasets, options }) => {
  const canvasRef = useRef(null);

  useEffect(() => {
    let chart = null;
    let activo = true;
    cargarChartJs().then((Chart) => {
      if (!activo || !canvasRef.current) return;
      const ctx = canvasRef.current.getContext('2d');
      const defaultOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } };
      chart = new Chart(ctx, { type, data: { labels, datasets }, options: Object.assign({}, defaultOptions, options || {}) });
    }).catch(() => {});
    return () => { activo = false; chart?.destroy(); };
  }, [type, labels, datasets, options]);

  // Wrap canvas in fixed-height container so all charts match visually
  return (
    <div style={{ width: '100%', height: 220 }}>
      <canvas id={id} ref={canvasRef} style={{ width: '100%', height: '100%' }} />
    </div>
  );
};

// "2025-12-20" -> "20/12" sin pasar por Date (que lo interpretaría en UTC y daría el día anterior)
const formatDia = (fecha) => {
  const [, mes, dia] = String(fecha).split('-');
  return dia && mes ? `${dia}/${mes}` : fecha;
};

const Dashboard = () => {
  const [data, setData] = useState(null);
  const [estado, setEstado] = useState('cargando'); // cargando | listo | error

  useEffect(() => {
    let mounted = true;
    (async () => {
      try {
        const d = await admin.getDashboardInventario();
        if (mounted) {
          setData(d);
          setEstado('listo');
        }
        return;
      } catch (err) {
        if (import.meta.env.DEV) {
          console.error('[Dashboard] Error al cargar desde API:', err);
        }

        // In development only, try loading a sample JSON to make the UI usable offline.
        if (import.meta.env.DEV) {
          try {
            const resp = await fetch(`${import.meta.env.BASE_URL}sample-dashboard.json`);
            if (!resp.ok) throw new Error('sample not available');
            const d = await resp.json();
            if (mounted) {
              setData(d);
              setEstado('listo');
            }
            return;
          } catch (e) {
            console.warn('[Dashboard] No se pudo cargar sample-dashboard.json:', e.message || e);
          }
        }

        if (mounted) setEstado('error');
      }
    })();

    return () => { mounted = false; };
  }, []);

  if (estado === 'cargando') return (
    <Card className="shadow-sm p-4 text-center">
      <div><Spinner animation="border" size="sm" className="me-2" />Cargando dashboard...</div>
    </Card>
  );

  if (estado === 'error' || !data) return (
    <Card className="shadow-sm p-4">
      <div>
        <strong>Dashboard</strong>
        <div className="text-muted">No se pudieron cargar los datos. Intenta recargar o revisa la conexión.</div>
      </div>
    </Card>
  );

  // Prepare data for charts
  const ventasLabels = data.ventas_por_temporada.map(v => formatDia(v.fecha));
  const ventasValues = data.ventas_por_temporada.map(v => v.ventas);

  const topProducts = data.productos.slice().sort((a,b)=>b.ventas-a.ventas).slice(0,5);
  const prodLabels = topProducts.map(p=>p.nombre);
  const prodValues = topProducts.map(p=>p.ventas);

  // Solo productos con costo registrado: sin costo la "ganancia" sería el ingreso completo
  const conCosto = data.productos.filter(p => p.costo_conocido !== false && p.profit !== null);
  const sinCosto = data.productos.length - conCosto.length;
  const topProfit = conCosto.slice().sort((a,b)=>b.profit-a.profit).slice(0,5);
  const profitLabels = topProfit.map(p=>p.nombre);
  const profitValues = topProfit.map(p=>p.profit);

  const topPanaderos = data.panaderos.slice().sort((a,b)=>b.produccion-a.produccion).slice(0,3);

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
            <small className="text-muted">Ingresos hoy (pagados)</small>
            <h3>Bs. {formatCurrency(Number(data.ingresos_hoy) || 0)}</h3>
          </Card>
        </Col>
        <Col md={3}>
          <Card className="shadow-sm p-3">
            <small className="text-muted">Producción hoy</small>
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
            <h5>Panaderos con más producción hoy</h5>
            {topPanaderos.length === 0 ? (
              <div className="text-muted">Todavía no hay producción registrada hoy.</div>
            ) : (
              <Table size="sm" borderless>
                <tbody>
                  {topPanaderos.map(p => (
                    <tr key={p.id}>
                      <td style={{ width: 200 }}>{p.nombre}</td>
                      <td style={{ width: 120 }}>{p.produccion} uds</td>
                      <td><MiniBar values={[p.produccion]} /></td>
                    </tr>
                  ))}
                </tbody>
              </Table>
            )}
          </Card>
        </Col>
        <Col md={6}>
          <Card className="shadow-sm p-3" style={{ minHeight: 340, display: 'flex', flexDirection: 'column', justifyContent: 'space-between' }}>
            <h5>Productos más rentables (últimos 7 días)</h5>
            <div style={{ flex: 1 }}>
              {topProfit.length > 0 ? (
                <ChartArea id="profitChart" type="bar" labels={profitLabels} datasets={[{ label: 'Ganancia (Bs.)', data: profitValues, backgroundColor: '#8b6f47' }]} />
              ) : (
                <div className="text-muted">Sin datos de ganancia todavía.</div>
              )}
            </div>
            {sinCosto > 0 && (
              <small className="text-muted">
                {sinCosto} producto(s) vendidos no tienen costo registrado y no se muestran. El costo se calcula al registrar producción.
              </small>
            )}
          </Card>
        </Col>
      </Row>

      <Row>
        <Col md={8}>
          <Card className="shadow-sm p-3" style={{ minHeight: 320 }}>
            <h5>Ventas pagadas (últimos 7 días)</h5>
            <ChartArea id="ventasLine" type="line" labels={ventasLabels} datasets={[{ label: 'Ventas (Bs.)', data: ventasValues, borderColor: '#8b6f47', backgroundColor: 'rgba(139,111,71,0.1)', fill: true }]} />
          </Card>
        </Col>
        <Col md={4}>
          <Card className="shadow-sm p-3" style={{ minHeight: 320, display: 'flex', flexDirection: 'column', justifyContent: 'center' }}>
            <h5>Top productos por unidades vendidas</h5>
            {topProducts.length > 0 ? (
              <ChartArea id="topProdPie" type="pie" labels={prodLabels} datasets={[{ data: prodValues, backgroundColor: ['#8b6f47','#c28f5b','#f3c9a6','#d9b79a','#e8e2d8'] }]} />
            ) : (
              <div className="text-muted">Sin ventas pagadas en los últimos 7 días.</div>
            )}
          </Card>
        </Col>
      </Row>
    </div>
  );
};

export default Dashboard;
