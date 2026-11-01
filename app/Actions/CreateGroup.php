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
 * Create a group of contacts in a vault, such as a household or a team. It starts with nobody in it.
 */
class CreateGroup
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly string $name,
        private readonly ?string $description = null,
        private readonly ?CarbonImmutable $startedOn = null,
        private readonly DatePrecisionEnum $startedPrecision = DatePrecisionEnum::Unknown,
        private readonly ?CarbonImmutable $endedOn = null,
        private readonly DatePrecisionEnum $endedPrecision = DatePrecisionEnum::Unknown,
        private readonly ?string $davUri = null,
    ) {}

    public function execute(): Group
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        $group = Group::query()->create([
            'account_id' => $this->user->account_id,
            'vault_id' => $this->vault->id,
            'name' => $this->name,
            'description' => $this->description,
            'started_on' => $this->startedOn,
            'started_precision' => $this->startedOn === null ? DatePrecisionEnum::Unknown : $this->startedPrecision,
            'ended_on' => $this->endedOn,
            'ended_precision' => $this->endedOn === null ? DatePrecisionEnum::Unknown : $this->endedPrecision,
            'dav_uri' => $this->davUri,
        ]);

        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::GroupCreation,
            vault: $this->vault,
            parameters: ['name' => $group->name],
            loggable: $group,
        )->onQueue('low');

        return $group;
    }
}
