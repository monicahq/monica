<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The mobile apps sign in through one public client that every instance holds under the same id,
 * so nobody running Monica has anything to create. The id and the return address are written out
 * rather than read from the model, which may change after this migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('oauth_clients')->insertOrIgnore([
            'id' => '0198f6a4-7c2e-7d3b-9a61-5e0b4c8d2f17',
            'owner_type' => null,
            'owner_id' => null,
            'name' => 'Monica for mobile',
            'secret' => null,
            'provider' => null,
            'redirect_uris' => json_encode(['com.monicahq.android:/oauth/callback']),
            'grant_types' => json_encode(['authorization_code', 'refresh_token']),
            'revoked' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
