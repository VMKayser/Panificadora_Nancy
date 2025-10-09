import { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { toast } from 'react-toastify';

export default function Register() {
  const navigate = useNavigate();
  const { register } = useAuth();
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
  });
  const [loading, setLoading] = useState(false);

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value,
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (formData.password !== formData.password_confirmation) {
      toast.error('Las contraseñas no coinciden');
      return;
    }

    setLoading(true);

    try {
      const result = await register(formData);
      
      if (result.success) {
        // Si el backend solicita verificación por correo, mostrar mensaje adecuado
        if (result.message) {
          toast.info(result.message);
          navigate('/login');
        } else {
          toast.success('¡Registro exitoso! Bienvenido');
          navigate('/');
        }
      } else {
        toast.error(result.error);
      }
    } catch (error) {
      toast.error('Error al registrarse');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-vh-100 d-flex align-items-center justify-content-center py-5" style={{ backgroundColor: '#f5f5f5' }}>
      <div className="container">
        <div className="row justify-content-center">
          <div className="col-md-6 col-lg-5">
            <div className="card shadow-sm border-0 rounded-3">
              <div className="card-body p-4">
                {/* Logo */}
                <div className="text-center mb-4">
                  <h2 className="fw-bold" style={{ color: '#534031' }}>
                    🥖 Panificadora Nancy
                  </h2>
                  <p className="text-muted">Crea tu cuenta</p>
                </div>

                <form onSubmit={handleSubmit}>
                  {/* Nombre */}
                  <div className="mb-3">
                    <label htmlFor="name" className="form-label fw-semibold">
                      Nombre completo
                    </label>
                    <input
                      type="text"
                      className="form-control"
                      id="name"
                      name="name"
                      value={formData.name}
                      onChange={handleChange}
                      required
                      placeholder="Juan Pérez"
                      style={{ borderColor: '#8b6f47' }}
                    />
                  </div>

                  {/* Email */}
                  <div className="mb-3">
                    <label htmlFor="email" className="form-label fw-semibold">
                      Correo electrónico
                    </label>
                    <input
                      type="email"
                      className="form-control"
                      id="email"
                      name="email"
                      value={formData.email}
                      onChange={handleChange}
                      required
                      placeholder="tu@email.com"
                      style={{ borderColor: '#8b6f47' }}
                    />
                  </div>

                  {/* Teléfono */}
                  <div className="mb-3">
                    <label htmlFor="phone" className="form-label fw-semibold">
                      Teléfono <span className="text-muted">(opcional)</span>
                    </label>
                    <input
                      type="tel"
                      className="form-control"
                      id="phone"
                      name="phone"
                      value={formData.phone}
                      onChange={handleChange}
                      placeholder="77777777"
                      style={{ borderColor: '#8b6f47' }}
                    />
                  </div>

                  {/* Password */}
                  <div className="mb-3">
                    <label htmlFor="password" className="form-label fw-semibold">
                      Contraseña
                    </label>
                    <input
                      type="password"
                      className="form-control"
                      id="password"
                      name="password"
                      value={formData.password}
                      onChange={handleChange}
                      required
                      minLength="6"
                      placeholder="Mínimo 6 caracteres"
                      style={{ borderColor: '#8b6f47' }}
                    />
                  </div>

                  {/* Confirm Password */}
                  <div className="mb-3">
                    <label htmlFor="password_confirmation" className="form-label fw-semibold">
                      Confirmar contraseña
                    </label>
                    <input
                      type="password"
                      className="form-control"
                      id="password_confirmation"
                      name="password_confirmation"
                      value={formData.password_confirmation}
                      onChange={handleChange}
                      required
                      minLength="6"
                      placeholder="Repite tu contraseña"
                      style={{ borderColor: '#8b6f47' }}
                    />
                  </div>

                  {/* Botón Submit */}
                  <button
                    type="submit"
                    className="btn w-100 text-white fw-semibold py-2 mb-3"
                    disabled={loading}
                    style={{ backgroundColor: '#8b6f47', border: 'none' }}
                  >
                    {loading ? (
                      <>
                        <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        Registrando...
                      </>
                    ) : (
                      'Crear cuenta'
                    )}
                  </button>

                  {/* Link a login */}
                  <div className="text-center">
                    <p className="mb-0 text-muted">
                      ¿Ya tienes cuenta?{' '}
                      <Link to="/login" style={{ color: '#8b6f47', textDecoration: 'none', fontWeight: '600' }}>
                        Inicia sesión
                      </Link>
                    </p>
                  </div>

                  {/* Volver al inicio */}
                  <div className="text-center mt-3">
                    <Link to="/" className="text-muted" style={{ textDecoration: 'none', fontSize: '0.9rem' }}>
                      ← Volver al inicio
                    </Link>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
