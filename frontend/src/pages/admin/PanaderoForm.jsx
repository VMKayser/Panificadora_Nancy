import { useState, useEffect } from 'react';
import { useNavigate, useParams, Link } from 'react-router-dom';
import { toast } from 'react-toastify';
import { motion } from 'framer-motion';
import { Save, X, ArrowLeft, User, Mail, Phone, MapPin, Calendar, DollarSign, Briefcase, Clock } from 'lucide-react';
import { pananaderoService } from '../../services/empleadosService';
import './PanaderoForm.css';

const PanaderoForm = () => {
  const navigate = useNavigate();
  const { id } = useParams();
  const isEditMode = Boolean(id);

  const [loading, setLoading] = useState(false);
  const [loadingData, setLoadingData] = useState(isEditMode);
  const [errors, setErrors] = useState({});

  const [formData, setFormData] = useState({
    nombre: '',
    apellido: '',
    email: '',
    ci: '',
    telefono: '',
    direccion: '',
    fecha_ingreso: new Date().toISOString().split('T')[0],
    turno: 'mañana',
    especialidad: 'pan',
    salario_base: '',
    activo: true
  });

  // Cargar datos en modo edición
  useEffect(() => {
    if (isEditMode) {
      cargarPanadero();
    }
  }, [id]);

  const cargarPanadero = async () => {
    try {
      setLoadingData(true);
      const response = await pananaderoService.getById(id);
      
      if (response.success) {
        const panadero = response.data;
        setFormData({
          nombre: panadero.nombre || '',
          apellido: panadero.apellido || '',
          email: panadero.email || '',
          ci: panadero.ci || '',
          telefono: panadero.telefono || '',
          direccion: panadero.direccion || '',
          fecha_ingreso: panadero.fecha_ingreso || '',
          turno: panadero.turno || 'mañana',
          especialidad: panadero.especialidad || 'pan',
          salario_base: panadero.salario_base || '',
          activo: panadero.activo !== undefined ? panadero.activo : true
        });
      }
    } catch (error) {
      console.error('Error cargando panadero:', error);
      toast.error('Error al cargar los datos del panadero');
      navigate('/admin/empleados/panaderos');
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
    if (!formData.nombre?.trim()) newErrors.nombre = 'El nombre es requerido';
    if (!formData.apellido?.trim()) newErrors.apellido = 'El apellido es requerido';
    if (!formData.email?.trim()) {
      newErrors.email = 'El email es requerido';
    } else if (!/\S+@\S+\.\S+/.test(formData.email)) {
      newErrors.email = 'Email inválido';
    }
    if (!formData.ci?.trim()) newErrors.ci = 'La CI es requerida';
    if (!formData.telefono?.trim()) newErrors.telefono = 'El teléfono es requerido';
    if (!formData.fecha_ingreso) newErrors.fecha_ingreso = 'La fecha de ingreso es requerida';
    if (!formData.salario_base || formData.salario_base <= 0) {
      newErrors.salario_base = 'El salario debe ser mayor a 0';
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
        salario_base: parseFloat(formData.salario_base)
      };

      let response;
      if (isEditMode) {
        response = await pananaderoService.update(id, dataToSend);
      } else {
        response = await pananaderoService.create(dataToSend);
      }

      if (response.success) {
        toast.success(isEditMode ? 'Panadero actualizado exitosamente' : 'Panadero creado exitosamente');
        navigate('/admin/empleados/panaderos');
      }
    } catch (error) {
      console.error('Error guardando panadero:', error);
      
      // Manejar errores de validación del servidor
      if (error.response?.data?.errors) {
        const serverErrors = {};
        Object.keys(error.response.data.errors).forEach(key => {
          serverErrors[key] = error.response.data.errors[key][0];
        });
        setErrors(serverErrors);
        toast.error('Error de validación. Por favor revisa los campos.');
      } else {
        toast.error(error.response?.data?.message || 'Error al guardar el panadero');
      }
    } finally {
      setLoading(false);
    }
  };

  const handleCancel = () => {
    if (window.confirm('¿Estás seguro de cancelar? Los cambios no guardados se perderán.')) {
      navigate('/admin/empleados/panaderos');
    }
  };

  if (loadingData) {
    return (
      <div className="panadero-form-container">
        <div className="loading-container">
          <div className="spinner"></div>
          <p>Cargando datos...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="panadero-form-container">
      {/* Header */}
      <motion.div
        initial={{ opacity: 0, y: -20 }}
        animate={{ opacity: 1, y: 0 }}
        className="page-header"
      >
        <div className="header-title">
          <Link to="/admin/empleados/panaderos" className="back-link">
            <ArrowLeft size={20} />
            Volver a la lista
          </Link>
          <h1>{isEditMode ? 'Editar Panadero' : 'Nuevo Panadero'}</h1>
          <p>
            {isEditMode 
              ? 'Actualiza la información del panadero' 
              : 'Completa el formulario para agregar un nuevo panadero'}
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
          {/* Información Personal */}
          <div className="form-section">
            <div className="section-header">
              <User size={20} />
              <h2>Información Personal</h2>
            </div>
            
            <div className="form-grid">
              <div className="form-group">
                <label htmlFor="nombre">
                  Nombre <span className="required">*</span>
                </label>
                <input
                  type="text"
                  id="nombre"
                  name="nombre"
                  className={`form-control ${errors.nombre ? 'is-invalid' : ''}`}
                  value={formData.nombre}
                  onChange={handleChange}
                  placeholder="Ej: Juan"
                />
                {errors.nombre && <div className="invalid-feedback">{errors.nombre}</div>}
              </div>

              <div className="form-group">
                <label htmlFor="apellido">
                  Apellido <span className="required">*</span>
                </label>
                <input
                  type="text"
                  id="apellido"
                  name="apellido"
                  className={`form-control ${errors.apellido ? 'is-invalid' : ''}`}
                  value={formData.apellido}
                  onChange={handleChange}
                  placeholder="Ej: Pérez"
                />
                {errors.apellido && <div className="invalid-feedback">{errors.apellido}</div>}
              </div>

              <div className="form-group">
                <label htmlFor="ci">
                  CI <span className="required">*</span>
                </label>
                <input
                  type="text"
                  id="ci"
                  name="ci"
                  className={`form-control ${errors.ci ? 'is-invalid' : ''}`}
                  value={formData.ci}
                  onChange={handleChange}
                  placeholder="Ej: 12345678"
                />
                {errors.ci && <div className="invalid-feedback">{errors.ci}</div>}
              </div>

              <div className="form-group">
                <label htmlFor="fecha_ingreso">
                  Fecha de Ingreso <span className="required">*</span>
                </label>
                <div className="input-icon">
                  <Calendar size={18} />
                  <input
                    type="date"
                    id="fecha_ingreso"
                    name="fecha_ingreso"
                    className={`form-control ${errors.fecha_ingreso ? 'is-invalid' : ''}`}
                    value={formData.fecha_ingreso}
                    onChange={handleChange}
                  />
                </div>
                {errors.fecha_ingreso && <div className="invalid-feedback">{errors.fecha_ingreso}</div>}
              </div>
            </div>
          </div>

          {/* Información de Contacto */}
          <div className="form-section">
            <div className="section-header">
              <Mail size={20} />
              <h2>Información de Contacto</h2>
            </div>
            
            <div className="form-grid">
              <div className="form-group">
                <label htmlFor="email">
                  Email <span className="required">*</span>
                </label>
                <div className="input-icon">
                  <Mail size={18} />
                  <input
                    type="email"
                    id="email"
                    name="email"
                    className={`form-control ${errors.email ? 'is-invalid' : ''}`}
                    value={formData.email}
                    onChange={handleChange}
                    placeholder="ejemplo@email.com"
                  />
                </div>
                {errors.email && <div className="invalid-feedback">{errors.email}</div>}
              </div>

              <div className="form-group">
                <label htmlFor="telefono">
                  Teléfono <span className="required">*</span>
                </label>
                <div className="input-icon">
                  <Phone size={18} />
                  <input
                    type="tel"
                    id="telefono"
                    name="telefono"
                    className={`form-control ${errors.telefono ? 'is-invalid' : ''}`}
                    value={formData.telefono}
                    onChange={handleChange}
                    placeholder="70123456"
                  />
                </div>
                {errors.telefono && <div className="invalid-feedback">{errors.telefono}</div>}
              </div>

              <div className="form-group full-width">
                <label htmlFor="direccion">Dirección</label>
                <div className="input-icon">
                  <MapPin size={18} />
                  <input
                    type="text"
                    id="direccion"
                    name="direccion"
                    className="form-control"
                    value={formData.direccion}
                    onChange={handleChange}
                    placeholder="Av. Principal #123"
                  />
                </div>
              </div>
            </div>
          </div>

          {/* Información Laboral */}
          <div className="form-section">
            <div className="section-header">
              <Briefcase size={20} />
              <h2>Información Laboral</h2>
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
                    <option value="rotativo">Rotativo</option>
                  </select>
                </div>
              </div>

              <div className="form-group">
                <label htmlFor="especialidad">
                  Especialidad <span className="required">*</span>
                </label>
                <div className="input-icon">
                  <Briefcase size={18} />
                  <select
                    id="especialidad"
                    name="especialidad"
                    className="form-control"
                    value={formData.especialidad}
                    onChange={handleChange}
                  >
                    <option value="pan">Pan</option>
                    <option value="reposteria">Repostería</option>
                    <option value="ambos">Ambos</option>
                  </select>
                </div>
              </div>

              <div className="form-group">
                <label htmlFor="salario_base">
                  Salario Base (Bs) <span className="required">*</span>
                </label>
                <div className="input-icon">
                  <DollarSign size={18} />
                  <input
                    type="number"
                    id="salario_base"
                    name="salario_base"
                    className={`form-control ${errors.salario_base ? 'is-invalid' : ''}`}
                    value={formData.salario_base}
                    onChange={handleChange}
                    placeholder="2000.00"
                    step="0.01"
                    min="0"
                  />
                </div>
                {errors.salario_base && <div className="invalid-feedback">{errors.salario_base}</div>}
              </div>

              <div className="form-group">
                <label className="checkbox-label">
                  <input
                    type="checkbox"
                    name="activo"
                    checked={formData.activo}
                    onChange={handleChange}
                  />
                  <span>Panadero Activo</span>
                </label>
                <small className="form-text">
                  Los panaderos inactivos no aparecerán en las asignaciones de producción
                </small>
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
                  {isEditMode ? 'Actualizar Panadero' : 'Crear Panadero'}
                </>
              )}
            </button>
          </div>
        </form>
      </motion.div>
    </div>
  );
};

export default PanaderoForm;
