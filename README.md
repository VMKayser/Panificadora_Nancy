# Panificadora Nancy

Monorepo que contiene:
- `backend/` - Laravel API
- `frontend/` - React + Vite frontend

Requisitos:
- Node 18.x
- PHP 8.x
- Composer
- Docker (opcional, Sail)

Instrucciones rápidas de desarrollo

Backend (Laravel):

```bash# comprobar listener en 3306
sudo ss -tulpn | grep -E ':3306\b' || true

# o con lsof
sudo lsof -i :3306 -sTCP:LISTEN -Pn || true
cd backend
composer install
cp .env.example .env
# Ajusta .env (DB, etc.)
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=0.0.0.0 --port=8000
```

Frontend (React + Vite):

```bash
cd frontend
npm install
npm run dev
# Abre http://localhost:5173
```

Notas:
- Las variables sensibles no deben subirse al repo (.env está en .gitignore)
- Para producción, construir el frontend y entregar los archivos estáticos

Contribuir

- Crear ramas por feature: `git checkout -b feat/nueva-funcion`
- Hacer PR y asignar revisores
- Mantener ramas actualizadas con `main`
