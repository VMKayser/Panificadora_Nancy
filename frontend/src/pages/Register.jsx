import { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { auth as authApi } from '../services/api';
import { toast } from 'react-toastify';
import { MailCheck } from 'lucide-react';
import CampoClave from '../components/tienda/CampoClave';

export default function Register() {
  const navigate = useNavigate();
  const { register } = useAuth();
  const [formData, setFormData] = useState({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    nit_ci: '',
    password: '',
    password_confirmation: '',
  });
  const [loading, setLoading] = useState(false);
  const [registered, setRegistered] = useState(false);
  const [verificationMessage, setVerificationMessage] = useState('');
  const [registeredEmail, setRegisteredEmail] = useState('');

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
      // Compose the full 'name' field expected by the backend from the two inputs
      const payload = {
        ...formData,
        name: `${formData.first_name} ${formData.last_name}`.trim(),
      };
      // backend expects 'name' (full name); remove helper fields to keep payload clean
      delete payload.first_name;
      delete payload.last_name;

      const result = await register(payload);

      if (result.success) {
        // Si el backend solicita verificación por correo, mostrar pantalla de 'Revisa tu correo'
        if (result.message) {
          setVerificationMessage(result.message);
          // Guardar el email que se usó para el registro para mostrarlo en la UI
          setRegisteredEmail(payload.email || formData.email || '');
          setRegistered(true);
          toast.info(result.message);
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

  const reenviar = async () => {
    try {
      setLoading(true);
      const resp = await authApi.resendVerification(formData.email);
      toast.success(resp.message || 'Correo reenviado');
    } catch (e) {
      toast.error(e.response?.data?.message || 'Error al reenviar verificación');
    } finally {
      setLoading(false);
    }
  };

  if (registered) {
    return (
      <main className="pn-auth">
        <div className="pn-panel pn-auth__card pn-auth__card--centro">
          <MailCheck size={44} className="pn-auth__icono" aria-hidden="true" />
          <h1 className="pn-auth__title">Revisa tu correo</h1>
          <p className="pn-auth__sub">{verificationMessage || 'Te enviamos un enlace para verificar tu cuenta.'}</p>
          {registeredEmail && <p className="pn-auth__correo">{registeredEmail}</p>}
          <button type="button" className="pn-btn pn-btn--ghost pn-btn--block" onClick={reenviar} disabled={loading}>
            Reenviar correo de verificación
          </button>
          <p className="pn-auth__alt"><Link to="/login">Ir a iniciar sesión</Link></p>
        </div>
      </main>
    );
  }

  return (
    <main className="pn-auth">
      <div className="pn-panel pn-auth__card">
        <h1 className="pn-auth__title">Crea tu cuenta</h1>
        <p className="pn-auth__sub">Guarda tus datos y sigue tus pedidos.</p>

        <form onSubmit={handleSubmit} className="pn-auth__form">
          <div className="pn-auth__fila">
            <div className="pn-field">
              <label htmlFor="first_name" className="pn-field__label">Nombre</label>
              <input type="text" className="pn-input" id="first_name" name="first_name" value={formData.first_name} onChange={handleChange} required autoComplete="given-name" />
            </div>
            <div className="pn-field">
              <label htmlFor="last_name" className="pn-field__label">Apellido</label>
              <input type="text" className="pn-input" id="last_name" name="last_name" value={formData.last_name} onChange={handleChange} required autoComplete="family-name" />
            </div>
          </div>

          <div className="pn-field">
            <label htmlFor="email" className="pn-field__label">Correo electrónico</label>
            <input type="email" className="pn-input" id="email" name="email" value={formData.email} onChange={handleChange} required autoComplete="email" placeholder="tu@correo.com" />
          </div>

          <div className="pn-auth__fila">
            <div className="pn-field">
              <label htmlFor="phone" className="pn-field__label">Celular <span className="pn-opc">(opcional)</span></label>
              <input type="tel" inputMode="tel" className="pn-input" id="phone" name="phone" value={formData.phone} onChange={handleChange} autoComplete="tel-national" placeholder="71234567" />
            </div>
            <div className="pn-field">
              <label htmlFor="nit_ci" className="pn-field__label">NIT / CI <span className="pn-opc">(opcional)</span></label>
              <input type="text" inputMode="numeric" className="pn-input" id="nit_ci" name="nit_ci" value={formData.nit_ci} onChange={handleChange} />
            </div>
          </div>

          <div className="pn-field">
            <label htmlFor="password" className="pn-field__label">Contraseña</label>
            <CampoClave id="password" name="password" value={formData.password} onChange={handleChange} minLength={8} autoComplete="new-password" placeholder="Mínimo 8: mayúscula, minúscula y número" />
          </div>

          <div className="pn-field">
            <label htmlFor="password_confirmation" className="pn-field__label">Repite la contraseña</label>
            <CampoClave id="password_confirmation" name="password_confirmation" value={formData.password_confirmation} onChange={handleChange} minLength={8} autoComplete="new-password" />
          </div>

          <button type="submit" className="pn-btn pn-btn--primary pn-btn--lg pn-btn--block" disabled={loading}>
            {loading ? 'Creando cuenta…' : 'Crear cuenta'}
          </button>
        </form>

        <p className="pn-auth__alt">
          ¿Ya tienes cuenta? <Link to="/login">Inicia sesión</Link>
        </p>
      </div>
    </main>
  );
}
