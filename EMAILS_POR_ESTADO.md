# 📧 Sistema de Emails por Estado - Implementación Completa

**Fecha**: 15 de octubre de 2025  
**Estado**: ✅ IMPLEMENTADO

---

## 🎯 Emails Implementados

### 1. Email "Confirmado" - `PedidoConfirmado` ✅

**Cuándo se envía**: Admin cambia estado a `confirmado`

**Contenido**:
- ✅ Confirmación del pedido
- ✅ Número de pedido
- ✅ Detalles completos de productos
- ✅ Total a pagar
- ✅ Información de entrega
- ✅ Método de pago

**Asunto**: `🥐 Pedido Confirmado #000123 - Panificadora Nancy`

---

### 2. Email "Preparando" - `PedidoEstadoCambiado` ✅

**Cuándo se envía**: Admin cambia estado a `preparando`

**Contenido**:
- 👨‍🍳 Nuestros panaderos están preparando tu pedido
- Línea de tiempo del pedido
- Detalles del pedido
- Tiempo estimado

**Asunto**: `👨‍🍳 Preparando - Pedido #000123 - Panificadora Nancy`

---

### 3. Email "Listo" - `PedidoEstadoCambiado` ✅

**Cuándo se envía**: Admin cambia estado a `listo`

**Contenido**:
- ✨ Tu pedido está listo para recoger/entregar
- Dirección del local (si es recoger)
- Horario disponible
- Detalles del pedido

**Asunto**: `✨ Listo - Pedido #000123 - Panificadora Nancy`

---

### 4. Email "En Camino" - `PedidoEstadoCambiado` ✅

**Cuándo se envía**: Admin cambia estado a `en_camino`

**Contenido**:
- 🚗 Tu pedido está en camino
- Dirección de entrega
- Tiempo estimado de llegada
- Detalles del pedido

**Asunto**: `🚗 En Camino - Pedido #000123 - Panificadora Nancy`

---

### 5. Email "Entregado" - `PedidoEstadoCambiado` ✅

**Cuándo se envía**: Admin cambia estado a `entregado`

**Contenido**:
- 📦 Pedido entregado exitosamente
- Agradecimiento
- Invitación a volver a comprar
- Detalles del pedido completado

**Asunto**: `📦 Entregado - Pedido #000123 - Panificadora Nancy`

---

### 6. Email "Cancelado" - `PedidoEstadoCambiado` ✅

**Cuándo se envía**: Admin cambia estado a `cancelado`

**Contenido**:
- ❌ Notificación de cancelación
- Razón de cancelación (si se proporciona)
- Información de reembolso (si aplica)
- Detalles del pedido

**Asunto**: `❌ Cancelado - Pedido #000123 - Panificadora Nancy`

---

## 🔄 Flujo Completo de Emails

