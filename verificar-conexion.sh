#!/bin/bash

echo "🔍 Verificando configuración del sistema Panificadora Nancy..."
echo ""

# Colores
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 1. Verificar Docker
echo "📦 Verificando contenedores Docker..."
if docker ps | grep -q "backend-laravel.test-1"; then
    echo -e "${GREEN}✅ Backend Laravel corriendo${NC}"
    BACKEND_PORT=$(docker ps --format "{{.Ports}}" --filter "name=backend-laravel.test-1" | grep -oP '0.0.0.0:\K\d+(?=->80)' | head -n1)
    echo "   Puerto: ${BACKEND_PORT}"
else
    echo -e "${RED}❌ Backend Laravel NO está corriendo${NC}"
    echo "   Ejecuta: cd backend && docker compose up -d"
fi

if docker ps | grep -q "backend-mysql-1"; then
    echo -e "${GREEN}✅ MySQL corriendo${NC}"
else
    echo -e "${RED}❌ MySQL NO está corriendo${NC}"
fi

echo ""

# 2. Verificar Backend API
echo "🌐 Verificando API del Backend..."
RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/api/productos 2>/dev/null)
if [ "$RESPONSE" = "200" ]; then
    echo -e "${GREEN}✅ API respondiendo correctamente (HTTP 200)${NC}"
    echo "   URL: http://localhost/api/productos"
else
    echo -e "${RED}❌ API NO responde (HTTP $RESPONSE)${NC}"
fi

echo ""

# 3. Verificar Frontend
echo "🎨 Verificando Frontend..."
if lsof -ti:5174 >/dev/null 2>&1; then
    echo -e "${GREEN}✅ Frontend corriendo en puerto 5174${NC}"
    echo "   URL: http://localhost:5174/app/"
else
    echo -e "${RED}❌ Frontend NO está corriendo${NC}"
    echo "   Ejecuta: cd frontend && npm run dev"
fi

echo ""

# 4. Verificar configuración del Frontend
echo "⚙️  Verificando configuración Frontend..."
if [ -f "frontend/.env" ]; then
    API_URL=$(grep VITE_API_URL frontend/.env | cut -d'=' -f2)
    echo "   VITE_API_URL: $API_URL"
    
    if [ "$API_URL" = "http://localhost/api" ]; then
        echo -e "${GREEN}✅ Configuración correcta${NC}"
    else
        echo -e "${YELLOW}⚠️  Configuración puede ser incorrecta${NC}"
        echo "   Debería ser: http://localhost/api"
    fi
else
    echo -e "${RED}❌ Archivo frontend/.env no encontrado${NC}"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "📋 Resumen:"
echo "   Backend:  http://localhost/api"
echo "   Frontend: http://localhost:5174/app/"
echo "   PHPMyAdmin: http://localhost:8080"
echo ""
echo "🚀 Para iniciar todo el sistema:"
echo "   1. cd backend && docker compose up -d"
echo "   2. cd frontend && npm run dev"
echo ""
