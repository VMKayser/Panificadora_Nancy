import { useState, useEffect } from 'react';
import { Container, Row, Col, Button, ButtonGroup, Spinner, Alert } from 'react-bootstrap';
import { getProductos } from '../services/api';
import Carousel from '../components/Carousel';
import ProductCard from '../components/ProductCard';
import Footer from '../components/Footer';
import '../estilos.css'; // Importar estilos de presentación
import { useSEO, generateProductListSchema } from '../hooks/useSEO';

const Home = () => {
  const [productosTemporada, setProductosTemporada] = useState([]);
  const [todosProductos, setTodosProductos] = useState([]);
  const [categoriaSeleccionada, setCategoriaSeleccionada] = useState('todos');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // SEO debe llamarse SIEMPRE, antes de cualquier return condicional
  useSEO({
    title: 'Panificadora Nancy - Pan Artesanal Fresco | Productos',
    description: 'Explora nuestra variedad de panes artesanales, bizcochos y tortas. Pedidos online con entrega el mismo día.',
    keywords: 'pan artesanal, pan fresco, pedidos online, bizcochos, tortas',
    image: '/productos-og.jpg',
    canonical: 'https://www.panificadoranancy.com/productos',
    structuredData: todosProductos.length > 0 ? generateProductListSchema(todosProductos) : null
  });

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
          <h2 className="hero-subtitle">Elaborado como en casa</h2>
          <h1 className="hero-title">Panificadora</h1>
          <h1 className="hero-title hero-title--accent">Nancy</h1>
        </div>
        <div className="logo">
          <img 
            src={`${import.meta.env.BASE_URL}images/logo.jpg`}
            alt="Logo Panificadora Nancy" 
            className="hero-logo"
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

        {/* Grid de Productos usando Flexbox para anchos fijos */}
        <div style={{ 
          display: 'flex', 
          flexWrap: 'wrap', 
          gap: '20px',
          justifyContent: 'center',
          textAlign: 'center',
          alignItems: 'center'
        }}>
          {productosFiltrados.map(producto => (
            <ProductCard key={producto.id} producto={producto} />
          ))}
        </div>

        {productosFiltrados.length === 0 && (
          <Alert variant="info" className="text-center mt-4">
            No hay productos en esta categoría
          </Alert>
        )}
      </Container>

      {/* Footer */}
      <Footer />
    </>
  );
};

export default Home;
