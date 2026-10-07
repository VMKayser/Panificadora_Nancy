import { createContext, useContext, useEffect, useState, useCallback } from 'react';
import api from '../services/api';

// Logo y QR que el admin configura en su perfil. La última respuesta se guarda
// en localStorage para pintar el logo correcto desde el primer momento en las
// visitas siguientes; en la primera visita se usa la copia local del logo
// oficial (public/images) mientras responde la API.
const CLAVE_CACHE = 'pn_site_config';

const leerCache = () => {
  try {
    return JSON.parse(localStorage.getItem(CLAVE_CACHE) || 'null') || {};
  } catch {
    return {};
  }
};

const valorDe = (resp, campo = 'valor') => resp?.data?.[campo] || null;

// Copia local del logo oficial para la primera visita (o si la API falla)
const LOGO_LOCAL = {
  chico: `${import.meta.env.BASE_URL}images/logo-128.webp`,
  grande: `${import.meta.env.BASE_URL}images/logo-600.webp`,
};

const SiteConfigContext = createContext({
  logoUrl: null,
  qrUrl: null,
  refresh: () => {}
});

export const SiteConfigProvider = ({ children }) => {
  const [config, setConfig] = useState(leerCache);

  // Los archivos subidos tienen nombre único (hash), así que un logo nuevo
  // trae otra URL y no hace falta romper la caché del navegador.
  const loadConfig = useCallback(async () => {
    const [logoResp, qrResp] = await Promise.all([
      api.get('/configuraciones/public/logo_url/valor').catch(() => null),
      api.get('/configuraciones/public/qr_pago_url/valor').catch(() => null),
    ]);
    // Si la API no respondió, se queda con lo que había
    if (!logoResp && !qrResp) return;
    const nuevo = {
      logoUrl: valorDe(logoResp),
      logoChico: valorDe(logoResp, 'miniatura'),
      logoGrande: valorDe(logoResp, 'mediana'),
      qrUrl: valorDe(qrResp),
    };
    setConfig(nuevo);
    try {
      localStorage.setItem(CLAVE_CACHE, JSON.stringify(nuevo));
    } catch {
      // Sin localStorage solo se pierde la caché entre visitas
    }
  }, []);

  useEffect(() => {
    loadConfig();
    // Si el admin cambia el logo en otra pestaña, recargar
    const handler = (e) => {
      if (e.key === 'site_config_update') loadConfig();
    };
    window.addEventListener('storage', handler);
    return () => window.removeEventListener('storage', handler);
  }, [loadConfig]);

  const refresh = async () => {
    await loadConfig();
    try {
      localStorage.setItem('site_config_update', Date.now().toString());
    } catch {
      // ignorar
    }
  };

  return (
    <SiteConfigContext.Provider
      value={{
        logoUrl: config.logoUrl || null,
        // Cabecera y pie (48 px) y portada (290 px): la versión reducida si existe
        logoChico: config.logoChico || config.logoUrl || LOGO_LOCAL.chico,
        logoGrande: config.logoGrande || config.logoUrl || LOGO_LOCAL.grande,
        qrUrl: config.qrUrl || null,
        refresh,
      }}
    >
      {children}
    </SiteConfigContext.Provider>
  );
};

// El archivo exporta el Provider y su hook: separarlos no aporta y el
// único efecto es que Fast Refresh recarga la página al editar este archivo.
// eslint-disable-next-line react-refresh/only-export-components
export const useSiteConfig = () => {
  return useContext(SiteConfigContext);
};

export default SiteConfigContext;
