import { toast } from 'react-toastify';
import AvisoCarrito from '../components/tienda/AvisoCarrito';

const ID_CARRITO = 'pn-carrito';

// Un solo aviso de carrito a la vez: si el cliente agrega varios productos
// seguidos, el aviso se actualiza en lugar de apilar uno por producto.
export const avisarAgregado = (producto, cantidad = 1) => {
  const opciones = {
    icon: false,
    autoClose: 2600,
    className: 'pn-toast--carrito',
  };
  const contenido = <AvisoCarrito producto={producto} cantidad={cantidad} />;

  if (toast.isActive(ID_CARRITO)) {
    toast.update(ID_CARRITO, { render: contenido, ...opciones });
  } else {
    toast(contenido, { toastId: ID_CARRITO, ...opciones });
  }
};
