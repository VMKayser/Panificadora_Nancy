import { useEffect, useState } from 'react';
import { ToastContainer, Slide } from 'react-toastify';
import { CircleAlert, CircleCheck, Info, TriangleAlert, X } from 'lucide-react';

const ICONOS = { success: CircleCheck, error: CircleAlert, warning: TriangleAlert, info: Info };
const ESCRITORIO = '(min-width: 992px)';

const esEscritorio = () => typeof window !== 'undefined' && window.matchMedia(ESCRITORIO).matches;

const Icono = ({ type }) => {
  const Componente = ICONOS[type];
  return Componente ? <Componente size={20} aria-hidden="true" /> : null;
};

const Cerrar = ({ closeToast }) => (
  <button type="button" className="pn-toast__close" onClick={closeToast} aria-label="Cerrar aviso">
    <X size={16} />
  </button>
);

// Contenedor único de avisos (tienda y paneles). En el teléfono salen abajo,
// cerca del pulgar y sobre la barra del carrito; en escritorio arriba a la
// derecha, bajo la cabecera, sin tapar el menú.
const Avisos = () => {
  const [escritorio, setEscritorio] = useState(esEscritorio);

  useEffect(() => {
    const mq = window.matchMedia(ESCRITORIO);
    const cambiar = () => setEscritorio(mq.matches);
    mq.addEventListener('change', cambiar);
    return () => mq.removeEventListener('change', cambiar);
  }, []);

  return (
    <ToastContainer
      className="pn-toasts"
      position={escritorio ? 'top-right' : 'bottom-center'}
      transition={Slide}
      limit={3}
      autoClose={3000}
      hideProgressBar
      newestOnTop
      closeOnClick={false}
      draggable
      pauseOnHover
      pauseOnFocusLoss
      icon={Icono}
      closeButton={Cerrar}
    />
  );
};

export default Avisos;
