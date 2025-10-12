import React from 'react';
import { Helmet } from 'react-helmet-async';

/**
 * Componente SEO reutilizable
 * Uso: <SEO title="Título" description="Descripción" />
 */
const SEO = ({
  title = 'Panificadora Nancy - Pan Artesanal Fresco',
  description = 'Pan artesanal recién horneado todos los días. Realiza tu pedido online y recíbelo el mismo día.',
  keywords = 'panificadora, panadería, pan artesanal, pan fresco, bizcochos, tortas, pedidos online',
  image = '/og-image.jpg',
  url,
  type = 'website',
  structuredData,
  noindex = false,
  children,
}) => {
  const siteUrl = 'https://www.panificadoranancy.com';
  const canonicalUrl = url || window.location.href;
  const ogImage = image.startsWith('http') ? image : `${siteUrl}${image}`;

  return (
    <Helmet>
      {/* Primary Meta Tags */}
      <title>{title}</title>
      <meta name="title" content={title} />
      <meta name="description" content={description} />
      {keywords && <meta name="keywords" content={keywords} />}
      <meta name="robots" content={noindex ? 'noindex, nofollow' : 'index, follow'} />
      
      {/* Canonical */}
      <link rel="canonical" href={canonicalUrl} />

      {/* Open Graph / Facebook */}
      <meta property="og:type" content={type} />
      <meta property="og:url" content={canonicalUrl} />
      <meta property="og:title" content={title} />
      <meta property="og:description" content={description} />
      <meta property="og:image" content={ogImage} />
      <meta property="og:site_name" content="Panificadora Nancy" />

      {/* Twitter */}
      <meta name="twitter:card" content="summary_large_image" />
      <meta name="twitter:url" content={canonicalUrl} />
      <meta name="twitter:title" content={title} />
      <meta name="twitter:description" content={description} />
      <meta name="twitter:image" content={ogImage} />

      {/* Structured Data */}
      {structuredData && (
        <script type="application/ld+json">
          {JSON.stringify(structuredData)}
        </script>
      )}

      {/* Additional custom meta tags */}
      {children}
    </Helmet>
  );
};

export default SEO;
