import React, { createContext, useContext, useEffect, useState, useCallback } from 'react';
import api from '../services/api';

// Simple in-memory cache for site config (logo/qr). TTL is short because
// admins may update images; keep 5 minutes by default.
let _siteConfigCache = null;
let _siteConfigAt = 0;
const SITE_CONFIG_TTL = 1000 * 60 * 5; // 5 minutes

const SiteConfigContext = createContext({
  logoUrl: null,
  qrUrl: null,
  refresh: () => {}
});

export const SiteConfigProvider = ({ children }) => {
  const [logoUrl, setLogoUrl] = useState(null);
  const [qrUrl, setQrUrl] = useState(null);

  const defaultLogo = `${import.meta.env.BASE_URL}images/logo.jpg`;

  // Keep a small cache-bust token so clients reload the logo when updated
  const [cacheToken, setCacheToken] = useState(() => localStorage.getItem('site_config_version') || Date.now().toString());

  const loadConfig = useCallback(async (forceRefresh = false) => {
    try {
      // Use in-memory cache to avoid repeated fetches across components
      const now = Date.now();
      if (!forceRefresh && _siteConfigCache && now - _siteConfigAt < SITE_CONFIG_TTL) {
        const { logoVal, qrVal } = _siteConfigCache;
        setLogoUrl(logoVal ? `${logoVal}?v=${cacheToken}` : null);
        setQrUrl(qrVal ? `${qrVal}?v=${cacheToken}` : null);
        return;
      }

      // Public endpoint (no auth) that returns only whitelisted config keys
      const logoResp = await api.get(`/configuraciones/public/logo_url/valor`).catch(() => null);
      const logoVal = logoResp && logoResp.data && typeof logoResp.data.valor !== 'undefined' ? logoResp.data.valor : null;

      const qrResp = await api.get(`/configuraciones/public/qr_pago_url/valor`).catch(() => null);
      const qrVal = qrResp && qrResp.data && typeof qrResp.data.valor !== 'undefined' ? qrResp.data.valor : null;

      // Save to in-memory cache
      _siteConfigCache = { logoVal, qrVal };
      _siteConfigAt = Date.now();

      setLogoUrl(logoVal ? `${logoVal}?v=${cacheToken}` : null);
      setQrUrl(qrVal ? `${qrVal}?v=${cacheToken}` : null);
    } catch (err) {
      console.error('Error loading site config', err);
      setLogoUrl(null);
      setQrUrl(null);
    }
  }, [cacheToken]);

  useEffect(() => {
    loadConfig();
    // Listen to storage events from other tabs so logo updates propagate
    const handler = (e) => {
      if (e.key === 'site_config_update') {
        const newVersion = localStorage.getItem('site_config_version') || Date.now().toString();
        setCacheToken(newVersion);
      }
    };
    window.addEventListener('storage', handler);

    return () => window.removeEventListener('storage', handler);
  }, [loadConfig]);

  // Expose a refresh that forces a server reload and updates the local cache token
  const refresh = async () => {
    const newVersion = Date.now().toString();
    localStorage.setItem('site_config_version', newVersion);
    // update cache token so URLs change (bust client caching)
    setCacheToken(newVersion);
    // force reload from server and update in-memory cache
    await loadConfig(true);
    // notify other tabs
    try {
      localStorage.setItem('site_config_update', newVersion);
      // keep the key stable for other tabs to pick up; do not remove it
    } catch (e) {
      // ignore
    }
  };

  return (
    <SiteConfigContext.Provider value={{ logoUrl: logoUrl || defaultLogo, qrUrl, refresh }}>
      {children}
    </SiteConfigContext.Provider>
  );
};

export const useSiteConfig = () => {
  return useContext(SiteConfigContext);
};

export default SiteConfigContext;
