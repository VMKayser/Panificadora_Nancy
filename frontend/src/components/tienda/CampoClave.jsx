import { useState } from 'react';
import PropTypes from 'prop-types';
import { Eye, EyeOff } from 'lucide-react';

// Campo de contraseña con botón para mostrarla u ocultarla
const CampoClave = ({ id, name, value, onChange, placeholder, autoComplete, minLength }) => {
  const [visible, setVisible] = useState(false);

  return (
    <div className="pn-clave">
      <input
        type={visible ? 'text' : 'password'}
        className="pn-input"
        id={id}
        name={name}
        value={value}
        onChange={onChange}
        placeholder={placeholder}
        autoComplete={autoComplete}
        minLength={minLength}
        required
      />
      <button
        type="button"
        onClick={() => setVisible((v) => !v)}
        aria-label={visible ? 'Ocultar contraseña' : 'Mostrar contraseña'}
        aria-pressed={visible}
      >
        {visible ? <EyeOff size={20} /> : <Eye size={20} />}
      </button>
    </div>
  );
};

CampoClave.propTypes = {
  id: PropTypes.string.isRequired,
  name: PropTypes.string.isRequired,
  value: PropTypes.string.isRequired,
  onChange: PropTypes.func.isRequired,
  placeholder: PropTypes.string,
  autoComplete: PropTypes.string,
  minLength: PropTypes.number,
};

export default CampoClave;
