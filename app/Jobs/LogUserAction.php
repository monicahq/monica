<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\LogSourceEnum;
use App\Enums\UserActionEnum;
use App\Models\Account;
use App\Models\Log;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Write down that something happened. Every action that changes state dispatches this, and the
 * address, the subject and the interface it came through are read in the constructor, since the
 * queue has no request behind it and half of what is worth recording is a deletion.
 */
class LogUserAction implements ShouldQueueAfterCommit
{
    use Queueable;

    private ?string $ipAddress;

    public LogSourceEnum $source;

    public ?string $sourceDetail;

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
        ?LogSourceEnum $source = null,
        ?string $sourceDetail = null,
        ?string $ipAddress = null,
    ) {
        $this->onQueue('low');

        $this->ipAddress = $ipAddress ?? request()->ip();
        $this->source = $source ?? request()->attributes->get('log_source', LogSourceEnum::Web);
        $this->sourceDetail = $sourceDetail ?? request()->attributes->get('log_source_detail');

        if ($loggable instanceof Model) {
            $this->loggableType = $loggable->getMorphClass();
            $this->loggableId = $loggable->getKey();
        }
    }

    public function isAbout(Model $model): bool
    {
        return $this->loggableType === $model->getMorphClass()
            && $this->loggableId === $model->getKey();
    }

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
