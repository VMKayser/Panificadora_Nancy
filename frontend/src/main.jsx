import React from 'react'
import ReactDOM from 'react-dom/client'
import App from './App.jsx'
import 'bootstrap/dist/css/bootstrap.min.css' // Importar Bootstrap CSS primero
import './index.css' // Luego los estilos base
import './estilos.css' // Finalmente tus estilos personalizados
import './styles/tienda.css' // Tienda pública (cabecera, catálogo, carrito, checkout, pie)
// HelmetProvider permite usar react-helmet-async en toda la app
import { HelmetProvider } from 'react-helmet-async'
import { SiteConfigProvider } from './context/SiteConfigContext'

// En producción no imprimimos logs completos para evitar filtrado de datos sensibles.
// Mantener impresiones solo en desarrollo.
if (!import.meta.env.DEV) {
  ['log', 'info', 'warn', 'error', 'debug'].forEach((fn) => {
    if (typeof console[fn] === 'function') {
      console[fn] = () => {};
    }
  });
}

ReactDOM.createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <HelmetProvider>
      <SiteConfigProvider>
        <App />
      </SiteConfigProvider>
    </HelmetProvider>
  </React.StrictMode>,
)