```
┌──────────────────────────────────────────────────────────┐
│              FLUJO DE PEDIDO Y EMAILS                    │
├──────────────────────────────────────────────────────────┤
│                                                          │
│  1. Cliente crea pedido                                 │
│     Estado: "pendiente"                                  │
│     ❌ NO se envía email                                 │
│                                                          │
├──────────────────────────────────────────────────────────┤
│                                                          │
│  2. Admin confirma pedido                               │
│     Estado: "pendiente" → "confirmado"                   │
│     ✅ EMAIL: PedidoConfirmado                           │
│     📧 "🥐 Pedido Confirmado #000123"                     │
│                                                          │
├──────────────────────────────────────────────────────────┤
│                                                          │
│  3. Admin marca como preparando                         │
│     Estado: "confirmado" → "preparando"                  │
│     ✅ EMAIL: PedidoEstadoCambiado                       │
│     📧 "👨‍🍳 Preparando - Pedido #000123"                  │
│                                                          │
├──────────────────────────────────────────────────────────┤
│                                                          │
│  4. Pedido está listo                                   │
│     Estado: "preparando" → "listo"                       │
│     ✅ EMAIL: PedidoEstadoCambiado                       │
│     📧 "✨ Listo - Pedido #000123"                        │
│                                                          │
├──────────────────────────────────────────────────────────┤
│                                                          │
│  5. Pedido sale para entrega                            │
│     Estado: "listo" → "en_camino"                        │
│     ✅ EMAIL: PedidoEstadoCambiado                       │
│     📧 "🚗 En Camino - Pedido #000123"                    │
│                                                          │
├──────────────────────────────────────────────────────────┤
│                                                          │
│  6. Pedido entregado                                    │
│     Estado: "en_camino" → "entregado"                    │
│     ✅ EMAIL: PedidoEstadoCambiado                       │
│     📧 "📦 Entregado - Pedido #000123"                    │
│                                                          │
└──────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────┐
│              FLUJO ALTERNATIVO (Cancelación)             │
├──────────────────────────────────────────────────────────┤
│                                                          │
│  Admin cancela pedido                                   │
│     Estado: cualquier estado → "cancelado"               │
│     ✅ EMAIL: PedidoEstadoCambiado                       │
│     📧 "❌ Cancelado - Pedido #000123"                    │
│                                                          │
└──────────────────────────────────────────────────────────┘
```

---

## 💻 Código Implementado

### AdminPedidoController.php

```php
public function updateEstado(Request $request, $id)
{
    $pedido = Pedido::findOrFail($id);
    $estadoAnterior = $pedido->estado;
    $estadoNuevo = $request->estado;
    
    $pedido->update(['estado' => $estadoNuevo]);
    
    // Cargar relaciones necesarias para los correos
    $pedido->load(['detalles.producto', 'metodoPago', 'cliente']);
    
    // Enviar emails según el estado
    try {
        // Email especial de confirmación (con PedidoConfirmado)
        if ($estadoAnterior !== 'confirmado' && $estadoNuevo === 'confirmado') {
            Mail::to($pedido->cliente_email)->send(new PedidoConfirmado($pedido));
            Log::info("Email de pedido confirmado enviado a {$pedido->cliente_email} para pedido #{$pedido->id}");
        }
        // Emails de cambio de estado para otros estados importantes
        elseif (in_array($estadoNuevo, ['preparando', 'listo', 'en_camino', 'entregado', 'cancelado'])) {
            Mail::to($pedido->cliente_email)->send(new PedidoEstadoCambiado($pedido));
            Log::info("Email de estado '{$estadoNuevo}' enviado a {$pedido->cliente_email} para pedido #{$pedido->id}");
        }
    } catch (\Exception $e) {
        // Loguear el error pero no fallar la actualización
        Log::error("Error enviando correo de estado '{$estadoNuevo}': " . $e->getMessage());
    }
    
    Cache::forget('pedidos.index.page.1.per.20');
    Cache::forget('pedidos.index.page.1.per.50');
    Cache::forget('pedidos.index.page.1.per.100');
    
    return response()->json(['success' => true, 'pedido' => $pedido]);
}
```

---

## 📊 Tabla de Estados y Emails

| Estado Anterior | Estado Nuevo | Email Enviado | Clase Mailable |
|----------------|--------------|---------------|----------------|
| `pendiente` | `confirmado` | ✅ Sí | `PedidoConfirmado` |
| Cualquiera | `preparando` | ✅ Sí | `PedidoEstadoCambiado` |
| Cualquiera | `listo` | ✅ Sí | `PedidoEstadoCambiado` |
| Cualquiera | `en_camino` | ✅ Sí | `PedidoEstadoCambiado` |
| Cualquiera | `entregado` | ✅ Sí | `PedidoEstadoCambiado` |
| Cualquiera | `cancelado` | ✅ Sí | `PedidoEstadoCambiado` |
| Cualquiera | `pendiente` | ❌ No | - |

---

## 🎨 Características de los Emails

### Diseño Común (Todos los emails)

