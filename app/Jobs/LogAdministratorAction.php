<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\AdministratorActionEnum;
use App\Models\Account;
use App\Models\AdministratorLog;
use App\Models\Log;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Write down that an administrator did something. Every action in the admin
 * space dispatches this, and none of them may decide not to.
 *
 * It reads the address and the subject in the constructor for the same two
 * reasons {@see LogUserAction} does: by the time the queue reaches the handler
 * there is no request to ask, and half of what is worth writing down is about
 * something that has just stopped existing.
 *
 * An administrator of null means the command line acted, which is how the first
 * administrator of an instance is designated and how an instance with none is
 * recovered. An account of null means the action was not about one, which is
 * true of every marketplace decision.
 */
class LogAdministratorAction implements ShouldQueue
{
    use Queueable;

    private ?string $ipAddress;

    public ?string $subjectType = null;

    public int|string|null $subjectId = null;

    /**
     * @param  array<string, mixed>|null  $parameters
     */
    public function __construct(
        public ?User $administrator,
        public AdministratorActionEnum $action,
        public ?Account $account = null,
        public ?array $parameters = null,
        ?Model $subject = null,
        ?string $ipAddress = null,
    ) {
        $this->ipAddress = $ipAddress ?? request()->ip();

        if ($subject instanceof Model) {
            $this->subjectType = $subject->getMorphClass();
            $this->subjectId = $subject->getKey();
        }
    }

    /**
     * Whether the entry is about the given row. It is asked this way rather than
     * by handing the row back, because the job does not keep one.
     */
    public function isAbout(Model $model): bool
    {
        return $this->subjectType === $model->getMorphClass()
            && $this->subjectId === $model->getKey();
    }

    public function handle(): void
    {
        AdministratorLog::query()->create([
            'user_id' => $this->administrator?->id,
            'user_email' => $this->administrator->email ?? Log::SYSTEM,
            'action' => $this->action->value,
            'parameters' => $this->parameters,
            'account_id' => $this->account?->id,
            'subject_type' => $this->subjectType,
            'subject_id' => $this->subjectId,
            'ip_address' => $this->ipAddress,
        ]);
    }
}
