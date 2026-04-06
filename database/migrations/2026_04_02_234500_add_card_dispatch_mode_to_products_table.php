<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('card_dispatch_mode', 20)
                ->default('new_first')
                ->after('sold_count');
        });

        DB::table('products')
            ->whereNull('card_dispatch_mode')
            ->update(['card_dispatch_mode' => 'new_first']);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('card_dispatch_mode');
        });
    }
};
