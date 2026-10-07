<?php
// Simple healthcheck for monitoring. Place under public/healthcheck.php or configure route
// It performs quick checks: DB connection and Redis ping (if available)
require __DIR__.'/../../backend/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

http_response_code(503);
$ok = true;
$errors = [];

// DB check (MySQL)
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=panificadora;port=3306", 'user', 'pass');
    if (!$pdo) { $ok = false; $errors[] = 'DB connection failed'; }
} catch (Exception $e) { $ok = false; $errors[] = 'DB: '.$e->getMessage(); }

// Redis check
if (class_exists('Redis')) {
    try {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        $pong = $redis->ping();
        if ($pong !== '+PONG' && $pong !== true) { $ok = false; $errors[] = 'Redis ping failed'; }
    } catch (Exception $e) { $ok = false; $errors[] = 'Redis: '.$e->getMessage(); }
} else {
    // Redis extension not available on this PHP build; skip strict check but include a warning
    $errors[] = 'Redis extension not installed, skipped Redis healthcheck';
}

if ($ok) {
    http_response_code(200);
    echo json_encode(['status' => 'ok']);
} else {
    echo json_encode(['status' => 'error', 'details' => $errors]);
}
