import { createContext, useContext, useState, useEffect } from 'react';
import { toast } from 'react-toastify';
import { getAvailableUnits } from '../utils/stock';

const CartContext = createContext();

// Un mismo producto pedido para dos personas queda en una sola línea con
// los dos textos: "Juan Pérez; María López".
const unirTextos = (actual, nuevo) => {
  const a = String(actual ?? '').trim();
  const b = String(nuevo ?? '').trim();
  if (!b || a === b) return a;
  return a ? `${a}; ${b}` : b;
};

// El archivo exporta el Provider y su hook: separarlos no aporta y el
// único efecto es que Fast Refresh recarga la página al editar este archivo.
// eslint-disable-next-line react-refresh/only-export-components
export const useCart = () => {
  const context = useContext(CartContext);
  if (!context) {
    throw new Error('useCart debe usarse dentro de un CartProvider');
  }
  return context;
};

export const CartProvider = ({ children }) => {
  const [cart, setCart] = useState([]);

  // Cargar carrito desde localStorage al iniciar
  useEffect(() => {
    const savedCart = localStorage.getItem('cart');
    if (savedCart) {
      setCart(JSON.parse(savedCart));
    }
  }, []);

  // Guardar carrito en localStorage cuando cambie (con debounce para performance)
  useEffect(() => {
    const timer = setTimeout(() => {
      localStorage.setItem('cart', JSON.stringify(cart));
    }, 500); // Esperar 500ms después del último cambio antes de guardar

    return () => clearTimeout(timer);
  }, [cart]);

  // Agregar producto al carrito. personalizacion: el dato que pide el
  // producto (ej. nombre del difunto), si lo pide.
  const addToCart = (producto, cantidad = 1, personalizacion = '') => {
    const available = getAvailableUnits(producto);
    const safeQuantity = Math.max(1, Math.floor(Number(cantidad) || 1));
    if (available !== null && Number(available) <= 0) {
      toast.error(`${producto.nombre || 'Producto'} sin stock`);
      return;
    }
    if (available !== null && safeQuantity > Number(available)) {
      toast.error(`Cantidad solicitada supera el stock disponible (${available})`);
      return;
    }
    setCart(prevCart => {
      // If producto is an extra (es_extra flag), treat separately
      if (producto.es_extra) {
        const existingExtra = prevCart.find(item => item.id === producto.id);
        if (existingExtra) {
          const nuevo = existingExtra.cantidad + safeQuantity;
          if (available !== null && nuevo > Number(available)) {
            toast.error(`No hay suficiente stock. Disponible: ${available}`);
            return prevCart;
          }
          return prevCart.map(item => item.id === producto.id ? { ...item, cantidad: nuevo } : item);
        }
        // Ensure we store precio and producto_padre_id if present
        return [...prevCart, { ...producto, cantidad: safeQuantity }];
      }

      // For main products, try to find existing non-extra item with same id
      const existingItem = prevCart.find(item => item.id === producto.id && !item.es_extra);
      if (existingItem) {
        // Si existe, comprobar no superar stock
        const nuevo = existingItem.cantidad + safeQuantity;
        if (available !== null && nuevo > Number(available)) {
          toast.error(`No hay suficiente stock. Disponible: ${available}`);
          return prevCart;
        }
        return prevCart.map(item =>
          item.id === producto.id && !item.es_extra
            ? { ...item, cantidad: item.cantidad + safeQuantity, personalizacion: unirTextos(item.personalizacion, personalizacion) }
            : item
        );
      }

      return [...prevCart, { ...producto, cantidad: safeQuantity, personalizacion: String(personalizacion ?? '').trim() }];
    });
  };

  // Editar el dato personalizado de una línea desde el carrito
  const setPersonalizacion = (productoId, texto) => {
    setCart(prevCart => prevCart.map(item => (
      item.id === productoId && !item.es_extra ? { ...item, personalizacion: texto } : item
    )));
  };

  // Eliminar producto del carrito
  const removeFromCart = (productoId) => {
    setCart(prevCart => {
      const item = prevCart.find(i => i.id === productoId);
      if (!item) return prevCart;

      if (!item.es_extra) {
        // Remove parent and any extras linked to it
        return prevCart.filter(i => {
          if (i.es_extra) {
            if (i.producto_padre_id && i.producto_padre_id === item.id) return false;
            if (String(i.id).startsWith(`${item.id}-extra-`)) return false;
          }
          return i.id !== productoId;
        });
      }

      // If removing an extra, just remove it
      return prevCart.filter(i => i.id !== productoId);
    });
  };

  // Actualizar cantidad de un producto
  const updateQuantity = (productoId, cantidad) => {
    const safeQuantity = Math.floor(Number(cantidad) || 0);
    if (safeQuantity <= 0) {
      removeFromCart(productoId);
      return;
    }
    setCart(prevCart =>
      prevCart.map(item => {
        if (item.id !== productoId) return item;
        const available = getAvailableUnits(item);
        if (available !== null && safeQuantity > available) {
          toast.error(`No hay suficiente stock. Disponible: ${available}`);
          return { ...item, cantidad: available };
        }
        return { ...item, cantidad: safeQuantity };
      })
    );
  };

  // Vaciar carrito
  const clearCart = () => {
    setCart([]);
  };

  // Obtener total del carrito
  const getTotal = () => {
    return cart.reduce((total, item) => {
      const price = (item.precio !== undefined) ? parseFloat(item.precio) : parseFloat(item.precio_minorista || 0);
      return total + ((isNaN(price) ? 0 : price) * (item.cantidad || 0));
    }, 0);
  };

  // Obtener cantidad total de items
  const getTotalItems = () => {
    return cart.reduce((total, item) => total + (item.cantidad || 0), 0);
  };

  const value = {
    cart,
    addToCart,
    removeFromCart,
    updateQuantity,
    setPersonalizacion,
    clearCart,
    getTotal,
    getTotalItems,
  };

  return <CartContext.Provider value={value}>{children}</CartContext.Provider>;
};
