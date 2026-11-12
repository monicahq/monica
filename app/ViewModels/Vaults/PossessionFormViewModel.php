<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\CurrencyEnum;
use App\Enums\VaultColourEnum;
use App\Helpers\AvailableLocales;
use App\Helpers\LocaleCollation;
use App\Helpers\LocationTree;
use App\Models\Location;
use App\Models\Possession;
use App\Models\PossessionFieldValue;
use App\Models\PossessionTag;
use App\Models\PossessionType;
use App\Models\User;
use App\Models\Vault;

/**
 * The form that adds a possession to a vault, or edits one: the account's types for the picker, the
 * fields of the chosen type, the locations with the address each one takes, the currency of the
 * declared value and the tags to suggest. Every value is read back from the old input first, so a
 * refused save loses nothing, then from the possession being edited. An edited possession keeps its
 * type.
 */
class PossessionFormViewModel
{
    public const int RECENT_TYPE_LIMIT = 4;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly ?PossessionType $chosenType = null,
        private readonly ?Possession $possession = null,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function vault(): Vault
    {
        return $this->vault;
    }

    public function isEditing(): bool
    {
        return $this->possession !== null;
    }

    public function title(): string
    {
        if ($this->possession === null) {
            return __('Add a possession');
        }

        return __('Edit :name', ['name' => $this->possession->name]);
    }

    public function possessionName(): string
    {
        return $this->possession->name ?? '';
    }

    public function possessionUrl(): string
    {
        if ($this->possession === null) {
            return '';
        }

        return route('possessions.show', ['vault' => $this->vault, 'possession' => $this->possession]);
    }

    public function formAction(): string
    {
        if ($this->possession === null) {
            return route('possessions.create', $this->vault);
        }

        return route('possessions.update', ['vault' => $this->vault, 'possession' => $this->possession]);
    }

    public function formMethod(): string
    {
        return $this->possession === null ? 'post' : 'put';
    }

    public function indexUrl(): string
    {
        return route('possessions.index', $this->vault);
    }

    public function cancelUrl(): string
    {
        if ($this->possession === null) {
            return $this->indexUrl();
        }

        return $this->possessionUrl();
    }

    public function typesSettingsUrl(): string
    {
        return route('settings.possessionTypes.index');
    }

    public function newLocationUrl(): string
    {
        return route('settings.locations.new');
    }

    public function name(): string
    {
        return (string) old('name', $this->possession->name ?? '');
    }

    public function description(): string
    {
        return (string) old('description', $this->possession->description ?? '');
    }

    /** @return list<array{id: int, uuid: string, name: string, colour: string, colourName: string, fieldCount: string, fieldsUrl: string}> */
    public function types(): array
    {
        return once(fn (): array => LocaleCollation::sortByCollator(
            $this->user->account->possessionTypes()->withCount('fields')->get(),
            'name',
        )
            ->map($this->type(...))
            ->values()
            ->all());
    }

    /** @return list<array{id: int, uuid: string, name: string, colour: string, colourName: string, fieldCount: string, fieldsUrl: string}> */
    public function recentTypes(): array
    {
        $types = collect($this->types())->keyBy('id');

        return Possession::query()
            ->where('vault_id', $this->vault->id)
            ->latest('id')
            ->limit(50)
            ->pluck('possession_type_id')
            ->unique()
            ->map(fn (int $id): ?array => $types->get($id))
            ->filter()
            ->take(self::RECENT_TYPE_LIMIT)
            ->values()
            ->all();
    }

    public function chosenType(): string
    {
        if ($this->possession !== null) {
            return $this->possession->possessionType->uuid;
        }

        $chosen = (string) old('type', $this->chosenType->uuid ?? '');

        return collect($this->types())->contains('uuid', $chosen) ? $chosen : '';
    }

    public function typeCountLine(): string
    {
        return trans_choice(':count type in your account|:count types in your account', count($this->types()));
    }

