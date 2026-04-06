<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->string('background_palette_primary', 7)->nullable()->after('background_image_path');
            $table->string('background_palette_secondary', 7)->nullable()->after('background_palette_primary');
            $table->string('background_palette_accent', 7)->nullable()->after('background_palette_secondary');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'background_palette_primary',
                'background_palette_secondary',
                'background_palette_accent',
            ]);
        });
    }
};
