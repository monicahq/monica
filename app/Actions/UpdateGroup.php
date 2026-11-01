<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\DatePrecisionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Group;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Change what a group is called, what is said about it and the dates it existed between.
 */
class UpdateGroup
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Group $group,
        private readonly string $name,
        private readonly ?string $description,
        private readonly ?CarbonImmutable $startedOn,
        private readonly DatePrecisionEnum $startedPrecision,
        private readonly ?CarbonImmutable $endedOn,
        private readonly DatePrecisionEnum $endedPrecision,
    ) {}

    public function execute(): Group
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        if ($this->group->account_id !== $this->user->account_id || $this->group->vault_id !== $this->vault->id) {
            throw new ModelNotFoundException('This group is in another vault.');
        }

        $this->group->update([
            'name' => $this->name,
            'description' => $this->description,
            'started_on' => $this->startedOn,
            'started_precision' => $this->startedOn === null ? DatePrecisionEnum::Unknown : $this->startedPrecision,
            'ended_on' => $this->endedOn,
            'ended_precision' => $this->endedOn === null ? DatePrecisionEnum::Unknown : $this->endedPrecision,
        ]);

        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::GroupUpdate,
            vault: $this->vault,
            parameters: ['name' => $this->group->name],
            loggable: $this->group,
        );

        return $this->group;
    }
}
