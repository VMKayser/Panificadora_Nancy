import PropTypes from 'prop-types';
import { Minus, Plus } from 'lucide-react';

// Selector de cantidad con botones de 42px (36px en la variante pequeña),
// pensado para dedos en móvil.
const QtyStepper = ({ value, onDecrease, onIncrease, min = 1, max = null, size = 'md', label = 'Cantidad' }) => (
  <div className={`pn-stepper${size === 'sm' ? ' pn-stepper--sm' : ''}`} role="group" aria-label={label}>
    <button type="button" onClick={onDecrease} disabled={value <= min} aria-label="Quitar uno">
      <Minus size={16} />
    </button>
    <output aria-live="polite">{value}</output>
    <button type="button" onClick={onIncrease} disabled={max !== null && value >= max} aria-label="Agregar uno">
      <Plus size={16} />
    </button>
  </div>
);

QtyStepper.propTypes = {
  value: PropTypes.number.isRequired,
  onDecrease: PropTypes.func.isRequired,
  onIncrease: PropTypes.func.isRequired,
  min: PropTypes.number,
  max: PropTypes.number,
  size: PropTypes.oneOf(['sm', 'md']),
  label: PropTypes.string,
};

export default QtyStepper;
