<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The iOS app signs in through the same public client as the Android app, with its own return
 * address. The addresses are written out rather than read from the model, which may change after
 * this migration. Running it again writes the same list.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('oauth_clients')
            ->where('id', '0198f6a4-7c2e-7d3b-9a61-5e0b4c8d2f17')
            ->update([
                'redirect_uris' => json_encode(['com.monicahq.android:/oauth/callback', 'com.monicahq.ios:/oauth/callback']),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('oauth_clients')
            ->where('id', '0198f6a4-7c2e-7d3b-9a61-5e0b4c8d2f17')
            ->update([
                'redirect_uris' => json_encode(['com.monicahq.android:/oauth/callback']),
                'updated_at' => now(),
            ]);
    }
};
