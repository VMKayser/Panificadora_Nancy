<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido a Panificadora Nancy</title>
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
            padding: 40px 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .header h1 {
            margin: 0;
            font-size: 32px;
        }
        .content {
            background: #fff;
            padding: 30px;
            border: 1px solid #ddd;
        }
        .welcome-icon {
            text-align: center;
            font-size: 80px;
            margin: 20px 0;
        }
        .feature {
            background: #f9f9f9;
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
            border-left: 4px solid #8B4513;
        }
        .feature h3 {
            margin: 0 0 10px 0;
            color: #8B4513;
        }
        .button {
            display: inline-block;
            padding: 15px 35px;
            background: #8B4513;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .footer {
            background: #f4f4f4;
            padding: 20px;
            text-align: center;
            border-radius: 0 0 10px 10px;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🥐 Panificadora Nancy</h1>
        <p style="margin: 10px 0 0 0; font-size: 20px;">¡Bienvenido a nuestra familia!</p>
    </div>

    <div class="content">
        <div class="welcome-icon">🎉</div>
        
        <h2 style="text-align: center; color: #8B4513;">¡Hola {{ $usuario->nombre }}!</h2>
        
        <p style="text-align: center; font-size: 16px;">
            Nos alegra mucho tenerte con nosotros. Has dado el primer paso para disfrutar 
            de los mejores productos de panadería artesanal.
        </p>

        <div style="background: #fff3cd; padding: 20px; border-radius: 8px; margin: 25px 0; text-align: center;">
            <strong style="font-size: 18px;">✅ Tu cuenta ha sido creada exitosamente</strong>
            <p style="margin: 10px 0 0 0;">Email: {{ $usuario->email }}</p>
        </div>

        <h3 style="color: #8B4513; text-align: center;">¿Qué puedes hacer ahora?</h3>

        <div class="feature">
            <h3>🛍️ Hacer Pedidos Online</h3>
            <p style="margin: 0;">
                Explora nuestro catálogo y realiza pedidos desde la comodidad de tu hogar.
                Delivery o recojo en tienda, tú eliges.
            </p>
        </div>

        <div class="feature">
            <h3>🎂 Productos Personalizados</h3>
            <p style="margin: 0;">
                Pide t'anta wawas personalizadas, tortas especiales y productos únicos 
                para tus celebraciones.
            </p>
        </div>

        <div class="feature">
            <h3>📋 Seguimiento de Pedidos</h3>
            <p style="margin: 0;">
                Recibe notificaciones en tiempo real sobre el estado de tus pedidos, 
                desde la confirmación hasta la entrega.
            </p>
        </div>

        <div class="feature">
            <h3>⭐ Ofertas Exclusivas</h3>
            <p style="margin: 0;">
                Accede a promociones especiales y descuentos exclusivos para clientes registrados.
            </p>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ env('FRONTEND_URL', 'http://localhost:5174') }}" class="button">
                🥖 Explorar Productos
            </a>
        </div>

        <div style="background: #e7f3ff; padding: 20px; border-radius: 8px; margin: 25px 0;">
            <h3 style="margin: 0 0 10px 0; color: #0066cc;">💡 Consejos para empezar</h3>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>Completa tu perfil con tu dirección para entregas más rápidas</li>
                <li>Guarda tus productos favoritos para pedidos futuros</li>
                <li>Suscríbete a nuestras notificaciones para no perderte nada</li>
                <li>Para productos de temporada (como t'anta wawas), realiza tu pedido con anticipación</li>
            </ul>
        </div>

        <div style="border-top: 2px solid #8B4513; padding-top: 20px; margin-top: 30px;">
            <h3 style="color: #8B4513;">Sobre Panificadora Nancy</h3>
            <p>
                Somos una panadería familiar con más de [X] años de tradición, 
                especializada en productos artesanales de alta calidad. Cada producto 
                es elaborado con ingredientes frescos y mucho amor.
            </p>
            <p>
                Nos especializamos en productos tradicionales bolivianos como t'anta wawas, 
                masitas de Todos Santos, y pan de cada día, siempre manteniendo 
                las recetas originales y el sabor auténtico.
            </p>
        </div>

        <div style="text-align: center; margin-top: 30px; color: #666;">
            <p>
                <strong>¿Necesitas ayuda?</strong><br>
                Estamos aquí para ayudarte. Contáctanos en cualquier momento.
            </p>
        </div>
    </div>

    <div class="footer">
        <p><strong>Panificadora Nancy</strong></p>
        <p>Teléfono: [Tu teléfono] | Email: [Tu email]</p>
        <p>Dirección: [Tu dirección]</p>
        <p style="font-size: 12px; margin-top: 15px;">
            Recibiste este correo porque te registraste en Panificadora Nancy.
        </p>
    </div>
</body>
</html>
