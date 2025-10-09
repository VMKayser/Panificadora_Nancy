<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Models\Pedido;
use App\Mail\BienvenidaUsuario;
use App\Mail\PedidoConfirmado;
use App\Mail\PedidoEstadoCambiado;

class EnviarCorreoPrueba extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'enviar:correo-prueba 
                            {tipo=bienvenida : Tipo de correo (bienvenida, pedido-confirmado, pedido-estado)}
                            {email? : Email de destino (opcional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía un correo de prueba para verificar la configuración';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tipo = $this->argument('tipo');
        $email = $this->argument('email');

        // Si no se proporciona email, solicitar al usuario
        if (!$email) {
            $email = $this->ask('¿A qué correo deseas enviar la prueba?');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('El email proporcionado no es válido');
            return 1;
        }

        $this->info("Preparando envío de correo tipo: {$tipo} a {$email}");
        $this->info('Configuración actual:');
        $this->line('  MAIL_MAILER: ' . config('mail.default'));
        $this->line('  MAIL_HOST: ' . config('mail.mailers.smtp.host'));
        $this->line('  MAIL_PORT: ' . config('mail.mailers.smtp.port'));
        $this->line('  MAIL_FROM: ' . config('mail.from.address'));
        $this->newLine();

        try {
            switch ($tipo) {
                case 'bienvenida':
                    $this->enviarBienvenida($email);
                    break;
                
                case 'pedido-confirmado':
                    $this->enviarPedidoConfirmado($email);
                    break;
                
                case 'pedido-estado':
                    $this->enviarPedidoEstado($email);
                    break;
                
                default:
                    $this->error("Tipo de correo no válido. Usa: bienvenida, pedido-confirmado, pedido-estado");
                    return 1;
            }

            $this->newLine();
            $this->info('✅ Correo enviado exitosamente!');
            
            if (config('mail.default') === 'log') {
                $this->warn('⚠️  MAIL_MAILER está configurado como "log"');
                $this->warn('   El correo se guardó en storage/logs/laravel.log');
                $this->warn('   Para enviar correos reales, configura SMTP en el archivo .env');
            } elseif (str_contains(config('mail.mailers.smtp.host'), 'mailtrap')) {
                $this->info('📧 Revisa tu bandeja de Mailtrap: https://mailtrap.io/');
            } else {
                $this->info("📧 Revisa el email en: {$email}");
            }

            $this->newLine();
            $this->info('Para ver el correo en los logs:');
            $this->line('  tail -f storage/logs/laravel.log');

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error al enviar el correo:');
            $this->error($e->getMessage());
            $this->newLine();
            $this->warn('Posibles soluciones:');
            $this->line('  1. Verifica las credenciales SMTP en el archivo .env');
            $this->line('  2. Ejecuta: php artisan config:clear');
            $this->line('  3. Verifica que el queue worker esté corriendo');
            $this->line('  4. Revisa los logs: tail -f storage/logs/laravel.log');
            return 1;
        }
    }

    protected function enviarBienvenida($email)
    {
        $this->info('Creando usuario de prueba...');
        
        // Crear un usuario temporal para la prueba
        $usuario = new User([
            'name' => 'Usuario de Prueba',
            'email' => $email,
        ]);
        $usuario->id = 999; // ID ficticio

        $this->info('Enviando correo de bienvenida...');
        Mail::to($email)->send(new BienvenidaUsuario($usuario));
    }

    protected function enviarPedidoConfirmado($email)
    {
        $this->info('Buscando o creando pedido de prueba...');
        
        // Intentar obtener un pedido real o crear uno ficticio
        $pedido = Pedido::with(['detalles.producto', 'metodoPago'])->first();
        
        if (!$pedido) {
            $this->warn('No hay pedidos en la base de datos. Creando pedido ficticio...');
            $pedido = $this->crearPedidoFicticio($email);
        } else {
            $this->info("Usando pedido #{$pedido->id}");
            // Sobrescribir el email del cliente
            $pedido->cliente_email = $email;
        }

        $this->info('Enviando correo de pedido confirmado...');
        Mail::to($email)->send(new PedidoConfirmado($pedido));
    }

    protected function enviarPedidoEstado($email)
    {
        $this->info('Buscando o creando pedido de prueba...');
        
        $pedido = Pedido::with(['detalles.producto', 'metodoPago'])->first();
        
        if (!$pedido) {
            $this->warn('No hay pedidos en la base de datos. Creando pedido ficticio...');
            $pedido = $this->crearPedidoFicticio($email);
        } else {
            $this->info("Usando pedido #{$pedido->id}");
            $pedido->cliente_email = $email;
        }

        // Cambiar el estado para la demostración
        $estadoAnterior = $pedido->estado;
        $pedido->estado = 'listo';
        
        $this->info("Simulando cambio de estado: {$estadoAnterior} → listo");
        $this->info('Enviando correo de cambio de estado...');
        
        Mail::to($email)->send(new PedidoEstadoCambiado($pedido));
    }

    protected function crearPedidoFicticio($email)
    {
        // Crear un pedido ficticio solo para la prueba (no se guarda en BD)
        $pedido = new Pedido([
            'numero_pedido' => 'PED-2025-0001',
            'cliente_nombre' => 'Cliente',
            'cliente_apellido' => 'de Prueba',
            'cliente_email' => $email,
            'cliente_telefono' => '70123456',
            'tipo_entrega' => 'delivery',
            'direccion_entrega' => 'Av. Ejemplo #123, Zona Test',
            'subtotal' => 150.00,
            'descuento' => 0,
            'total' => 150.00,
            'estado' => 'confirmado',
            'estado_pago' => 'pendiente',
        ]);
        
        $pedido->id = 1;
        $pedido->created_at = now();
        
        // Simular relación metodoPago
        $pedido->setRelation('metodoPago', (object)[
            'id' => 1,
            'nombre' => 'Efectivo'
        ]);

        // Simular detalles del pedido
        $pedido->setRelation('detalles', collect([
            (object)[
                'producto' => (object)[
                    'nombre' => 'Pan Francés',
                ],
                'cantidad' => 10,
                'precio_unitario' => 5.00,
                'subtotal' => 50.00,
                'extras' => null,
                'personalizacion' => null,
            ],
            (object)[
                'producto' => (object)[
                    'nombre' => 'Empanada de Queso',
                ],
                'cantidad' => 5,
                'precio_unitario' => 8.00,
                'subtotal' => 40.00,
                'extras' => [
                    ['nombre' => 'Extra queso', 'precio' => 2.00]
                ],
                'personalizacion' => null,
            ],
            (object)[
                'producto' => (object)[
                    'nombre' => "T'anta Wawa Personalizada",
                ],
                'cantidad' => 1,
                'precio_unitario' => 60.00,
                'subtotal' => 60.00,
                'extras' => null,
                'personalizacion' => 'Nombre: María - Figura: Niña',
            ],
        ]));

        return $pedido;
    }
}
