<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/*
 * Every phone connection recorded so far carries the API scope. Each assistant connected before
 * connections were recorded becomes one earlier connection per person, standing for every live
 * token it holds. Running it twice changes nothing more.
 */
return new class extends Migration
{
    private const string MOBILE_CLIENT_ID = '0198f6a4-7c2e-7d3b-9a61-5e0b4c8d2f17';

    public function up(): void
    {
        DB::table('oauth_connections')->whereNull('scopes')->update(['scopes' => json_encode(['api'])]);

        $tokens = DB::table('oauth_access_tokens')
            ->whereNotNull('user_id')
            ->where('client_id', '!=', self::MOBILE_CLIENT_ID)
            ->where('revoked', false)
            ->whereNotIn('id', DB::table('oauth_connections')->whereNotNull('access_token_id')->select('access_token_id'))
            ->where(fn (Builder $query) => $query
                ->where('expires_at', '>', now())
                ->orWhereExists(fn (Builder $refresh) => $refresh
                    ->from('oauth_refresh_tokens')
                    ->whereColumn('oauth_refresh_tokens.access_token_id', 'oauth_access_tokens.id')
                    ->where('revoked', false)
                    ->where('expires_at', '>', now())))
            ->orderBy('created_at')
            ->get(['user_id', 'client_id', 'scopes', 'created_at']);

        foreach ($tokens->groupBy(fn (object $token): string => "{$token->user_id}|{$token->client_id}") as $group) {
            $oldest = $group->first();
            $newest = $group->last();

            $alreadyRecorded = DB::table('oauth_connections')
                ->where('user_id', $oldest->user_id)
                ->where('client_id', $oldest->client_id)
                ->whereNull('authorization_code_hash')
                ->exists();

            if ($alreadyRecorded) {
                continue;
            }

            DB::table('oauth_connections')->insert([
                'user_id' => $oldest->user_id,
                'client_id' => $oldest->client_id,
                'label' => null,
                'scopes' => $newest->scopes,
                'access_token_id' => null,
                'refresh_token_hash' => null,
                'previous_refresh_token_hash' => null,
                'authorization_code_hash' => null,
                'last_used_at' => $newest->created_at,
                'ended_at' => null,
                'created_at' => $oldest->created_at,
                'updated_at' => now(),
            ]);
        }
    }
};
