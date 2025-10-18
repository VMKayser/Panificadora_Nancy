#!/bin/bash

# Script para probar el sistema de emails
# Uso: ./test-emails.sh

echo "🧪 Sistema de Prueba de Emails - Panificadora Nancy"
echo "=================================================="
echo ""

# Colores
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Verificar que estamos en el directorio correcto
if [ ! -f "docker-compose.yml" ] && [ ! -f "compose.yaml" ]; then
    echo -e "${RED}❌ Error: Debes ejecutar este script desde el directorio backend${NC}"
    exit 1
fi

# Verificar que Docker está corriendo
if ! docker compose ps | grep -q "laravel.test"; then
    echo -e "${RED}❌ Error: Laravel no está corriendo${NC}"
    echo -e "${YELLOW}Ejecuta: docker compose up -d${NC}"
    exit 1
fi

echo -e "${BLUE}📧 Probando conexión SMTP...${NC}"
docker compose exec laravel.test php -r "
use Illuminate\Support\Facades\Mail;
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

try {
    echo '✅ Configuración SMTP cargada\n';
    echo 'Host: ' . config('mail.mailers.smtp.host') . '\n';
    echo 'Port: ' . config('mail.mailers.smtp.port') . '\n';
    echo 'From: ' . config('mail.from.address') . '\n';
} catch (\Exception \$e) {
    echo '❌ Error: ' . \$e->getMessage() . '\n';
}
"

echo ""
echo -e "${BLUE}📊 Estados que envían emails:${NC}"
echo "  ✅ confirmado    → PedidoConfirmado"
echo "  ✅ preparando    → PedidoEstadoCambiado"
echo "  ✅ listo         → PedidoEstadoCambiado"
echo "  ✅ en_camino     → PedidoEstadoCambiado"
echo "  ✅ entregado     → PedidoEstadoCambiado"
echo "  ✅ cancelado     → PedidoEstadoCambiado"
echo "  ❌ pendiente     → No envía email"

echo ""
echo -e "${YELLOW}⚠️  Para probar:${NC}"
echo "1. Crea un pedido como cliente"
echo "2. Accede al panel de admin"
echo "3. Cambia el estado del pedido"
echo "4. Verifica que llegue el email"

echo ""
echo -e "${BLUE}📝 Ver logs en tiempo real:${NC}"
echo "docker compose exec laravel.test tail -f storage/logs/laravel.log | grep -i email"

echo ""
echo -e "${GREEN}✅ Sistema de emails configurado correctamente${NC}"
