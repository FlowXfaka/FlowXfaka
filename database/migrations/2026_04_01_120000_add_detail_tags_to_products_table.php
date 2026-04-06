<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('detail_tag_style', 24)->nullable()->after('description_html');
            $table->json('detail_tags')->nullable()->after('detail_tag_style');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['detail_tag_style', 'detail_tags']);
        });
    }
};
