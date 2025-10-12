<?php

// Envío de correos con diseño HTML usando el método que funciona
require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

$email_destino = 'valenciamedinafreddydaniel1@gmail.com';

// Credenciales que funcionan
$host = 'pro.turbo-smtp.com';
$port = 587;
$username = '4f2c31202981296d3f69129cd1e3e234';
$password = 'KB2hA1q8vmgNja9xOYIP6cnfUrdks3VR';

echo "=== Enviando 3 correos de Panificadora Nancy ===\n\n";

function enviarCorreo($host, $port, $username, $password, $email_destino, $asunto, $html, $tipo) {
    try {
        $transport = new EsmtpTransport($host, $port);
        $transport->setUsername($username);
        $transport->setPassword($password);
        
        $mailer = new Mailer($transport);
        
        $emailObj = (new Email())
            ->from('valenciamedinafreddydaniel1@gmail.com')
            ->to($email_destino)
            ->subject($asunto)
            ->html($html);
        
        $mailer->send($emailObj);
        
        echo "✅ Correo de $tipo enviado exitosamente!\n";
        
    } catch (\Exception $e) {
        echo "❌ Error enviando $tipo: " . $e->getMessage() . "\n";
    }
}

// 1. CORREO DE BIENVENIDA
$htmlBienvenida = "
<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Bienvenido a Panificadora Nancy</title>
</head>
<body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
    <div style='background: linear-gradient(135deg, #8B4513, #D2691E); color: white; text-align: center; padding: 30px; border-radius: 10px 10px 0 0;'>
        <h1 style='margin: 0; font-size: 28px;'>🥐 Panificadora Nancy</h1>
        <p style='margin: 10px 0 0 0; font-size: 16px;'>Los mejores sabores caseros</p>
    </div>
    
    <div style='background: #fff; padding: 30px; border: 1px solid #ddd; border-radius: 0 0 10px 10px;'>
        <h2 style='color: #8B4513; margin-top: 0;'>¡Bienvenido Usuario de Prueba!</h2>
        
        <p>Gracias por registrarte en <strong>Panificadora Nancy</strong>. Estamos emocionados de tenerte como parte de nuestra familia.</p>
        
        <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
            <h3 style='color: #8B4513; margin-top: 0;'>¿Qué puedes hacer ahora?</h3>
            <ul style='margin: 0; padding-left: 20px;'>
                <li>Explorar nuestros deliciosos productos</li>
                <li>Realizar pedidos online</li>
                <li>Recibir notificaciones sobre el estado de tus pedidos</li>
                <li>Disfrutar de promociones exclusivas</li>
            </ul>
        </div>
        
        <p style='text-align: center; margin: 30px 0;'>
            <a href='#' style='background: #8B4513; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>Ver Productos</a>
        </p>
        
        <hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>
        
        <p style='text-align: center; color: #666; font-size: 14px;'>
            <strong>Panificadora Nancy</strong><br>
            Los mejores sabores caseros<br>
            📧 valenciamedinafreddydaniel1@gmail.com
        </p>
    </div>
</body>
</html>";

echo "1. Enviando correo de bienvenida...\n";
enviarCorreo($host, $port, $username, $password, $email_destino, 
    '🥐 ¡Bienvenido a Panificadora Nancy!', $htmlBienvenida, 'BIENVENIDA');

// 2. CORREO DE PEDIDO CONFIRMADO
$htmlPedidoConfirmado = "
<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Pedido Confirmado</title>
</head>
<body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
    <div style='background: linear-gradient(135deg, #28a745, #20c997); color: white; text-align: center; padding: 30px; border-radius: 10px 10px 0 0;'>
        <h1 style='margin: 0; font-size: 28px;'>✅ Pedido Confirmado</h1>
        <p style='margin: 10px 0 0 0; font-size: 16px;'>Panificadora Nancy</p>
    </div>
    
    <div style='background: #fff; padding: 30px; border: 1px solid #ddd; border-radius: 0 0 10px 10px;'>
        <h2 style='color: #28a745; margin-top: 0;'>¡Gracias por tu pedido!</h2>
        
        <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
            <h3 style='color: #333; margin-top: 0;'>Detalles del Pedido</h3>
            <p><strong>Número:</strong> PED-2025-001</p>
            <p><strong>Fecha:</strong> " . date('d/m/Y H:i') . "</p>
            <p><strong>Estado:</strong> <span style='color: #28a745; font-weight: bold;'>Confirmado</span></p>
        </div>
        
        <div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107;'>
            <p style='margin: 0; color: #856404;'><strong>⏰ Tiempo estimado:</strong> 2-3 horas</p>
        </div>
        
        <p>Te notificaremos cuando tu pedido esté listo para entrega.</p>
        
        <hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>
        
        <p style='text-align: center; color: #666; font-size: 14px;'>
            <strong>Panificadora Nancy</strong><br>
            📧 valenciamedinafreddydaniel1@gmail.com
        </p>
    </div>
</body>
</html>";

echo "2. Enviando correo de pedido confirmado...\n";
enviarCorreo($host, $port, $username, $password, $email_destino, 
    '✅ Pedido Confirmado - Panificadora Nancy', $htmlPedidoConfirmado, 'PEDIDO CONFIRMADO');

// 3. CORREO DE ESTADO CAMBIADO
$htmlEstadoCambiado = "
<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Estado del Pedido</title>
</head>
<body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
    <div style='background: linear-gradient(135deg, #17a2b8, #6f42c1); color: white; text-align: center; padding: 30px; border-radius: 10px 10px 0 0;'>
        <h1 style='margin: 0; font-size: 28px;'>📦 Estado del Pedido</h1>
        <p style='margin: 10px 0 0 0; font-size: 16px;'>Panificadora Nancy</p>
    </div>
    
    <div style='background: #fff; padding: 30px; border: 1px solid #ddd; border-radius: 0 0 10px 10px;'>
        <h2 style='color: #17a2b8; margin-top: 0;'>Tu pedido está listo! 🎉</h2>
        
        <div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #28a745;'>
            <p style='margin: 0; color: #155724;'><strong>Estado actual:</strong> Listo para entrega</p>
        </div>
        
        <p>Tu pedido <strong>PED-2025-001</strong> ya está preparado y listo para ser entregado.</p>
        
        <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
            <h3 style='color: #333; margin-top: 0;'>Próximos pasos:</h3>
            <ul style='margin: 0; padding-left: 20px;'>
                <li>Recibirás una llamada para coordinar la entrega</li>
                <li>Asegúrate de tener el dinero exacto</li>
                <li>Ten disponible tu número de pedido</li>
            </ul>
        </div>
        
        <hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>
        
        <p style='text-align: center; color: #666; font-size: 14px;'>
            <strong>Panificadora Nancy</strong><br>
            📧 valenciamedinafreddydaniel1@gmail.com
        </p>
    </div>
</body>
</html>";

echo "3. Enviando correo de estado cambiado...\n";
enviarCorreo($host, $port, $username, $password, $email_destino, 
    '📦 Estado de tu Pedido - Panificadora Nancy', $htmlEstadoCambiado, 'ESTADO CAMBIADO');

echo "\n=== RESUMEN ===\n";
echo "✅ Se enviaron 3 correos a: $email_destino\n";
echo "1. 🥐 Correo de Bienvenida\n";
echo "2. ✅ Correo de Pedido Confirmado\n";
echo "3. 📦 Correo de Estado Cambiado\n\n";
echo "Revisa tu bandeja de entrada (y también spam por si acaso)\n";