<?php

namespace App\Domains\Contact\Dav\Jobs;

use App\Domains\Contact\Dav\Event\TokenDeleteEvent;
use App\Interfaces\ServiceInterface;
use App\Models\SyncToken;
use App\Services\QueuableService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CleanSyncToken extends QueuableService implements ServiceInterface
{
    private Carbon $timefix;

    /**
     * Clean token list.
     */
    public function execute(array $data): void
    {
        $this->timefix = now()->addDays(-7);

        DB::table('synctokens')
            ->orderBy('user_id')
            ->groupBy('user_id', 'name')
            ->select(DB::raw('user_id, name, max(timestamp) as timestamp'))
            ->chunk(200, function ($tokens) {
                foreach ($tokens as $token) {
                    $this->handleUserToken($token->user_id, $token->name, $token->timestamp);
                }
            });
    }

    /**
     * Handle tokens for a user.
     */
    private function handleUserToken(int $userId, string $tokenName, string $timestamp): void
    {
        $tokens = SyncToken::where([
            ['user_id', $userId],
            ['name', $tokenName],
            ['timestamp', '<', Carbon::parse($timestamp)],
            ['timestamp', '<', $this->timefix],
        ])
            ->orderByDesc('timestamp')
            ->get();

        foreach ($tokens as $token) {
            TokenDeleteEvent::dispatch($token);

            $token->delete();
        }
    }
}
