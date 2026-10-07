import { defineConfig, loadEnv } from 'vite'
import react from '@vitejs/plugin-react'

// Minimal, stable Vite config for development with explicit HMR and ignored
// watch paths to avoid reloads triggered by backend or vendor changes.
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  for (const [k, v] of Object.entries(env)) if (process.env[k] === undefined) process.env[k] = v

  return {
    plugins: [react()],
    base: '/',
    build: { sourcemap: process.env.ANALYZE === 'true' },
    server: {
      host: '0.0.0.0',
      port: 5174,
      strictPort: true,
      hmr: { protocol: 'ws', host: 'localhost', port: 5174 },
      // hmr: process.env.VITE_DEV_TUNNEL_HOST
      //   ? { protocol: 'wss', host: process.env.VITE_DEV_TUNNEL_HOST.replace(/(^https?:\/\/)/, ''), clientPort: 443 }
      //   : { protocol: 'ws', host: 'localhost', port: 5174 },
      watch: {
        ignored: ['**/node_modules/**', '**/.git/**', '../backend/**', '../vendor/**', '**/storage/**', '**/public/storage/**', '**/dist/**']
      },
      proxy: {
        '/api': {
          target: 'http://localhost:80',  // ← Fixed: backend is on port 80
          changeOrigin: true,
          secure: false,
          configure: (proxy) => {
            proxy.on('error', (err) => console.log('[Proxy] Error:', err))
            proxy.on('proxyReq', (proxyReq, req) => {
              try { proxyReq.setHeader('X-Requested-With', 'XMLHttpRequest') } catch (e) { /* cabeceras ya enviadas */ }
              console.log('[Proxy] Sending Request:', req.method, req.url, '→', proxyReq.path)
            })
            proxy.on('proxyRes', (proxyRes, req) => console.log('[Proxy] Received Response:', proxyRes.statusCode, 'from', req.url))
          }
        }
      }
    }
  }
})
