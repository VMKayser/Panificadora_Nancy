# 📚 GUÍA COMPLETA: Estructura React + Vite

## 🏗️ **ESTRUCTURA DEL PROYECTO**

```
frontend/
│
├── public/                    # Archivos estáticos (no se procesan)
│   └── vite.svg              # Imágenes públicas
│
├── src/                      # 🔥 AQUÍ TRABAJAS TODO
│   ├── main.jsx             # ⚡ PUNTO DE ENTRADA (inicio de todo)
│   ├── App.jsx              # 🏠 COMPONENTE PRINCIPAL
│   ├── index.css            # 🎨 Estilos globales base
│   ├── estilos.css          # 🎨 Tus estilos personalizados
│   │
│   ├── components/          # 📦 COMPONENTES REUTILIZABLES
│   │   ├── Header.jsx       # Navbar del sitio
│   │   ├── ProductCard.jsx  # Tarjeta de producto
│   │   ├── Carousel.jsx     # Carrusel de productos
│   │   └── Footer.jsx       # Pie de página (crear)
│   │
│   ├── pages/              # 📄 PÁGINAS COMPLETAS
│   │   ├── Home.jsx        # Página de inicio
│   │   ├── Cart.jsx        # Página del carrito
│   │   ├── Checkout.jsx    # Página de pago (crear)
│   │   └── Profile.jsx     # Página de perfil (crear)
│   │
│   ├── context/            # 🌐 ESTADO GLOBAL
│   │   └── CartContext.jsx # Estado del carrito compartido
│   │
│   ├── services/           # 🔌 CONEXIÓN CON BACKEND
│   │   └── api.js          # Funciones para llamar API
│   │
│   └── assets/             # 🖼️ Recursos (imágenes, íconos)
│
├── index.html              # HTML principal
├── package.json            # Dependencias del proyecto
├── vite.config.js         # Configuración de Vite
└── node_modules/          # Librerías instaladas (NO TOCAR)
```

---

## ⚡ **FLUJO DE EJECUCIÓN**

### **1️⃣ Inicio de la aplicación:**

```
index.html
    ↓
main.jsx (punto de entrada)
    ↓
App.jsx (componente raíz)
    ↓
Header + Routes (páginas)
    ↓
Home / Cart / Checkout / etc.
```

### **Explicación:**

1. **`index.html`** - HTML básico con `<div id="root">`
2. **`main.jsx`** - Monta React en el div#root
3. **`App.jsx`** - Define rutas y layout general
4. **Páginas** - Se renderizan según la URL

---

## 📝 **ARCHIVOS PRINCIPALES**

### **1. `main.jsx` - Punto de Entrada**

```jsx
import React from 'react'
import ReactDOM from 'react-dom/client'
import App from './App.jsx'
import 'bootstrap/dist/css/bootstrap.min.css'  
import './index.css'                           
import './estilos.css'                         

// Aquí React se "monta" en el HTML
ReactDOM.createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <App />
  </React.StrictMode>,
)
```

**¿Qué hace?**
- Importa Bootstrap, CSS global y tus estilos
- Monta `<App />` en el `<div id="root">` del index.html
- `StrictMode` ayuda a detectar errores en desarrollo

---

### **2. `App.jsx` - Componente Principal**

```jsx
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import { CartProvider } from './context/CartContext';
import Header from './components/Header';
import Home from './pages/Home';
import Cart from './pages/Cart';

function App() {
  return (
    <CartProvider>           {/* Estado global del carrito */}
      <Router>               {/* Manejo de rutas */}
        <Header />           {/* Navbar siempre visible */}
        <Routes>
          <Route path="/" element={<Home />} />
          <Route path="/carrito" element={<Cart />} />
          {/* Más rutas... */}
        </Routes>
      </Router>
    </CartProvider>
  );
}
```

**¿Qué hace?**
- Define las rutas (URLs) de tu aplicación
- Envuelve todo en `CartProvider` para compartir el carrito
- Muestra `Header` en todas las páginas
- Cambia el contenido según la ruta

---

### **3. `CartContext.jsx` - Estado Global**

