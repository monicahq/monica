<?php

namespace App\Http\Controllers\Settings;

use PragmaRX\Random\Random;
use Illuminate\Http\Request;
use App\Models\User\RecoveryCode;
use App\Http\Controllers\Controller;
use App\Traits\JsonRespondController;

class RecoveryCodesController extends Controller
{
    use JsonRespondController;

    /**
     * Generate recovery codes.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Support\Collection<array-key, array{id: int, recovery: string, used: bool}>
     */
    public function store(Request $request)
    {
        // Remove previous codes
        auth()->user()->recoveryCodes()
            ->each(function ($code) {
                $code->delete();
            });

        // Generate new codes
        $this->generate();

        $codes = auth()->user()->recoveryCodes()->get();

        return $this->response($codes);
    }

    /**
     * Get list of recovery codes.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Support\Collection<array-key, array{id: int, recovery: string, used: bool}>
     */
    public function index(Request $request)
    {
        $codes = auth()->user()->recoveryCodes()->get();

        if (count($codes) == 0) {
            $this->generate();
            $codes = auth()->user()->recoveryCodes()->get();
        }

        return $this->response($codes);
    }

    /**
     * Format codes collection for response.
     *
     * @param  \Illuminate\Support\Collection<array-key, \App\Models\User\RecoveryCode>  $codes
     * @return \Illuminate\Support\Collection<array-key, array{id: int, recovery: string, used: bool}>
     */
    private function response($codes)
    {
        return $codes->map(function (RecoveryCode $code): array {
            return [
                'id' => $code->id,
                'recovery' => $code->recovery,
                'used' => (bool) $code->used,
            ];
        });
    }

    /**
     * Generate new recovery codes.
     *
     * @return void
     */
    private function generate()
    {
        // Generate new codes
        $random = new Random();
        $random->uppercase(true);

        $codes = [];

        for ($i = 1; $i <= (int) config('auth.recovery.count'); $i++) {
            $blocks = [];

            for ($j = 1; $j <= (int) config('auth.recovery.blocks'); $j++) {
                $blocks[] = $random->size(config('auth.recovery.chars'))->get();
            }

            $codes[] = implode('-', $blocks);
        }

        foreach ($codes as $code) {
            RecoveryCode::create([
                'account_id' => auth()->user()->account_id,
                'user_id' => auth()->user()->id,
                'recovery' => $code,
            ]);
        }
    }
}
