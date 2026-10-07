// Imagen de respaldo local (no depende de servicios externos: via.placeholder.com
// dejó de funcionar y picsum.photos mostraba fotos al azar en productos sin imagen).
export const IMAGEN_PLACEHOLDER = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(
  '<svg xmlns="http://www.w3.org/2000/svg" width="300" height="200" viewBox="0 0 300 200">' +
  '<rect width="300" height="200" fill="#f3ede4"/>' +
  '<text x="150" y="108" font-family="sans-serif" font-size="18" fill="#8b6f47" text-anchor="middle">Sin imagen</text>' +
  '</svg>'
);

// onError para <img>: cambia una sola vez a la imagen de respaldo. Quita el
// srcset (si no, el navegador vuelve a pedir la imagen rota) y marca el
// elemento para no entrar en un bucle si el respaldo también fallara.
export const usarImagenRespaldo = (e) => {
  const img = e.currentTarget;
  if (img.dataset.respaldo) return;
  img.dataset.respaldo = '1';
  img.removeAttribute('srcset');
  img.src = IMAGEN_PLACEHOLDER;
};
