<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\PossessionTags;

use App\Enums\VaultColourEnum;
use App\Helpers\LocaleCollation;
use App\Models\PossessionTag;
use App\Models\User;

class PossessionTagsViewModel
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
        return __('Possession tags');
    }

    public function description(): string
    {
        return __('Words you put on possessions to sort them across types and locations, like “insured” or “to sell”. Tags belong to your account, so every vault uses the same list.');
    }

    public function createAction(): string
    {
        return route('settings.possessionTags.create');
    }

    /** @return list<array{value: string, label: string, isDefault: bool}> */
    public function colours(): array
    {
        return array_map(
            fn (VaultColourEnum $colour): array => [
                'value' => $colour->value,
                'label' => __($colour->label()),
                'isDefault' => $colour === VaultColourEnum::Blue,
            ],
            VaultColourEnum::cases(),
        );
    }

    /**
     * Every tag of the account, sorted by name in the reader's language.
     *
     * @return list<array{uuid: string, name: string, colour: string, colourName: string, deleteConsequence: string, updateAction: string, destroyAction: string}>
     */
    public function tags(): array
    {
        return LocaleCollation::sortByCollator($this->user->account->possessionTags()->withCount('possessions')->get(), 'name')
            ->map(fn (PossessionTag $possessionTag): array => [
                'uuid' => $possessionTag->uuid,
                'name' => $possessionTag->name,
                'colour' => $possessionTag->colour,
                'colourName' => __(VaultColourEnum::tryFrom($possessionTag->colour)?->label() ?? 'Slate'),
                'deleteConsequence' => $this->deleteConsequence((int) $possessionTag->possessions_count),
                'updateAction' => route('settings.possessionTags.update', $possessionTag),
                'destroyAction' => route('settings.possessionTags.destroy', $possessionTag),
            ])
            ->values()
            ->all();
    }

    public function countLine(): string
    {
        $count = $this->user->account->possessionTags()->count();

        if ($count === 0) {
            return '';
        }

        return trans_choice(':count tag, sorted by name.|:count tags, sorted by name.', $count);
    }

    public function isEmpty(): bool
    {
        return $this->user->account->possessionTags()->doesntExist();
    }

    private function deleteConsequence(int $possessionCount): string
    {
        if ($possessionCount === 0) {
            return '';
        }

        return trans_choice('{1}One possession carries it and loses it.|:count possessions carry it and lose it.', $possessionCount);
    }
}
