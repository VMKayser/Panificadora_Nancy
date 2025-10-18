# 📧 Verificación de Email con Hostinger

## ❓ ¿Qué es la verificación de email?

La **verificación de email** es un proceso donde el usuario debe confirmar su dirección de correo electrónico haciendo clic en un enlace que se envía a su bandeja de entrada después del registro.

**Propósito:**
- ✅ Confirmar que el email existe y es válido
- ✅ Evitar registros con emails falsos
- ✅ Asegurar que el usuario tiene acceso al email proporcionado
- ✅ Reducir spam y cuentas bot

---

## 🏢 Hostinger vs Verificación de Email

### ⚠️ **IMPORTANTE: Son servicios DIFERENTES**

#### 📨 Hostinger = SMTP (Envío de Emails)
Hostinger te permite:
- ✅ **Enviar emails transaccionales** (confirmación de pedidos, notificaciones)
- ✅ Crear direcciones de correo profesionales (`noreply@panificadoranancy.com`)
- ✅ Configurar SMTP para que Laravel envíe emails
- ✅ Autenticación SPF/DKIM para evitar spam

**Hostinger NO incluye:**
- ❌ Sistema de verificación de email automático
- ❌ Plantillas de verificación listas
- ❌ Base de datos para tokens de verificación

---

#### 🔐 Verificación de Email = Feature de Laravel
Es una **funcionalidad que debes programar tú** en Laravel:
- ✅ Generar token único de verificación
- ✅ Guardar token en base de datos
- ✅ Enviar email con enlace de verificación
- ✅ Validar el token cuando el usuario hace clic
- ✅ Marcar la cuenta como verificada

**Usa Hostinger para:**
- ✅ **Enviar** el email de verificación
- ✅ Asegurar que llegue a la bandeja (no spam)

---

## 🛠️ Implementación Completa

### Paso 1: Migración para Email Verification

```php
// database/migrations/xxxx_add_email_verification_to_users_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('email_verified_at')->nullable()->after('email');
            $table->string('verification_token', 64)->nullable()->after('email_verified_at');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['email_verified_at', 'verification_token']);
        });
    }
};
```

**Ejecutar:**
```bash
cd backend
docker compose exec laravel.test php artisan make:migration add_email_verification_to_users_table
# Copiar el código de arriba
docker compose exec laravel.test php artisan migrate
```

---

### Paso 2: Crear Mailable de Verificación

```php
// backend/app/Mail/VerificarEmail.php
<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerificarEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public string $token)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '✉️ Verifica tu Email - Panificadora Nancy',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verificar-email',
            with: [
                'usuario' => $this->user,
                'token' => $this->token,
                'url' => config('app.frontend_url') . '/verificar-email?token=' . $this->token,
            ],
        );
    }
}
```

---

### Paso 3: Plantilla del Email

```blade
{{-- backend/resources/views/emails/verificar-email.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifica tu Email</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f5f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%); padding: 40px 20px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px;">
                                🥐 Panificadora Nancy
                            </h1>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="color: #8B4513; margin-top: 0;">¡Hola, {{ $usuario->name }}!</h2>
                            
                            <p style="color: #333; line-height: 1.6; font-size: 16px;">
                                Gracias por registrarte en <strong>Panificadora Nancy</strong>. 
                                Para completar tu registro y activar tu cuenta, necesitamos verificar tu dirección de correo electrónico.
                            </p>

                            <p style="color: #333; line-height: 1.6; font-size: 16px;">
                                Por favor, haz clic en el botón de abajo:
                            </p>

                            <!-- CTA Button -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 30px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $url }}" 
                                           style="background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%); 
                                                  color: #ffffff; 
                                                  text-decoration: none; 
                                                  padding: 15px 40px; 
                                                  border-radius: 5px; 
                                                  font-weight: bold; 
                                                  font-size: 16px;
                                                  display: inline-block;">
                                            ✅ Verificar mi Email
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="color: #666; line-height: 1.6; font-size: 14px; border-left: 4px solid #8B4513; padding-left: 15px; margin: 20px 0;">
                                <strong>⏰ Este enlace expira en 24 horas.</strong><br>
                                Si no solicitaste este registro, simplemente ignora este correo.
                            </p>

                            <p style="color: #999; line-height: 1.6; font-size: 13px; margin-top: 30px;">
                                Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
                                <a href="{{ $url }}" style="color: #8B4513; word-break: break-all;">{{ $url }}</a>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9f9f9; padding: 20px; text-align: center; border-top: 1px solid #eee;">
                            <p style="color: #666; margin: 5px 0; font-size: 12px;">
                                <strong>Panificadora Nancy</strong>
                            </p>
                            <p style="color: #666; margin: 5px 0; font-size: 12px;">
                                📍 Av. Principal #123, Quillacollo, Cochabamba
                            </p>
                            <p style="color: #666; margin: 5px 0; font-size: 12px;">
                                📞 +591 4-1234567 | 📧 info@panificadoranancy.com
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
```

