<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\PossessionConditions;

use App\Enums\VaultColourEnum;
use App\Models\PossessionCondition;
use App\Models\PossessionMaintenanceRecord;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PossessionConditionsViewModel
{
    /** @var Collection<int, PossessionCondition>|null */
    private ?Collection $possessionConditions = null;

    public function __construct(
        private readonly User $user,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function title(): string
    {
        return __('Possession conditions');
    }

    public function description(): string
    {
        return __('The states a possession can be in, from best to worst, like “new” or “broken”. Conditions belong to your account, so every vault uses the same list.');
    }

    public function createAction(): string
    {
        return route('settings.possessionConditions.create');
    }

    /**
     * The vault colours, as the mark picker lists them. There is no neutral entry: a condition
     * always has a colour.
     *
     * @return list<array{value: string, label: string, tint: string, swatch: string}>
     */
    public function colours(): array
    {
        return array_map(
            fn (VaultColourEnum $colour): array => [
                'value' => $colour->value,
                'label' => __($colour->label()),
                'tint' => $colour->tint(),
                'swatch' => $colour->value,
            ],
            VaultColourEnum::cases(),
        );
    }

    /** @return array<int, string> */
    public function glyphs(): array
    {
        return Section::ICONS;
    }

    /**
     * Every condition of the account, in the order the account keeps them.
     *
     * @return list<array{uuid: string, name: string, colour: string, tint: string, colourName: string, icon: string|null, position: int, isFirst: bool, isLast: bool, updateAction: string, positionAction: string, destroyAction: string, deleteWarning: string}>
     */
    public function conditions(): array
    {
        $possessionConditions = $this->possessionConditions();
        $last = $possessionConditions->count() - 1;
        $namedBy = $this->maintenanceRecordCounts();

        return $possessionConditions
            ->values()
            ->map(function (PossessionCondition $possessionCondition, int $index) use ($last, $namedBy): array {
                $colour = VaultColourEnum::tryFrom($possessionCondition->colour) ?? VaultColourEnum::Slate;

                return [
                    'uuid' => $possessionCondition->uuid,
                    'name' => $possessionCondition->name,
                    'colour' => $colour->value,
                    'tint' => $colour->tint(),
                    'colourName' => __($colour->label()),
                    'icon' => $possessionCondition->icon,
                    'position' => $possessionCondition->position,
                    'isFirst' => $index === 0,
                    'isLast' => $index === $last,
                    'updateAction' => route('settings.possessionConditions.update', $possessionCondition),
                    'positionAction' => route('settings.possessionConditionPosition.update', $possessionCondition),
                    'destroyAction' => route('settings.possessionConditions.destroy', $possessionCondition),
                    'deleteWarning' => $this->deleteWarning($namedBy[$possessionCondition->id] ?? 0),
                ];
            })
            ->all();
    }

    public function countLine(): string
    {
        $count = $this->possessionConditions()->count();

        if ($count === 0) {
            return '';
        }

        return trans_choice(':count condition, from best to worst.|:count conditions, from best to worst.', $count);
    }

    public function isEmpty(): bool
    {
        return $this->possessionConditions()->isEmpty();
    }

    private function deleteWarning(int $maintenanceRecordCount): string
    {
        if ($maintenanceRecordCount === 0) {
            return '';
        }

        return trans_choice(':count maintenance record names this condition. It will read “Not recorded” there.|:count maintenance records name this condition. They will read “Not recorded” there.', $maintenanceRecordCount);
    }

    /**
     * How many maintenance records of the account name each condition, before or after the job. A
     * record naming the same condition on both sides counts once.
     *
     * @return array<int, int>
     */
    private function maintenanceRecordCounts(): array
    {
        $counts = [];

        PossessionMaintenanceRecord::query()
            ->where('account_id', $this->user->account_id)
            ->where(fn (Builder $query) => $query->whereNotNull('condition_before_id')->orWhereNotNull('condition_after_id'))
            ->get(['condition_before_id', 'condition_after_id'])
            ->each(function (PossessionMaintenanceRecord $record) use (&$counts): void {
                foreach (array_unique(array_filter([$record->condition_before_id, $record->condition_after_id])) as $conditionId) {
                    $counts[$conditionId] = ($counts[$conditionId] ?? 0) + 1;
                }
            });

        return $counts;
    }

    /** @return Collection<int, PossessionCondition> */
    private function possessionConditions(): Collection
    {
        return $this->possessionConditions ??= $this->user->account->possessionConditions()->get();
    }
}
