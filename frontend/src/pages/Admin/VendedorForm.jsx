import { useState, useEffect } from 'react';
import { useNavigate, useParams, Link } from 'react-router-dom';
import { toast } from 'react-toastify';
import { motion } from 'framer-motion';
import { Save, X, ArrowLeft, User, DollarSign, Clock, Shield, Percent } from 'lucide-react';
import { vendedorService } from '../../services/empleadosService';
import userService from '../../services/userService';
import './VendedorForm.css';

const VendedorForm = () => {
  const navigate = useNavigate();
  const { id } = useParams();
  const isEditMode = Boolean(id);

  const [loading, setLoading] = useState(false);
  const [loadingData, setLoadingData] = useState(isEditMode);
  const [loadingUsuarios, setLoadingUsuarios] = useState(true);
  const [usuariosDisponibles, setUsuariosDisponibles] = useState([]);
  const [errors, setErrors] = useState({});

  const [formData, setFormData] = useState({
    user_id: '',
    comision_porcentaje: '3.00',
    descuento_maximo_bs: '50.00',
    turno: 'flexible',
    puede_gestionar_credito: false,
    puede_aplicar_descuentos: true,
    puede_ver_costos: false,
    estado: 'activo'
  });

  // Cargar usuarios disponibles
  useEffect(() => {
    cargarUsuariosDisponibles();
  }, []);

  // Cargar datos en modo edición
  useEffect(() => {
    if (isEditMode) {
      cargarVendedor();
    }
  }, [id]);

  const cargarUsuariosDisponibles = async () => {
    try {
      setLoadingUsuarios(true);
      const response = await userService.getUsuariosDisponiblesVendedor();
      
      if (response.success) {
        setUsuariosDisponibles(response.data);
      }
    } catch (error) {
      console.error('Error cargando usuarios:', error);
      toast.error('Error al cargar usuarios disponibles');
    } finally {
      setLoadingUsuarios(false);
    }
  };

  const cargarVendedor = async () => {
    try {
      setLoadingData(true);
      const response = await vendedorService.getById(id);
      
      if (response.success) {
        const vendedor = response.data;
        setFormData({
          user_id: vendedor.user_id || '',
          comision_porcentaje: vendedor.comision_porcentaje || '3.00',
          descuento_maximo_bs: vendedor.descuento_maximo_bs || '50.00',
          turno: vendedor.turno || 'flexible',
          puede_gestionar_credito: vendedor.puede_gestionar_credito || false,
          puede_aplicar_descuentos: vendedor.puede_aplicar_descuentos !== undefined ? vendedor.puede_aplicar_descuentos : true,
          puede_ver_costos: vendedor.puede_ver_costos || false,
          estado: vendedor.estado || 'activo'
        });

        // Si estamos editando, agregar el usuario actual a la lista
        if (vendedor.user) {
          setUsuariosDisponibles(prev => {
            const exists = prev.find(u => u.id === vendedor.user.id);
            if (!exists) {
              return [...prev, vendedor.user];
            }
            return prev;
          });
        }
      }
    } catch (error) {
      console.error('Error cargando vendedor:', error);
      toast.error('Error al cargar los datos del vendedor');
      navigate('/admin/empleados/vendedores');
    } finally {
      setLoadingData(false);
    }
  };

  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value
    }));
    
    // Limpiar error del campo
    if (errors[name]) {
      setErrors(prev => ({ ...prev, [name]: null }));
    }
  };

  const validateForm = () => {
    const newErrors = {};

    // Validaciones requeridas
    if (!formData.user_id) newErrors.user_id = 'Debes seleccionar un usuario';
    
    if (!formData.comision_porcentaje || formData.comision_porcentaje < 0 || formData.comision_porcentaje > 100) {
      newErrors.comision_porcentaje = 'La comisión debe estar entre 0 y 100';
    }

    if (!formData.descuento_maximo_bs || formData.descuento_maximo_bs < 0) {
      newErrors.descuento_maximo_bs = 'El descuento máximo debe ser mayor o igual a 0';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!validateForm()) {
      toast.error('Por favor corrige los errores en el formulario');
      return;
    }

    try {
      setLoading(true);

      const dataToSend = {
        ...formData,
        user_id: parseInt(formData.user_id),
        comision_porcentaje: parseFloat(formData.comision_porcentaje),
        descuento_maximo_bs: parseFloat(formData.descuento_maximo_bs)
      };

      let response;
      if (isEditMode) {
        response = await vendedorService.update(id, dataToSend);
      } else {
        response = await vendedorService.create(dataToSend);
      }

      if (response.success) {
        toast.success(isEditMode ? 'Vendedor actualizado exitosamente' : 'Vendedor creado exitosamente');
        navigate('/admin/empleados/vendedores');
      }
    } catch (error) {
      console.error('Error guardando vendedor:', error);
      
      // Manejar errores de validación del servidor
      if (error.response?.data?.errors) {
        const serverErrors = {};
        Object.keys(error.response.data.errors).forEach(key => {
          serverErrors[key] = error.response.data.errors[key][0];
        });
        setErrors(serverErrors);
        toast.error('Error de validación. Por favor revisa los campos.');
      } else {
        toast.error(error.response?.data?.message || 'Error al guardar el vendedor');
      }
    } finally {
      setLoading(false);
    }
  };

  const handleCancel = () => {
    if (window.confirm('¿Estás seguro de cancelar? Los cambios no guardados se perderán.')) {
      navigate('/admin/empleados/vendedores');
    }
  };

  if (loadingData || loadingUsuarios) {
    return (
      <div className="vendedor-form-container">
        <div className="loading-container">
          <div className="spinner"></div>
          <p>Cargando datos...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="vendedor-form-container">
      {/* Header */}
      <motion.div
        initial={{ opacity: 0, y: -20 }}
        animate={{ opacity: 1, y: 0 }}
        className="page-header"
      >
        <div className="header-title">
          <Link to="/admin/empleados/vendedores" className="back-link">
            <ArrowLeft size={20} />
            Volver a la lista
          </Link>
          <h1>{isEditMode ? 'Editar Vendedor' : 'Nuevo Vendedor'}</h1>
          <p>
            {isEditMode 
              ? 'Actualiza la información del vendedor' 
              : 'Completa el formulario para agregar un nuevo vendedor'}
          </p>
        </div>
      </motion.div>

      {/* Formulario */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.1 }}
        className="form-card"
      >
        <form onSubmit={handleSubmit}>
          {/* Información del Usuario */}
          <div className="form-section">
            <div className="section-header">
              <User size={20} />
              <h2>Usuario Asociado</h2>
            </div>
            
            <div className="form-grid">
              <div className="form-group full-width">
                <label htmlFor="user_id">
                  Seleccionar Usuario <span className="required">*</span>
                </label>
                <div className="input-icon">
                  <User size={18} />
                  <select
                    id="user_id"
                    name="user_id"
                    className={`form-control ${errors.user_id ? 'is-invalid' : ''}`}
                    value={formData.user_id}
                    onChange={handleChange}
                    disabled={isEditMode}
                  >
                    <option value="">-- Seleccione un usuario --</option>
                    {usuariosDisponibles.map(usuario => (
                      <option key={usuario.id} value={usuario.id}>
                        {usuario.name} ({usuario.email})
                      </option>
                    ))}
                  </select>
                </div>
                {errors.user_id && <div className="invalid-feedback">{errors.user_id}</div>}
                {isEditMode && (
                  <small className="form-text">
                    El usuario no puede ser modificado una vez creado el vendedor
                  </small>
                )}
              </div>
            </div>
          </div>

          {/* Configuración de Comisiones */}
          <div className="form-section">
            <div className="section-header">
              <DollarSign size={20} />
              <h2>Comisiones y Descuentos</h2>
            </div>
            
            <div className="form-grid">
              <div className="form-group">
                <label htmlFor="comision_porcentaje">
                  Comisión (%) <span className="required">*</span>
                </label>
                <div className="input-icon">
                  <Percent size={18} />
                  <input
                    type="number"
                    id="comision_porcentaje"
                    name="comision_porcentaje"
                    className={`form-control ${errors.comision_porcentaje ? 'is-invalid' : ''}`}
                    value={formData.comision_porcentaje}
                    onChange={handleChange}
                    placeholder="3.00"
                    step="0.01"
                    min="0"
                    max="100"
                  />
                </div>
                {errors.comision_porcentaje && <div className="invalid-feedback">{errors.comision_porcentaje}</div>}
                <small className="form-text">
                  Porcentaje de comisión sobre cada venta (0-100%)
                </small>
              </div>

              <div className="form-group">
                <label htmlFor="descuento_maximo_bs">
                  Descuento Máximo (Bs) <span className="required">*</span>
                </label>
                <div className="input-icon">
                  <DollarSign size={18} />
                  <input
                    type="number"
                    id="descuento_maximo_bs"
                    name="descuento_maximo_bs"
                    className={`form-control ${errors.descuento_maximo_bs ? 'is-invalid' : ''}`}
                    value={formData.descuento_maximo_bs}
                    onChange={handleChange}
                    placeholder="50.00"
                    step="0.01"
                    min="0"
                  />
                </div>
                {errors.descuento_maximo_bs && <div className="invalid-feedback">{errors.descuento_maximo_bs}</div>}
                <small className="form-text">
                  Monto máximo que puede descontar en bolivianos
                </small>
              </div>
            </div>
          </div>

          {/* Configuración Laboral */}
          <div className="form-section">
            <div className="section-header">
              <Clock size={20} />
              <h2>Configuración Laboral</h2>
            </div>
            
            <div className="form-grid">
              <div className="form-group">
                <label htmlFor="turno">
                  Turno <span className="required">*</span>
                </label>
                <div className="input-icon">
                  <Clock size={18} />
                  <select
                    id="turno"
                    name="turno"
                    className="form-control"
                    value={formData.turno}
                    onChange={handleChange}
                  >
                    <option value="mañana">Mañana</option>
                    <option value="tarde">Tarde</option>
                    <option value="noche">Noche</option>
                    <option value="flexible">Flexible</option>
                  </select>
                </div>
              </div>

              <div className="form-group">
                <label htmlFor="estado">Estado</label>
                <select
                  id="estado"
                  name="estado"
                  className="form-control"
                  value={formData.estado}
                  onChange={handleChange}
                >
                  <option value="activo">Activo</option>
                  <option value="inactivo">Inactivo</option>
                  <option value="suspendido">Suspendido</option>
                </select>
              </div>
            </div>
          </div>

          {/* Permisos */}
          <div className="form-section">
            <div className="section-header">
              <Shield size={20} />
              <h2>Permisos y Capacidades</h2>
            </div>
            
            <div className="permissions-grid">
              <div className="permission-item">
                <label className="checkbox-label">
                  <input
                    type="checkbox"
                    name="puede_aplicar_descuentos"
                    checked={formData.puede_aplicar_descuentos}
                    onChange={handleChange}
                  />
                  <span>
                    <strong>Aplicar Descuentos</strong>
                    <small>Permitir aplicar descuentos en ventas</small>
                  </span>
                </label>
              </div>

              <div className="permission-item">
                <label className="checkbox-label">
                  <input
                    type="checkbox"
                    name="puede_gestionar_credito"
                    checked={formData.puede_gestionar_credito}
                    onChange={handleChange}
                  />
                  <span>
                    <strong>Gestionar Crédito</strong>
                    <small>Permitir ventas a crédito</small>
                  </span>
                </label>
              </div>

              <div className="permission-item">
                <label className="checkbox-label">
                  <input
                    type="checkbox"
                    name="puede_ver_costos"
                    checked={formData.puede_ver_costos}
                    onChange={handleChange}
                  />
                  <span>
                    <strong>Ver Costos</strong>
                    <small>Ver costos de producción</small>
                  </span>
                </label>
              </div>
            </div>
          </div>

          {/* Botones de acción */}
          <div className="form-actions">
            <button
              type="button"
              className="btn btn-secondary"
              onClick={handleCancel}
              disabled={loading}
            >
              <X size={18} />
              Cancelar
            </button>
            
            <button
              type="submit"
              className="btn btn-primary"
              disabled={loading}
            >
              {loading ? (
                <>
                  <div className="spinner-sm"></div>
                  {isEditMode ? 'Actualizando...' : 'Guardando...'}
                </>
              ) : (
                <>
                  <Save size={18} />
                  {isEditMode ? 'Actualizar Vendedor' : 'Crear Vendedor'}
                </>
              )}
            </button>
          </div>
        </form>
      </motion.div>
    </div>
  );
};

export default VendedorForm;
