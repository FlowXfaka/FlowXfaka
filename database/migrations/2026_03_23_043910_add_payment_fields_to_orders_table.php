<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('payment_channel')->nullable()->after('status')->index();
            $table->string('payment_trade_no')->nullable()->unique()->after('payment_channel');
            $table->string('payment_buyer_logon_id')->nullable()->after('payment_trade_no');
            $table->timestamp('payment_notified_at')->nullable()->after('payment_buyer_logon_id');
            $table->json('payment_payload')->nullable()->after('payment_notified_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique(['payment_trade_no']);
            $table->dropIndex(['payment_channel']);
            $table->dropColumn([
                'payment_channel',
                'payment_trade_no',
                'payment_buyer_logon_id',
                'payment_notified_at',
                'payment_payload',
            ]);
        });
    }
};
