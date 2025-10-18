import { createContext, useContext, useState, useEffect } from 'react';
import { toast } from 'react-toastify';

const CartContext = createContext();

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

  // Guardar carrito en localStorage cuando cambie
  useEffect(() => {
    localStorage.setItem('cart', JSON.stringify(cart));
  }, [cart]);

  // Agregar producto al carrito
  const addToCart = (producto, cantidad = 1) => {
    // Comprueba stock disponible si viene en el objeto producto
    const available = producto?.inventario?.stock_actual ?? producto?.stock_actual ?? producto?.stock ?? null;
    if (available !== null && Number(available) <= 0) {
      toast.error(`${producto.nombre || 'Producto'} sin stock`);
      return;
    }
    if (available !== null && cantidad > Number(available)) {
      toast.error(`Cantidad solicitada supera el stock disponible (${available})`);
      return;
    }
    setCart(prevCart => {
      // If producto is an extra (es_extra flag), treat separately
      if (producto.es_extra) {
        const existingExtra = prevCart.find(item => item.id === producto.id);
        if (existingExtra) {
          return prevCart.map(item => item.id === producto.id ? { ...item, cantidad: item.cantidad + cantidad } : item);
        }
        // Ensure we store precio and producto_padre_id if present
        return [...prevCart, { ...producto, cantidad }];
      }

      // For main products, try to find existing non-extra item with same id
      const existingItem = prevCart.find(item => item.id === producto.id && !item.es_extra);
      if (existingItem) {
        // Si existe, comprobar no superar stock
        const nuevo = existingItem.cantidad + cantidad;
        if (available !== null && nuevo > Number(available)) {
          toast.error(`No hay suficiente stock. Disponible: ${available}`);
          return prevCart;
        }
        return prevCart.map(item =>
          item.id === producto.id && !item.es_extra
            ? { ...item, cantidad: item.cantidad + cantidad }
            : item
        );
      }

      return [...prevCart, { ...producto, cantidad }];
    });
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
    if (cantidad <= 0) {
      removeFromCart(productoId);
      return;
    }
    setCart(prevCart =>
      prevCart.map(item =>
        item.id === productoId
          ? { ...item, cantidad }
          : item
      )
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
      return total + ( (isNaN(price) ? 0 : price) * (item.cantidad || 0) );
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
    clearCart,
    getTotal,
    getTotalItems,
  };

  return <CartContext.Provider value={value}>{children}</CartContext.Provider>;
};
