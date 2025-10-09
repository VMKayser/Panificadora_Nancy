import { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { toast } from 'react-toastify';

export default function Login() {
  const navigate = useNavigate();
  const { login } = useAuth();
  const [formData, setFormData] = useState({
    email: '',
    password: '',
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
    setLoading(true);

    try {
      const result = await login(formData.email, formData.password);
      
      if (result.success) {
        toast.success('¡Bienvenido!');
        navigate('/');
      } else {
        toast.error(result.error);
      }
    } catch (error) {
      toast.error('Error al iniciar sesión');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-vh-100 d-flex align-items-center justify-content-center" style={{ backgroundColor: '#f5f5f5' }}>
      <div className="container">
        <div className="row justify-content-center">
          <div className="col-md-5 col-lg-4">
            <div className="card shadow-sm border-0 rounded-3">
              <div className="card-body p-4">
                {/* Logo */}
                <div className="text-center mb-4">
                  <h2 className="fw-bold" style={{ color: '#534031' }}>
                    🥖 Panificadora Nancy
                  </h2>
                  <p className="text-muted">Inicia sesión en tu cuenta</p>
                </div>

                <form onSubmit={handleSubmit}>
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
                      placeholder="••••••••"
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
                        Iniciando sesión...
                      </>
                    ) : (
                      'Iniciar sesión'
                    )}
                  </button>

                  {/* Link a registro */}
                  <div className="text-center">
                    <p className="mb-0 text-muted">
                      ¿No tienes cuenta?{' '}
                      <Link to="/register" style={{ color: '#8b6f47', textDecoration: 'none', fontWeight: '600' }}>
                        Regístrate aquí
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

                {/* Demo credentials */}
                <div className="alert alert-info mt-4 mb-0" role="alert" style={{ fontSize: '0.85rem' }}>
                  <strong>👤 Usuario de prueba:</strong><br />
                  Email: admin@panificadoranancy.com<br />
                  Contraseña: admin123
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
