<?php

namespace App\Services;

use App\Models\SiteSetting;
use App\Models\User;
use App\Support\EnvironmentFileWriter;
use App\Support\InstallLock;
use App\Support\StorefrontTheme;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use RuntimeException;
use Throwable;

class ApplicationInstaller
{
    public function __construct(
        private readonly InstallationRequirements $requirements,
        private readonly EnvironmentFileWriter $environmentFileWriter,
    ) {
    }

    /**
     * @return array{
     *     app_url:string,
     *     site_name:string,
     *     admin_name:string,
     *     admin_password:string,
     *     admin_password_confirmation:string,
     *     db_host:string,
     *     db_port:int,
     *     db_database:string,
     *     db_username:string,
     *     db_password:string,
     *     use_redis:bool,
     *     redis_host:string,
     *     redis_port:int,
     *     redis_password:string
     * }
     */
    public function defaults(): array
    {
        $siteName = trim((string) config('app.name'));

        if ($siteName === '' || in_array(strtolower($siteName), ['laravel', 'flowx'], true)) {
            $siteName = 'FlowXfaka';
        }

        $appUrl = trim((string) config('app.url'));

        if ($appUrl === '') {
            $appUrl = 'http://localhost';
        }

        $useRedis = config('session.driver') === 'redis'
            || config('cache.default') === 'redis'
            || config('queue.default') === 'redis';

        return [
            'app_url' => $appUrl,
            'site_name' => $siteName,
            'admin_name' => 'admin',
            'admin_password' => '',
            'admin_password_confirmation' => '',
            'db_host' => (string) config('database.connections.mysql.host', '127.0.0.1'),
            'db_port' => (int) config('database.connections.mysql.port', 3306),
            'db_database' => (string) config('database.connections.mysql.database', 'flowx'),
            'db_username' => (string) config('database.connections.mysql.username', 'root'),
            'db_password' => '',
            'use_redis' => $useRedis,
            'redis_host' => (string) config('database.redis.default.host', '127.0.0.1'),
            'redis_port' => (int) config('database.redis.default.port', 6379),
            'redis_password' => '',
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{
     *     app_url:string,
     *     site_name:string,
     *     admin_name:string,
     *     admin_password:string,
     *     admin_password_confirmation:string,
     *     db_host:string,
     *     db_port:int,
     *     db_database:string,
     *     db_username:string,
     *     db_password:string,
     *     use_redis:bool,
     *     redis_host:string,
     *     redis_port:int,
     *     redis_password:string
     * }
     */
    public function prepareInput(array $input): array
    {
        $defaults = $this->defaults();

        return [
            'app_url' => trim((string) ($input['app_url'] ?? $defaults['app_url'])),
            'site_name' => trim((string) ($input['site_name'] ?? $defaults['site_name'])),
            'admin_name' => trim((string) ($input['admin_name'] ?? $defaults['admin_name'])),
            'admin_password' => (string) ($input['admin_password'] ?? ''),
            'admin_password_confirmation' => (string) ($input['admin_password_confirmation'] ?? ''),
            'db_host' => trim((string) ($input['db_host'] ?? $defaults['db_host'])),
            'db_port' => (int) ($input['db_port'] ?? $defaults['db_port']),
            'db_database' => trim((string) ($input['db_database'] ?? $defaults['db_database'])),
            'db_username' => trim((string) ($input['db_username'] ?? $defaults['db_username'])),
            'db_password' => (string) ($input['db_password'] ?? ''),
            'use_redis' => filter_var($input['use_redis'] ?? $defaults['use_redis'], FILTER_VALIDATE_BOOLEAN),
            'redis_host' => trim((string) ($input['redis_host'] ?? $defaults['redis_host'])),
            'redis_port' => (int) ($input['redis_port'] ?? $defaults['redis_port']),
            'redis_password' => (string) ($input['redis_password'] ?? ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function makeValidator(array $input, bool $requirePasswordConfirmation = false): \Illuminate\Contracts\Validation\Validator
    {
        $prepared = $this->prepareInput($input);

        $validator = Validator::make($prepared, [
            'app_url' => ['required', 'string', 'max:255', 'url'],
            'site_name' => ['required', 'string', 'max:80'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_password' => ['required', 'string', Password::min(8)],
            'admin_password_confirmation' => [$requirePasswordConfirmation ? 'required' : 'nullable', 'string'],
            'db_host' => ['required', 'string', 'max:255'],
            'db_port' => ['required', 'integer', 'between:1,65535'],
            'db_database' => ['required', 'string', 'max:255'],
            'db_username' => ['required', 'string', 'max:255'],
            'db_password' => ['nullable', 'string', 'max:255'],
            'use_redis' => ['boolean'],
            'redis_host' => [Rule::requiredIf($prepared['use_redis']), 'nullable', 'string', 'max:255'],
            'redis_port' => [Rule::requiredIf($prepared['use_redis']), 'nullable', 'integer', 'between:1,65535'],
            'redis_password' => ['nullable', 'string', 'max:255'],
        ]);

        if ($requirePasswordConfirmation) {
            $validator->after(function ($validator) use ($prepared): void {
                if ($prepared['admin_password'] !== $prepared['admin_password_confirmation']) {
                    $validator->errors()->add('admin_password_confirmation', 'Password confirmation does not match.');
                }
            });
        }

        return $validator;
    }

    /**
     * @param  array{
     *     app_url:string,
     *     site_name:string,
     *     admin_name:string,
     *     admin_password:string,
     *     admin_password_confirmation?:string,
     *     db_host:string,
     *     db_port:int,
     *     db_database:string,
     *     db_username:string,
     *     db_password:string,
     *     use_redis:bool,
     *     redis_host:string,
     *     redis_port:int,
     *     redis_password:string
     * }  $input
     * @return array{
     *     admin_name:string,
     *     admin_url:string,
     *     queue_connection:string,
     *     queue_command:string,
     *     site_name:string,
     *     lock_path:string,
     *     warnings:list<string>,
     *     uses_redis:bool
     * }
     */
    public function install(array $input, bool $force = false): array
    {
        if (InstallLock::exists() && ! $force) {
            throw new RuntimeException('Install lock already exists at '.InstallLock::path().'. Use --force to re-run initialization.');
        }

        $this->requirements->assertReady();
        $this->ensureRuntimeDirectories();

        $environment = $this->buildEnvironment($input);

        $this->environmentFileWriter->write($environment);
        $this->clearBootstrapCaches();
        $this->applyRuntimeConfiguration($input, $environment);
        $this->verifyDatabaseConnection();
        $this->runMigrations();

        $admin = DB::transaction(fn (): User => $this->createOrUpdateAdmin($input));
        $this->initializeSiteSettings($input);

        $warnings = $this->runtimeWarnings($input);
        $queueConnection = (string) config('queue.default');

        InstallLock::write([
            'installed_at' => now()->toIso8601String(),
            'app_url' => $input['app_url'],
            'site_name' => $input['site_name'],
            'admin_name' => $admin->name,
            'queue_connection' => $queueConnection,
            'uses_redis' => $input['use_redis'],
        ]);

        return [
            'admin_name' => $admin->name,
            'admin_url' => rtrim($input['app_url'], '/').'/admin/login',
            'queue_connection' => $queueConnection,
            'queue_command' => $this->queueCommand($queueConnection),
            'site_name' => $input['site_name'],
            'lock_path' => InstallLock::path(),
            'warnings' => $warnings,
            'uses_redis' => $input['use_redis'],
        ];
    }

    /**
     * @param  array{
     *     app_url:string,
     *     site_name:string,
     *     admin_name:string,
     *     admin_password:string,
     *     db_host:string,
     *     db_port:int,
     *     db_database:string,
     *     db_username:string,
     *     db_password:string,
     *     use_redis:bool,
     *     redis_host:string,
     *     redis_port:int,
     *     redis_password:string
     * }  $input
     * @return array<string, bool|int|string|null>
     */
    private function buildEnvironment(array $input): array
    {
        $appKey = $this->environmentFileWriter->currentValue('APP_KEY');

        if (! is_string($appKey) || trim($appKey) === '') {
            $appKey = 'base64:'.base64_encode(random_bytes(32));
        }

        return [
            'APP_NAME' => $input['site_name'],
            'APP_ENV' => 'production',
            'APP_DEBUG' => false,
            'APP_URL' => $input['app_url'],
            'APP_KEY' => $appKey,
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $input['db_host'],
            'DB_PORT' => $input['db_port'],
            'DB_DATABASE' => $input['db_database'],
            'DB_USERNAME' => $input['db_username'],
            'DB_PASSWORD' => $input['db_password'],
            'SESSION_DRIVER' => $input['use_redis'] ? 'redis' : 'file',
            'CACHE_STORE' => $input['use_redis'] ? 'redis' : 'database',
            'QUEUE_CONNECTION' => $input['use_redis'] ? 'redis' : 'database',
            'REDIS_CLIENT' => 'predis',
            'REDIS_HOST' => $input['redis_host'] !== '' ? $input['redis_host'] : '127.0.0.1',
            'REDIS_PORT' => $input['redis_port'] > 0 ? $input['redis_port'] : 6379,
            'REDIS_PASSWORD' => $input['redis_password'] !== '' ? $input['redis_password'] : null,
        ];
    }

    /**
     * @param  array{
     *     app_url:string,
     *     site_name:string,
     *     admin_name:string,
     *     admin_password:string,
     *     db_host:string,
     *     db_port:int,
     *     db_database:string,
     *     db_username:string,
     *     db_password:string,
     *     use_redis:bool,
     *     redis_host:string,
     *     redis_port:int,
     *     redis_password:string
     * }  $input
     * @param  array<string, bool|int|string|null>  $environment
     */
    private function applyRuntimeConfiguration(array $input, array $environment): void
    {
        foreach ($environment as $key => $value) {
            $raw = match (true) {
                $value === null => 'null',
                is_bool($value) => $value ? 'true' : 'false',
                default => (string) $value,
            };

            $_ENV[$key] = $raw;
            $_SERVER[$key] = $raw;
        }

        config([
            'app.name' => $input['site_name'],
            'app.url' => $input['app_url'],
            'app.key' => $environment['APP_KEY'],
            'database.default' => 'mysql',
            'database.connections.mysql.host' => $input['db_host'],
            'database.connections.mysql.port' => $input['db_port'],
            'database.connections.mysql.database' => $input['db_database'],
            'database.connections.mysql.username' => $input['db_username'],
            'database.connections.mysql.password' => $input['db_password'],
            'session.driver' => $environment['SESSION_DRIVER'],
            'cache.default' => $environment['CACHE_STORE'],
            'queue.default' => $environment['QUEUE_CONNECTION'],
            'database.redis.client' => 'predis',
            'database.redis.default.host' => $environment['REDIS_HOST'],
            'database.redis.default.password' => $input['redis_password'] !== '' ? $input['redis_password'] : null,
            'database.redis.default.port' => (int) $environment['REDIS_PORT'],
            'database.redis.cache.host' => $environment['REDIS_HOST'],
            'database.redis.cache.password' => $input['redis_password'] !== '' ? $input['redis_password'] : null,
            'database.redis.cache.port' => (int) $environment['REDIS_PORT'],
        ]);

        DB::purge();
    }

    private function ensureRuntimeDirectories(): void
    {
        $bootstrapCachePath = base_path('bootstrap/cache');

        foreach ([
            storage_path('app'),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
            $bootstrapCachePath,
            public_path('uploads'),
        ] as $directory) {
            File::ensureDirectoryExists($directory);
        }
    }

    private function clearBootstrapCaches(): void
    {
        foreach (glob(base_path('bootstrap/cache/*.php')) ?: [] as $file) {
            File::delete($file);
        }
    }

    private function verifyDatabaseConnection(): void
    {
        try {
            DB::connection()->getPdo();
        } catch (Throwable $exception) {
            throw new RuntimeException('Database connection failed: '.$exception->getMessage(), 0, $exception);
        }
    }

    private function runMigrations(): void
    {
        $status = Artisan::call('migrate', ['--force' => true]);

        if ($status === 0) {
            return;
        }

        $output = trim(Artisan::output());

        throw new RuntimeException(
            $output !== '' ? 'Migration failed: '.$output : 'Migration failed.',
        );
    }

    /**
     * @param  array{
     *     admin_name:string,
     *     admin_password:string
     * }  $input
     */
    private function createOrUpdateAdmin(array $input): User
    {
        $admin = User::query()
            ->where('is_admin', true)
            ->orderBy('id')
            ->first();

        if (! $admin) {
            $admin = User::query()->where('name', $input['admin_name'])->first();
        }

        $admin ??= new User();
        $admin->name = $input['admin_name'];
        $admin->password = $input['admin_password'];
        $admin->is_admin = true;
        $admin->save();

        return $admin->refresh();
    }

    /**
     * @param  array{site_name:string}  $input
     */
    private function initializeSiteSettings(array $input): void
    {
        $settings = SiteSetting::current();
        $settings->site_name = $input['site_name'];

        if (Schema::hasColumn('site_settings', 'frontend_theme') && trim((string) $settings->frontend_theme) === '') {
            $settings->frontend_theme = StorefrontTheme::defaultTheme();
        }

        $settings->save();
    }

    /**
     * @param  array{app_url:string,use_redis:bool}  $input
     * @return list<string>
     */
    private function runtimeWarnings(array $input): array
    {
        $warnings = [];

        if ($input['use_redis'] && ! $this->redisReachable()) {
            $warnings[] = 'Redis was enabled, but the configured Redis server did not respond to PING.';
        }

        if (str_contains($input['app_url'], 'localhost') || str_contains($input['app_url'], '127.0.0.1')) {
            $warnings[] = 'APP_URL still points to localhost. Update it before enabling payment callbacks.';
        }

        return $warnings;
    }

    private function redisReachable(): bool
    {
        try {
            $result = Redis::connection()->command('PING');

            return strtoupper((string) $result) === 'PONG';
        } catch (Throwable) {
            return false;
        }
    }

    private function queueCommand(string $queueConnection): string
    {
        return sprintf(
            'php artisan queue:work %s --queue=payments,fulfillment,default --sleep=1 --tries=3 --timeout=90',
            $queueConnection,
        );
    }
}
