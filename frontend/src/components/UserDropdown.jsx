import { useRef, useState } from 'react';
import { CircleUser } from 'lucide-react';
import { Overlay } from 'react-bootstrap';
import { Link } from 'react-router-dom';

const UserDropdown = ({ user, onLogout }) => {
  const [show, setShow] = useState(false);
  const toggleRef = useRef(null);

  const handleToggle = (e) => {
    e.preventDefault();
    setShow(s => !s);
  };

  const handleHide = () => setShow(false);

  return (
    <>
      <a href="#" ref={toggleRef} onClick={handleToggle} className="nav-link mx-2 d-flex align-items-center gap-2" style={{ cursor: 'pointer' }}>
        <CircleUser size={18} /> {user.name}
      </a>

      <Overlay
        target={toggleRef.current}
        show={show}
        placement="bottom-end"
        container={document.body}
        rootClose
        onHide={handleHide}
      >
        {({ placement, arrowProps, show: _s, popper, ...props }) => (
          <div
            className={`dropdown-menu show`}
            style={{ position: 'absolute', minWidth: 160 }}
            {...props}
          >
            <Link to="/perfil" className="dropdown-item" onClick={handleHide}>Mi Perfil</Link>
            <Link to="/mis-pedidos" className="dropdown-item" onClick={handleHide}>Mis Pedidos</Link>
            <div className="dropdown-divider"></div>
            <button className="dropdown-item" onClick={() => { handleHide(); onLogout(); }}>Cerrar Sesión</button>
          </div>
        )}
      </Overlay>
    </>
  );
};

export default UserDropdown;
