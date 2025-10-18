# 📧 Sistema de Mailers - Panificadora Nancy

## 📋 Estado Actual del Sistema de Correos

### 🎯 Emails Implementados

El sistema actualmente tiene **1 tipo de email** activo:

#### ~~1. Email de Bienvenida~~ (`BienvenidaUsuario`) ❌ ELIMINADO
- **Estado**: **ELIMINADO** - No es necesario enviar email de bienvenida
- **Razón**: Simplificar el proceso de registro, reducir fricción
- ~~Disparador: `AuthController::register()` línea 79~~
- ~~Vista: `resources/views/emails/bienvenida.blade.php`~~

**✅ Los usuarios ahora se registran sin recibir email de bienvenida.**

---

#### 2. **Email de Pedido Confirmado** (`PedidoConfirmado`) ✅ ACTIVO
- **Cuándo se envía**: **Cuando el ADMIN cambia el estado del pedido a "confirmado"**
- **Disparador**: `AdminPedidoController::updateEstado()` 
- **Destinatario**: Email del cliente que realizó el pedido
- **Asunto**: "🥐 Pedido Confirmado #000001 - Panificadora Nancy"
- **Vista**: `resources/views/emails/pedido-confirmado.blade.php`

```php
// En AdminPedidoController.php (cuando admin confirma el pedido)
if ($estadoAnterior !== 'confirmado' && $request->estado === 'confirmado') {
    Mail::to($pedido->cliente_email)->send(new PedidoConfirmado($pedido));
}
```

**Contenido del email:**
- Detalles del pedido (número, fecha, estado)
- Lista de productos con cantidades y precios
- Total del pedido
- Información de entrega
- Método de pago

**⚠️ IMPORTANTE**: 
- El email **NO se envía** al crear el pedido
- El email **SÍ se envía** cuando el admin cambia el estado de cualquier otro estado a "confirmado"
- Evita enviar el email múltiples veces si ya está confirmado

---

#### 3. **Email de Cambio de Estado** (`PedidoEstadoCambiado`)
- **⚠️ Estado**: Este email **NO se está usando actualmente**
- **Vista**: `resources/views/emails/pedido-estado-cambiado.blade.php`
- **Propósito**: Notificar cuando el estado del pedido cambia
- **Estados posibles**: pendiente → confirmado → preparando → listo → en_camino → entregado

**⚠️ Este email está creado pero no se dispara en ningún controlador actualmente.**
**💡 Opcional**: Podrías usarlo para notificar otros cambios de estado (en_camino, entregado, etc.)

---

## ⚙️ Configuración de Email Actual

### Proveedor SMTP
Actualmente configurado con **TurboSMTP** (servicio comercial):

```env
MAIL_MAILER=smtp
MAIL_HOST=pro.turbo-smtp.com
MAIL_PORT=587
MAIL_USERNAME=4f2c31202981296d3f69129cd1e3e234
MAIL_PASSWORD=KB2hA1q8vmgNja9xOYIP6cnfUrdks3VR
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="valenciamedinafreddydaniel1@gmail.com"
MAIL_FROM_NAME="Panificadora Nancy"
```

### ⚠️ Problemas Potenciales Identificados

1. **Remitente personal (Gmail)**
   - Se está usando `valenciamedinafreddydaniel1@gmail.com` como remitente
   - Los emails de Gmail personal tienen **alta probabilidad de ir a spam** cuando se envían masivamente
   - Falta de autenticación SPF/DKIM/DMARC del dominio

2. **Sin dominio propio**
   - No hay un dominio empresarial (ej: `noreply@panificadoranancy.com`)
   - Los filtros de spam penalizan correos de dominios gratuitos

3. **Email de cambio de estado no implementado**
   - Los clientes no reciben notificaciones cuando su pedido avanza

---

## 🛡️ Mejoras para Evitar que los Emails Vayan a SPAM

### 1. **Usar un Dominio Propio Verificado**

#### Opción A: Dominio con Hostinger (Recomendado)
```env
# Configuración para producción
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=587
MAIL_USERNAME=noreply@panificadoranancy.com
MAIL_PASSWORD=tu_contraseña_segura
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@panificadoranancy.com"
MAIL_FROM_NAME="Panificadora Nancy"
```

**Pasos:**
1. Comprar dominio `panificadoranancy.com`
2. Configurar email profesional en Hostinger
3. Crear cuenta `noreply@panificadoranancy.com`
4. Actualizar `.env` con las credenciales

#### Opción B: Proveedores Especializados (Mejor para volumen alto)

