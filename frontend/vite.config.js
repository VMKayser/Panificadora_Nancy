import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [react()],
  server: {
    // permitir conexiones externas si se necesita (dev tunnel) y definir proxy
    host: true,
    port: 5174,
    proxy: {
      // reenvía las llamadas /api al backend (ajusta target si tu backend corre en otra dirección/puerto)
      '/api': {
        target: 'http://localhost',
        changeOrigin: true,
        secure: false,
        rewrite: (path) => path.replace(/^\/api/, '/api')
      }
    }
  }
})
