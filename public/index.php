<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Fresh installs boot before .env is fully populated. Seed safe in-memory
// defaults so the installer can render and then write the real values.
$installLockPath = __DIR__.'/../storage/app/install.lock';
$envPath = __DIR__.'/../.env';

if (! file_exists($installLockPath)) {
    $envContents = is_file($envPath)
        ? (string) file_get_contents($envPath)
        : '';

    $readEnvValue = static function (string $key) use ($envContents): ?string {
        if (preg_match('/^'.preg_quote($key, '/').'\s*=\s*(.*)$/m', $envContents, $matches) !== 1) {
            return null;
        }

        return trim(trim($matches[1]), " \t\n\r\0\x0B\"'");
    };

    $seedRuntimeValue = static function (string $key, string $value) use ($readEnvValue): void {
        if ($readEnvValue($key) !== null && $readEnvValue($key) !== '') {
            return;
        }

        if (! isset($_ENV[$key]) || $_ENV[$key] === '') {
            $_ENV[$key] = $value;
        }

        if (! isset($_SERVER[$key]) || $_SERVER[$key] === '') {
            $_SERVER[$key] = $value;
        }
    };

    if (($readEnvValue('APP_KEY') ?? '') === '') {
        $temporaryAppKey = 'base64:'.base64_encode(hash('sha256', dirname(__DIR__), true));
        $seedRuntimeValue('APP_KEY', $temporaryAppKey);
    }

    $seedRuntimeValue('APP_ENV', 'production');
    $seedRuntimeValue('APP_NAME', 'FlowXfaka');
    $seedRuntimeValue('SESSION_DRIVER', 'file');
    $seedRuntimeValue('CACHE_STORE', 'file');
    $seedRuntimeValue('QUEUE_CONNECTION', 'sync');
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
