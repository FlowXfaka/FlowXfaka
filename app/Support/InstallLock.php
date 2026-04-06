<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

class InstallLock
{
    public static function path(): string
    {
        return storage_path('app/install.lock');
    }

    public static function exists(): bool
    {
        return File::exists(static::path());
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function write(array $payload): void
    {
        $path = static::path();
        File::ensureDirectoryExists(dirname($path));
        File::put(
            $path,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
        );
    }

    public static function delete(): void
    {
        if (static::exists()) {
            File::delete(static::path());
        }
    }
}
