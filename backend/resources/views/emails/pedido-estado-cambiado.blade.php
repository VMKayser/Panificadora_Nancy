<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualización de Pedido</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .content {
            background: #fff;
            padding: 30px;
            border: 1px solid #ddd;
        }
        .estado-badge {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 18px;
            margin: 20px 0;
        }
        .estado-pendiente { background: #ffc107; color: #000; }
        .estado-confirmado { background: #17a2b8; color: #fff; }
        .estado-preparando { background: #fd7e14; color: #fff; }
        .estado-listo { background: #28a745; color: #fff; }
        .estado-en-camino { background: #007bff; color: #fff; }
        .estado-entregado { background: #28a745; color: #fff; }
        .estado-cancelado { background: #dc3545; color: #fff; }
        .pedido-info {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .footer {
            background: #f4f4f4;
            padding: 20px;
            text-align: center;
            border-radius: 0 0 10px 10px;
            color: #666;
            font-size: 14px;
        }
        .timeline {
            position: relative;
            padding-left: 30px;
            margin: 20px 0;
        }
        .timeline-item {
            position: relative;
            padding-bottom: 20px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -22px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #ddd;
        }
        .timeline-item.active::before {
            background: #8B4513;
        }
        .timeline-item::after {
            content: '';
            position: absolute;
            left: -17px;
            top: 17px;
            width: 2px;
            height: 100%;
            background: #ddd;
        }
        .timeline-item:last-child::after {
            display: none;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .info-label {
            font-weight: bold;
            color: #8B4513;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🥐 Panificadora Nancy</h1>
        <p style="margin: 10px 0 0 0; font-size: 18px;">Actualización de tu Pedido</p>
    </div>

    <div class="content">
        <h2>¡Hola {{ $pedido->cliente->nombre ?? $pedido->nombre_cliente }}!</h2>
        
        <p>Tu pedido ha sido actualizado:</p>

        <div style="text-align: center;">
            <span class="estado-badge estado-{{ $pedido->estado }}">
                @switch($pedido->estado)
                    @case('pendiente')
                        ⏳ Pendiente
                        @break
                    @case('confirmado')
                        ✅ Confirmado
                        @break
                    @case('preparando')
                        👨‍🍳 Preparando
                        @break
                    @case('listo')
                        ✨ Listo para recoger/entregar
                        @break
                    @case('en_camino')
                        🚗 En camino
                        @break
                    @case('entregado')
                        📦 Entregado
                        @break
                    @case('cancelado')
                        ❌ Cancelado
                        @break
                    @default
                        {{ ucfirst($pedido->estado) }}
                @endswitch
            </span>
        </div>

        <div class="pedido-info">
            <h3 style="margin-top: 0; color: #8B4513;">📋 Información del Pedido</h3>
            
            <div class="info-row">
                <span class="info-label">Número de Pedido:</span>
                <span>#{{ str_pad($pedido->id, 6, '0', STR_PAD_LEFT) }}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Fecha:</span>
                <span>{{ $pedido->created_at->format('d/m/Y H:i') }}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Total:</span>
                <span style="font-weight: bold; font-size: 18px;">Bs. {{ number_format($pedido->total, 2) }}</span>
            </div>
        </div>

        @switch($pedido->estado)
            @case('confirmado')
                <div style="background: #d1ecf1; padding: 15px; border-radius: 5px; border-left: 4px solid #17a2b8;">
                    <strong>✅ Tu pedido ha sido confirmado</strong>
                    <p style="margin: 10px 0 0 0;">Estamos comenzando a preparar tu pedido. Te notificaremos cuando esté listo.</p>
                </div>
                @break
            
            @case('preparando')
                <div style="background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #fd7e14;">
                    <strong>👨‍🍳 Estamos preparando tu pedido</strong>
                    <p style="margin: 10px 0 0 0;">Nuestros panaderos están trabajando en tu pedido con todo el cariño.</p>
                </div>
                @break
            
            @case('listo')
                <div style="background: #d4edda; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745;">
                    <strong>✨ ¡Tu pedido está listo!</strong>
                    @if($pedido->tipo_entrega === 'recojo_tienda')
                        <p style="margin: 10px 0 0 0;">Puedes pasar a recogerlo en nuestra tienda en el horario que prefieras.</p>
                    @else
                        <p style="margin: 10px 0 0 0;">Pronto saldrá para entrega. Te notificaremos cuando esté en camino.</p>
                    @endif
                </div>
                @break
            
            @case('en_camino')
                <div style="background: #cce5ff; padding: 15px; border-radius: 5px; border-left: 4px solid #007bff;">
                    <strong>🚗 Tu pedido está en camino</strong>
                    <p style="margin: 10px 0 0 0;">El repartidor está en camino a tu dirección. ¡Llegará pronto!</p>
                    @if($pedido->direccion_entrega)
                        <p style="margin: 5px 0 0 0;"><strong>Dirección:</strong> {{ $pedido->direccion_entrega }}</p>
                    @endif
                </div>
                @break
            
            @case('entregado')
                <div style="background: #d4edda; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745;">
                    <strong>📦 Pedido entregado</strong>
                    <p style="margin: 10px 0 0 0;">¡Gracias por tu compra! Esperamos que disfrutes nuestros productos. 😊</p>
                    <p style="margin: 10px 0 0 0;">Te esperamos pronto de nuevo en Panificadora Nancy.</p>
                </div>
                @break
            
            @case('cancelado')
                <div style="background: #f8d7da; padding: 15px; border-radius: 5px; border-left: 4px solid #dc3545;">
                    <strong>❌ Pedido cancelado</strong>
                    <p style="margin: 10px 0 0 0;">Lamentamos informarte que tu pedido ha sido cancelado.</p>
                    @if($pedido->notas_cancelacion)
                        <p style="margin: 10px 0 0 0;"><strong>Motivo:</strong> {{ $pedido->notas_cancelacion }}</p>
                    @endif
                    <p style="margin: 10px 0 0 0;">Si tienes alguna duda, contáctanos.</p>
                </div>
                @break
        @endswitch

        <h3 style="color: #8B4513; margin-top: 30px;">📍 Seguimiento</h3>
        <div class="timeline">
            <div class="timeline-item {{ in_array($pedido->estado, ['pendiente', 'confirmado', 'preparando', 'listo', 'en_camino', 'entregado']) ? 'active' : '' }}">
                <strong>Pedido recibido</strong>
                <br>
                <small style="color: #666;">{{ $pedido->created_at->format('d/m/Y H:i') }}</small>
            </div>
            <div class="timeline-item {{ in_array($pedido->estado, ['confirmado', 'preparando', 'listo', 'en_camino', 'entregado']) ? 'active' : '' }}">
                <strong>Confirmado</strong>
            </div>
            <div class="timeline-item {{ in_array($pedido->estado, ['preparando', 'listo', 'en_camino', 'entregado']) ? 'active' : '' }}">
                <strong>En preparación</strong>
            </div>
            <div class="timeline-item {{ in_array($pedido->estado, ['listo', 'en_camino', 'entregado']) ? 'active' : '' }}">
                <strong>Listo</strong>
            </div>
            @if($pedido->tipo_entrega !== 'recojo_tienda')
            <div class="timeline-item {{ in_array($pedido->estado, ['en_camino', 'entregado']) ? 'active' : '' }}">
                <strong>En camino</strong>
            </div>
            @endif
            <div class="timeline-item {{ $pedido->estado === 'entregado' ? 'active' : '' }}">
                <strong>Entregado</strong>
            </div>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <p style="color: #666;">
                ¿Tienes alguna duda sobre tu pedido? Contáctanos.
            </p>
        </div>
    </div>

    <div class="footer">
        <p><strong>Panificadora Nancy</strong></p>
        <p>Teléfono: [Tu teléfono] | Email: [Tu email]</p>
        <p style="font-size: 12px; margin-top: 15px;">
            Este es un correo automático, por favor no responder directamente.
        </p>
    </div>
</body>
</html>
