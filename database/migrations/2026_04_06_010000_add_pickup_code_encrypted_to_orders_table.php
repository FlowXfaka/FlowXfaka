<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders') || Schema::hasColumn('orders', 'pickup_code_encrypted')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->text('pickup_code_encrypted')->nullable()->after('pickup_code_hash');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders') || ! Schema::hasColumn('orders', 'pickup_code_encrypted')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('pickup_code_encrypted');
        });
    }
};
