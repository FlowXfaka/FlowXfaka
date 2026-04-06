<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use RuntimeException;

class InstallationRequirements
{
    /**
     * @return array{
     *     ready: bool,
     *     runtime: list<array{label:string,detail:string,status:bool,required:bool}>,
     *     paths: list<array{label:string,detail:string,status:bool,required:bool}>
     * }
     */
    public function report(): array
    {
        $runtime = $this->runtimeChecks();
        $paths = $this->pathChecks();

        $ready = collect([...$runtime, ...$paths])
            ->every(fn (array $item): bool => ! $item['required'] || $item['status']);

        return [
            'ready' => $ready,
            'runtime' => $runtime,
            'paths' => $paths,
        ];
    }

    public function assertReady(): void
    {
        $report = $this->report();
        $failures = collect([...$report['runtime'], ...$report['paths']])
            ->filter(fn (array $item): bool => $item['required'] && ! $item['status'])
            ->map(fn (array $item): string => $item['label'].' ('.$item['detail'].')')
            ->values()
            ->all();

        if ($failures === []) {
            return;
        }

        throw new RuntimeException('Installer requirements failed: '.implode('; ', $failures));
    }

    /**
     * @return list<array{label:string,detail:string,status:bool,required:bool}>
     */
    private function runtimeChecks(): array
    {
        return [
            $this->makeCheck('PHP 8.2+', PHP_VERSION, version_compare(PHP_VERSION, '8.2.0', '>='), true),
            $this->extensionCheck('ctype'),
            $this->extensionCheck('fileinfo'),
            $this->extensionCheck('json'),
            $this->extensionCheck('mbstring'),
            $this->extensionCheck('openssl'),
            $this->extensionCheck('pdo'),
            $this->extensionCheck('pdo_mysql'),
            $this->extensionCheck('tokenizer'),
            $this->extensionCheck('xml'),
            $this->extensionCheck('curl', false),
            $this->extensionCheck('gd', false),
        ];
    }

    /**
     * @return list<array{label:string,detail:string,status:bool,required:bool}>
     */
    private function pathChecks(): array
    {
        $bootstrapCachePath = base_path('bootstrap/cache');
        $publicUploadsPath = public_path('uploads');

        return [
            $this->makeCheck('storage/ writable', storage_path(), $this->isWritablePath(storage_path()), true),
            $this->makeCheck('bootstrap/cache writable', $bootstrapCachePath, $this->isWritablePath($bootstrapCachePath), true),
            $this->makeCheck('public/uploads writable', $publicUploadsPath, $this->isWritablePath($publicUploadsPath), true),
            $this->makeCheck('.env writable', '.env or project root', $this->envWritable(), true),
            $this->makeCheck('.env.example readable', '.env.example', ! File::exists(base_path('.env.example')) || is_readable(base_path('.env.example')), false),
        ];
    }

    private function extensionCheck(string $extension, bool $required = true): array
    {
        return $this->makeCheck(
            'PHP extension: '.$extension,
            $required ? 'required' : 'recommended',
            extension_loaded($extension),
            $required,
        );
    }

    private function envWritable(): bool
    {
        $envPath = base_path('.env');

        if (File::exists($envPath)) {
            return is_writable($envPath);
        }

        return is_writable(base_path());
    }

    private function isWritablePath(string $path): bool
    {
        if (File::exists($path)) {
            return is_writable($path);
        }

        $parent = dirname($path);

        while (! File::exists($parent) && dirname($parent) !== $parent) {
            $parent = dirname($parent);
        }

        return File::exists($parent) && is_writable($parent);
    }

    /**
     * @return array{label:string,detail:string,status:bool,required:bool}
     */
    private function makeCheck(string $label, string $detail, bool $status, bool $required): array
    {
        return [
            'label' => $label,
            'detail' => $detail,
            'status' => $status,
            'required' => $required,
        ];
    }
}
