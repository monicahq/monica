<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\PossessionTypes;

use App\Enums\VaultColourEnum;
use App\Helpers\LocaleCollation;
use App\Models\PossessionType;
use App\Models\Section;
use App\Models\User;

class PossessionTypesViewModel
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function title(): string
    {
        return __('Possession types');
    }

    public function description(): string
    {
        return __('The kinds of things you own, and the details a thing of each kind carries: a serial number, a power rating, the end of a warranty.');
    }

    public function createAction(): string
    {
        return route('settings.possessionTypes.create');
    }

    public function importUrl(): string
    {
        return route('settings.possessionTypeImport.new');
    }

    /**
     * Every type of the account, sorted by name in the reader's language.
     *
     * @return list<array{uuid: string, name: string, colour: string, colourName: string, tint: string, icon: string, line: string, editUrl: string}>
     */
    public function possessionTypes(): array
    {
        $possessionTypes = $this->user->account->possessionTypes()
            ->withCount(['groups', 'fields'])
            ->get();

        return LocaleCollation::sortByCollator($possessionTypes, 'name')
            ->map(fn (PossessionType $possessionType): array => [
                'uuid' => $possessionType->uuid,
                'name' => $possessionType->name,
                'colour' => $possessionType->colour,
                'colourName' => __(VaultColourEnum::tryFrom($possessionType->colour)?->label() ?? 'Slate'),
                'tint' => VaultColourEnum::tintFor($possessionType->colour),
                'icon' => $possessionType->icon ?? Section::DEFAULT_ICON,
                'line' => $this->line((int) $possessionType->groups_count, (int) $possessionType->fields_count),
                'editUrl' => route('settings.possessionTypes.edit', $possessionType),
            ])
            ->values()
            ->all();
    }

    public function isEmpty(): bool
    {
        return $this->user->account->possessionTypes()->doesntExist();
    }

    private function line(int $groupCount, int $fieldCount): string
    {
        if ($fieldCount === 0) {
            return __('No fields yet');
        }

        if ($groupCount === 0) {
            return trans_choice(':count field|:count fields', $fieldCount);
        }

        $fields = trans_choice(':count field|:count fields', $fieldCount);
        $groups = trans_choice(':count group|:count groups', $groupCount);

        return "{$fields} · {$groups}";
    }
}
