import { Link } from 'react-router-dom';
import { Award, Check, Heart, Users } from 'lucide-react';
import Footer from '../components/Footer';
import { useSEO } from '../hooks/useSEO';

const IMG = `${import.meta.env.BASE_URL}images`;

const Nosotros = () => {
  useSEO({
    title: 'Nosotros - Panificadora Nancy',
    description: 'Más de 42 años amasando tradición y calidad en Quillacollo.',
  });

  return (
    <>
      <main>
        <section className="pn-pagehero pn-pagehero--foto" style={{ backgroundImage: `url(${IMG}/nosotros1cabecera1.webp)` }}>
          <div className="pn-wrap">
            <h1>Nuestra historia</h1>
            <p>Más de 42 años amasando tradición y calidad.</p>
          </div>
        </section>

        <div className="pn-wrap pn-page pn-historia">
          <section className="pn-bloque" aria-labelledby="quienes">
            <figure className="pn-bloque__img">
              <img src={`${IMG}/primeraImagenNosotros.webp`} alt="La familia de Panificadora Nancy" loading="lazy" decoding="async" />
            </figure>
            <div className="pn-bloque__txt">
              <h2 id="quienes">Quiénes somos</h2>
              <p>
                <strong>Panificadora Nancy</strong> es más que un negocio; es el corazón de una familia y el resultado
                de una historia de resiliencia y esfuerzo. Con más de 42 años de trayectoria en el oficio panadero y
                23 años perfeccionando nuestro panetón, hemos crecido gracias al apoyo incondicional de nuestra familia
                y a la confianza de nuestros clientes.
              </p>
              <p>
                Nacimos de la convicción de que el trabajo hecho con sacrificio, esmero y responsabilidad es capaz de
                construir un futuro. Somos artesanos por herencia y emprendedores por convicción, dedicados a llevar un
                producto de calidad a tu mesa.
              </p>
              <dl className="pn-cifras">
                <div><dt>42+</dt><dd>años de tradición</dd></div>
                <div><dt>23+</dt><dd>años de panetón</dd></div>
              </dl>
            </div>
          </section>

          <section className="pn-bloque pn-bloque--inv" aria-labelledby="mision">
            <figure className="pn-bloque__img">
              <img src={`${IMG}/terceraImagenNosotros.webp`} alt="Elaboración artesanal del pan" loading="lazy" decoding="async" />
            </figure>
            <div className="pn-bloque__txt">
              <h2 id="mision">Nuestra misión</h2>
              <p>
                Nuestra misión es transformar ingredientes de calidad en una experiencia de sabor auténtico, manteniendo
                viva la esencia de la panadería tradicional. Cada producto que elaboramos es un reflejo de nuestro
                compromiso, amasado con paciencia y dedicación para garantizar la calidad que nos caracteriza.
              </p>
              <p>
                Nos dedicamos a cumplir con cada pedido con la máxima seriedad, entendiendo que detrás de cada entrega
                hay un cliente que deposita su confianza en nosotros.
              </p>
              <ul className="pn-checks">
                <li><Check size={18} aria-hidden="true" /> Ingredientes de primera calidad</li>
                <li><Check size={18} aria-hidden="true" /> Panadería tradicional artesanal</li>
                <li><Check size={18} aria-hidden="true" /> Compromiso con cada cliente</li>
              </ul>
            </div>
          </section>

          <section className="pn-bloque" aria-labelledby="vision">
            <figure className="pn-bloque__img">
              <img src={`${IMG}/segundaImagenNosotros.webp`} alt="Panetón tradicional" loading="lazy" decoding="async" />
            </figure>
            <div className="pn-bloque__txt">
              <h2 id="vision">Nuestra visión</h2>
              <p>
                Nuestra visión es ser un referente de calidad y tradición en la comunidad, honrando el legado de un
                oficio que se aprende con el corazón. Aspiramos a que el espíritu del horno artesanal, ese que une a las
                familias, siga presente en cada uno de nuestros productos.
              </p>
              <p>
                Queremos que Panificadora Nancy continúe siendo el símbolo de una historia de superación y que el sabor
                que creamos siga uniendo a las futuras generaciones en torno a la mesa.
              </p>
              <ul className="pn-valores">
                <li><Award size={20} aria-hidden="true" /><strong>Calidad</strong><span>En cada producto</span></li>
                <li><Users size={20} aria-hidden="true" /><strong>Familia</strong><span>Uniendo generaciones</span></li>
                <li><Heart size={20} aria-hidden="true" /><strong>Tradición</strong><span>Legado artesanal</span></li>
              </ul>
            </div>
          </section>

          <section className="pn-ctaband">
            <h2>Forma parte de nuestra historia</h2>
            <p>Descubre el sabor de la tradición en cada bocado.</p>
            <div className="pn-ctaband__btns">
              <Link to="/productos" className="pn-btn pn-btn--light pn-btn--lg">Ver productos</Link>
              <Link to="/contacto" className="pn-btn pn-btn--onphoto pn-btn--lg">Contáctanos</Link>
            </div>
          </section>
        </div>
      </main>
      <Footer />
    </>
  );
};

export default Nosotros;
