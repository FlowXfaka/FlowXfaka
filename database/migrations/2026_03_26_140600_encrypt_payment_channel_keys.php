<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('payment_channels')->orderBy('id')->get()->each(function ($channel): void {
            $updates = [];

            foreach (['merchant_public_key', 'merchant_private_key'] as $column) {
                $value = trim((string) ($channel->{$column} ?? ''));

                if ($value === '') {
                    continue;
                }

                try {
                    Crypt::decryptString($value);
                } catch (\Throwable) {
                    $updates[$column] = Crypt::encryptString($value);
                }
            }

            if ($updates !== []) {
                DB::table('payment_channels')->where('id', $channel->id)->update($updates);
            }
        });
    }

    public function down(): void
    {
        DB::table('payment_channels')->orderBy('id')->get()->each(function ($channel): void {
            $updates = [];

            foreach (['merchant_public_key', 'merchant_private_key'] as $column) {
                $value = trim((string) ($channel->{$column} ?? ''));

                if ($value === '') {
                    continue;
                }

                try {
                    $updates[$column] = Crypt::decryptString($value);
                } catch (\Throwable) {
                }
            }

            if ($updates !== []) {
                DB::table('payment_channels')->where('id', $channel->id)->update($updates);
            }
        });
    }
};
