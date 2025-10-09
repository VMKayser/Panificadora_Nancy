import { useState } from 'react';
import { Modal, Button, Form } from 'react-bootstrap';
import PropTypes from 'prop-types';
import { useCart } from '../context/CartContext';
import { toast } from 'react-toastify';

const ProductModal = ({ show, onHide, producto }) => {
  const { addToCart } = useCart();
  const [cantidad, setCantidad] = useState(1);
  const [extrasSeleccionados, setExtrasSeleccionados] = useState({});

  if (!producto) return null;

  const imagen = producto.imagenes && producto.imagenes.length > 0
    ? producto.imagenes[0].url_imagen
    : 'https://picsum.photos/600/400';

  const handleExtraChange = (extraIndex, cantidadDocenas) => {
    setExtrasSeleccionados(prev => ({
      ...prev,
      [extraIndex]: cantidadDocenas
    }));
  };

  const calcularTotalExtras = () => {
    if (!producto.extras_disponibles) return 0;
    
    return Object.entries(extrasSeleccionados).reduce((total, [index, docenas]) => {
      const extra = producto.extras_disponibles[parseInt(index)];
      return total + (extra.precio_unitario * extra.cantidad_minima * docenas);
    }, 0);
  };

  const handleAgregarAlCarrito = () => {
    // Agregar producto principal
    addToCart(producto, cantidad);

    // Agregar extras seleccionados
    if (producto.extras_disponibles) {
      Object.entries(extrasSeleccionados).forEach(([index, docenas]) => {
        if (docenas > 0) {
          const extra = producto.extras_disponibles[parseInt(index)];
          const productoExtra = {
            ...producto,
            id: `${producto.id}-extra-${index}`,
            nombre: `${producto.nombre} - ${extra.nombre}`,
            precio_minorista: extra.precio_unitario,
            presentacion: `${extra.cantidad_minima} ${extra.unidad}`,
            es_extra: true,
            producto_padre_id: producto.id
          };
          addToCart(productoExtra, docenas);
        }
      });
    }

    // Mostrar notificación
    const totalItems = cantidad + Object.values(extrasSeleccionados).reduce((sum, val) => sum + val, 0);
    toast.success(`✅ ${totalItems} producto(s) agregado(s) al carrito`, {
      position: "bottom-right",
      autoClose: 2000,
    });

    // Resetear y cerrar
    setCantidad(1);
    setExtrasSeleccionados({});
    onHide();
  };

  const totalExtras = calcularTotalExtras();
  const totalGeneral = (parseFloat(producto.precio_minorista) * cantidad) + totalExtras;

  return (
    <Modal show={show} onHide={onHide} size="lg" centered>
      <Modal.Header closeButton style={{ borderBottom: '1px solid #ddd' }}>
        <Modal.Title style={{ color: '#000' }}>{producto.nombre}</Modal.Title>
      </Modal.Header>
      <Modal.Body>
        <div className="row">
          <div className="col-md-6">
            <img
              src={imagen}
              alt={producto.nombre}
              style={{ width: '100%', borderRadius: '12px', marginBottom: '20px' }}
            />
          </div>
          <div className="col-md-6">
            {producto.presentacion && (
              <div style={{
                backgroundColor: '#f5f1ed',
                padding: '8px 12px',
                borderRadius: '8px',
                display: 'inline-block',
                marginBottom: '12px',
                fontSize: '14px',
                fontWeight: '600',
                color: '#8b6f47'
              }}>
                📦 {producto.presentacion}
              </div>
            )}

            <h3 style={{ color: '#8b6f47', fontWeight: 'bold', marginBottom: '16px' }}>
              Bs {parseFloat(producto.precio_minorista).toFixed(2)}
            </h3>

            {producto.requiere_tiempo_anticipacion && (
              <div style={{
                backgroundColor: '#fff3cd',
                border: '1px solid #ffc107',
                borderRadius: '8px',
                padding: '10px',
                marginBottom: '16px',
                fontSize: '14px'
              }}>
                ⏰ <strong>Importante:</strong> Requiere {producto.tiempo_anticipacion} {producto.unidad_tiempo} de anticipación
              </div>
            )}

            <div style={{ marginBottom: '20px' }}>
              <label style={{ display: 'block', marginBottom: '8px', fontWeight: '600' }}>Cantidad:</label>
              <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                <Button
                  onClick={() => setCantidad(Math.max(1, cantidad - 1))}
                  style={{ backgroundColor: 'transparent', border: 'none', color: '#000', fontSize: '20px' }}
                >
                  -
                </Button>
                <span style={{ fontSize: '18px', fontWeight: 'bold', minWidth: '30px', textAlign: 'center' }}>
                  {cantidad}
                </span>
                <Button
                  onClick={() => setCantidad(cantidad + 1)}
                  style={{ backgroundColor: 'transparent', border: 'none', color: '#000', fontSize: '20px' }}
                >
                  +
                </Button>
              </div>
            </div>

            {/* Extras Opcionales */}
            {producto.extras_disponibles && producto.extras_disponibles.length > 0 && (
              <div style={{ marginTop: '24px', padding: '16px', backgroundColor: '#f8f9fa', borderRadius: '8px' }}>
                <h5 style={{ marginBottom: '16px', color: '#534031' }}>✨ Extras Opcionales</h5>
                <p style={{ fontSize: '13px', color: '#666', marginBottom: '16px' }}>
                  Agrega masitas especiales a tu pedido (no incluidas en la mesa base)
                </p>
                
                {producto.extras_disponibles.map((extra, index) => (
                  <div key={index} style={{ marginBottom: '16px', display: 'flex', gap: '12px', alignItems: 'flex-start' }}>
                    {extra.imagen_url && (
                      <img 
                        src={extra.imagen_url} 
                        alt={extra.nombre}
                        style={{ width: '60px', height: '60px', borderRadius: '8px', objectFit: 'cover', flexShrink: 0 }}
                      />
                    )}
                    <div style={{ flex: 1 }}>
                      <Form.Check
                        type="checkbox"
                        id={`extra-${index}`}
                        checked={extrasSeleccionados[index] > 0}
                        onChange={(e) => handleExtraChange(index, e.target.checked ? 1 : 0)}
                        label={
                          <div>
                            <strong>{extra.nombre}</strong>
                            <div style={{ fontSize: '13px', color: '#666' }}>{extra.descripcion}</div>
                            <div style={{ fontSize: '14px', color: '#8b6f47', fontWeight: 'bold' }}>
                              Bs {extra.precio_unitario.toFixed(2)} x {extra.cantidad_minima} {extra.unidad}
                            </div>
                          </div>
                        }
                      />
                      
                      {extrasSeleccionados[index] > 0 && (
                        <div style={{ marginTop: '8px', marginLeft: '24px', display: 'flex', alignItems: 'center', gap: '12px' }}>
                          <span style={{ fontSize: '14px' }}>Cantidad de {extra.unidad}s:</span>
                          <Button
                            size="sm"
                            onClick={() => handleExtraChange(index, Math.max(0, extrasSeleccionados[index] - 1))}
                            style={{ backgroundColor: 'transparent', border: 'none', color: '#000' }}
                          >
                            -
                          </Button>
                          <span style={{ fontWeight: 'bold', minWidth: '20px', textAlign: 'center' }}>
                            {extrasSeleccionados[index]}
                          </span>
                          <Button
                            size="sm"
                            onClick={() => handleExtraChange(index, extrasSeleccionados[index] + 1)}
                            style={{ backgroundColor: 'transparent', border: 'none', color: '#000' }}
                          >
                            +
                          </Button>
                          <span style={{ fontSize: '14px', color: '#8b6f47', marginLeft: '8px' }}>
                            = Bs {(extra.precio_unitario * extra.cantidad_minima * extrasSeleccionados[index]).toFixed(2)}
                          </span>
                        </div>
                      )}
                    </div>
                  </div>
                ))}

                {totalExtras > 0 && (
                  <div style={{ 
                    borderTop: '1px solid #ddd', 
                    paddingTop: '12px', 
                    marginTop: '12px',
                    display: 'flex',
                    justifyContent: 'space-between',
                    fontWeight: 'bold'
                  }}>
                    <span>Subtotal extras:</span>
                    <span style={{ color: '#8b6f47' }}>Bs {totalExtras.toFixed(2)}</span>
                  </div>
                )}
              </div>
            )}
          </div>
        </div>

        {/* Descripción completa */}
        <div style={{ marginTop: '24px', borderTop: '1px solid #ddd', paddingTop: '24px' }}>
          <h5 style={{ marginBottom: '16px', color: '#534031' }}>Descripción Detallada</h5>
          <div style={{ whiteSpace: 'pre-line', lineHeight: '1.8', color: '#333' }}>
            {producto.descripcion}
          </div>
        </div>
      </Modal.Body>
      <Modal.Footer style={{ borderTop: '1px solid #ddd', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
        <div>
          <div style={{ fontSize: '14px', color: '#666' }}>Total:</div>
          <div style={{ fontSize: '24px', fontWeight: 'bold', color: '#000' }}>
            Bs {totalGeneral.toFixed(2)}
          </div>
        </div>
        <div>
          <Button 
            variant="secondary" 
            onClick={onHide}
            style={{ marginRight: '12px', backgroundColor: '#fff', border: '1px solid #ccc', color: '#000' }}
          >
            Cancelar
          </Button>
          <Button variant="primary" onClick={handleAgregarAlCarrito}>
            🛒 Agregar al Carrito
          </Button>
        </div>
      </Modal.Footer>
    </Modal>
  );
};

ProductModal.propTypes = {
  show: PropTypes.bool.isRequired,
  onHide: PropTypes.func.isRequired,
  producto: PropTypes.shape({
    id: PropTypes.number,
    nombre: PropTypes.string,
    descripcion: PropTypes.string,
    descripcion_corta: PropTypes.string,
    presentacion: PropTypes.string,
    precio_minorista: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
    requiere_tiempo_anticipacion: PropTypes.bool,
    tiempo_anticipacion: PropTypes.number,
    unidad_tiempo: PropTypes.string,
    extras_disponibles: PropTypes.arrayOf(PropTypes.shape({
      nombre: PropTypes.string,
      descripcion: PropTypes.string,
      precio_unitario: PropTypes.number,
      unidad: PropTypes.string,
      cantidad_minima: PropTypes.number,
    })),
    imagenes: PropTypes.arrayOf(PropTypes.shape({
      url_imagen: PropTypes.string,
    })),
  }),
};

export default ProductModal;
