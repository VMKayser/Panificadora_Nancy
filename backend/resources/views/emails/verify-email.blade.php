<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirma tu correo | {{ $brandName }}</title>
    <style>
        :root {
            color-scheme: light;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f7f3f0;
            color: #3a2a1b;
        }
        .wrapper {
            width: 100%;
            padding: 24px;
            box-sizing: border-box;
        }
        .card {
            max-width: 640px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(58, 42, 27, 0.08);
            overflow: hidden;
        }
        .hero {
            background: radial-gradient(circle at top right, #ffdbc4, #f3b493);
            padding: 40px 32px 60px;
            text-align: center;
            color: #fff7ef;
        }
        .hero h1 {
            margin: 0;
            font-size: 28px;
            letter-spacing: 0.4px;
        }
        .hero p {
            margin: 12px 0 0;
            font-size: 16px;
        }
        .content {
            padding: 32px;
        }
        h2 {
            font-size: 22px;
            margin-bottom: 12px;
            color: #B04E2A;
        }
        p {
            line-height: 1.6;
            margin: 0 0 16px;
        }
        .cta {
            text-align: center;
            margin: 32px 0;
        }
        .cta a {
            background: linear-gradient(120deg, #E36C36, #C8501E);
            color: #fff;
            padding: 16px 32px;
            border-radius: 999px;
            font-weight: bold;
            text-decoration: none;
            letter-spacing: 0.6px;
            display: inline-block;
            min-width: 220px;
        }
        .highlights {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin: 24px 0;
        }
        .highlight {
            background: #fff8f3;
            border-radius: 14px;
            padding: 16px;
            border: 1px solid #f7e1d6;
        }
        .highlight h3 {
            margin: 0 0 8px;
            font-size: 15px;
            color: #C8501E;
        }
        .divider {
            border: 0;
            height: 1px;
            background: #f0e3da;
            margin: 32px 0;
        }
        .small {
            font-size: 13px;
            color: #7c6758;
        }
        .code {
            background: #f8f4f0;
            padding: 12px;
            border-radius: 12px;
            font-size: 12px;
            word-break: break-all;
        }
        .footer {
            background: #2f1d14;
            color: #f1e6df;
            padding: 24px 32px;
            text-align: center;
        }
        .footer a {
            color: #ffd4a8;
            text-decoration: none;
        }
        @media (max-width: 600px) {
            .wrapper {
                padding: 12px;
            }
            .content,
            .footer {
                padding: 24px;
            }
            .cta a {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="hero">
                <h1>¡Bienvenido a {{ $brandName }}!</h1>
                <p>Solo falta confirmar tu correo para activar tu cuenta.</p>
            </div>
            <div class="content">
                <h2>Hola {{ $userName }} 👋</h2>
                <p>
                    Nos alegra que quieras formar parte de nuestra comunidad panadera. Con tu cuenta podrás hacer
                    pedidos en línea, dar seguimiento a tus compras y descubrir las novedades que preparamos cada día.
                </p>
                <p>
                    Para mantener segura tu información necesitamos confirmar que este correo es tuyo. Solo haz clic en el botón
                    a continuación. El enlace estará disponible por {{ $expirationMinutes }} minutos.
                </p>
                <div class="cta">
                    <a href="{{ $verificationUrl }}" target="_blank" rel="noopener noreferrer">Confirmar mi correo</a>
                </div>
                <div class="highlights">
                    <div class="highlight">
                        <h3>Pedidos sin complicaciones</h3>
                        <p>Guarda tus favoritos y repite tus pedidos en segundos.</p>
                    </div>
                    <div class="highlight">
                        <h3>Novedades y promociones</h3>
                        <p>Recibe antes que nadie nuestras ediciones limitadas.</p>
                    </div>
                    <div class="highlight">
                        <h3>Atención cercana</h3>
                        <p>Te acompañamos en cada paso para que disfrutes tu pedido.</p>
                    </div>
                </div>
                <p class="small">
                    Si el botón no funciona, copia y pega este enlace en tu navegador:
                </p>
                <p class="code">{{ $verificationUrl }}</p>
                <hr class="divider"/>
                <p class="small">
                    Después de confirmar tu correo te llevaremos automáticamente a
                    <a href="{{ $frontendUrl }}" target="_blank" rel="noopener noreferrer">panificadoranancy.com</a>
                    para que continúes tu experiencia.
                </p>
                <p class="small">
                    ¿No fuiste tú? Ignora este mensaje y tu cuenta quedará desactivada.
                </p>
            </div>
            <div class="footer">
                <p>
                    {{ $brandName }} · <a href="{{ $frontendUrl }}" target="_blank" rel="noopener noreferrer">{{ $frontendUrl }}</a>
                </p>
                <p>
                    ¿Necesitas ayuda? Escríbenos a <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>
                    @if(!empty($supportPhone)) o llámanos al <a href="tel:{{ $supportPhone }}">{{ $supportPhone }}</a> @endif
                </p>
                <p style="font-size: 12px; margin-top: 16px; color: #d7c1b4;">
                    © {{ date('Y') }} {{ $brandName }}. Todos los derechos reservados.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
