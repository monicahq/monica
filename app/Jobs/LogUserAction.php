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
 * What the entry is about is read there for the same reason, and for a sharper
 * one: half of what is worth writing down is a deletion, and by the time the
 * queue reaches the job the thing it names is gone. A job holding the row itself
 * would go looking for it on the way out of the queue and fail, which would lose
 * the record of every deletion the product performs. So the type and the number
 * are taken while the row is still in hand, and the job carries nothing that has
 * to survive it.
 *
 * A user of null means the application acted on its own, and a vault of null
 * that the action was not performed inside one.
 */
class LogUserAction implements ShouldQueue
{
    use Queueable;

    private ?string $ipAddress;

    public ?string $loggableType = null;

    public int|string|null $loggableId = null;

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
        ?Model $loggable = null,
        public ?array $changes = null,
        public LogSourceEnum $source = LogSourceEnum::Web,
        public ?string $sourceDetail = null,
        ?string $ipAddress = null,
    ) {
        $this->ipAddress = $ipAddress ?? request()->ip();

        if ($loggable instanceof Model) {
            $this->loggableType = $loggable->getMorphClass();
            $this->loggableId = $loggable->getKey();
        }
    }

    /**
     * Whether the entry is about the given row. It is asked this way rather than
     * by handing the row back, because the job does not keep one.
     */
    public function isAbout(Model $model): bool
    {
        return $this->loggableType === $model->getMorphClass()
            && $this->loggableId === $model->getKey();
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
            'loggable_type' => $this->loggableType,
            'loggable_id' => $this->loggableId,
            'changes' => $this->changes,
            'source' => $this->source,
            'source_detail' => $this->sourceDetail,
            'ip_address' => $this->ipAddress,
        ]);
    }
}
