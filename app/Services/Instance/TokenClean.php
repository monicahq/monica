<?php

namespace App\Services\Instance;

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
     * Clean token list.
     *
     * @param array $data
     */
    public function execute(array $data)
    {
        DB::table('synctoken')
            ->groupBy(['account_id', 'user_id', 'name'])
            ->orderBy('id')
            ->select('id', 'timestamp')
            /*
            ->where([
                ['timestamp', '<', 'max(timestamp)'],
                ['timestamp', '<', now()->addDays(-7)],
            ])
            */
            ->chunk(200, function ($tokens) use ($data) {
                foreach ($tokens as $token) {
                    event(new TokenDeleteEvent($token));
                    if (! $data['dryrun']) {
                        $token->delete();
                    }
                }
            });
    }
}
