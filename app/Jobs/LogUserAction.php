<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\LogSourceEnum;
use App\Enums\UserActionEnum;
use App\Models\Account;
use App\Models\Log;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Write down that something happened. Every action that changes state dispatches
 * this, and none of them may decide not to.
 *
 * The address the action came from is read in the constructor rather than in
 * the handler, since by then the job is on a queue with no request behind it.
 * Nothing needs to ask whether there is one: a request built from a command
 * line has no address to give, and the column is null for exactly that case.
 *
 * A user of null means the application acted on its own, and a vault of null
 * that the action was not performed inside one.
 */
class LogUserAction implements ShouldQueue
{
    use Queueable;

    private ?string $ipAddress;

    /**
     * @param  array<string, mixed>|null  $parameters
     * @param  array<string, mixed>|null  $changes
     */
    public function __construct(
        public Account $account,
        public ?User $user,
        public UserActionEnum $action,
        public ?Vault $vault = null,
        public ?array $parameters = null,
        public ?Model $loggable = null,
        public ?array $changes = null,
        public LogSourceEnum $source = LogSourceEnum::Web,
        public ?string $sourceDetail = null,
        ?string $ipAddress = null,
    ) {
        $this->ipAddress = $ipAddress ?? request()->ip();
    }

    /**
     * Log the action in the logs table.
     */
    public function handle(): void
    {
        Log::query()->create([
            'account_id' => $this->account->id,
            'vault_id' => $this->vault?->id,
            'user_id' => $this->user?->id,
            'user_email' => $this->user->email ?? Log::SYSTEM,
            'action' => $this->action->value,
            'parameters' => $this->parameters,
            'loggable_type' => $this->loggable === null ? null : $this->loggable->getMorphClass(),
            'loggable_id' => $this->loggable?->getKey(),
            'changes' => $this->changes,
            'source' => $this->source,
            'source_detail' => $this->sourceDetail,
            'ip_address' => $this->ipAddress,
        ]);
    }
}
