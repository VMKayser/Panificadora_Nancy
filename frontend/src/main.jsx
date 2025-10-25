import React from 'react'
import ReactDOM from 'react-dom/client'
import App from './App.jsx'
import 'bootstrap/dist/css/bootstrap.min.css' // Importar Bootstrap CSS primero
import './index.css' // Luego los estilos base
import './estilos.css' // Finalmente tus estilos personalizados
// HelmetProvider permite usar react-helmet-async en toda la app
import { HelmetProvider } from 'react-helmet-async'
import { SiteConfigProvider } from './context/SiteConfigContext'

ReactDOM.createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <HelmetProvider>
      <SiteConfigProvider>
        <App />
      </SiteConfigProvider>
    </HelmetProvider>
  </React.StrictMode>,
)
