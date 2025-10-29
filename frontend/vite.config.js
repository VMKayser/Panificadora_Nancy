import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import fs from 'fs'
import path from 'path'

// Load .env in frontend folder so VITE_DEV_TUNNEL_HOST is available at config time
const envPath = path.resolve(__dirname, '.env');
if (fs.existsSync(envPath)) {
  const raw = fs.readFileSync(envPath, 'utf8');
  raw.split('\n').forEach(line => {
    const m = line.match(/^\s*([A-Za-z0-9_]+)\s*=\s*(.*)\s*$/);
    if (m) {
      let val = m[2] || '';
      // Remove surrounding quotes
      if ((val.startsWith('"') && val.endsWith('"')) || (val.startsWith("'") && val.endsWith("'"))) {
        val = val.slice(1, -1);
      }
      if (!process.env[m[1]]) process.env[m[1]] = val;
    }
  });
}

// https://vitejs.dev/config/
export default defineConfig(async () => {
  const plugins = [react()];

    if (process.env.ANALYZE === 'true') {
    try {
      const mod = await import('rollup-plugin-visualizer');
      const visualizer = mod.visualizer || mod.default || mod;
      // If the caller requested JSON raw-data output, write stats JSON for programmatic analysis
      if (process.env.ANALYZE_JSON === 'true') {
        const filename = path.resolve(__dirname, 'dist', 'bundle-stats.json');
        plugins.push(visualizer({ filename, template: 'raw-data', emitFile: false }));
        console.log('[vite.config] visualizer (raw-data) enabled, will write:', filename);
      }
      // Also keep the human-friendly HTML when ANALYZE is set
      const filenameHtml = path.resolve(__dirname, 'dist', 'bundle-analysis.html');
      plugins.push(visualizer({ filename: filenameHtml, gzipSize: true, brotliSize: true }));
      console.log('[vite.config] visualizer (html) enabled, will write:', filenameHtml);
    } catch (e) {
      console.error('[vite.config] visualizer import failed:', e && e.message ? e.message : e);
    }
  }

  return {
    plugins,
    base: '/app/', // Ruta base para servir desde /app en Laravel
    // Enable sourcemaps when analyzing so bundle visualizers can show composition
    build: {
      sourcemap: process.env.ANALYZE === 'true',
      // Manual chunking: separate large vendor libs into dedicated chunks for better caching
      rollupOptions: {
        output: {
          manualChunks(id) {
            if (!id) return;
            if (id.includes('node_modules')) {
              if (id.includes('react-dom') || id.match(/node_modules\/react(\/|$)/)) {
                return 'vendor.react';
              }
              if (id.includes('framer-motion') || id.includes('motion-dom')) {
                return 'vendor.motion';
              }
              if (id.includes('react-bootstrap') || id.includes('@restart') || id.includes('@popperjs')) {
                return 'vendor.bootstrap';
              }
              if (id.includes('date-fns')) {
                return 'vendor.datefns';
              }
            }
          }
        }
      }
    },
    server: {
      // permitir conexiones externas si se necesita (dev tunnel) y definir proxy
      host: '0.0.0.0', // Exponer a todas las interfaces de red
      port: 5174,
      strictPort: true, // Fallar si el puerto no está disponible
      // HMR dinámico: si se expone el servidor mediante un túnel público (p. ej. VS Code Ports)
      // podemos pasar VITE_DEV_TUNNEL_HOST para que el cliente HMR use wss://<tunnel-host>
      hmr: process.env.VITE_DEV_TUNNEL_HOST ? {
        protocol: 'wss',
        host: process.env.VITE_DEV_TUNNEL_HOST.replace(/(^https?:\/\/)/, ''),
        clientPort: 443,
      } : undefined,
      proxy: {
        // reenvía las llamadas /api al backend Laravel (Docker Sail en puerto 80)
        '/api': {
          target: 'http://localhost:80',
          changeOrigin: true,
          secure: false,
          configure: (proxy, _options) => {
            proxy.on('error', (err, _req, _res) => {
              console.log('[Proxy] Error:', err);
            });
            proxy.on('proxyReq', (proxyReq, req, _res) => {
              // Mark proxied requests as AJAX so Laravel's expectsJson() returns true
              try {
                proxyReq.setHeader('X-Requested-With', 'XMLHttpRequest');
              } catch (e) {
                // ignore if not supported by the proxy implementation
              }
              console.log('[Proxy] Sending Request:', req.method, req.url, '→', proxyReq.path);
            });
            proxy.on('proxyRes', (proxyRes, req, _res) => {
              console.log('[Proxy] Received Response:', proxyRes.statusCode, 'from', req.url);
            });
          },
        }
      }
    }
  }
});