```jsx
export const CartProvider = ({ children }) => {
  const [cart, setCart] = useState([]);
  
  const addToCart = (producto) => { /* ... */ }
  const removeFromCart = (id) => { /* ... */ }
  
  return (
    <CartContext.Provider value={{ cart, addToCart, removeFromCart }}>
      {children}
    </CartContext.Provider>
  );
};

// Para usar en cualquier componente:
import { useCart } from '../context/CartContext';

const MiComponente = () => {
  const { cart, addToCart } = useCart();
  // Ahora puedes usar cart y addToCart
};
```

**¿Qué hace?**
- Guarda el estado del carrito en localStorage
- Permite que CUALQUIER componente acceda al carrito
- Funciones: agregar, eliminar, actualizar cantidad

---

### **4. `api.js` - Comunicación con Backend**

```jsx
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost/api',  // ← URL de Laravel
});

// Funciones para llamar a la API
export const getProductos = async () => {
  const response = await api.get('/productos');
  return response.data;
};

export const crearPedido = async (pedidoData) => {
  const response = await api.post('/pedidos', pedidoData);
  return response.data;
};
```

**¿Qué hace?**
- Configura axios para hablar con Laravel
- Funciones reutilizables para llamar endpoints
- Se usa en componentes con `import { getProductos } from '../services/api'`

---

## 🧩 **TIPOS DE ARCHIVOS**

### **Componentes (.jsx)**

Son piezas reutilizables de UI:

```jsx
// ProductCard.jsx
const ProductCard = ({ producto }) => {
  return (
    <Card>
      <Card.Img src={producto.imagen} />
      <Card.Title>{producto.nombre}</Card.Title>
      <Button>Añadir</Button>
    </Card>
  );
};

export default ProductCard;
```

**Características:**
- ✅ Reciben props (datos desde afuera)
- ✅ Se pueden reutilizar múltiples veces
- ✅ Van en `/src/components/`

---

### **Páginas (.jsx)**

Son componentes completos que representan una vista:

```jsx
// Home.jsx
const Home = () => {
  const [productos, setProductos] = useState([]);
  
  useEffect(() => {
    fetchProductos();
  }, []);
  
  return (
    <Container>
      <h1>Bienvenido</h1>
      {productos.map(p => <ProductCard producto={p} />)}
    </Container>
  );
};
```

**Características:**
- ✅ Manejan estado con `useState`
- ✅ Hacen fetch de datos con `useEffect`
- ✅ Van en `/src/pages/`
- ✅ Se asocian a una ruta en App.jsx

---

### **Estilos (.css)**

```css
/* index.css - Estilos globales */
* { margin: 0; padding: 0; }
body { background: #f5f5f5; }

/* estilos.css - Tus estilos personalizados */
.presentacion {
  width: 100vw;
  height: 450px;
  background-image: url("...");
}
```

**Orden de importación:**
1. Bootstrap CSS (framework)
2. index.css (reset global)
3. estilos.css (tus overrides)

---

## 🚀 **CÓMO MOVERTE EN REACT**

### **1. ¿Quieres crear una nueva página?**

```bash
# 1. Crear archivo en src/pages/
src/pages/Contacto.jsx

# 2. Crear el componente
const Contacto = () => {
  return <div><h1>Contacto</h1></div>;
};
export default Contacto;

# 3. Agregar ruta en App.jsx
import Contacto from './pages/Contacto';
<Route path="/contacto" element={<Contacto />} />
```

---

### **2. ¿Quieres crear un componente reutilizable?**

```bash
# 1. Crear archivo en src/components/
src/components/Badge.jsx

# 2. Crear el componente con props
const Badge = ({ texto, color }) => {
  return <span style={{ color }}>{texto}</span>;
};
export default Badge;

# 3. Usarlo en cualquier página
import Badge from '../components/Badge';
<Badge texto="Nuevo" color="red" />
```

---

### **3. ¿Quieres llamar a la API?**

```bash
# 1. Ir a src/services/api.js
export const getMisDatos = async () => {
  const response = await api.get('/mi-endpoint');
  return response.data;
};

# 2. Usar en un componente
import { getMisDatos } from '../services/api';

const MiComponente = () => {
  const [datos, setDatos] = useState([]);
  
  useEffect(() => {
    getMisDatos().then(data => setDatos(data));
  }, []);
};
```

---

### **4. ¿Quieres agregar estilos?**

**Opción A: Bootstrap (recomendado)**
```jsx
import { Button, Card } from 'react-bootstrap';
<Button variant="primary">Click</Button>
```

**Opción B: CSS personalizado**
```jsx
// estilos.css
.mi-clase { color: red; }

// Componente
<div className="mi-clase">Hola</div>
```

