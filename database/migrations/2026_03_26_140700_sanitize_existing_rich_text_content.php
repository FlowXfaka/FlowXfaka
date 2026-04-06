<?php

use App\Support\RichTextSanitizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('site_settings')->orderBy('id')->get()->each(function ($row): void {
            $sanitized = RichTextSanitizer::sanitize((string) ($row->notice_html ?? ''));

            if ($sanitized !== (string) ($row->notice_html ?? '')) {
                DB::table('site_settings')->where('id', $row->id)->update(['notice_html' => $sanitized]);
            }
        });

        DB::table('products')->orderBy('id')->get()->each(function ($row): void {
            $original = (string) ($row->description_html ?? '');
            $sanitized = RichTextSanitizer::sanitize($original);
            $plain = trim(strip_tags(preg_replace('/<img\b[^>]*>/i', ' [image] ', str_replace('&nbsp;', ' ', $sanitized))));

            if ($plain === '') {
                $sanitized = null;
            }

            if ($sanitized !== $row->description_html) {
                DB::table('products')->where('id', $row->id)->update(['description_html' => $sanitized]);
            }
        });
    }

    public function down(): void
    {
    }
};
