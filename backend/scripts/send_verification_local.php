<?php
// send_verification_local.php
// Usage: php scripts/send_verification_local.php user@example.com
// This script creates a local sqlite database file (backend/database/testing.sqlite),
// runs migrations there (isolated), creates the user if missing, and sends the verification
// email using MAIL_MAILER=log so the message is written to storage/logs/laravel.log.

$email = $argv[1] ?? null;
if (!$email) {
    echo "Usage: php scripts/send_verification_local.php user@example.com\n";
    exit(2);
}

$base = dirname(__DIR__);
$testingDb = $base . '/database/testing.sqlite';

// Ensure sqlite file exists
if (!file_exists($testingDb)) {
    if (!is_dir(dirname($testingDb))) mkdir(dirname($testingDb), 0755, true);
    touch($testingDb);
    echo "Created testing sqlite DB: {$testingDb}\n";
}

// Set environment for this process to use sqlite and log mail
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=' . $testingDb);
putenv('MAIL_MAILER=log');
putenv('APP_ENV=local');
putenv('APP_DEBUG=true');

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Run migrations into the sqlite testing DB
echo "Running migrations into testing sqlite DB...\n";
try {
    // Run migrations programmatically
    $exitCode = $kernel->call('migrate', ['--force' => true]);
    echo "migrate exit code: $exitCode\n";
} catch (Throwable $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
}

use App\Models\User;

try {
    $user = User::where('email', $email)->first();
    if (!$user) {
        echo "User not found in testing DB, creating...\n";
        $user = User::create([
            'name' => 'Prueba Local',
            'email' => $email,
            'password' => bcrypt('Test1234'),
            'is_active' => true,
        ]);
        echo "User created with id {$user->id}\n";
    } else {
        echo "User found with id {$user->id}\n";
    }

    // Ensure user implements MustVerifyEmail - model already implements it in this project
    if (method_exists($user, 'hasVerifiedEmail') && $user->hasVerifiedEmail()) {
        echo "User already verified.\n";
    } else {
        echo "Dispatching verification notification (MAIL_LOG).\n";
        $user->sendEmailVerificationNotification();
        echo "Notification dispatched. Check storage/logs/laravel.log for the message content and verification URL.\n";

        // Show last lines of the log to help find the message
        $logFile = __DIR__ . '/../storage/logs/laravel.log';
        if (file_exists($logFile)) {
            $lines = array_slice(file($logFile), -50);
            echo "--- last lines of laravel.log ---\n";
            echo implode('', $lines);
            echo "--- end log tail ---\n";
        } else {
            echo "Log file not found at {$logFile}\n";
        }
    }
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

echo "Done.\n";
