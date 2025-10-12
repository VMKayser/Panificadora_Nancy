#!/bin/bash

# Script de prueba para verificar el sistema de inventario
# Ejecutar desde: backend/

echo "🔍 VERIFICANDO SISTEMA DE INVENTARIO"
echo "======================================"
echo ""

echo "1️⃣ Verificando migraciones..."
./vendor/bin/sail artisan migrate:status | grep inventario_sistema_completo
echo ""

echo "2️⃣ Verificando rutas API..."
RUTAS=$(./vendor/bin/sail artisan route:list --path=inventario --compact | wc -l)
echo "   ✅ $RUTAS rutas de inventario registradas"
echo ""

echo "3️⃣ Verificando modelos..."
MODELOS=(
    "app/Models/MateriaPrima.php"
    "app/Models/Receta.php"
    "app/Models/IngredienteReceta.php"
    "app/Models/Produccion.php"
    "app/Models/InventarioProductoFinal.php"
    "app/Models/MovimientoMateriaPrima.php"
    "app/Models/MovimientoProductoFinal.php"
)

for modelo in "${MODELOS[@]}"; do
    if [ -f "$modelo" ]; then
        echo "   ✅ $modelo"
    else
        echo "   ❌ $modelo NO ENCONTRADO"
    fi
done
echo ""

echo "4️⃣ Verificando controladores..."
CONTROLADORES=(
    "app/Http/Controllers/MateriaPrimaController.php"
    "app/Http/Controllers/RecetaController.php"
    "app/Http/Controllers/ProduccionController.php"
    "app/Http/Controllers/InventarioController.php"
)

for controlador in "${CONTROLADORES[@]}"; do
    if [ -f "$controlador" ]; then
        echo "   ✅ $controlador"
    else
        echo "   ❌ $controlador NO ENCONTRADO"
    fi
done
echo ""

echo "5️⃣ Verificando datos de prueba..."
MATERIAS=$(./vendor/bin/sail artisan tinker --execute="echo App\\Models\\MateriaPrima::count();")
RECETAS=$(./vendor/bin/sail artisan tinker --execute="echo App\\Models\\Receta::count();")
echo "   📦 Materias primas: $MATERIAS"
echo "   📋 Recetas: $RECETAS"
echo ""

echo "======================================"
echo "✅ VERIFICACIÓN COMPLETA"
echo ""
echo "Para probar la API manualmente:"
echo "  1. Login: POST /api/login"
echo "  2. Ver stock bajo: GET /api/inventario/materias-primas/stock-bajo"
echo "  3. Dashboard: GET /api/inventario/dashboard"
echo ""
echo "Documentación completa en:"
echo "  📚 Instrucciones contenedor/API_INVENTARIO.md"
echo "  💻 Instrucciones contenedor/EJEMPLO_SERVICIO_FRONTEND.js"
