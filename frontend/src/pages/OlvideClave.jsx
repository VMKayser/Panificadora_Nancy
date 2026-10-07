import { useState } from 'react';
import { Link } from 'react-router-dom';
import { toast } from 'react-toastify';
import { CircleCheck } from 'lucide-react';
import { auth } from '../services/api';

export default function OlvideClave() {
  const [email, setEmail] = useState('');
  const [loading, setLoading] = useState(false);
  const [enviado, setEnviado] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    try {
      await auth.forgotPassword(email);
      // La respuesta es la misma exista o no la cuenta
      setEnviado(true);
    } catch (error) {
      if (error.response?.status !== 429) {
        toast.error(error.response?.data?.message || 'No pudimos procesar la solicitud. Intenta de nuevo.');
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <main className="pn-auth">
      <div className="pn-panel pn-auth__card">
        <h1 className="pn-auth__title">¿Olvidaste tu contraseña?</h1>
        <p className="pn-auth__sub">Te enviaremos un enlace para crear una nueva.</p>

        {enviado ? (
          <div className="pn-auth__ok" role="status">
            <CircleCheck size={18} aria-hidden="true" />
            <span>Si <strong>{email}</strong> está registrado, revisa tu correo. El enlace vence en 60 minutos.</span>
          </div>
        ) : (
          <form onSubmit={handleSubmit} className="pn-auth__form">
            <div className="pn-field">
              <label htmlFor="email" className="pn-field__label">Correo electrónico</label>
              <input
                type="email"
                className="pn-input"
                id="email"
                name="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
                autoComplete="email"
                placeholder="tu@correo.com"
              />
            </div>

            <button type="submit" className="pn-btn pn-btn--primary pn-btn--lg pn-btn--block" disabled={loading}>
              {loading ? 'Enviando…' : 'Enviar enlace'}
            </button>
          </form>
        )}

        <p className="pn-auth__alt">
          <Link to="/login">Volver a iniciar sesión</Link>
        </p>
      </div>
    </main>
  );
}