**SendGrid** (Recomendado para producción):
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=SG.tu_api_key_aqui
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@panificadoranancy.com"
MAIL_FROM_NAME="Panificadora Nancy"
```

**Beneficios:**
- ✅ 100 emails gratis/día (plan gratuito)
- ✅ Autenticación automática
- ✅ Dashboards de entrega y estadísticas
- ✅ Reputación de IP excelente

**Mailgun**:
```env
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=mg.panificadoranancy.com
MAILGUN_SECRET=key-tu_api_key
MAIL_FROM_ADDRESS="noreply@panificadoranancy.com"
MAIL_FROM_NAME="Panificadora Nancy"
```

---

### 2. **Configurar Registros DNS (CRÍTICO)**

Agregar estos registros en tu proveedor de dominio:

#### SPF (Sender Policy Framework)
```
Tipo: TXT
Nombre: @
Valor: v=spf1 include:_spf.hostinger.com ~all
```
Para SendGrid:
```
Valor: v=spf1 include:sendgrid.net ~all
```

#### DKIM (DomainKeys Identified Mail)
Tu proveedor de email te dará las claves. Ejemplo:
```
Tipo: TXT
Nombre: default._domainkey
Valor: k=rsa; p=MIGfMA0GCSq... (clave pública)
```

#### DMARC (Domain-based Message Authentication)
```
Tipo: TXT
Nombre: _dmarc
Valor: v=DMARC1; p=quarantine; rua=mailto:postmaster@panificadoranancy.com
```

---

### 3. **Implementar Email de Cambio de Estado**

Actualmente **NO se está enviando**. Aquí está la implementación:

**Archivo**: `backend/app/Http/Controllers/Admin/AdminPedidoController.php`

Agregar en el método `updateEstado()`:

```php
use App\Mail\PedidoEstadoCambiado;
use Illuminate\Support\Facades\Mail;

public function updateEstado(Request $request, $id)
{
    $validated = $request->validate([
        'estado' => 'required|in:pendiente,confirmado,en_preparacion,listo,en_camino,entregado,cancelado',
        'notas' => 'nullable|string'
    ]);

    try {
        $pedido = Pedido::findOrFail($id);
        $estadoAnterior = $pedido->estado;
        
        $pedido->estado = $validated['estado'];
        if (isset($validated['notas'])) {
            $pedido->notas_admin = $validated['notas'];
        }
        $pedido->save();

        // 📧 ENVIAR EMAIL DE CAMBIO DE ESTADO
        // Solo si el estado cambió y no es "pendiente"
        if ($estadoAnterior !== $validated['estado'] && $validated['estado'] !== 'pendiente') {
            try {
                Mail::to($pedido->cliente_email)->send(new PedidoEstadoCambiado($pedido));
                Log::info("Email de cambio de estado enviado a {$pedido->cliente_email}");
            } catch (\Exception $e) {
                // No fallar si el email no se envía
                Log::error("Error enviando email de cambio de estado: " . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado correctamente',
            'pedido' => $pedido
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al actualizar estado: ' . $e->getMessage()
        ], 500);
    }
}
```

---

### 4. **Mejoras en las Plantillas de Email**

#### Headers Importantes (Agregar en los Mailables)

**En cada clase Mailable**, agregar el método `headers()`:

```php
// En BienvenidaUsuario.php, PedidoConfirmado.php, etc.

use Illuminate\Mail\Mailables\Headers;

public function headers(): Headers
{
    return new Headers(
        messageId: null,
        references: [],
        text: [
            // Evita que se marque como marketing
            'X-Priority' => '3',
            'X-Mailer' => 'Panificadora Nancy System',
            // Lista de desuscripción (opcional pero recomendado)
            'List-Unsubscribe' => '<mailto:unsubscribe@panificadoranancy.com>',
        ],
    );
}
```

#### Mejoras en el HTML

**Principios anti-spam:**
1. ✅ Ratio texto/imágenes balanceado (más texto que imágenes)
2. ✅ Evitar MAYÚSCULAS EXCESIVAS
3. ✅ No usar palabras spam: "GRATIS", "PROMOCIÓN", "GANADOR"
4. ✅ Enlaces válidos y con HTTPS
5. ✅ Incluir dirección física de la empresa
6. ✅ Botón de desuscripción visible

**Ejemplo de footer mejorado:**

```html
<!-- Agregar al final de cada plantilla -->
<div style="background: #f5f5f5; padding: 20px; text-align: center; font-size: 12px; color: #666; margin-top: 30px;">
    <p><strong>Panificadora Nancy</strong></p>
    <p>📍 Av. Principal #123, Quillacollo, Cochabamba, Bolivia</p>
    <p>📞 Teléfono: +591 4-1234567 | 📧 Email: info@panificadoranancy.com</p>
    <p>🕐 Horario: Lunes a Sábado de 6:00 AM a 8:00 PM</p>
    <hr style="border: none; border-top: 1px solid #ddd; margin: 20px 0;">
    <p style="font-size: 11px;">
        Has recibido este correo porque creaste una cuenta o realizaste un pedido en Panificadora Nancy.
        <br>Si deseas dejar de recibir notificaciones, 
        <a href="{{ config('app.url') }}/unsubscribe?email={{ urlencode($pedido->cliente_email ?? $usuario->email) }}" 
           style="color: #8B4513;">haz clic aquí</a>.
    </p>
</div>
```

---

### 5. **Configurar Cola de Emails (Queue)**

Para no bloquear las peticiones HTTP mientras se envían emails:

**1. Modificar los Mailables para usar cola:**

```php
// En BienvenidaUsuario.php, PedidoConfirmado.php, etc.

class BienvenidaUsuario extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    
    // El resto del código igual...
}
```

**2. Configurar el driver de cola:**

```env
# .env
QUEUE_CONNECTION=database  # o redis para mejor performance
```

**3. Crear tabla de trabajos:**

```bash
php artisan queue:table
php artisan migrate
```

**4. Procesar la cola:**

```bash
# En desarrollo
php artisan queue:work