✅ **Header con gradiente** (#8B4513 → #D2691E - colores de panadería)  
✅ **Responsive** (se adapta a móviles)  
✅ **Emojis en asuntos** (mejor tasa de apertura)  
✅ **Badge de estado** con colores distintivos  
✅ **Línea de tiempo** del pedido  
✅ **Detalles completos** del pedido  
✅ **Footer informativo** con datos de contacto  
✅ **Inline CSS** (compatible con todos los clientes de email)

### Colores por Estado

```css
.estado-confirmado { background: #17a2b8; } /* Cyan */
.estado-preparando { background: #fd7e14; } /* Naranja */
.estado-listo      { background: #28a745; } /* Verde */
.estado-en-camino  { background: #007bff; } /* Azul */
.estado-entregado  { background: #28a745; } /* Verde */
.estado-cancelado  { background: #dc3545; } /* Rojo */
```

---

## 🧪 Testing

### Probar el Sistema

1. **Crear un pedido de prueba**
   ```
   - Login como cliente
   - Agregar productos al carrito
   - Realizar pedido
   ```

2. **Confirmar pedido (Email 1)**
   ```
   - Login como admin
   - Panel Admin → Pedidos
   - Cambiar estado a "confirmado"
   - ✅ Verificar email recibido
   ```

3. **Marcar como preparando (Email 2)**
   ```
   - Cambiar estado a "preparando"
   - ✅ Verificar email recibido
   ```

4. **Continuar la secuencia**
   ```
   - "listo" → ✅ Email
   - "en_camino" → ✅ Email
   - "entregado" → ✅ Email
   ```

5. **Probar cancelación**
   ```
   - Crear otro pedido
   - Cambiar a "cancelado"
   - ✅ Verificar email recibido
   ```

---

## 📝 Logs para Debugging

### Ver logs en tiempo real

```bash
cd backend

# Ver todos los logs
docker compose exec laravel.test tail -f storage/logs/laravel.log

# Filtrar solo emails
docker compose exec laravel.test tail -f storage/logs/laravel.log | grep -i email

# Filtrar por estado específico
docker compose exec laravel.test tail -f storage/logs/laravel.log | grep -i "en_camino"
```

### Ejemplo de log exitoso

```
[2025-10-15 14:30:45] local.INFO: Email de pedido confirmado enviado a cliente@example.com para pedido #123
[2025-10-15 14:35:12] local.INFO: Email de estado 'preparando' enviado a cliente@example.com para pedido #123
[2025-10-15 14:40:33] local.INFO: Email de estado 'listo' enviado a cliente@example.com para pedido #123
```

### Ejemplo de log con error

```
[2025-10-15 14:30:45] local.ERROR: Error enviando correo de estado 'confirmado': Connection timeout
```

---

## ⚙️ Configuración SMTP

### Desarrollo (Mailtrap recomendado)

```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=tu_username
MAIL_PASSWORD=tu_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@panificadoranancy.com"
MAIL_FROM_NAME="Panificadora Nancy"
```

### Producción (Hostinger - cuando esté listo)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=587
MAIL_USERNAME=noreply@tudominio.com
MAIL_PASSWORD=tu_contraseña_segura
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@tudominio.com"
MAIL_FROM_NAME="Panificadora Nancy"
```

---

## 🚀 Próximos Pasos (Cuando tengas el dominio)

### 1. Configurar Email Profesional
- [ ] Acceder a panel de Hostinger
- [ ] Ir a "Emails"
- [ ] Crear cuenta: `noreply@tudominio.com`
- [ ] Copiar credenciales SMTP

### 2. Actualizar .env de Producción
```bash
# En tu servidor de producción
nano .env

# Actualizar estas líneas:
MAIL_HOST=smtp.hostinger.com
MAIL_USERNAME=noreply@tudominio.com
MAIL_PASSWORD=contraseña_del_panel
MAIL_FROM_ADDRESS="noreply@tudominio.com"
```

### 3. Configurar DNS (Crítico para evitar spam)

**SPF Record:**
```
Tipo: TXT
Nombre: @
Valor: v=spf1 include:_spf.hostinger.com ~all
```

**DKIM Record:**
```
Hostinger te proporcionará esto en:
Panel → Emails → Tu cuenta → Configuración → DKIM
```

**DMARC Record:**
```
Tipo: TXT
Nombre: _dmarc
Valor: v=DMARC1; p=quarantine; rua=mailto:admin@tudominio.com
```

### 4. Probar en Producción

```bash
# Conectar al servidor
ssh usuario@tu-servidor

# Probar SMTP
cd /var/www/panificadora-nancy/backend
php artisan tinker

# En tinker:
Mail::raw('Test email', function($message) {
    $message->to('tu_email@gmail.com')
            ->subject('Test desde Hostinger');
});
```

### 5. Monitorear Deliverability

- Revisar tasa de apertura
- Verificar que no van a spam
- Comprobar bounces (rebotes)
- Revisar logs de Hostinger

---

## 📋 Checklist de Implementación

### Backend
- [x] Importar `PedidoEstadoCambiado` en AdminPedidoController
- [x] Implementar lógica de envío en `updateEstado()`
- [x] Agregar logs para debugging
- [x] Manejar errores sin fallar la actualización
- [x] Cargar relaciones necesarias para los emails

### Emails
- [x] Clase `PedidoConfirmado` (ya existía)
- [x] Clase `PedidoEstadoCambiado` (ya existía)
- [x] Vista `pedido-confirmado.blade.php` (ya existía)
- [x] Vista `pedido-estado-cambiado.blade.php` (ya existía)

### Testing
- [ ] Probar email de "confirmado"
- [ ] Probar email de "preparando"
- [ ] Probar email de "listo"
- [ ] Probar email de "en_camino"
- [ ] Probar email de "entregado"
- [ ] Probar email de "cancelado"
- [ ] Verificar en Gmail, Outlook, Yahoo
- [ ] Probar en móvil

### Producción (Cuando tengas dominio)
- [ ] Configurar email en Hostinger
- [ ] Actualizar .env con credenciales
- [ ] Configurar SPF, DKIM, DMARC
- [ ] Probar envío desde producción
- [ ] Monitorear deliverability primeros días

---

## 💡 Mejoras Futuras (Opcional)

### 1. Colas (Queue)
```php
// Hacer que los emails se procesen en background
class PedidoConfirmado extends Mailable implements ShouldQueue
{
    use Queueable;
    // ...
}
```

### 2. Personalización por Cliente
```php
// Enviar emails personalizados según tipo de cliente
if ($pedido->cliente->tipo === 'mayorista') {
    // Email especial para mayoristas
}
```

### 3. Notificaciones Push (Adicional)
```php
// Además del email, enviar notificación push
Notification::send($user, new PedidoEstadoCambiadoNotification($pedido));
```

### 4. SMS (Opcional)
```php
// Para pedidos urgentes o de alto valor
if ($pedido->total > 500) {
    SMS::send($pedido->cliente_telefono, "Tu pedido está en camino");
}
```

---

## 📚 Archivos Relevantes

```
backend/
├── app/
│   ├── Http/Controllers/Api/
│   │   └── AdminPedidoController.php          ← ✅ MODIFICADO
│   └── Mail/
│       ├── PedidoConfirmado.php               ← ✅ USA ESTE
│       └── PedidoEstadoCambiado.php           ← ✅ USA ESTE
├── resources/views/emails/
│   ├── pedido-confirmado.blade.php            ← ✅ VISTA
│   └── pedido-estado-cambiado.blade.php       ← ✅ VISTA
└── config/
    └── mail.php                                ← Configuración SMTP
```

---

**Estado**: ✅ Implementación completa  
**Emails activos**: 6 (confirmado, preparando, listo, en_camino, entregado, cancelado)  
**Pendiente**: Configuración de dominio en producción  
**Última actualización**: 15 de octubre de 2025
