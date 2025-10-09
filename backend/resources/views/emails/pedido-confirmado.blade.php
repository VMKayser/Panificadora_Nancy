<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido Confirmado</title>
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
        .pedido-info {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .producto-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
        }
        .producto-item:last-child {
            border-bottom: none;
        }
        .total {
            background: #8B4513;
            color: white;
            padding: 15px;
            text-align: right;
            font-size: 20px;
            font-weight: bold;
            border-radius: 5px;
            margin-top: 20px;
        }
        .footer {
            background: #f4f4f4;
            padding: 20px;
            text-align: center;
            border-radius: 0 0 10px 10px;
            color: #666;
            font-size: 14px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: #8B4513;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
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
        <p style="margin: 10px 0 0 0; font-size: 18px;">¡Pedido Confirmado!</p>
    </div>

    <div class="content">
        <h2>¡Hola {{ $pedido->cliente->nombre ?? $pedido->nombre_cliente }}!</h2>
        
        <p>Gracias por tu pedido. Hemos recibido tu orden y estamos preparando todo para ti.</p>

        <div class="pedido-info">
            <h3 style="margin-top: 0; color: #8B4513;">📋 Detalles del Pedido</h3>
            
            <div class="info-row">
                <span class="info-label">Número de Pedido:</span>
                <span>#{{ str_pad($pedido->id, 6, '0', STR_PAD_LEFT) }}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Fecha:</span>
                <span>{{ $pedido->created_at->format('d/m/Y H:i') }}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Estado:</span>
                <span style="color: #28a745; font-weight: bold;">{{ ucfirst($pedido->estado) }}</span>
            </div>

            @if($pedido->metodo_pago)
            <div class="info-row">
                <span class="info-label">Método de Pago:</span>
                <span>{{ $pedido->metodoPago->nombre ?? 'N/A' }}</span>
            </div>
            @endif

            @if($pedido->tipo_entrega)
            <div class="info-row">
                <span class="info-label">Tipo de Entrega:</span>
                <span>{{ ucfirst(str_replace('_', ' ', $pedido->tipo_entrega)) }}</span>
            </div>
            @endif

            @if($pedido->direccion_entrega)
            <div class="info-row">
                <span class="info-label">Dirección de Entrega:</span>
                <span>{{ $pedido->direccion_entrega }}</span>
            </div>
            @endif
        </div>

        <h3 style="color: #8B4513;">🛍️ Productos</h3>
        
        @foreach($pedido->detalles as $detalle)
        <div class="producto-item">
            <div>
                <strong>{{ $detalle->producto->nombre ?? 'Producto' }}</strong>
                <br>
                <small style="color: #666;">Cantidad: {{ $detalle->cantidad }} × Bs. {{ number_format($detalle->precio_unitario, 2) }}</small>
                @if($detalle->extras)
                    <br>
                    <small style="color: #8B4513;">
                        Extras: 
                        @foreach($detalle->extras as $extra)
                            {{ $extra['nombre'] }} (+Bs. {{ number_format($extra['precio'], 2) }}){{ !$loop->last ? ', ' : '' }}
                        @endforeach
                    </small>
                @endif
                @if($detalle->personalizacion)
                    <br>
                    <small style="color: #666;">📝 {{ $detalle->personalizacion }}</small>
                @endif
            </div>
            <div style="text-align: right;">
                <strong>Bs. {{ number_format($detalle->subtotal, 2) }}</strong>
            </div>
        </div>
        @endforeach

        <div class="total">
            Total: Bs. {{ number_format($pedido->total, 2) }}
        </div>

        @if($pedido->notas)
        <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin-top: 20px;">
            <strong>📌 Notas:</strong>
            <p style="margin: 5px 0 0 0;">{{ $pedido->notas }}</p>
        </div>
        @endif

        <div style="text-align: center;">
            <p style="margin-top: 30px; color: #666;">
                Te notificaremos cuando tu pedido cambie de estado.
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
