import { useEffect } from 'react';
import { useLocation } from 'react-router-dom';

/**
 * Hook personalizado para gestión de SEO
 * @param {Object} seoData - Datos de SEO para la página
 * @param {string} seoData.title - Título de la página
 * @param {string} seoData.description - Descripción de la página
 * @param {string} seoData.keywords - Keywords de la página
 * @param {string} seoData.image - Imagen Open Graph
 * @param {string} seoData.canonical - URL canónica
 * @param {Object} seoData.structuredData - Datos estructurados JSON-LD
 */
export const useSEO = (seoData = {}) => {
  const location = useLocation();

  useEffect(() => {
    const {
      title = 'Panificadora Nancy - Pan Artesanal Fresco',
      description = 'Pan artesanal recién horneado todos los días. Realiza tu pedido online.',
      keywords = 'panificadora, pan artesanal, pan fresco, pedidos online',
      image = '/og-image.jpg',
      canonical,
      structuredData,
      noindex = false,
    } = seoData;

    // Actualizar título
    document.title = title;

    // Actualizar meta tags
    updateMetaTag('name', 'description', description);
    updateMetaTag('name', 'keywords', keywords);
    updateMetaTag('name', 'robots', noindex ? 'noindex, nofollow' : 'index, follow');

    // Open Graph
    updateMetaTag('property', 'og:title', title);
    updateMetaTag('property', 'og:description', description);
    updateMetaTag('property', 'og:image', image);
    updateMetaTag('property', 'og:url', canonical || window.location.href);

    // Twitter
    updateMetaTag('name', 'twitter:title', title);
    updateMetaTag('name', 'twitter:description', description);
    updateMetaTag('name', 'twitter:image', image);

    // Canonical URL
    updateCanonical(canonical || window.location.href);

    // Structured Data
    if (structuredData) {
      updateStructuredData(structuredData);
    }

    // Actualizar breadcrumbs
    updateBreadcrumbs(location.pathname);

  }, [seoData, location]);
};

/**
 * Actualizar o crear meta tag
 */
const updateMetaTag = (attribute, key, content) => {
  if (!content) return;

  let element = document.querySelector(`meta[${attribute}="${key}"]`);
  
  if (element) {
    element.setAttribute('content', content);
  } else {
    element = document.createElement('meta');
    element.setAttribute(attribute, key);
    element.setAttribute('content', content);
    document.head.appendChild(element);
  }
};

/**
 * Actualizar canonical URL
 */
const updateCanonical = (url) => {
  let link = document.querySelector('link[rel="canonical"]');
  
  if (link) {
    link.setAttribute('href', url);
  } else {
    link = document.createElement('link');
    link.setAttribute('rel', 'canonical');
    link.setAttribute('href', url);
    document.head.appendChild(link);
  }
};

/**
 * Actualizar datos estructurados
 */
const updateStructuredData = (data) => {
  let script = document.querySelector('script[type="application/ld+json"]#page-schema');
  
  if (script) {
    script.textContent = JSON.stringify(data);
  } else {
    script = document.createElement('script');
    script.type = 'application/ld+json';
    script.id = 'page-schema';
    script.textContent = JSON.stringify(data);
    document.head.appendChild(script);
  }
};

/**
 * Actualizar breadcrumbs estructurados
 */
const updateBreadcrumbs = (pathname) => {
  const paths = pathname.split('/').filter(Boolean);
  const baseUrl = window.location.origin;
  
  const breadcrumbList = {
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
      {
        "@type": "ListItem",
        "position": 1,
        "name": "Inicio",
        "item": baseUrl
      }
    ]
  };

  // Agregar rutas intermedias
  const pathNames = {
    'productos': 'Productos',
    'carrito': 'Carrito',
    'perfil': 'Mi Perfil',
    'admin': 'Administración',
    'inventario': 'Inventario',
  };

  paths.forEach((path, index) => {
    breadcrumbList.itemListElement.push({
      "@type": "ListItem",
      "position": index + 2,
      "name": pathNames[path] || path.charAt(0).toUpperCase() + path.slice(1),
      "item": `${baseUrl}/${paths.slice(0, index + 1).join('/')}`
    });
  });

  const breadcrumbScript = document.getElementById('breadcrumb-schema');
  if (breadcrumbScript) {
    breadcrumbScript.textContent = JSON.stringify(breadcrumbList);
  }
};

/**
 * Hook para rastrear vistas de página (Google Analytics)
 */
export const usePageTracking = () => {
  const location = useLocation();

  useEffect(() => {
    // Google Analytics pageview
    if (typeof window.gtag !== 'undefined') {
      window.gtag('config', 'G-XXXXXXXXXX', {
        page_path: location.pathname + location.search,
      });
    }

    // Facebook Pixel pageview
    if (typeof window.fbq !== 'undefined') {
      window.fbq('track', 'PageView');
    }
  }, [location]);
};

/**
 * Generar structured data para productos
 */
export const generateProductSchema = (product) => {
  return {
    "@context": "https://schema.org",
    "@type": "Product",
    "name": product.nombre,
    "image": product.imagen_url || product.imagenes?.[0]?.url,
    "description": product.descripcion,
    "sku": product.id.toString(),
    "brand": {
      "@type": "Brand",
      "name": "Panificadora Nancy"
    },
    "offers": {
      "@type": "Offer",
      "url": `${window.location.origin}/productos/${product.id}`,
      "priceCurrency": "BOB",
      "price": product.precio,
      "priceValidUntil": new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
      "itemCondition": "https://schema.org/NewCondition",
      "availability": product.stock > 0 ? "https://schema.org/InStock" : "https://schema.org/OutOfStock",
      "seller": {
        "@type": "Organization",
        "name": "Panificadora Nancy"
      }
    },
    "aggregateRating": product.rating ? {
      "@type": "AggregateRating",
      "ratingValue": product.rating,
      "reviewCount": product.reviews || 1
    } : undefined
  };
};

/**
 * Generar structured data para lista de productos
 */
export const generateProductListSchema = (products, category) => {
  return {
    "@context": "https://schema.org",
    "@type": "ItemList",
    "name": category ? `${category} - Panificadora Nancy` : "Productos - Panificadora Nancy",
    "itemListElement": products.map((product, index) => ({
      "@type": "ListItem",
      "position": index + 1,
      "item": {
        "@type": "Product",
        "name": product.nombre,
        "image": product.imagen_url || product.imagenes?.[0]?.url,
        "url": `${window.location.origin}/productos/${product.id}`,
        "offers": {
          "@type": "Offer",
          "price": product.precio,
          "priceCurrency": "BOB"
        }
      }
    }))
  };
};

export default useSEO;
