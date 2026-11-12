<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\CurrencyEnum;
use App\Enums\DateFormatEnum;
use App\Enums\PossessionMaintenanceTypeEnum;
use App\Helpers\AvailableLocales;
use App\Helpers\PossessionValueFormatter;
use App\Models\Possession;
use App\Models\PossessionCondition;
use App\Models\PossessionMaintenanceRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * The Maintenance section of a possession: every job done on it, the latest first, with the condition
 * before and after, and whether the latest one is overdue. Nothing is copied onto the possession.
 */
class PossessionMaintenanceViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly Possession $possession,
        private readonly ?PossessionMaintenanceRecord $editing = null,
    ) {
        $this->possession->load('maintenanceRecords.conditionBefore', 'maintenanceRecords.conditionAfter');
    }

    public function page(): PossessionViewModel
    {
        return once(fn (): PossessionViewModel => new PossessionViewModel(user: $this->user, possession: $this->possession));
    }

    /**
     * Every record, the latest performed first. The one being edited is left out, since the form
     * stands in its place.
     *
     * @return list<array{id: string, type: string, typeColour: string, typeTint: string, title: string, isOverdue: bool, cost: string, hasCost: bool, parts: list<array{label: string, value: string, isSet: bool}>, description: ?string, editUrl: string, destroyUrl: string, deleteTitle: string}>
     */
    public function records(): array
    {
        $overdue = $this->possession->overdueMaintenanceRecord(CarbonImmutable::now($this->user->timezone));

        return $this->possession->maintenanceRecords
            ->reject(fn (PossessionMaintenanceRecord $record): bool => $this->editing?->is($record) ?? false)
            ->map(fn (PossessionMaintenanceRecord $record): array => [
                'id' => $record->uuid,
                'type' => __($record->type->label()),
                'typeColour' => $record->type->colour()->value,
                'typeTint' => $record->type->colour()->tint(),
                'title' => $record->title,
                'isOverdue' => $overdue?->is($record) ?? false,
                'cost' => $record->cost === null ? __('No cost') : $this->money($record->cost, $record->currency?->value),
                'hasCost' => $record->cost !== null,
                'parts' => [
                    ['label' => __('Performed by'), 'value' => $record->performed_by ?? __('Not recorded'), 'isSet' => $record->performed_by !== null],
                    ['label' => __('Performed'), 'value' => $this->writeDate($record->performed_on), 'isSet' => true],
                    ['label' => __('Condition'), 'value' => $this->condition($record), 'isSet' => $record->conditionBefore !== null || $record->conditionAfter !== null],
                    ['label' => __('Next due'), 'value' => $record->next_due_on === null ? __('Not scheduled') : $this->writeDate($record->next_due_on), 'isSet' => $record->next_due_on !== null],
                ],
                'description' => $record->description,
                'editUrl' => route('possessions.maintenanceRecords.edit', [$this->possession->vault, $this->possession, $record]),
                'destroyUrl' => route('possessions.maintenanceRecords.destroy', [$this->possession->vault, $this->possession, $record]),
                'deleteTitle' => __('Delete the “:title” record?', ['title' => $record->title]),
            ])
            ->values()
            ->all();
    }

    /** @return list<array{value: string, label: string}> */
    public function types(): array
    {
        return array_map(
            fn (PossessionMaintenanceTypeEnum $type): array => ['value' => $type->value, 'label' => __($type->label())],
            PossessionMaintenanceTypeEnum::cases(),
        );
    }

    /**
     * The account's conditions, best to worst, for the two fields of the form.
     *
     * @return list<array{value: string, label: string}>
     */
    public function conditions(): array
    {
        return $this->user->account->possessionConditions()
            ->get()
            ->map(fn (PossessionCondition $condition): array => ['value' => $condition->uuid, 'label' => $condition->name])
            ->values()
            ->all();
    }

    /**
     * What the form starts with: the record being edited, else a repair done today in the currency of
     * the declared value.
     *
     * @return array{isEditing: bool, action: string, method: string, cancelUrl: ?string, values: array<string, string>, currency: ?CurrencyEnum, today: string}
     */
    public function form(): array
    {
        $editing = $this->editing;
        $today = CarbonImmutable::now($this->user->timezone)->toDateString();

        if (! $editing instanceof PossessionMaintenanceRecord) {
            return [
                'isEditing' => false,
                'action' => route('possessions.maintenanceRecords.create', [$this->possession->vault, $this->possession]),
                'method' => 'post',
                'cancelUrl' => null,
                'values' => ['type' => PossessionMaintenanceTypeEnum::Repair->value, 'performed_on' => $today],
                'currency' => CurrencyEnum::tryFrom((string) $this->possession->declared_value_currency),
                'today' => $today,
            ];
        }

        return [
            'isEditing' => true,
            'action' => route('possessions.maintenanceRecords.update', [$this->possession->vault, $this->possession, $editing]),
            'method' => 'put',
            'cancelUrl' => route('possessions.maintenanceRecords.index', [$this->possession->vault, $this->possession]),
            'values' => array_map(fn (?string $value): string => (string) $value, [
                'type' => $editing->type->value,
                'title' => $editing->title,
                'performed_by' => $editing->performed_by,
                'performed_on' => $editing->performed_on->toDateString(),
                'cost' => $editing->cost,
                'condition_before' => $editing->conditionBefore?->uuid,
                'condition_after' => $editing->conditionAfter?->uuid,
                'next_due_on' => $editing->next_due_on?->toDateString(),
                'description' => $editing->description,
            ]),
            'currency' => $editing->currency ?? CurrencyEnum::tryFrom((string) $this->possession->declared_value_currency),
            'today' => $today,
        ];
    }

    private function condition(PossessionMaintenanceRecord $record): string
    {
        if ($record->conditionBefore === null && $record->conditionAfter === null) {
            return __('Not recorded');
        }

        return __(':before → :after', [
            'before' => $record->conditionBefore->name ?? __('Not recorded'),
            'after' => $record->conditionAfter->name ?? __('Not recorded'),
        ]);
    }

    private function money(string $amount, ?string $currency): string
    {
        return new PossessionValueFormatter($this->user)->formatExact($amount, $currency);
    }

    private function writeDate(CarbonInterface $date): string
    {
        $format = $this->user->date_format->value
            ?? (string) AvailableLocales::details(app()->getLocale())['date_format'];

        return DateFormatEnum::write($date->locale(app()->getLocale()), $format);
    }
}
