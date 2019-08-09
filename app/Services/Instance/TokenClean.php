<?php

namespace App\Services\Instance;

use Carbon\Carbon;
use App\Models\User\SyncToken;
use App\Events\TokenDeleteEvent;
use Illuminate\Support\Facades\DB;

class TokenClean
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'dryrun' => 'boolean',
        ];
    }

    /**
     * @var Carbon
     */
    private $timefix;

    /**
     * Clean token list.
     *
     * @param array $data
     */
    public function execute(array $data)
    {
        $this->timefix = now()->addDays(-7);

        DB::table('synctoken')
            ->orderBy('user_id')
            ->groupBy('user_id', 'name')
            ->select(DB::raw('user_id, name, max(timestamp) as timestamp'))
            ->chunk(200, function ($tokens) use ($data) {
                foreach ($tokens as $token) {
                    $this->handleUserToken($data, $token->user_id, $token->name, $token->timestamp);
                }
            });
    }

    /**
     * Handle tokens for a user.
     *
     * @param array $data
     * @param int $userId
     * @param string $tokenName
     * @param string $timestamp
     */
    private function handleUserToken(array $data, int $userId, string $tokenName, string $timestamp)
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
            event(new TokenDeleteEvent($token));
            if (! $data['dryrun']) {
                $token->delete();
            }
        }
    }
}
