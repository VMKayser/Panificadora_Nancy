import { useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { toast } from 'react-toastify';
import { auth } from '../services/api';
import CampoClave from '../components/tienda/CampoClave';

export default function RestablecerClave() {
  const navigate = useNavigate();
  const location = useLocation();
  const params = new URLSearchParams(location.search);
  const token = params.get('token') || '';
  const email = params.get('email') || '';

  const [formData, setFormData] = useState({ password: '', password_confirmation: '' });
  const [loading, setLoading] = useState(false);

  const handleChange = (e) => setFormData({ ...formData, [e.target.name]: e.target.value });

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (formData.password !== formData.password_confirmation) {
      toast.error('Las contraseñas no coinciden');
      return;
    }
    setLoading(true);
    try {
      const data = await auth.resetPassword({ token, email, ...formData });
      toast.success(data.message || 'Contraseña actualizada');
      navigate('/login');
    } catch (error) {
      const errores = error.response?.data?.errors;
      const primero = errores ? Object.values(errores)[0]?.[0] : null;
      toast.error(primero || error.response?.data?.message || 'No se pudo restablecer la contraseña');
    } finally {
      setLoading(false);
    }
  };

  if (!token || !email) {
    return (
      <main className="pn-auth">
        <div className="pn-panel pn-auth__card">
          <h1 className="pn-auth__title">Enlace incompleto</h1>
          <p className="pn-auth__sub">Abre el enlace completo que te llegó por correo o solicita uno nuevo.</p>
          <p className="pn-auth__alt"><Link to="/olvide-clave">Solicitar un enlace nuevo</Link></p>
        </div>
      </main>
    );
  }

  return (
    <main className="pn-auth">
      <div className="pn-panel pn-auth__card">
        <h1 className="pn-auth__title">Crea una nueva contraseña</h1>
        <p className="pn-auth__sub">Para <strong>{email}</strong>. Mínimo 8 caracteres, con mayúscula, minúscula y número.</p>

        <form onSubmit={handleSubmit} className="pn-auth__form">
          <div className="pn-field">
            <label htmlFor="password" className="pn-field__label">Nueva contraseña</label>
            <CampoClave id="password" name="password" value={formData.password} onChange={handleChange} minLength={8} autoComplete="new-password" />
          </div>
          <div className="pn-field">
            <label htmlFor="password_confirmation" className="pn-field__label">Repite la contraseña</label>
            <CampoClave id="password_confirmation" name="password_confirmation" value={formData.password_confirmation} onChange={handleChange} minLength={8} autoComplete="new-password" />
          </div>

          <button type="submit" className="pn-btn pn-btn--primary pn-btn--lg pn-btn--block" disabled={loading}>
            {loading ? 'Guardando…' : 'Guardar contraseña'}
          </button>
        </form>

        <p className="pn-auth__alt">
          <Link to="/olvide-clave">Solicitar un enlace nuevo</Link>
        </p>
      </div>
    </main>
  );
}