---

### Paso 4: Actualizar AuthController

```php
// backend/app/Http/Controllers/Api/AuthController.php

use App\Mail\VerificarEmail;
use Illuminate\Support\Str;

public function register(Request $request)
{
    // ... validación existente ...

    DB::beginTransaction();
    try {
        // Generar token de verificación
        $verificationToken = Str::random(64);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'verification_token' => $verificationToken,
            'email_verified_at' => null, // No verificado inicialmente
        ]);

        // Asignar rol...
        $user->assignRole($validated['role'] ?? 'cliente');

        // Crear cliente si es rol cliente...
        if ($user->hasRole('cliente')) {
            Cliente::create([...]);
        }

        // Enviar email de verificación
        try {
            Mail::to($user->email)->send(new VerificarEmail($user, $verificationToken));
        } catch (\Exception $e) {
            Log::error('Error enviando email de verificación: ' . $e->getMessage());
        }

        DB::commit();

        return response()->json([
            'message' => 'Usuario registrado. Por favor verifica tu email para activar tu cuenta.',
            'user' => $user->load('roles'),
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}
```

---

### Paso 5: Ruta de Verificación

```php
// backend/routes/api.php

Route::get('/verificar-email', [AuthController::class, 'verificarEmail']);
```

---

### Paso 6: Método de Verificación en AuthController

```php
// backend/app/Http/Controllers/Api/AuthController.php

public function verificarEmail(Request $request)
{
    $request->validate([
        'token' => 'required|string|size:64',
    ]);

    $user = User::where('verification_token', $request->token)->first();

    if (!$user) {
        return response()->json([
            'message' => 'Token de verificación inválido o expirado.'
        ], 400);
    }

    // Verificar si el token tiene más de 24 horas
    if ($user->created_at->diffInHours(now()) > 24) {
        return response()->json([
            'message' => 'El enlace de verificación ha expirado. Solicita uno nuevo.'
        ], 400);
    }

    // Marcar como verificado
    $user->update([
        'email_verified_at' => now(),
        'verification_token' => null, // Limpiar el token
    ]);

    return response()->json([
        'message' => '✅ Email verificado exitosamente. Ya puedes iniciar sesión.',
        'user' => $user
    ], 200);
}
```

---

### Paso 7: Proteger Login (Opcional)

Si quieres que **SOLO usuarios verificados** puedan iniciar sesión:

```php
// backend/app/Http/Controllers/Api/AuthController.php

public function login(Request $request)
{
    // ... validación existente ...

    if (!Auth::attempt($credentials)) {
        return response()->json(['message' => 'Credenciales inválidas'], 401);
    }

    $user = Auth::user();

    // Verificar si el email está verificado
    if (is_null($user->email_verified_at)) {
        Auth::logout();
        return response()->json([
            'message' => 'Por favor verifica tu email antes de iniciar sesión. Revisa tu bandeja de entrada.',
            'email_not_verified' => true
        ], 403);
    }

    $token = $user->createToken('auth-token')->plainTextToken;

    return response()->json([
        'token' => $token,
        'user' => $user->load('roles'),
    ]);
}
```

---

### Paso 8: Frontend - Página de Verificación

