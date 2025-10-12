import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [react()],
  base: '/app/', // Ruta base para servir desde /app en Laravel
  server: {
    // permitir conexiones externas si se necesita (dev tunnel) y definir proxy
    host: '0.0.0.0', // Exponer a todas las interfaces de red
    port: 5174,
    strictPort: true, // Fallar si el puerto no está disponible
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
            console.log('[Proxy] Sending Request:', req.method, req.url, '→', proxyReq.path);
          });
          proxy.on('proxyRes', (proxyRes, req, _res) => {
            console.log('[Proxy] Received Response:', proxyRes.statusCode, 'from', req.url);
          });
        },
      }
    }
  }
})
