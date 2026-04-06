<?php

namespace App\Console\Commands;

use App\Services\ApplicationInstaller;
use Illuminate\Console\Command;
use Throwable;

class InstallApplication extends Command
{
    protected $signature = 'app:install
        {--app-url= : Public application URL, for example https://shop.example.com}
        {--site-name= : Site name shown in FlowX}
        {--admin-name= : Admin login name}
        {--admin-password= : Admin password}
        {--db-host= : Database host}
        {--db-port= : Database port}
        {--db-name= : Database name}
        {--db-user= : Database username}
        {--db-password= : Database password}
        {--use-redis : Use Redis for session, cache, and queue}
        {--redis-host= : Redis host}
        {--redis-port= : Redis port}
        {--redis-password= : Redis password}
        {--force : Allow re-running after install.lock exists}';

    protected $description = 'Install and initialize the FlowX application';

    public function __construct(private readonly ApplicationInstaller $installer)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $input = $this->collectInstallData();
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $validator = $this->installer->makeValidator($input);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        try {
            $result = $this->installer->install(
                $validator->validated(),
                (bool) $this->option('force'),
            );
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Application installation completed.');
        $this->line('Admin login: '.$result['admin_name']);
        $this->line('Admin URL: '.$result['admin_url']);
        $this->line('Install lock: '.$result['lock_path']);
        $this->line('Queue worker: '.$result['queue_command']);

        foreach ($result['warnings'] as $warning) {
            $this->warn($warning);
        }

        $this->newLine();
        $this->line('Next steps:');
        $this->line('1. Point your web root to `public/`.');
        $this->line('2. Start the queue worker shown above.');
        $this->line('3. Open `/admin/login` and finish payment channel setup.');

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function collectInstallData(): array
    {
        $defaults = $this->installer->defaults();
        $usesRedis = (bool) ($this->option('use-redis') ?: false);

        if (! $this->option('use-redis') && $this->input->isInteractive()) {
            $usesRedis = $this->confirm('Use Redis for session, cache, and queue?', (bool) $defaults['use_redis']);
        } elseif (! $this->option('use-redis')) {
            $usesRedis = (bool) $defaults['use_redis'];
        }

        $appUrl = trim((string) ($this->option('app-url') ?: $this->ask('App URL', (string) $defaults['app_url'])));
        $siteName = trim((string) ($this->option('site-name') ?: $this->ask('Site name', (string) $defaults['site_name'])));
        $adminName = trim((string) ($this->option('admin-name') ?: $this->ask('Admin login', (string) $defaults['admin_name'])));
        $dbHost = trim((string) ($this->option('db-host') ?: $this->ask('Database host', (string) $defaults['db_host'])));
        $dbPort = (int) ($this->option('db-port') ?: $this->ask('Database port', (string) $defaults['db_port']));
        $dbName = trim((string) ($this->option('db-name') ?: $this->ask('Database name', (string) $defaults['db_database'])));
        $dbUser = trim((string) ($this->option('db-user') ?: $this->ask('Database username', (string) $defaults['db_username'])));
        $dbPassword = (string) ($this->option('db-password') ?? $this->secret('Database password (leave empty if none)'));

        $passwordOption = $this->option('admin-password');
        $adminPassword = is_string($passwordOption) && trim($passwordOption) !== ''
            ? $passwordOption
            : (string) $this->secret('Admin password (min 8 characters)');

        if (! is_string($passwordOption) || trim($passwordOption) === '') {
            $confirmation = (string) $this->secret('Confirm admin password');

            if ($adminPassword !== $confirmation) {
                throw new \RuntimeException('Password confirmation does not match.');
            }
        }

        $input = [
            'app_url' => $appUrl,
            'site_name' => $siteName,
            'admin_name' => $adminName,
            'admin_password' => $adminPassword,
            'admin_password_confirmation' => $adminPassword,
            'db_host' => $dbHost,
            'db_port' => $dbPort,
            'db_database' => $dbName,
            'db_username' => $dbUser,
            'db_password' => $dbPassword,
            'use_redis' => $usesRedis,
            'redis_host' => '',
            'redis_port' => 6379,
            'redis_password' => '',
        ];

        if ($usesRedis) {
            $input['redis_host'] = trim((string) ($this->option('redis-host') ?: $this->ask('Redis host', (string) $defaults['redis_host'])));
            $input['redis_port'] = (int) ($this->option('redis-port') ?: $this->ask('Redis port', (string) $defaults['redis_port']));
            $input['redis_password'] = (string) ($this->option('redis-password') ?? $this->secret('Redis password (leave empty if none)'));
        }

        return $input;
    }
}
