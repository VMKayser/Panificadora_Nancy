import { Link } from 'react-router-dom';
import { ArrowRight, Clock, Facebook, Instagram, MapPin, MessageCircle, Phone } from 'lucide-react';
import Footer from '../components/Footer';
import { useSEO } from '../hooks/useSEO';
import { enlaceWhatsapp, WHATSAPP_TIENDA } from '../utils/whatsapp';

const TELEFONO = { mostrar: '+591 764 90687', enlace: '+59176490687' };

const ENLACES = {
  whatsapp: enlaceWhatsapp(WHATSAPP_TIENDA, 'Hola, me gustaría obtener más información sobre sus productos.'),
  facebook: 'https://www.facebook.com/profile.php?id=61557646906876',
  instagram: 'https://www.instagram.com/panificadora_nancy01',
  mapa: 'https://www.google.com/maps/search/?api=1&query=-17.403381642688004,-66.2815992191286',
};

const MAPA_EMBEBIDO = 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3807.171494047522!2d-66.28152411349427!3d-17.40355569999998!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x93e30ba453da6e15%3A0x8b450cc22c162906!2sPanificadora%20Nancy!5e0!3m2!1ses-419!2sbo!4v1760199467626!5m2!1ses-419!2sbo';

const Contacto = () => {
  useSEO({
    title: 'Contacto - Panificadora Nancy',
    description: 'Escríbenos por WhatsApp, llámanos o visítanos en Av. Martín Cardenas, Quillacollo.',
  });

  return (
    <>
      <main>
        <section className="pn-pagehero">
          <div className="pn-wrap">
            <h1>Contáctanos</h1>
            <p>Escríbenos, llámanos o visítanos en Quillacollo.</p>
          </div>
        </section>

        <div className="pn-wrap pn-page">
          <div className="pn-acciones">
            <a href={ENLACES.whatsapp} target="_blank" rel="noopener noreferrer" className="pn-accion">
              <span className="pn-accion__icon pn-accion__icon--wa"><MessageCircle size={22} /></span>
              <span className="pn-accion__txt">
                <strong>WhatsApp</strong>
                <small>{TELEFONO.mostrar}</small>
              </span>
              <ArrowRight size={18} className="pn-accion__ir" aria-hidden="true" />
            </a>
            <a href={`tel:${TELEFONO.enlace}`} className="pn-accion">
              <span className="pn-accion__icon"><Phone size={22} /></span>
              <span className="pn-accion__txt">
                <strong>Llámanos</strong>
                <small>{TELEFONO.mostrar}</small>
              </span>
              <ArrowRight size={18} className="pn-accion__ir" aria-hidden="true" />
            </a>
            <a href={ENLACES.mapa} target="_blank" rel="noopener noreferrer" className="pn-accion">
              <span className="pn-accion__icon"><MapPin size={22} /></span>
              <span className="pn-accion__txt">
                <strong>Cómo llegar</strong>
                <small>Av. Martín Cardenas, Quillacollo</small>
              </span>
              <ArrowRight size={18} className="pn-accion__ir" aria-hidden="true" />
            </a>
          </div>

          <div className="pn-contacto">
            <section className="pn-panel" aria-labelledby="encuentranos">
              <h2 className="pn-panel__title" id="encuentranos">Encuéntranos</h2>
              <div className="pn-mapa">
                <iframe
                  src={MAPA_EMBEBIDO}
                  allowFullScreen
                  loading="lazy"
                  referrerPolicy="no-referrer-when-downgrade"
                  title="Ubicación de Panificadora Nancy en Quillacollo"
                />
              </div>
              <p className="pn-contacto__dir">
                <MapPin size={18} aria-hidden="true" />
                <span>HPW9+J94, Av. Martín Cardenas<br />Quillacollo, Cochabamba - Bolivia</span>
              </p>
            </section>

            <aside className="pn-stack">
              <section className="pn-panel" aria-labelledby="horario">
                <h2 className="pn-panel__title" id="horario">Horario</h2>
                <p className="pn-contacto__dato"><Clock size={18} aria-hidden="true" /> Lunes a sábado, de 8:00 a 20:00</p>
              </section>

              <section className="pn-panel" aria-labelledby="redes">
                <h2 className="pn-panel__title" id="redes">Síguenos</h2>
                <p className="pn-contacto__nota">Promociones y novedades de temporada.</p>
                <div className="pn-redes">
                  <a href={ENLACES.facebook} target="_blank" rel="noopener noreferrer" className="pn-btn pn-btn--ghost">
                    <Facebook size={18} /> Facebook
                  </a>
                  <a href={ENLACES.instagram} target="_blank" rel="noopener noreferrer" className="pn-btn pn-btn--ghost">
                    <Instagram size={18} /> Instagram
                  </a>
                </div>
              </section>

              <section className="pn-panel pn-contacto__pedido">
                <h2 className="pn-panel__title">¿Quieres hacer un pedido?</h2>
                <p className="pn-contacto__nota">Elige tus productos y pide en línea; recoges en la tienda o te lo llevamos.</p>
                <Link to="/productos" className="pn-btn pn-btn--primary pn-btn--block">Ver productos</Link>
              </section>
            </aside>
          </div>
        </div>
      </main>
      <Footer />
    </>
  );
};

export default Contacto;
