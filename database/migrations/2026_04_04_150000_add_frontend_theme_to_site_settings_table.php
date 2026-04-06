<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_settings') || Schema::hasColumn('site_settings', 'frontend_theme')) {
            return;
        }

        Schema::table('site_settings', function (Blueprint $table): void {
            $table->string('frontend_theme', 40)
                ->default(config('storefront.default_theme', 'default'))
                ->after('frontend_text_mode');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_settings') || ! Schema::hasColumn('site_settings', 'frontend_theme')) {
            return;
        }

        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn('frontend_theme');
        });
    }
};
