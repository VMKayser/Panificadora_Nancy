import React, { Suspense, useEffect, useState } from 'react';
import { Container } from 'react-bootstrap';
import '../styles/Footer.css';
import { useSiteConfig } from '../context/SiteConfigContext';
import Spinner from 'react-bootstrap/Spinner';

const AnimatedFooter = React.lazy(() => import('./AnimatedFooter'));

const Footer = () => {
  const { logoUrl } = useSiteConfig();
  const [showAnimated, setShowAnimated] = useState(false);

  // Defer loading the animated footer until after first paint/hydration to avoid pulling framer-motion
  useEffect(() => {
    const t = setTimeout(() => setShowAnimated(true), 600); // small delay
    return () => clearTimeout(t);
  }, []);

  // Lightweight static footer markup shown while animation chunk hasn't loaded.
  const StaticFooter = (
    <footer className="footer-modern">
      <Container className="py-4 text-center">
        <img src={logoUrl || `${import.meta.env.BASE_URL}images/logo.jpg`} alt="Panificadora Nancy" className="footer-logo" style={{ maxWidth: 120 }} />
        <p className="mt-2">© {new Date().getFullYear()} Panificadora Nancy. Todos los derechos reservados.</p>
      </Container>
    </footer>
  );

  if (!showAnimated) return StaticFooter;

  return (
    <Suspense fallback={StaticFooter}>
      <AnimatedFooter />
    </Suspense>
  );
};

export default Footer;