```jsx
// frontend/src/pages/VerificarEmail.jsx
import React, { useEffect, useState } from 'react';
import { useSearchParams, useNavigate } from 'react-router-dom';
import { Container, Card, Spinner, Alert } from 'react-bootstrap';
import { CheckCircle, XCircle } from 'lucide-react';
import api from '../services/api';

function VerificarEmail() {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const [estado, setEstado] = useState('verificando'); // verificando, exito, error
  const [mensaje, setMensaje] = useState('');

  useEffect(() => {
    const token = searchParams.get('token');
    
    if (!token) {
      setEstado('error');
      setMensaje('Token de verificación no encontrado.');
      return;
    }

    verificarEmail(token);
  }, [searchParams]);

  const verificarEmail = async (token) => {
    try {
      const response = await api.get('/verificar-email', {
        params: { token }
      });
      
      setEstado('exito');
      setMensaje(response.data.message);
      
      // Redirigir al login después de 3 segundos
      setTimeout(() => {
        navigate('/login');
      }, 3000);
      
    } catch (error) {
      setEstado('error');
      setMensaje(error.response?.data?.message || 'Error al verificar email.');
    }
  };

  return (
    <Container className="py-5">
      <Card className="mx-auto" style={{ maxWidth: '500px' }}>
        <Card.Body className="text-center p-5">
          {estado === 'verificando' && (
            <>
              <Spinner animation="border" variant="primary" className="mb-3" />
              <h4>Verificando tu email...</h4>
              <p className="text-muted">Por favor espera un momento</p>
            </>
          )}

          {estado === 'exito' && (
            <>
              <CheckCircle size={64} className="text-success mb-3" />
              <h4 className="text-success">¡Email Verificado!</h4>
              <Alert variant="success" className="mt-3">
                {mensaje}
              </Alert>
              <p className="text-muted mt-3">
                Redirigiendo al inicio de sesión...
              </p>
            </>
          )}

          {estado === 'error' && (
            <>
              <XCircle size={64} className="text-danger mb-3" />
              <h4 className="text-danger">Error de Verificación</h4>
              <Alert variant="danger" className="mt-3">
                {mensaje}
              </Alert>
              <button 
                className="btn btn-primary mt-3"
                onClick={() => navigate('/login')}
              >
                Ir al Login
              </button>
            </>
          )}
        </Card.Body>
      </Card>
    </Container>
  );
}

export default VerificarEmail;
```

---

### Paso 9: Agregar Ruta en App.jsx

```jsx
// frontend/src/App.jsx
import VerificarEmail from './pages/VerificarEmail';

// En las rutas:
<Route path="/verificar-email" element={<VerificarEmail />} />
```

---

## 📋 Resumen

### ✅ Lo que SÍ hace Hostinger:
1. **Enviar el email** de verificación al usuario
2. Proporcionar **credenciales SMTP** para que Laravel envíe emails
3. **Autenticación** (SPF/DKIM) para evitar spam
4. Email profesional (`noreply@panificadoranancy.com`)

### ❌ Lo que NO hace Hostinger:
1. Generar tokens de verificación (lo hace Laravel)
2. Validar el token cuando el usuario hace clic (lo hace Laravel)
3. Actualizar la base de datos (lo hace Laravel)
4. Crear la interfaz de verificación (lo haces tú en React)

---

## 🎯 Diferencia Clave

```
┌─────────────────────────────────────────────────────┐
│                  FLUJO COMPLETO                     │
├─────────────────────────────────────────────────────┤
│                                                     │
│  1. Usuario se registra                            │
│     ↓                                               │
│  2. LARAVEL genera token único                     │
│     ↓                                               │
│  3. LARAVEL crea email con enlace                  │
│     ↓                                               │
│  4. HOSTINGER envía el email                       │ ← Aquí actúa Hostinger
│     ↓                                               │
│  5. Usuario recibe email en su bandeja             │
│     ↓                                               │
│  6. Usuario hace clic en el enlace                 │
│     ↓                                               │
│  7. LARAVEL valida el token                        │
│     ↓                                               │
│  8. LARAVEL marca cuenta como verificada           │
│     ↓                                               │
│  9. Usuario puede iniciar sesión                   │
│                                                     │
└─────────────────────────────────────────────────────┘
```

---

## 💡 Recomendación

**Para Panificadora Nancy:**

❓ **¿Necesitas verificación de email?**

- ✅ **SI** tu prioridad es seguridad y prevenir cuentas falsas
- ❌ **NO** si quieres facilitar el proceso de registro (menos fricción)

**Alternativa intermedia:**
- Permitir login sin verificación
- Pero **limitar ciertas acciones** hasta verificar:
  - ❌ No puede hacer más de 2 pedidos sin verificar
  - ❌ No puede acceder a descuentos especiales
  - ✅ Puede navegar y ver productos

---

## 🚀 Configuración Hostinger

```env
# .env (Producción)
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=587
MAIL_USERNAME=noreply@panificadoranancy.com
MAIL_PASSWORD=tu_contraseña_segura
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@panificadoranancy.com"
MAIL_FROM_NAME="Panificadora Nancy"

# URL del frontend (para construir el enlace de verificación)
FRONTEND_URL=https://panificadoranancy.com
```

---

**Fecha**: 15 de octubre de 2025  
**Estado**: Documentación completa de verificación de email
