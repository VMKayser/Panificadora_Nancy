<?php
// send_verification.php
// Usage: php scripts/send_verification.php user@example.com

// Bootstrap Laravel application so we can use models and notifications
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Bootstrap the kernel to have the container ready
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Use the User model
use App\Models\User;

$email = $argv[1] ?? null;
if (!$email) {
    echo "Usage: php scripts/send_verification.php user@example.com\n";
    exit(2);
}

try {
    $user = User::where('email', $email)->first();
    if (!$user) {
        echo "User with email {$email} not found.\n";
        exit(1);
    }

    if (method_exists($user, 'hasVerifiedEmail') && $user->hasVerifiedEmail()) {
        echo "User {$email} is already verified.\n";
        exit(0);
    }

    // Send verification notification (will use mail config from .env)
    $user->sendEmailVerificationNotification();
    echo "Verification notification dispatched for {$email}.\n";
    exit(0);
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
