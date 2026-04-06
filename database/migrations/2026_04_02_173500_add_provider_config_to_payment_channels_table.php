<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_channels', function (Blueprint $table): void {
            $table->longText('provider_config')->nullable()->after('merchant_private_key');
        });

        DB::table('payment_channels')->orderBy('id')->get()->each(function ($channel): void {
            $provider = trim((string) ($channel->provider ?? ''));
            $config = [];

            if ($provider === 'alipay') {
                $appId = trim((string) ($channel->merchant_id ?? ''));
                $publicKey = trim((string) ($channel->merchant_public_key ?? ''));
                $privateKey = trim((string) ($channel->merchant_private_key ?? ''));

                if ($appId !== '') {
                    $config['app_id'] = $appId;
                }

                if ($publicKey !== '') {
                    $config['public_key'] = $publicKey;
                }

                if ($privateKey !== '') {
                    $config['private_key'] = $privateKey;
                }
            }

            if ($config === []) {
                return;
            }

            DB::table('payment_channels')->where('id', $channel->id)->update([
                'provider_config' => Crypt::encryptString(json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('payment_channels', function (Blueprint $table): void {
            $table->dropColumn('provider_config');
        });
    }
};
