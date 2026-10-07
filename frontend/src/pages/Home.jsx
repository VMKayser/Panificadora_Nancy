import { useState, useEffect, useMemo, useRef, useCallback } from 'react';
import { useLocation } from 'react-router-dom';
import { Bike, ChevronLeft, ChevronRight, MessageCircle, Search, SearchX, Store, Truck } from 'lucide-react';
import { toast } from 'react-toastify';
import { getProductos, getCategorias } from '../services/api';
import ProductCard from '../components/ProductCard';
import Footer from '../components/Footer';
import { useSEO, generateProductListSchema } from '../hooks/useSEO';
import { useSiteConfig } from '../context/SiteConfigContext';

const POR_PAGINA = 12;
const WHATSAPP = 'https://wa.me/59176490687';

// Búsqueda sin acentos ni mayúsculas: "paneton" encuentra "Panetón".
const normalizar = (texto) => String(texto || '')
  .normalize('NFD')
  .replace(/[̀-ͯ]/g, '')
  .toLowerCase();

const Home = () => {
  const [todosProductos, setTodosProductos] = useState([]);
  const [categorias, setCategorias] = useState([]);
  const [categoria, setCategoria] = useState('todos');
  const [busqueda, setBusqueda] = useState('');
  const [visibles, setVisibles] = useState(POR_PAGINA);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const { logoGrande } = useSiteConfig();
  const { pathname } = useLocation();
  const railRef = useRef(null);
  const catalogoRef = useRef(null);

  // SEO debe llamarse SIEMPRE, antes de cualquier return condicional
  useSEO({
    title: 'Panificadora Nancy - Pan Artesanal Fresco | Productos',
    description: 'Explora nuestra variedad de panes artesanales, bizcochos y tortas. Pedidos online con entrega el mismo día.',
    keywords: 'pan artesanal, pan fresco, pedidos online, bizcochos, tortas',
    image: '/productos-og.jpg',
    canonical: 'https://www.panificadoranancy.com/productos',
    structuredData: todosProductos.length > 0 ? generateProductListSchema(todosProductos) : null
  });

  const fetchProductos = useCallback(async () => {
    try {
      setLoading(true);
      setError(null);
      const data = await getProductos();
      const lista = Array.isArray(data) ? data : data?.data || [];
      // Por el orden de su categoría (la temporada en curso primero); dentro de
      // cada categoría, en el orden en que llegan
      setTodosProductos([...lista].sort((a, b) => (a.categoria?.order ?? 0) - (b.categoria?.order ?? 0)));
    } catch (err) {
      console.error('Error al cargar productos:', err);
      toast.error('No se pudieron cargar los productos');
      setError('No pudimos cargar los productos. Revisa tu conexión e inténtalo de nuevo.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    getCategorias()
      .then((data) => setCategorias(Array.isArray(data) ? data : data?.data || []))
      .catch((err) => {
        if (import.meta.env.DEV) console.warn('No se pudieron cargar categorías:', err?.message || err);
        setCategorias([]);
      });
    fetchProductos();
  }, [fetchProductos]);

  // /productos lleva directo al catálogo
  useEffect(() => {
    if (pathname === '/productos' && !loading && catalogoRef.current) {
      catalogoRef.current.scrollIntoView({ block: 'start' });
    }
  }, [pathname, loading]);

  useEffect(() => {
    setVisibles(POR_PAGINA);
  }, [categoria, busqueda]);

  const temporada = useMemo(() => todosProductos.filter((p) => p.es_de_temporada), [todosProductos]);

  // Solo se ofrecen las categorías que tienen productos a la venta
  const categoriasConProductos = useMemo(() => {
    const conteo = todosProductos.reduce((acc, p) => {
      const key = p.categoria?.url;
      if (key) acc[key] = (acc[key] || 0) + 1;
      return acc;
    }, {});
    return categorias.filter((c) => conteo[c.url || String(c.id)]);
  }, [categorias, todosProductos]);

  const filtrados = useMemo(() => {
    const q = normalizar(busqueda.trim());
    return todosProductos.filter((p) => {
      if (categoria !== 'todos' && p.categoria?.url !== categoria) return false;
      if (!q) return true;
      return normalizar(`${p.nombre} ${p.descripcion_corta || ''} ${p.presentacion || ''}`).includes(q);
    });
  }, [todosProductos, categoria, busqueda]);

  const mostrados = filtrados.slice(0, visibles);

  const moverRail = (dir) => {
    const rail = railRef.current;
    if (rail) rail.scrollBy({ left: dir * rail.clientWidth * 0.9, behavior: 'smooth' });
  };

  const irAlCatalogo = (e) => {
    e.preventDefault();
    catalogoRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  };

  return (
    <>
      <section className="pn-hero">
        <div className="pn-wrap pn-hero__inner">
          <div>
            <p className="pn-hero__eyebrow">Elaborado como en casa</p>
            <h1>Panificadora Nancy</h1>
            <p className="pn-hero__lead">
              Pan artesanal, masitas y productos de temporada hechos cada día en Quillacollo. Haz tu pedido y pásalo a recoger o te lo llevamos.
            </p>
            <div className="pn-hero__cta">
              <a href="#catalogo" className="pn-btn pn-btn--light pn-btn--lg" onClick={irAlCatalogo}>Ver productos</a>
              <a href={WHATSAPP} className="pn-btn pn-btn--onphoto pn-btn--lg" target="_blank" rel="noreferrer">
                <MessageCircle size={18} /> WhatsApp
              </a>
            </div>
            <ul className="pn-hero__facts">
              <li><Store size={16} /> Retiro en tienda</li>
              <li><Bike size={16} /> Delivery en Quillacollo</li>
              <li><Truck size={16} /> Envíos a todo Bolivia</li>
            </ul>
          </div>
          {/* lazy: en el teléfono está oculto y así no se descarga */}
          <img src={logoGrande} alt="Logo de Panificadora Nancy" className="pn-hero__logo" width="290" height="290" loading="lazy" decoding="async" />
        </div>
      </section>

      {temporada.length > 0 && (
        <section className="pn-band" aria-labelledby="temporada-titulo">
          <div className="pn-wrap pn-section">
            <div className="pn-section__head">
              <div>
                <h2 className="pn-section__title" id="temporada-titulo">De temporada</h2>
                <p className="pn-section__sub">Disponibles por tiempo limitado</p>
              </div>
              {temporada.length > 4 && (
                <div className="pn-rail-nav">
                  <button type="button" className="pn-iconbtn" onClick={() => moverRail(-1)} aria-label="Ver anteriores">
                    <ChevronLeft size={20} />
                  </button>
                  <button type="button" className="pn-iconbtn" onClick={() => moverRail(1)} aria-label="Ver siguientes">
                    <ChevronRight size={20} />
                  </button>
                </div>
              )}
            </div>
            <div className="pn-rail" ref={railRef}>
              {temporada.map((producto) => (
                <ProductCard key={producto.id} producto={producto} />
              ))}
            </div>
          </div>
        </section>
      )}

      <section className="pn-wrap pn-section" id="catalogo" ref={catalogoRef} aria-labelledby="catalogo-titulo" style={{ scrollMarginTop: 'var(--pn-header-h)' }}>
        <div className="pn-section__head">
          <div>
            <h2 className="pn-section__title" id="catalogo-titulo">Nuestros productos</h2>
            {!loading && !error && (
              <p className="pn-section__sub">{todosProductos.length} productos para pedir en línea</p>
            )}
          </div>
        </div>

        <div className="pn-toolbar">
          <label className="pn-search">
            <span className="pn-visually-hidden">Buscar productos</span>
            <Search size={18} />
            <input
              id="buscar-productos"
              type="search"
              value={busqueda}
              onChange={(e) => setBusqueda(e.target.value)}
              placeholder="Buscar pan, panetón, masitas…"
              autoComplete="off"
            />
          </label>
          {categoriasConProductos.length > 0 && (
            <div className="pn-chips" role="group" aria-label="Filtrar por categoría">
              <button type="button" aria-pressed={categoria === 'todos'} onClick={() => setCategoria('todos')}>
                Todos
              </button>
              {categoriasConProductos.map((cat) => {
                const key = cat.url || String(cat.id);
                return (
                  <button key={key} type="button" aria-pressed={categoria === key} onClick={() => setCategoria(key)}>
                    {cat.nombre || key}
                  </button>
                );
              })}
            </div>
          )}
        </div>

        {loading ? (
          <div className="d-flex justify-content-center py-5">
            <div className="spinner-border text-primary" role="status">
              <span className="visually-hidden">Cargando productos…</span>
            </div>
          </div>
        ) : error ? (
          <div className="pn-empty">
            <SearchX size={40} />
            <h3>No se cargaron los productos</h3>
            <p>{error}</p>
            <button type="button" className="pn-btn pn-btn--primary" onClick={fetchProductos}>Reintentar</button>
          </div>
        ) : filtrados.length === 0 ? (
          <div className="pn-empty">
            <SearchX size={40} />
            <h3>Sin resultados</h3>
            <p>
              {busqueda ? `No encontramos productos para “${busqueda}”.` : 'No hay productos en esta categoría por ahora.'}
            </p>
            <button
              type="button"
              className="pn-btn pn-btn--ghost"
              onClick={() => { setBusqueda(''); setCategoria('todos'); }}
            >
              Ver todos los productos
            </button>
          </div>
        ) : (
          <>
            <div className="pn-grid">
              {mostrados.map((producto) => (
                <ProductCard key={producto.id} producto={producto} />
              ))}
            </div>
            {filtrados.length > POR_PAGINA && (
              <div className="pn-more">
                <span>Mostrando {mostrados.length} de {filtrados.length}</span>
                {mostrados.length < filtrados.length && (
                  <button type="button" className="pn-btn pn-btn--ghost" onClick={() => setVisibles((v) => v + POR_PAGINA)}>
                    Ver más productos
                  </button>
                )}
              </div>
            )}
          </>
        )}
      </section>

      <Footer />
    </>
  );
};

export default Home;
