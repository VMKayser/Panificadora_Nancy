import { useEffect, useState } from 'react';
import { Link, Navigate, useLocation } from 'react-router-dom';
import { Modal } from 'react-bootstrap';
import { Check, CircleCheck, Download, MessageCircle } from 'lucide-react';
import { useSEO } from '../hooks/useSEO';
import api, { assetBase } from '../services/api';
import { formatCurrency } from '../utils/number';
import { enlaceWhatsapp, WHATSAPP_TIENDA } from '../utils/whatsapp';
import { ENTREGA_TEXTO, leerUltimoPedido, mensajePedido } from '../utils/pedido';

// Las imágenes del backend también se sirven bajo /api/storage del mismo
// dominio; desde ahí "Guardar QR" descarga la imagen en vez de abrirla
// (el atributo download no funciona con imágenes de otro dominio).
const urlDescarga = (url) => {
  const base = assetBase();
  if (!url || !base.startsWith('/')) return url;
  try {
    const { pathname } = new URL(url, window.location.origin);
    return pathname.startsWith('/storage/') ? `${base}${pathname}` : url;
  } catch {
    return url;
  }
};

const PedidoConfirmado = () => {
  const location = useLocation();
  const [pedido] = useState(() => (location.state?.numero ? location.state : leerUltimoPedido()));
  const [whatsapp, setWhatsapp] = useState(WHATSAPP_TIENDA);
  const [qrGrande, setQrGrande] = useState(false);
  const [enviado, setEnviado] = useState(false);

  useSEO({
    title: 'Pedido recibido - Panificadora Nancy',
    description: 'Tu pedido fue registrado.',
    noindex: true,
  });

  useEffect(() => {
    api.get('/configuraciones/public/whatsapp_empresa/valor')
      .then((r) => { if (r.data?.valor) setWhatsapp(r.data.valor); })
      .catch(() => {});
  }, []);

  // Sin pedido (ej. se abrió la dirección a mano) no hay nada que mostrar
  if (!pedido?.numero) {
    return <Navigate to="/" replace />;
  }

  const enlace = enlaceWhatsapp(whatsapp, mensajePedido(pedido));

  return (
    <main className="pn-wrap pn-page pn-gracias">
      <header className="pn-confirm">
        <CircleCheck size={52} className="pn-confirm__icon" strokeWidth={1.75} aria-hidden="true" />
        <h1 className="pn-page__title">¡Pedido recibido!</h1>
        <p className="pn-page__sub">Tu número de pedido es <strong className="pn-confirm__numero">{pedido.numero}</strong></p>
      </header>

      <section className="pn-panel pn-pago" aria-labelledby="ultimo-paso">
        <h2 className="pn-panel__title" id="ultimo-paso">Último paso: paga y avísanos</h2>

        <ol className="pn-pasos">
          <li>
            <span className="pn-step" aria-hidden="true">1</span>
            <div>
              <p className="pn-pasos__titulo">Paga <strong>Bs {formatCurrency(pedido.total)}</strong> con este QR</p>
              {pedido.qr ? (
                <>
                  <button type="button" className="pn-qr" onClick={() => setQrGrande(true)} aria-label="Ver el QR en grande">
                    <img src={pedido.qr} alt="Código QR para pagar" />
                  </button>
                  <div className="pn-qr__acciones">
                    <a className="pn-btn pn-btn--ghost pn-btn--sm" href={urlDescarga(pedido.qr)} download={`QR-${pedido.numero}.jpg`} target="_blank" rel="noreferrer">
                      <Download size={16} /> Guardar QR
                    </a>
                    <span className="pn-field__help">Guárdalo y ábrelo desde la app de tu banco.</span>
                  </div>
                </>
              ) : (
                <p className="pn-field__help">Te mandamos el QR por WhatsApp.</p>
              )}
            </div>
          </li>
          <li>
            <span className="pn-step" aria-hidden="true">2</span>
            <div>
              <p className="pn-pasos__titulo">Mándanos el comprobante por WhatsApp</p>
              <p className="pn-field__help">Se abre WhatsApp con tu pedido ya escrito: solo adjunta la captura del pago.</p>
              <a
                className="pn-btn pn-btn--wa pn-btn--lg pn-btn--block mt-2"
                href={enlace}
                target="_blank"
                rel="noreferrer"
                onClick={() => setEnviado(true)}
              >
                <MessageCircle size={20} /> {enviado ? 'Abrir WhatsApp otra vez' : 'Enviar por WhatsApp'}
              </a>
              {enviado && (
                <p className="pn-listo" role="status"><Check size={16} /> Listo. Te confirmamos el pedido por WhatsApp cuando revisemos el pago.</p>
              )}
            </div>
          </li>
        </ol>
      </section>

      <section className="pn-panel" aria-labelledby="resumen-pedido">
        <h2 className="pn-panel__title" id="resumen-pedido">Tu pedido</h2>
        <ul className="pn-resumen">
          {pedido.lineas.map((l, i) => (
            <li key={i} className="pn-sum__row">
              <span>
                {l.cantidad} × {l.nombre}
                {l.detalle && <small className="d-block text-muted">{l.detalle}</small>}
              </span>
              <span>Bs {formatCurrency(l.subtotal)}</span>
            </li>
          ))}
        </ul>
        <div className="pn-sum">
          <div className="pn-sum__row pn-sum__row--total"><span>Total</span><span>Bs {formatCurrency(pedido.total)}</span></div>
        </div>
        <dl className="pn-confirm__datos mt-3">
          <dt>Entrega</dt>
          <dd>
            {ENTREGA_TEXTO[pedido.tipoEntrega]}
            {pedido.direccion && <span className="pn-confirm__extra">{pedido.direccion}</span>}
          </dd>
          {pedido.cuando && (<><dt>Cuándo</dt><dd>{pedido.cuando}</dd></>)}
          <dt>A nombre de</dt><dd>{pedido.nombre} · {pedido.celular}</dd>
          {pedido.nota && (<><dt>Nota</dt><dd>{pedido.nota}</dd></>)}
        </dl>
      </section>

      <div className="pn-gracias__fin">
        <Link to="/productos" className="pn-btn pn-btn--ghost">Seguir comprando</Link>
        {pedido.conCuenta && <Link to="/mis-pedidos" className="pn-btn pn-btn--ghost">Ver mis pedidos</Link>}
      </div>

      <Modal show={qrGrande} onHide={() => setQrGrande(false)} centered className="pn-qrmodal">
        <Modal.Header closeButton closeLabel="Cerrar">
          <Modal.Title as="h2">QR para pagar Bs {formatCurrency(pedido.total)}</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <img src={pedido.qr} alt="Código QR para pagar" />
        </Modal.Body>
      </Modal>
    </main>
  );
};

export default PedidoConfirmado;
