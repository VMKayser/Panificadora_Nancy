// Número de la tienda con código de país. Se usa si la configuración
// whatsapp_empresa no carga o viene vacía.
export const WHATSAPP_TIENDA = '59176490687';

// wa.me exige el número internacional sin signos. En la configuración se
// guardó "76490687" (sin 591), y con eso el enlace abría un chat inválido.
export const normalizarWhatsapp = (valor) => {
  const digitos = String(valor ?? '').replace(/\D/g, '');
  if (digitos.length === 8) return `591${digitos}`;
  return digitos || WHATSAPP_TIENDA;
};

export const enlaceWhatsapp = (numero, texto = '') => {
  const base = `https://wa.me/${normalizarWhatsapp(numero)}`;
  return texto ? `${base}?text=${encodeURIComponent(texto)}` : base;
};

// Celular del cliente: solo dígitos y sin el 591 delante (así se guarda y se
// busca al cliente en el backend).
export const limpiarCelular = (valor) => {
  const digitos = String(valor ?? '').replace(/\D/g, '');
  return digitos.length === 11 && digitos.startsWith('591') ? digitos.slice(3) : digitos;
};

// Bolivia: celulares de 8 dígitos y fijos de 7.
export const celularValido = (valor) => /^\d{7,8}$/.test(limpiarCelular(valor));
