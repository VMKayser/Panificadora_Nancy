import { Link } from 'react-router-dom';
import { Clock, Facebook, Instagram, MapPin, MessageCircle, Phone } from 'lucide-react';
import { useSiteConfig } from '../context/SiteConfigContext';

const ENLACES = {
  facebook: 'https://www.facebook.com/profile.php?id=61557646906876',
  instagram: 'https://www.instagram.com/panificadora_nancy01',
  whatsapp: 'https://wa.me/59176490687',
  mapa: 'https://www.google.com/maps/search/?api=1&query=-17.403381642688004,-66.2815992191286',
};

// Pie de página de la tienda. Estático (sin animaciones al hacer scroll) para
// que el contenido se vea siempre y ocupe poco alto en el móvil.
const Footer = () => {
  const { logoChico } = useSiteConfig();
  const anio = new Date().getFullYear();

  return (
    <footer className="pn-footer">
      <div className="pn-wrap pn-footer__grid">
        <div className="pn-footer__brand">
          <div className="pn-footer__brandrow">
            <img src={logoChico} alt="" width="48" height="48" loading="lazy" decoding="async" />
            <strong>Panificadora Nancy</strong>
          </div>
          <p>Pan artesanal elaborado como en casa, en Quillacollo, Cochabamba.</p>
          <div className="pn-footer__social">
            <a href={ENLACES.facebook} target="_blank" rel="noopener noreferrer" aria-label="Facebook"><Facebook size={20} /></a>
            <a href={ENLACES.instagram} target="_blank" rel="noopener noreferrer" aria-label="Instagram"><Instagram size={20} /></a>
            <a href={ENLACES.whatsapp} target="_blank" rel="noopener noreferrer" aria-label="WhatsApp"><MessageCircle size={20} /></a>
          </div>
        </div>

        <nav aria-labelledby="pie-tienda">
          <h2 id="pie-tienda">Tienda</h2>
          <ul>
            <li><Link to="/productos">Productos</Link></li>
            <li><Link to="/carrito">Mi carrito</Link></li>
            <li><Link to="/mis-pedidos">Mis pedidos</Link></li>
            <li><Link to="/nosotros">Nosotros</Link></li>
            <li><Link to="/contacto">Contacto</Link></li>
          </ul>
        </nav>

        <div>
          <h2>Horario</h2>
          <ul>
            <li>Lunes a sábado</li>
            <li className="d-flex align-items-center gap-2"><Clock size={15} /> 8:00 a 20:00</li>
          </ul>
        </div>

        <div className="pn-footer__contact">
          <h2>Contacto</h2>
          <ul>
            <li><MapPin size={17} /><a href={ENLACES.mapa} target="_blank" rel="noopener noreferrer">HPW9+J94, Av. Martín Cardenas, Quillacollo</a></li>
            <li><Phone size={17} /><a href="tel:+59176490687">+591 764 90687</a></li>
            <li><MessageCircle size={17} /><a href={ENLACES.whatsapp} target="_blank" rel="noopener noreferrer">Escríbenos por WhatsApp</a></li>
          </ul>
        </div>
      </div>

      <div className="pn-wrap pn-footer__bottom">
        <span>© {anio} Panificadora Nancy</span>
        <span>Quillacollo, Cochabamba · Bolivia</span>
      </div>
    </footer>
  );
};

export default Footer;
