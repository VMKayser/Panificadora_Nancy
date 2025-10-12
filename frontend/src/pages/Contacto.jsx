import { motion } from 'framer-motion';
import { 
  MapPin, 
  Phone, 
  MessageCircle,
  Facebook,
  Instagram
} from 'lucide-react';
import Footer from '../components/Footer';
import './Contacto.css';

const Contacto = () => {

  const whatsappNumber = '59176490687'; // Código de país Bolivia + número
  const phoneDisplay = '+591 764 90687';
  const phoneLink = '+59176490687';
  const whatsappMessage = encodeURIComponent('Hola, me gustaría obtener más información sobre sus productos.');
  
  const socialLinks = {
    facebook: 'https://www.facebook.com/profile.php?id=61557646906876',
    instagram: 'https://www.instagram.com/panificadora_nancy01',
    googleMaps: 'https://maps.app.goo.gl/vpcVxdugSOBICI32Y'
  };

  const fadeInUp = {
    hidden: { opacity: 0, y: 60 },
    visible: { 
      opacity: 1, 
      y: 0,
      transition: { duration: 0.6, ease: "easeOut" }
    }
  };

  return (
    <div className="contacto-page">
      {/* Hero Section */}
      <motion.section 
        className="contacto-hero"
        initial="hidden"
        animate="visible"
        variants={fadeInUp}
      >
        <div className="hero-overlay">
          <motion.h1 
            className="hero-title"
            initial={{ opacity: 0, y: -30 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.2, duration: 0.8 }}
          >
            Contáctanos
          </motion.h1>
          <motion.p 
            className="hero-subtitle"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            transition={{ delay: 0.4, duration: 0.8 }}
          >
            Estamos aquí para atenderte
          </motion.p>
        </div>
      </motion.section>

      {/* Información de Contacto Rápida */}
      <section className="contacto-quick-info">
        <div className="container">
          <div className="quick-info-grid">
            {/* WhatsApp */}
            <motion.a
              href={`https://wa.me/${whatsappNumber}?text=${whatsappMessage}`}
              target="_blank"
              rel="noopener noreferrer"
              className="quick-info-card whatsapp-card"
              initial="hidden"
              whileInView="visible"
              viewport={{ once: true, amount: 0.3 }}
              variants={fadeInUp}
              whileHover={{ scale: 1.05, y: -5 }}
              whileTap={{ scale: 0.95 }}
            >
              <div className="card-icon whatsapp-icon">
                <MessageCircle size={32} />
              </div>
              <h3>WhatsApp</h3>
              <p className="contact-detail">{phoneDisplay}</p>
              <span className="card-badge">Chat directo</span>
            </motion.a>

            {/* Teléfono */}
            <motion.a
              href={`tel:${phoneLink}`}
              className="quick-info-card phone-card"
              initial="hidden"
              whileInView="visible"
              viewport={{ once: true, amount: 0.3 }}
              variants={fadeInUp}
              whileHover={{ scale: 1.05, y: -5 }}
              whileTap={{ scale: 0.95 }}
            >
              <div className="card-icon phone-icon">
                <Phone size={32} />
              </div>
              <h3>Llámanos</h3>
              <p className="contact-detail">{phoneDisplay}</p>
              <span className="card-badge">Disponible</span>
            </motion.a>

            {/* Ubicación */}
            <motion.a
              href={socialLinks.googleMaps}
              target="_blank"
              rel="noopener noreferrer"
              className="quick-info-card location-card"
              initial="hidden"
              whileInView="visible"
              viewport={{ once: true, amount: 0.3 }}
              variants={fadeInUp}
              whileHover={{ scale: 1.05, y: -5 }}
            >
              <div className="card-icon location-icon">
                <MapPin size={32} />
              </div>
              <h3>Ubicación</h3>
              <p className="contact-detail">Av. Martín Cardenas</p>
              <span className="card-badge">Quillacollo, Cochabamba</span>
            </motion.a>
          </div>
        </div>
      </section>

      {/* Mapa y Resumen */}
      <section className="contacto-main">
        <div className="container">
          <div className="contacto-grid">
            {/* Mapa */}
            <motion.div
              className="map-container"
              initial="hidden"
              whileInView="visible"
              viewport={{ once: true, amount: 0.3 }}
              variants={fadeInUp}
            >
              <h2 className="section-subtitle">
                <MapPin size={24} style={{ marginRight: '8px' }} />
                Encuéntranos
              </h2>
              
              {/* Mapa embebido de Google Maps */}
              <div className="map-wrapper">
                <iframe
                  src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3807.171494047522!2d-66.28152411349427!3d-17.40355569999998!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x93e30ba453da6e15%3A0x8b450cc22c162906!2sPanificadora%20Nancy!5e0!3m2!1ses-419!2sbo!4v1760199467626!5m2!1ses-419!2sbo"
                  width="100%"
                  height="100%"
                  style={{ border: 0, borderRadius: '15px' }}
                  allowFullScreen=""
                  loading="lazy"
                  referrerPolicy="no-referrer-when-downgrade"
                  title="Ubicación Panificadora Nancy - Quillacollo, Cochabamba"
                ></iframe>
              </div>

              {/* Información adicional */}
              <div className="location-details">
                <div className="detail-item">
                  <MapPin size={20} />
                  <div>
                    <strong>Dirección</strong>
                    <p>HPW9+J94, Av. Martín Cardenas</p>
                    <p>Quillacollo, Cochabamba - Bolivia</p>
                  </div>
                </div>
                <div className="detail-item">
                  <Phone size={20} />
                  <div>
                    <strong>Teléfono y WhatsApp</strong>
                    <p>{phoneDisplay}</p>
                  </div>
                </div>
              </div>
            </motion.div>

            {/* Resumen de la Panadería */}
            <motion.div
              className="about-summary"
              initial="hidden"
              whileInView="visible"
              viewport={{ once: true, amount: 0.3 }}
              variants={fadeInUp}
            >
              <h2 className="section-subtitle">Panificadora Nancy</h2>
              
              <div className="summary-content">
                <p className="summary-text">
                  Desde hace más de 30 años, elaboramos pan artesanal con recetas 
                  tradicionales y los mejores ingredientes. Cada producto es preparado 
                  con dedicación y amor, manteniendo la calidad que nos caracteriza.
                </p>
                
                <div className="summary-highlights">
                  <motion.div 
                    className="highlight-item"
                    whileHover={{ scale: 1.05 }}
                    transition={{ type: "spring", stiffness: 300 }}
                  >
                    <div className="highlight-icon">🍞</div>
                    <div>
                      <strong>Pan Fresco Diario</strong>
                      <p>Elaborado artesanalmente</p>
                    </div>
                  </motion.div>
                  
                  <motion.div 
                    className="highlight-item"
                    whileHover={{ scale: 1.05 }}
                    transition={{ type: "spring", stiffness: 300 }}
                  >
                    <div className="highlight-icon">⭐</div>
                    <div>
                      <strong>+30 Años</strong>
                      <p>Tradición y calidad garantizada</p>
                    </div>
                  </motion.div>
                  
                  <motion.div 
                    className="highlight-item"
                    whileHover={{ scale: 1.05 }}
                    transition={{ type: "spring", stiffness: 300 }}
                  >
                    <div className="highlight-icon">❤️</div>
                    <div>
                      <strong>Hecho con Amor</strong>
                      <p>Productos con dedicación</p>
                    </div>
                  </motion.div>
                </div>

                {/* Botón de WhatsApp destacado */}
                <div className="whatsapp-contact">
                  <h3>¿Quieres hacer un pedido?</h3>
                  <p>Contáctanos directamente por WhatsApp</p>
                  <motion.a
                    href={`https://wa.me/${whatsappNumber}?text=${whatsappMessage}`}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="whatsapp-btn-large"
                    whileHover={{ scale: 1.05 }}
                    whileTap={{ scale: 0.95 }}
                  >
                    <MessageCircle size={24} />
                    Chatear por WhatsApp
                  </motion.a>
                  <span className="phone-display">📱 {phoneDisplay}</span>
                </div>

                {/* Redes Sociales Compactas */}
                <div className="social-compact">
                  <h3>Síguenos en Redes Sociales</h3>
                  <div className="social-icons-row">
                    <motion.a
                      href={socialLinks.facebook}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="social-icon-btn facebook"
                      whileHover={{ scale: 1.1, rotate: 5 }}
                      whileTap={{ scale: 0.9 }}
                      title="Síguenos en Facebook"
                    >
                      <Facebook size={28} fill="currentColor" />
                    </motion.a>

                    <motion.a
                      href={socialLinks.instagram}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="social-icon-btn instagram"
                      whileHover={{ scale: 1.1, rotate: -5 }}
                      whileTap={{ scale: 0.9 }}
                      title="Síguenos en Instagram"
                    >
                      <Instagram size={28} />
                    </motion.a>

                    <motion.a
                      href={`https://wa.me/${whatsappNumber}`}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="social-icon-btn whatsapp"
                      whileHover={{ scale: 1.1, rotate: 5 }}
                      whileTap={{ scale: 0.9 }}
                      title="Escríbenos por WhatsApp"
                    >
                      <MessageCircle size={28} />
                    </motion.a>

                    <motion.a
                      href="tel:74243996"
                      className="social-icon-btn phone"
                      whileHover={{ scale: 1.1, rotate: -5 }}
                      whileTap={{ scale: 0.9 }}
                      title="Llámanos"
                    >
                      <Phone size={28} />
                    </motion.a>
                  </div>
                  <p className="social-text">
                    Mantente al día con nuestras promociones y novedades
                  </p>
                </div>
              </div>
            </motion.div>
          </div>
        </div>
      </section>
      
      <Footer />
    </div>
  );
};

export default Contacto;
