import { Container, Row, Col } from 'react-bootstrap';
import { Facebook, Instagram, Phone, MapPin, MessageCircle } from 'lucide-react';
import { motion } from 'framer-motion';
import '../styles/Footer.css';

const Footer = () => {
  const currentYear = new Date().getFullYear();

  // Enlaces de redes sociales - URLs reales de Panificadora Nancy
  const socialLinks = {
    facebook: 'https://www.facebook.com/profile.php?id=61557646906876',
    instagram: 'https://www.instagram.com/panificadora_nancy01',
    whatsapp: 'https://wa.me/59176490687', // Código Bolivia (+591)
    googleMaps: 'https://maps.app.goo.gl/vpcVxdugSOBICI32Y'
  };

  // Animaciones
  const containerVariants = {
    hidden: { opacity: 0 },
    visible: {
      opacity: 1,
      transition: {
        staggerChildren: 0.1
      }
    }
  };

  const itemVariants = {
    hidden: { opacity: 0, y: 20 },
    visible: { opacity: 1, y: 0 }
  };

  return (
    <footer className="footer-modern">
      <Container>
        <motion.div
          initial="hidden"
          whileInView="visible"
          viewport={{ once: true }}
          variants={containerVariants}
        >
          <Row className="footer-content">
            {/* Columna 1: Sobre Nosotros */}
            <Col lg={4} md={6} className="footer-col mb-4">
              <motion.div variants={itemVariants}>
                <div className="footer-brand">
                  <img 
                    src={`${import.meta.env.BASE_URL}images/logo.jpg`}
                    alt="Panificadora Nancy" 
                    className="footer-logo"
                  />
                  <h3 className="footer-title">Panificadora Nancy</h3>
                </div>
                <p className="footer-description">
                  Más de 30 años elaborando pan artesanal con amor y dedicación. 
                  Tradición, calidad y sabor en cada producto.
                </p>
                <div className="footer-info">
                  <motion.div 
                    className="info-item"
                    whileHover={{ x: 5 }}
                    transition={{ type: "spring", stiffness: 300 }}
                  >
                    <MapPin size={18} />
                    <a 
                      href={socialLinks.googleMaps}
                      target="_blank"
                      rel="noopener noreferrer"
                      style={{ color: 'inherit', textDecoration: 'none' }}
                    >
                      HPW9+J94, Av. Martín Cardenas, Quillacollo
                    </a>
                  </motion.div>
                  <motion.div 
                    className="info-item"
                    whileHover={{ x: 5 }}
                    transition={{ type: "spring", stiffness: 300 }}
                  >
                    <Phone size={18} />
                    <a 
                      href={`tel:+59176490687`}
                      style={{ color: 'inherit', textDecoration: 'none' }}
                    >
                      +591 764 90687
                    </a>
                  </motion.div>
                </div>
              </motion.div>
            </Col>

            {/* Columna 2: Enlaces Rápidos */}
            <Col lg={2} md={6} className="footer-col mb-4">
              <motion.div variants={itemVariants}>
                <h4 className="footer-subtitle">Información</h4>
                <ul className="footer-links">
                  <li><a href="/app/">Inicio</a></li>
                  <li><a href="/app/nosotros">Nosotros</a></li>
                  <li><a href="/app/contacto">Contacto</a></li>
                  <li>
                    <a 
                      href={socialLinks.googleMaps}
                      target="_blank"
                      rel="noopener noreferrer"
                    >
                      Ver en mapa
                    </a>
                  </li>
                </ul>
              </motion.div>
            </Col>

            {/* Columna 3: Contacto */}
            <Col lg={3} md={6} className="footer-col mb-4">
              <motion.div variants={itemVariants}>
                <h4 className="footer-subtitle">Contáctanos</h4>
                <div className="footer-contact">
                  <motion.a 
                    href={`tel:+59176490687`} 
                    className="contact-link"
                    whileHover={{ scale: 1.02, x: 5 }}
                    transition={{ type: "spring", stiffness: 300 }}
                  >
                    <Phone size={18} />
                    <span>+591 764 90687</span>
                  </motion.a>
                  <motion.a 
                    href={socialLinks.whatsapp}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="contact-link whatsapp-link"
                    whileHover={{ scale: 1.02, x: 5 }}
                    transition={{ type: "spring", stiffness: 300 }}
                  >
                    <MessageCircle size={18} />
                    <span>Escríbenos por WhatsApp</span>
                  </motion.a>
                  <motion.a 
                    href={socialLinks.googleMaps}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="contact-link"
                    whileHover={{ scale: 1.02, x: 5 }}
                    transition={{ type: "spring", stiffness: 300 }}
                  >
                    <MapPin size={18} />
                    <span>Cochabamba, Bolivia</span>
                  </motion.a>
                </div>
              </motion.div>
            </Col>

            {/* Columna 4: Redes Sociales */}
            <Col lg={3} md={6} className="footer-col mb-4">
              <motion.div variants={itemVariants}>
                <h4 className="footer-subtitle">Síguenos</h4>
                <p className="social-text">Conecta con nosotros en redes sociales</p>
                <div className="social-links">
                  <motion.a 
                    href={socialLinks.facebook} 
                    target="_blank" 
                    rel="noopener noreferrer"
                    className="social-icon facebook"
                    aria-label="Facebook"
                    whileHover={{ y: -6, scale: 1.08 }}
                    whileTap={{ scale: 0.95 }}
                    transition={{ type: "spring", stiffness: 400, damping: 17 }}
                  >
                    <Facebook size={24} />
                  </motion.a>
                  <motion.a 
                    href={socialLinks.instagram} 
                    target="_blank" 
                    rel="noopener noreferrer"
                    className="social-icon instagram"
                    aria-label="Instagram"
                    whileHover={{ y: -6, scale: 1.08 }}
                    whileTap={{ scale: 0.95 }}
                    transition={{ type: "spring", stiffness: 400, damping: 17 }}
                  >
                    <Instagram size={24} />
                  </motion.a>
                  <motion.a 
                    href={socialLinks.whatsapp} 
                    target="_blank" 
                    rel="noopener noreferrer"
                    className="social-icon whatsapp"
                    aria-label="WhatsApp"
                    whileHover={{ y: -6, scale: 1.08 }}
                    whileTap={{ scale: 0.95 }}
                    transition={{ type: "spring", stiffness: 400, damping: 17 }}
                  >
                    <MessageCircle size={24} />
                  </motion.a>
                </div>
              </motion.div>
            </Col>
          </Row>

          {/* Copyright */}
          <motion.div 
            className="footer-bottom"
            variants={itemVariants}
          >
            <p className="copyright">
              © {currentYear} Panificadora Nancy. Todos los derechos reservados.
            </p>
            <p className="made-with">
              Hecho con <span className="heart">❤️</span> en Cochabamba, Bolivia
            </p>
          </motion.div>
        </motion.div>
      </Container>
    </footer>
  );
};

export default Footer;
