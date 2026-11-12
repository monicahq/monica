<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\CurrencyEnum;
use App\Enums\PossessionMaintenanceTypeEnum;
use App\Enums\UserActionEnum;
use App\Helpers\PossessionMaintenanceRecordAttributes;
use App\Jobs\LogUserAction;
use App\Models\Possession;
use App\Models\PossessionCondition;
use App\Models\PossessionMaintenanceRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Record one job done on a possession: the type, a title and the day it was performed are required,
 * everything else, including the conditions before and after, may be added later.
 */
class CreatePossessionMaintenanceRecord
{
    private PossessionMaintenanceRecord $record;

    public function __construct(
        private readonly User $user,
        private readonly Possession $possession,
        private readonly PossessionMaintenanceTypeEnum $type,
        private readonly string $title,
        private readonly CarbonImmutable $performedOn,
        private readonly ?string $performedBy = null,
        private readonly ?string $cost = null,
        private readonly ?CurrencyEnum $currency = null,
        private readonly ?PossessionCondition $conditionBefore = null,
        private readonly ?PossessionCondition $conditionAfter = null,
        private readonly ?CarbonImmutable $nextDueOn = null,
        private readonly ?string $description = null,
    ) {}

    public function execute(): PossessionMaintenanceRecord
    {
        $this->validate();
        $this->create();
        $this->log();

        return $this->record;
    }

    private function validate(): void
    {
        if ($this->possession->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This possession is in another account.');
        }

        if ($this->conditionBefore instanceof PossessionCondition && $this->conditionBefore->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This possession condition is in another account.');
        }

        if ($this->conditionAfter instanceof PossessionCondition && $this->conditionAfter->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This possession condition is in another account.');
        }
    }

    private function create(): void
    {
        $attributes = PossessionMaintenanceRecordAttributes::from(
            type: $this->type,
            title: $this->title,
            performedOn: $this->performedOn,
            today: CarbonImmutable::now($this->user->timezone),
            performedBy: $this->performedBy,
            cost: $this->cost,
            currency: $this->currency,
            nextDueOn: $this->nextDueOn,
            description: $this->description,
        );

        $this->record = PossessionMaintenanceRecord::query()->create([
            'account_id' => $this->possession->account_id,
            'vault_id' => $this->possession->vault_id,
            'possession_id' => $this->possession->id,
            'condition_before_id' => $this->conditionBefore?->id,
            'condition_after_id' => $this->conditionAfter?->id,
            ...$attributes,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionMaintenanceRecordCreation,
            parameters: ['name' => $this->possession->name],
            loggable: $this->record,
        )->onQueue('low');
    }
}
