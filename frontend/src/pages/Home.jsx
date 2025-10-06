import { useState, useEffect } from 'react';
import { Container, Row, Col, Button, ButtonGroup, Spinner, Alert } from 'react-bootstrap';
import { getProductos } from '../services/api';
import Carousel from '../components/Carousel';
import ProductCard from '../components/ProductCard';

const Home = () => {
  const [productosTemporada, setProductosTemporada] = useState([]);
  const [todosProductos, setTodosProductos] = useState([]);
  const [categoriaSeleccionada, setCategoriaSeleccionada] = useState('todos');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    fetchProductos();
  }, []);

  const fetchProductos = async () => {
    try {
      setLoading(true);
      setError(null);
      const data = await getProductos();
      
      // Filtrar productos de temporada
      const temporada = data.filter(p => p.es_de_temporada);
      setProductosTemporada(temporada);
      setTodosProductos(data);
    } catch (error) {
      console.error('Error al cargar productos:', error);
      setError('Error al cargar los productos. Por favor, intenta nuevamente.');
    } finally {
      setLoading(false);
    }
  };

  const productosFiltrados = categoriaSeleccionada === 'todos'
    ? todosProductos
    : todosProductos.filter(p => p.categoria?.url === categoriaSeleccionada);

  if (loading) {
    return (
      <Container className="text-center py-5">
        <Spinner animation="border" variant="primary" />
        <p className="mt-3">Cargando productos...</p>
      </Container>
    );
  }

  if (error) {
    return (
      <Container className="py-5">
        <Alert variant="danger">{error}</Alert>
        <Button onClick={fetchProductos}>Reintentar</Button>
      </Container>
    );
  }

  return (
    <>
      {/* Hero / Presentación */}
      <div className="presentacion">
        <div className="texto">
          <h2 style={{ fontSize: '30px' }}>Elaborado como en casa</h2>
          <br />
          <h1 style={{ fontSize: '60px' }}>Panificadora</h1>
          <h1 style={{ fontSize: '60px' }}>Nancy</h1>
        </div>
        <div className="logo">
          <img 
            src="https://www.oep.org.bo/logos/EscudoBolivia_300x300.webp" 
            width="250" 
            alt="Logo" 
          />
        </div>
      </div>

      {/* Productos de Temporada */}
      {productosTemporada.length > 0 && (
        <div className="my-5">
          <h2 className="text-center mb-4" style={{ fontStyle: 'italic', fontSize: '50px' }}>
            🎃 Productos de Temporada
          </h2>
          <Carousel productos={productosTemporada} />
        </div>
      )}

      {/* Todos los Productos */}
      <Container className="my-5">
        <div className="text-center mb-4">
          <h2 style={{ fontSize: '50px' }}>Todos Nuestros Productos</h2>
          <p className="text-muted" style={{ fontStyle: 'italic' }}>
            Explora nuestras delicias de cada día
          </p>
        </div>

        {/* Filtros de Categoría con Bootstrap */}
        <div className="d-flex justify-content-center mb-4">
          <ButtonGroup>
            <Button 
              variant={categoriaSeleccionada === 'todos' ? 'primary' : 'outline-primary'}
              onClick={() => setCategoriaSeleccionada('todos')}
            >
              📋 Todos
            </Button>
            <Button 
              variant={categoriaSeleccionada === 'panes' ? 'primary' : 'outline-primary'}
              onClick={() => setCategoriaSeleccionada('panes')}
            >
              🍞 Panes
            </Button>
            <Button 
              variant={categoriaSeleccionada === 'empanadas' ? 'primary' : 'outline-primary'}
              onClick={() => setCategoriaSeleccionada('empanadas')}
            >
              🥟 Empanadas
            </Button>
            <Button 
              variant={categoriaSeleccionada === 'temporada' ? 'primary' : 'outline-primary'}
              onClick={() => setCategoriaSeleccionada('temporada')}
            >
              🎃 Temporada
            </Button>
          </ButtonGroup>
        </div>

        {/* Grid de Productos con Bootstrap Grid */}
        <Row xs={1} md={2} lg={3} xl={4} className="g-4">
          {productosFiltrados.map(producto => (
            <Col key={producto.id}>
              <ProductCard producto={producto} />
            </Col>
          ))}
        </Row>

        {productosFiltrados.length === 0 && (
          <Alert variant="info" className="text-center mt-4">
            No hay productos en esta categoría
          </Alert>
        )}
      </Container>

      {/* Footer */}
      <footer style={{ backgroundColor: 'rgb(83, 64, 49)', color: 'white', padding: '40px 0', width: '100%' }}>
        <Container fluid>
          <Row>
            <Col md={4}>
              <h3>Panificadora Nancy</h3>
              <p>Tradición y calidad en cada pan desde 1995.</p>
              <p>📅 Horario: Lunes a Sábado 6:00 AM - 8:00 PM</p>
            </Col>
            <Col md={4}>
              <h3>Contacto</h3>
              <p>📞 Teléfono: +591 78945612</p>
              <p>📧 Email: info@panificadoranancy.com</p>
            </Col>
            <Col md={4}>
              <h3>Síguenos</h3>
              <div className="d-flex gap-3">
                <img src="https://cdn-icons-png.flaticon.com/512/733/733547.png" alt="Facebook" width="40" />
                <img src="https://cdn-icons-png.flaticon.com/512/2111/2111463.png" alt="Instagram" width="40" />
                <img src="https://cdn-icons-png.flaticon.com/512/733/733585.png" alt="WhatsApp" width="40" />
              </div>
            </Col>
          </Row>
        </Container>
      </footer>
    </>
  );
};

export default Home;