**Opción C: Inline styles**
```jsx
<div style={{ color: 'red', fontSize: '20px' }}>Hola</div>
```

---

## 🔄 **HOOKS DE REACT (Funciones especiales)**

### **`useState` - Estado local**

```jsx
const [nombre, setNombre] = useState('Juan');

// Leer: {nombre}
// Cambiar: setNombre('Pedro')
```

### **`useEffect` - Efectos secundarios**

```jsx
useEffect(() => {
  // Código que se ejecuta al montar el componente
  fetchDatos();
}, []); // ← Array vacío = solo al inicio

useEffect(() => {
  // Se ejecuta cuando 'producto' cambie
  console.log(producto);
}, [producto]); // ← Dependencia
```

### **`useContext` - Acceder a contexto global**

```jsx
const { cart, addToCart } = useCart();
```

### **`useNavigate` - Navegar entre páginas**

```jsx
import { useNavigate } from 'react-router-dom';

const MiComponente = () => {
  const navigate = useNavigate();
  
  const irAlCarrito = () => {
    navigate('/carrito');
  };
};
```

---

## 📦 **COMPONENTES DE BOOTSTRAP**

### **Navbar**
```jsx
import { Navbar, Nav } from 'react-bootstrap';
<Navbar bg="light" expand="lg">
  <Nav.Link href="/">Inicio</Nav.Link>
</Navbar>
```

### **Card**
```jsx
import { Card, Button } from 'react-bootstrap';
<Card>
  <Card.Img src="imagen.jpg" />
  <Card.Body>
    <Card.Title>Título</Card.Title>
    <Button variant="primary">Click</Button>
  </Card.Body>
</Card>
```

### **Container, Row, Col**
```jsx
import { Container, Row, Col } from 'react-bootstrap';
<Container>
  <Row>
    <Col md={6}>Columna 1</Col>
    <Col md={6}>Columna 2</Col>
  </Row>
</Container>
```

### **Carousel (el que faltaba)**
```jsx
import { Carousel } from 'react-bootstrap';
<Carousel>
  <Carousel.Item>
    <img src="img1.jpg" alt="First" />
  </Carousel.Item>
  <Carousel.Item>
    <img src="img2.jpg" alt="Second" />
  </Carousel.Item>
</Carousel>
```

---

## 🎯 **COMANDOS ÚTILES**

```bash
# Instalar dependencias
npm install

# Iniciar servidor de desarrollo
npm run dev

# Compilar para producción
npm run build

# Preview del build
npm run preview

# Instalar una librería
npm install nombre-libreria
```

---

## 🗂️ **FLUJO DE TRABAJO**

1. **Planificar** - ¿Qué página/componente necesito?
2. **Crear archivo** - En `pages/` o `components/`
3. **Importar Bootstrap** - `import { Button } from 'react-bootstrap'`
4. **Crear JSX** - HTML + JavaScript juntos
5. **Manejar estado** - `useState` para datos dinámicos
6. **Llamar API** - `useEffect` + funciones de `api.js`
7. **Probar** - Ver en `localhost:5174`

---

## 🚨 **ERRORES COMUNES**

### **Error: Cannot read property of undefined**
```jsx
// ❌ Mal
{producto.nombre}

// ✅ Bien (validar primero)
{producto?.nombre || 'Sin nombre'}
```

### **Error: Objects are not valid as a React child**
```jsx
// ❌ Mal
<div>{producto}</div>

// ✅ Bien
<div>{producto.nombre}</div>
```

### **Error: Each child should have a unique key**
```jsx
// ❌ Mal
{productos.map(p => <Card>{p.nombre}</Card>)}

// ✅ Bien
{productos.map(p => <Card key={p.id}>{p.nombre}</Card>)}
```

---

## 🎓 **RESUMEN**

- **React** = Librería para crear interfaces
- **Vite** = Herramienta de desarrollo (super rápida)
- **Bootstrap** = Framework de CSS y componentes
- **JSX** = HTML dentro de JavaScript
- **Components** = Piezas reutilizables
- **Pages** = Vistas completas
- **Context** = Estado compartido entre componentes
- **Hooks** = Funciones especiales (useState, useEffect)

---

**¿Siguiente paso?** Ahora que entiendes la estructura, podemos crear la página de **Checkout** completa con formularios de Bootstrap!
