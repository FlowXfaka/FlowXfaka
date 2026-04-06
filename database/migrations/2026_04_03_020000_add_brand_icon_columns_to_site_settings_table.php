<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('brand_icon_mode', 20)->default('default')->after('site_name');
            $table->string('brand_icon_path')->nullable()->after('brand_icon_mode');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'brand_icon_mode',
                'brand_icon_path',
            ]);
        });
    }
};
