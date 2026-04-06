<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_channels', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 40);
            $table->string('provider', 20)->default('alipay');
            $table->string('merchant_id', 80)->nullable();
            $table->longText('merchant_public_key')->nullable();
            $table->longText('merchant_private_key')->nullable();
            $table->string('payment_mark', 40)->nullable();
            $table->string('payment_scene', 20)->default('general');
            $table->string('payment_method', 20)->default('page');
            $table->string('route_path', 120)->default('/payments/alipay/start');
            $table->boolean('is_enabled')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        if (DB::table('payment_channels')->count() === 0) {
            DB::table('payment_channels')->insert([
                'name' => "\u{652f}\u{4ed8}\u{5b9d}",
                'provider' => 'alipay',
                'merchant_id' => (string) env('ALIPAY_APP_ID', ''),
                'merchant_public_key' => (string) env('ALIPAY_PUBLIC_KEY', ''),
                'merchant_private_key' => (string) env('ALIPAY_PRIVATE_KEY', ''),
                'payment_mark' => 'alipay',
                'payment_scene' => 'general',
                'payment_method' => 'page',
                'route_path' => '/payments/alipay/start',
                'is_enabled' => filter_var(env('ALIPAY_ENABLED', false), FILTER_VALIDATE_BOOL),
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_channels');
    }
};
