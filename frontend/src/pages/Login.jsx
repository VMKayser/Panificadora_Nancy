import { useState, useEffect, useRef } from 'react';
import { useNavigate, Link, useLocation } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { toast } from 'react-toastify';
import { CircleCheck, X } from 'lucide-react';
import CampoClave from '../components/tienda/CampoClave';

export default function Login() {
  const navigate = useNavigate();
  const { login } = useAuth();
  const [formData, setFormData] = useState({
    email: '',
    password: '',
  });
  const [verifiedBanner, setVerifiedBanner] = useState(false);
  const location = useLocation();
  const emailInputRef = useRef(null);
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
        
        // Obtener el usuario desde localStorage (recién guardado)
        const userStr = localStorage.getItem('user');
        if (userStr) {
          const user = JSON.parse(userStr);
          // Redirigir según rol
          const isAdmin = user.roles?.some(role => role.name === 'admin');
          const isVendedor = user.roles?.some(role => role.name === 'vendedor');
          
          if (isAdmin) {
            navigate('/admin');
          } else if (isVendedor) {
            navigate('/vendedor');
          } else {
            navigate('/');
          }
        } else {
          navigate('/');
        }
      } else {
        toast.error(result.error);
      }
    } catch (error) {
      toast.error('Error al iniciar sesión');
    } finally {
      setLoading(false);
    }
  };

  // Detect ?verified=1&email=... and show a friendly banner. Prefill and focus email.
  useEffect(() => {
    try {
      const params = new URLSearchParams(location.search);
      const verified = params.get('verified');
      const email = params.get('email');

      if (verified === '1') {
        setVerifiedBanner(true);
        if (email) {
          setFormData((s) => ({ ...s, email }));
          // small timeout to ensure input is mounted
          setTimeout(() => emailInputRef.current?.focus(), 50);
        }
      }
    } catch (err) {
      // ignore malformed query
    }
  }, [location.search]);

  return (
    <main className="pn-auth">
      <div className="pn-panel pn-auth__card">
        <h1 className="pn-auth__title">Inicia sesión</h1>
        <p className="pn-auth__sub">Para ver tus pedidos y comprar más rápido.</p>

        {verifiedBanner && (
          <div className="pn-auth__ok" role="status">
            <CircleCheck size={18} aria-hidden="true" />
            <span><strong>Correo verificado.</strong> Ya puedes iniciar sesión.</span>
            <button type="button" onClick={() => setVerifiedBanner(false)} aria-label="Cerrar aviso"><X size={16} /></button>
          </div>
        )}

        <form onSubmit={handleSubmit} className="pn-auth__form">
          <div className="pn-field">
            <label htmlFor="email" className="pn-field__label">Correo electrónico</label>
            <input
              type="email"
              className="pn-input"
              id="email"
              name="email"
              value={formData.email}
              onChange={handleChange}
              ref={emailInputRef}
              required
              autoComplete="email"
              placeholder="tu@correo.com"
            />
          </div>

          <div className="pn-field">
            <label htmlFor="password" className="pn-field__label">Contraseña</label>
            <CampoClave
              id="password"
              name="password"
              value={formData.password}
              onChange={handleChange}
              autoComplete="current-password"
            />
          </div>

          <button type="submit" className="pn-btn pn-btn--primary pn-btn--lg pn-btn--block" disabled={loading}>
            {loading ? 'Iniciando sesión…' : 'Iniciar sesión'}
          </button>
        </form>

        <p className="pn-auth__alt">
          <Link to="/olvide-clave">¿Olvidaste tu contraseña?</Link>
        </p>
        <p className="pn-auth__alt">
          ¿No tienes cuenta? <Link to="/register">Regístrate</Link>
        </p>
        <p className="pn-auth__nota">No necesitas cuenta para comprar: puedes hacer tu pedido directamente.</p>
      </div>
    </main>
  );
}