    public function fieldsBlock(): ?PossessionTypeFieldsBlock
    {
        if ($this->chosenType() === '') {
            return null;
        }

        $type = PossessionType::query()
            ->where('account_id', $this->user->account_id)
            ->where('uuid', $this->chosenType())
            ->firstOrFail();

        $oldValues = old('values', $this->storedValues());

        return new PossessionTypeFieldsBlock(possessionType: $type, oldValues: is_array($oldValues) ? $oldValues : []);
    }

    /** @return list<array{uuid: string, path: string, name: string, address: string|null, addressNote: string|null}> */
    public function locations(): array
    {
        $tree = LocationTree::forAccount($this->user->account_id);

        return $tree->ordered()
            ->map(function (Location $location) use ($tree): array {
                $holder = $tree->addressHolder($location);

                return [
                    'uuid' => $location->uuid,
                    'path' => $tree->path($location),
                    'name' => $location->name,
                    'address' => $holder?->address,
                    'addressNote' => match (true) {
                        $holder === null => null,
                        $holder->is($location) => __('The address of :place.', ['place' => $holder->name]),
                        default => __('Taken from :place, which :location is part of.', ['place' => $holder->name, 'location' => $location->name]),
                    },
                ];
            })
            ->values()
            ->all();
    }

    public function chosenLocation(): string
    {
        return (string) old('location', $this->possession?->location->uuid ?? '');
    }

    public function declaredValue(): string
    {
        return (string) old('declared_value', $this->possession->declared_value ?? '');
    }

    /**
     * The currency of the declared value: the one chosen before a refused save, else the one of the
     * possession being edited, else the one of the reader's language, else the US dollar.
     */
    public function currency(): CurrencyEnum
    {
        $chosen = CurrencyEnum::tryFrom((string) old('declared_value_currency', $this->possession->declared_value_currency ?? ''));

        if ($chosen !== null) {
            return $chosen;
        }

        $suggested = AvailableLocales::details($this->user->locale ?? app()->getLocale())['currency'] ?? null;

        return CurrencyEnum::tryFrom(is_string($suggested) ? $suggested : '') ?? CurrencyEnum::UsDollar;
    }

    /** @return list<string> */
    public function accountTags(): array
    {
        return LocaleCollation::sortByCollator($this->user->account->possessionTags()->get(), 'name')
            ->map(fn (PossessionTag $tag): string => $tag->name)
            ->values()
            ->all();
    }

    /** @return list<string> */
    public function chosenTags(): array
    {
        $tags = $this->possession === null || session()->hasOldInput()
            ? old('tags', [])
            : $this->possession->tags->pluck('name')->all();

        return is_array($tags) ? array_values(array_filter(array_map(strval(...), $tags), fn (string $tag): bool => $tag !== '')) : [];
    }

    public function isFeatured(): bool
    {
        if ($this->possession === null || session()->hasOldInput()) {
            return (bool) old('is_featured');
        }

        return $this->possession->is_featured;
    }

    public function featuredLine(): string
    {
        $count = Possession::query()->where('vault_id', $this->vault->id)->where('is_featured', true)->count();

        if ($count === 0) {
            return __('Featured possessions sit at the top of All possessions.');
        }

        return __('Featured possessions sit at the top of All possessions.').' '.trans_choice('You currently feature :count possession.|You currently feature :count possessions.', $count);
    }

    /** @return array<string, string> */
    private function storedValues(): array
    {
        if ($this->possession === null) {
            return [];
        }

        return $this->possession->values()
            ->with('field')
            ->get()
            ->mapWithKeys(fn (PossessionFieldValue $value): array => [$value->field->uuid => $value->value])
            ->all();
    }

    /** @return array{id: int, uuid: string, name: string, colour: string, colourName: string, fieldCount: string, fieldsUrl: string} */
    private function type(PossessionType $type): array
    {
        return [
            'id' => $type->id,
            'uuid' => $type->uuid,
            'name' => $type->name,
            'colour' => $type->colour,
            'colourName' => __(VaultColourEnum::tryFrom($type->colour)?->label() ?? 'Slate'),
            'fieldCount' => trans_choice(':count field|:count fields', (int) $type->fields_count),
            'fieldsUrl' => route('possessions.typeFields.index', ['vault' => $this->vault, 'possessionType' => $type]),
        ];
    }
}
