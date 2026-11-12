<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PossessionFieldKindEnum;
use App\Enums\PossessionTypeEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\PossessionField;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Give an account the kinds of things people most often own, from comics to a home, each with its
 * fields, so recording a first possession starts from a sensible shape. The names are written in the
 * language of the user, and an account that already holds a type of its own is left alone.
 */
class CreateDefaultPossessionTypes
{
    private string $locale;

    public function __construct(
        private readonly User $user,
    ) {}

    /** @return list<PossessionType> */
    public function execute(): array
    {
        if ($this->user->account->possessionTypes()->exists()) {
            return [];
        }

        $this->locale = $this->user->locale ?? config('app.locale');

        $possessionTypes = DB::transaction(fn (): array => $this->create());

        $this->log();

        return $possessionTypes;
    }

    /** @return list<PossessionType> */
    private function create(): array
    {
        $possessionTypes = [];

        foreach (PossessionTypeEnum::cases() as $case) {
            $possessionType = PossessionType::query()->create([
                'account_id' => $this->user->account_id,
                'name' => __($case->label(), [], $this->locale),
                'colour' => $case->colour()->value,
                'icon' => $case->icon(),
            ]);

            $groupPosition = 1;

            foreach ($case->blocks() as $block) {
                $group = null;

                if ($block['name'] !== null) {
                    $group = PossessionFieldGroup::query()->create([
                        'account_id' => $this->user->account_id,
                        'possession_type_id' => $possessionType->id,
                        'name' => __($block['name'], [], $this->locale),
                        'position' => $groupPosition,
                    ]);

                    $groupPosition++;
                }

                $this->createFields($possessionType, $group, $block['fields']);
            }

            $possessionTypes[] = $possessionType;
        }

        return $possessionTypes;
    }

    /** @param  list<array{name: string, kind: PossessionFieldKindEnum, options: list<string>}>  $fields */
    private function createFields(PossessionType $possessionType, ?PossessionFieldGroup $group, array $fields): void
    {
        $position = 1;

        foreach ($fields as $field) {
            PossessionField::query()->create([
                'account_id' => $this->user->account_id,
                'possession_type_id' => $possessionType->id,
                'possession_field_group_id' => $group?->id,
                'name' => __($field['name'], [], $this->locale),
                'kind' => $field['kind'],
                'options' => $field['kind'] === PossessionFieldKindEnum::Select
                    ? array_map(fn (string $option): string => $this->option($option), $field['options'])
                    : null,
                'position' => $position,
            ]);

            $position++;
        }
    }

    private function option(string $option): string
    {
        if (in_array($option, PossessionTypeEnum::codes(), true)) {
            return $option;
        }

        return __($option, [], $this->locale);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionTypesSeeded,
        )->onQueue('low');
    }
}
