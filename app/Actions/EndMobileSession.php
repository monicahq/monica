<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\MobileSession;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;

/**
 * Sign one phone out: its access stops at once and its refresh token can no longer renew it.
 * The person's other phones, assistants and API keys are left alone.
 */
class EndMobileSession
{
    public function __construct(
        private readonly User $user,
        private readonly MobileSession $session,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->end();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->session->user_id !== $this->user->id) {
            throw new ModelNotFoundException('Mobile session not found');
        }

        if ($this->session->ended_at !== null) {
            throw new InvalidArgumentException('The phone is already signed out.');
        }
    }

    private function end(): void
    {
        DB::transaction(function (): void {
            Token::query()->whereKey($this->session->access_token_id)->update(['revoked' => true]);
            RefreshToken::query()->where('access_token_id', $this->session->access_token_id)->update(['revoked' => true]);

            $this->session->update(['ended_at' => now()]);
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::MobileSessionEnd,
            parameters: ['name' => $this->session->device_name ?? __('Unnamed phone')],
        );
    }
}
