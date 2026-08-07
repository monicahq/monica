<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\UserActionEnum;
use App\Models\Account;
use App\Models\Log;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class LogUserAction implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>|null  $parameters
     */
    public function __construct(
        public Account $account,
        public User $user,
        public UserActionEnum $action,
        public ?array $parameters = null,
    ) {}

    /**
     * Log the user action in the logs table.
     */
    public function handle(): void
    {
        Log::query()->create([
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'user_email' => $this->user->email,
            'action' => $this->action->value,
            'parameters' => $this->parameters,
        ]);
    }
}
