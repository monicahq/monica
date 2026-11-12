<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\CurrencyEnum;
use App\Enums\PossessionMaintenanceTypeEnum;
use App\Enums\UserActionEnum;
use App\Helpers\PossessionMaintenanceRecordAttributes;
use App\Jobs\LogUserAction;
use App\Models\PossessionCondition;
use App\Models\PossessionMaintenanceRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Change what a maintenance record says, under the same rules as recording it.
 */
class UpdatePossessionMaintenanceRecord
{
    public function __construct(
        private readonly User $user,
        private readonly PossessionMaintenanceRecord $record,
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
        $this->update();
        $this->log();

        return $this->record;
    }

    private function validate(): void
    {
        if ($this->record->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This maintenance record is in another account.');
        }

        if ($this->conditionBefore instanceof PossessionCondition && $this->conditionBefore->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This possession condition is in another account.');
        }

        if ($this->conditionAfter instanceof PossessionCondition && $this->conditionAfter->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This possession condition is in another account.');
        }
    }

    private function update(): void
    {
        $this->record->update([
            'condition_before_id' => $this->conditionBefore?->id,
            'condition_after_id' => $this->conditionAfter?->id,
            ...PossessionMaintenanceRecordAttributes::from(
                type: $this->type,
                title: $this->title,
                performedOn: $this->performedOn,
                today: CarbonImmutable::now($this->user->timezone),
                performedBy: $this->performedBy,
                cost: $this->cost,
                currency: $this->currency,
                nextDueOn: $this->nextDueOn,
                description: $this->description,
            ),
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionMaintenanceRecordUpdate,
            parameters: ['name' => $this->record->possession->name],
            loggable: $this->record,
        )->onQueue('low');
    }
}