# En producción (con Supervisor)
php artisan queue:work --daemon
```

**Beneficios:**
- ⚡ Respuestas más rápidas al usuario
- 🔄 Reintentos automáticos si falla
- 📊 Mejor control de errores

---

### 6. **Testing de Emails (Mailtrap)**

Para desarrollo, usar **Mailtrap** (evita enviar emails reales):

```env
# .env para desarrollo
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=tu_username_mailtrap
MAIL_PASSWORD=tu_password_mailtrap
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@panificadoranancy.com"
MAIL_FROM_NAME="Panificadora Nancy"
```

**Beneficios:**
- ✅ Ver cómo se ven los emails sin enviarlos
- ✅ Verificar HTML/CSS
- ✅ Comprobar headers anti-spam
- ✅ Testing de enlaces

---

### 7. **Monitoreo y Estadísticas**

#### Logging de Emails Enviados

**Crear tabla para tracking:**

```php
// Migration: create_email_logs_table.php
Schema::create('email_logs', function (Blueprint $table) {
    $table->id();
    $table->string('email_type'); // bienvenida, pedido_confirmado, etc.
    $table->string('recipient');
    $table->string('subject');
    $table->enum('status', ['sent', 'failed', 'bounced']);
    $table->text('error_message')->nullable();
    $table->timestamps();
});
```

**Implementar en un Listener:**

```php
// EventServiceProvider.php
protected $listen = [
    MessageSent::class => [
        LogSentMessage::class,
    ],
];
```

---

## 📊 Checklist de Implementación

### Prioridad Alta (Hacer YA)
- [ ] Comprar dominio empresarial (`panificadoranancy.com`)
- [ ] Configurar email profesional (`noreply@panificadoranancy.com`)
- [ ] Agregar registros SPF, DKIM, DMARC en DNS
- [ ] Actualizar `.env` con el nuevo remitente
- [ ] Implementar envío de email en cambio de estado
- [ ] Agregar headers anti-spam en Mailables
- [ ] Agregar footer legal completo en plantillas

### Prioridad Media (Próximos días)
- [ ] Configurar SendGrid o Mailgun para producción
- [ ] Implementar sistema de colas (Queue)
- [ ] Agregar opción de desuscripción
- [ ] Testing con Mailtrap
- [ ] Monitoreo de emails enviados

### Prioridad Baja (Mejoras futuras)
- [ ] Dashboard de estadísticas de emails
- [ ] A/B testing de plantillas
- [ ] Personalización avanzada por tipo de cliente
- [ ] Email de recordatorio de carrito abandonado
- [ ] Email de cumpleaños con descuento

---

## 🔧 Scripts de Ayuda

### Test SMTP Connection

```php
// backend/test-smtp.php (crear este archivo)
<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    Mail::raw('Test email from Panificadora Nancy', function ($message) {
        $message->to('tu_email@gmail.com')
                ->subject('Test SMTP Connection');
    });
    
    echo "✅ Email enviado correctamente!\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
```

**Ejecutar:**
```bash
cd backend
docker compose exec laravel.test php test-smtp.php
```

---

## 📚 Recursos Adicionales

- [SendGrid Documentation](https://docs.sendgrid.com/)
- [Mailgun Documentation](https://documentation.mailgun.com/)
- [SPF/DKIM/DMARC Guide](https://dmarcian.com/what-is-dmarc/)
- [Email on Acid - HTML Testing](https://www.emailonacid.com/)
- [Can I Email - CSS Support](https://www.caniemail.com/)

---

## ⚠️ Notas Importantes

1. **Nunca usar Gmail personal para producción**
   - Alto riesgo de bloqueo por parte de Google
   - Límite de 500 emails/día
   - Mala reputación para emails transaccionales

2. **Testing antes de producción**
   - Siempre probar con Mailtrap primero
   - Verificar en múltiples clientes (Gmail, Outlook, Yahoo)
   - Revisar versión móvil

3. **Compliance legal**
   - Incluir dirección física real
   - Opción de desuscripción obligatoria
   - Política de privacidad accesible

4. **Rate limiting**
   - No enviar demasiados emails muy rápido
   - Usar colas para distribuir carga
   - Respetar límites del proveedor SMTP

---

**Última actualización**: 15 de octubre de 2025
**Autor**: Sistema de Documentación Panificadora Nancy
