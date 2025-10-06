import axios from 'axios';

// Configurar la URL base de la API
const api = axios.create({
  baseURL: 'http://localhost/api',
  headers: {
    'Content-Type': 'application/json',
  },
});


export const getProductos = async (params = {}) => {
  const response = await api.get('/productos', { params });
  return response.data;
};

export const getProducto = async (id) => {
  const response = await api.get(`/productos/${id}`);
  return response.data;
};


export const crearPedido = async (pedidoData) => {
  const response = await api.post('/pedidos', pedidoData);
  return response.data;
};

export const getMetodosPago = async () => {
  const response = await api.get('/metodos-pago');
  return response.data;
};

export default api;
