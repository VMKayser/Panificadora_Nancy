<?php

// Test directo de SMTP con Turbo-SMTP
require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

echo "=== Test de conexión SMTP con Turbo-SMTP ===\n\n";

// Credenciales
$host = 'pro.turbo-smtp.com';
$port = 587;
$username = '4f2c31202981296d3f69129cd1e3e234';
$password = 'KB2hA1q8vmgNja9xOYIP6cnfUrdks3VR';

echo "Host: $host\n";
echo "Port: $port\n";
echo "Username: $username\n";
echo "Password: " . substr($password, 0, 5) . "...\n\n";

try {
    // Crear transporte SMTP
    $transport = new EsmtpTransport($host, $port);
    $transport->setUsername($username);
    $transport->setPassword($password);
    
    echo "✓ Transporte SMTP creado\n";
    
    // Crear mailer
    $mailer = new Mailer($transport);
    echo "✓ Mailer creado\n";
    
    // Crear email
    $email = (new Email())
        ->from('valenciamedinafreddydaniel1@gmail.com')
        ->to('valenciamedinafreddydaniel1@gmail.com')
        ->subject('Test directo Turbo-SMTP')
        ->text('Este es un correo de prueba enviado directamente con Symfony Mailer');
    
    echo "✓ Email creado\n";
    echo "\nEnviando correo...\n";
    
    // Enviar
    $mailer->send($email);
    
    echo "\n✅ ¡CORREO ENVIADO EXITOSAMENTE!\n";
    echo "Revisa tu bandeja: valenciamedinafreddydaniel1@gmail.com\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERROR:\n";
    echo "Tipo: " . get_class($e) . "\n";
    echo "Código: " . $e->getCode() . "\n";
    echo "Mensaje: " . $e->getMessage() . "\n\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}
