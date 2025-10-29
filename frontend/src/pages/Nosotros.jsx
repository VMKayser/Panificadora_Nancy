import { motion } from 'framer-motion';
import { Users, Target, Eye, Heart, Award, Clock } from 'lucide-react';
import Footer from '../components/Footer';
import './Nosotros.css';

const Nosotros = () => {
  // Animaciones
  const fadeInUp = {
    hidden: { opacity: 0, y: 60 },
    visible: { 
      opacity: 1, 
      y: 0,
      transition: { duration: 0.6, ease: "easeOut" }
    }
  };

  const fadeInLeft = {
    hidden: { opacity: 0, x: -60 },
    visible: { 
      opacity: 1, 
      x: 0,
      transition: { duration: 0.6, ease: "easeOut" }
    }
  };

  const fadeInRight = {
    hidden: { opacity: 0, x: 60 },
    visible: { 
      opacity: 1, 
      x: 0,
      transition: { duration: 0.6, ease: "easeOut" }
    }
  };

  return (
    <div className="nosotros-page">
      {/* Hero Section */}
      <motion.section 
        className="nosotros-hero"
        initial="hidden"
        animate="visible"
        variants={fadeInUp}
      >
        {/* Hero image positioned behind the overlay so we can control object-position (top) */}
        <img
          className="nosotros-hero-img"
          src="/images/nosotros1cabecera1.webp"
          alt="Cabecera Panificadora Nancy"
          onError={(e) => { e.target.src = '/images/cabecera.jpg'; }}
       loading="lazy"
       decoding="async"
       style={{ opacity: 0.7 ,
        }}
        />
        <div className="hero-overlay">
          <motion.h1 
            className="hero-title"
            initial={{ opacity: 0, y: -30 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.2, duration: 0.8 }}
          >
            Nuestra Historia  
          </motion.h1>
          <motion.p 
            className="hero-subtitle"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            transition={{ delay: 0.4, duration: 0.8 }}
          >
            Más de 42 años amasando tradición y calidad
          </motion.p>
        </div>
      </motion.section>

      {/* Quiénes Somos - Imagen a la izquierda */}
      <section className="nosotros-section">
        <div className="container">
          <div className="section-grid reverse">
            <motion.div 
              className="section-image"
              initial="hidden"
              whileInView="visible"
              viewport={{ once: true, amount: 0.3 }}
              variants={fadeInLeft}
            >
              <div className="image-wrapper">
                <img 
                  src="/images/primeraImagenNosotros.webp" 
                  alt="Familia Panificadora Nancy"
                  onError={(e) => {
                    e.target.src = 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=800&q=80';
                  }}
                  loading="lazy"
                  decoding="async"
                />
                <div className="image-badge">
                  <Clock className="badge-icon" />
                  <span>42 años de experiencia</span>
                </div>
              </div>
            </motion.div>

            <motion.div 
              className="section-content"
              initial="hidden"
              whileInView="visible"
              viewport={{ once: true, amount: 0.3 }}
              variants={fadeInRight}
            >
              <div className="section-icon">
                <Users size={48} />
              </div>
              <h2 className="section-title">Quiénes Somos</h2>
              <div className="title-divider"></div>
              <p className="section-text">
                <strong>Panificadora Nancy</strong> es más que un negocio; es el corazón de una familia 
                y el resultado de una historia de resiliencia y esfuerzo. Con más de <strong>42 años de 
                trayectoria</strong> en el oficio panadero y <strong>23 años perfeccionando nuestro panetón</strong>, 
                hemos crecido gracias al apoyo incondicional de nuestra familia y a la confianza de nuestros clientes.
              </p>
              <p className="section-text">
                Nacimos de la convicción de que el trabajo hecho con sacrificio, esmero y responsabilidad 
                es capaz de construir un futuro. Somos <strong>artesanos por herencia</strong> y 
                <strong> emprendedores por convicción</strong>, dedicados a llevar un producto de calidad a tu mesa.
              </p>
              <div className="stats-mini">
                <div className="stat-item">
                  <Heart className="stat-icon" />
                  <div>
                    <strong>42+</strong>
                    <span>Años de tradición</span>
                  </div>
                </div>
                <div className="stat-item">
                  <Award className="stat-icon" />
                  <div>
                    <strong>23+</strong>
                    <span>Años de panetón</span>
                  </div>
                </div>
              </div>
            </motion.div>
          </div>
        </div>
      </section>

      {/* Nuestra Misión - Imagen a la derecha */}
      <section className="nosotros-section bg-light">
        <div className="container">
          <div className="section-grid">
            <motion.div 
              className="section-content"
              initial="hidden"
              whileInView="visible"
              viewport={{ once: true, amount: 0.3 }}
              variants={fadeInLeft}
            >
              <div className="section-icon">
                <Target size={48} />
              </div>
              <h2 className="section-title">Nuestra Misión</h2>
              <div className="title-divider"></div>
              <p className="section-text">
                Nuestra misión es <strong>transformar ingredientes de calidad en una experiencia 
                de sabor auténtico</strong>, manteniendo viva la esencia de la panadería tradicional. 
                Cada producto que elaboramos es un reflejo de nuestro compromiso, amasado con paciencia 
                y dedicación para garantizar la calidad que nos caracteriza.
              </p>
              <p className="section-text">
                Nos dedicamos a cumplir con cada pedido con la máxima seriedad, entendiendo que 
                detrás de cada entrega hay un cliente que deposita su confianza en nosotros.
              </p>
              <div className="mission-highlights">
                <div className="highlight-item">
                  <div className="highlight-icon">✓</div>
                  <span>Ingredientes de primera calidad</span>
                </div>
                <div className="highlight-item">
                  <div className="highlight-icon">✓</div>
                  <span>Panadería tradicional artesanal</span>
                </div>
                <div className="highlight-item">
                  <div className="highlight-icon">✓</div>
                  <span>Compromiso con cada cliente</span>
                </div>
              </div>
            </motion.div>

            <motion.div 
              className="section-image"
              initial="hidden"
              whileInView="visible"
              viewport={{ once: true, amount: 0.3 }}
              variants={fadeInRight}
            >
              <div className="image-wrapper">
                <img 
                  src="/images/terceraImagenNosotros.webp" 
                  alt="Elaboración artesanal"
                  onError={(e) => {
                    e.target.src = 'https://images.unsplash.com/photo-1590846406792-0adc7f938f1d?w=800&q=80';
                  }}
                   loading="lazy"
                   decoding="async"
                />
                <div className="image-badge mission-badge">
                  <Heart className="badge-icon" />
                  <span>Hecho con dedicación</span>
                </div>
              </div>
            </motion.div>
          </div>
        </div>
      </section>

      {/* Nuestra Visión - Imagen a la izquierda */}
      <section className="nosotros-section">
        <div className="container">
          <div className="section-grid reverse">
            <motion.div 
              className="section-image"
              initial="hidden"
              whileInView="visible"
              viewport={{ once: true, amount: 0.3 }}
              variants={fadeInLeft}
            >
              <div className="image-wrapper">
                <img 
                  src="/images/segundaImagenNosotros.webp" zz
                  alt="Panetón tradicional"
                  onError={(e) => {
                    e.target.src = 'https://images.unsplash.com/photo-1608198093002-ad4e005484ec?w=800&q=80';
                  }}
                   loading="lazy"
                   decoding="async"
                />
                <div className="image-badge vision-badge">
                  <Award className="badge-icon" />
                  <span>Tradición que perdura</span>
                </div>
              </div>
            </motion.div>

            <motion.div 
              className="section-content"
              initial="hidden"
              whileInView="visible"
              viewport={{ once: true, amount: 0.3 }}
              variants={fadeInRight}
            >
              <div className="section-icon">
                <Eye size={48} />
              </div>
              <h2 className="section-title">Nuestra Visión</h2>
              <div className="title-divider"></div>
              <p className="section-text">
                Nuestra visión es <strong>ser un referente de calidad y tradición en la comunidad</strong>, 
                honrando el legado de un oficio que se aprende con el corazón. Aspiramos a que el espíritu 
                del horno artesanal, ese que une a las familias, siga presente en cada uno de nuestros productos.
              </p>
              <p className="section-text">
                Queremos que <strong>Panificadora Nancy</strong> continúe siendo el símbolo de una historia 
                de superación y que el sabor que creamos siga uniendo a las futuras generaciones en torno a la mesa.
              </p>
              <div className="vision-values">
                <div className="value-card">
                  <div className="value-icon">🏆</div>
                  <h4>Calidad</h4>
                  <p>Excelencia en cada producto</p>
                </div>
                <div className="value-card">
                  <div className="value-icon">👨‍👩‍👧‍👦</div>
                  <h4>Familia</h4>
                  <p>Uniendo generaciones</p>
                </div>
                <div className="value-card">
                  <div className="value-icon">❤️</div>
                  <h4>Tradición</h4>
                  <p>Legado artesanal</p>
                </div>
              </div>
            </motion.div>
          </div>
        </div>
      </section>

      {/* CTA Section */}
      <motion.section 
        className="nosotros-cta"
        initial="hidden"
        whileInView="visible"
        viewport={{ once: true, amount: 0.3 }}
        variants={fadeInUp}
      >
        <div className="container">
          <div className="cta-content">
            <h2>Forma Parte de Nuestra Historia</h2>
            <p>Descubre el sabor de la tradición y la calidad en cada bocado</p>
            <div className="cta-buttons">
              <a href="/productos" className="btn-primary">Ver Productos</a>
              <a href="/contacto" className="btn-secondary">Contáctanos</a>
            </div>
          </div>
        </div>
      </motion.section>
      
      <Footer />
    </div>
  );
};

export default Nosotros;
