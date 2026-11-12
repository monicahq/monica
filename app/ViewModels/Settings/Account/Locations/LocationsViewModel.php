<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Locations;

use App\Helpers\LocationTree;
use App\Models\Location;
use App\Models\User;

class LocationsViewModel
{
    public const array EMOJI_SUGGESTIONS = ['🏠', '🏡', '🏢', '🛋️', '🛏️', '🍳', '🖥️', '🚗', '🗄️', '📚', '📦', '🧰'];

    private ?LocationTree $tree = null;

    public function __construct(
        private readonly User $user,
        private readonly bool $creating = false,
        private readonly ?Location $parent = null,
        private readonly ?Location $editing = null,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function title(): string
    {
        return __('Locations');
    }

    public function description(): string
    {
        return __('The places your things are kept in: a house, a room, a shelf, a box. A location can sit inside another.');
    }

    public function newUrl(): string
    {
        return route('settings.locations.new');
    }

    public function indexUrl(): string
    {
        return route('settings.locations.index');
    }

    public function isEmpty(): bool
    {
        return $this->locationTree()->isEmpty();
    }

    /**
     * The locations at the top level, each holding the rows of the locations inside it.
     *
     * @return list<array<string, mixed>>
     */
    public function tree(): array
    {
        return $this->rowsInside(parent: null, level: 1);
    }

    public function formIsOpen(): bool
    {
        return $this->creating || $this->editing instanceof Location;
    }

    public function formTitle(): string
    {
        if ($this->editing instanceof Location) {
            return __('Edit “:name”', ['name' => $this->editing->name]);
        }

        if ($this->parent instanceof Location) {
            return __('New location inside :name', ['name' => $this->parent->name]);
        }

        return __('New location');
    }

    public function formAction(): string
    {
        if ($this->editing instanceof Location) {
            return route('settings.locations.update', $this->editing);
        }

        return route('settings.locations.create');
    }

    public function formMethod(): string
    {
        return $this->editing instanceof Location ? 'put' : 'post';
    }

    public function saveLabel(): string
    {
        return $this->editing instanceof Location ? __('Save changes') : __('Add the location');
    }

    /** @return array{name: string, parent: string, emoji: string, address: string} */
    public function values(): array
    {
        $parentUuid = $this->editing instanceof Location
            ? $this->editing->parent?->uuid
            : $this->parent?->uuid;

        return [
            'name' => (string) old('name', $this->editing->name ?? ''),
            'parent' => (string) old('parent', $parentUuid ?? ''),
            'emoji' => (string) old('emoji', $this->editing->emoji ?? ''),
            'address' => (string) old('address', $this->editing->address ?? ''),
        ];
    }

    /**
     * Every location the one in the form may sit inside, named by its path. The location being edited
     * and every location inside it are left out, since it cannot sit inside itself.
     *
     * @return list<array{value: string, label: string}>
     */
    public function parentOptions(): array
    {
        $excluded = $this->editing instanceof Location
            ? [$this->editing->id, ...$this->locationTree()->descendantIds($this->editing)]
            : [];

        return $this->locationTree()->ordered()
            ->reject(fn (Location $location): bool => in_array($location->id, $excluded, true))
            ->map(fn (Location $location): array => [
                'value' => $location->uuid,
                'label' => $this->locationTree()->path($location),
            ])
            ->values()
            ->all();
    }

    /** @return list<string> */
    public function emojiSuggestions(): array
    {
        return self::EMOJI_SUGGESTIONS;
    }

    public function defaultAddressHelp(): string
    {
        return __('Usually the address of the home or the building. The locations inside it use it too.');
    }

    /**
     * What the address field says for each parent the form may pick, keyed by the parent's uuid: the
     * address the location falls back on when its own is left empty.
     *
     * @return array<string, string>
     */
    public function addressHelpByParent(): array
    {
        $help = [];

        foreach ($this->locationTree()->ordered() as $location) {
            $holder = $this->locationTree()->addressHolder($location);

            $help[$location->uuid] = $holder instanceof Location
                ? __('Leave it empty to use the address of :name: :address.', [
                    'name' => $holder->name,
                    'address' => $this->oneLine((string) $holder->address),
                ])
                : $this->defaultAddressHelp();
        }

        return $help;
    }

    /** @return list<array<string, mixed>> */
    private function rowsInside(?Location $parent, int $level): array
    {
        return $this->locationTree()->childrenOf($parent)
            ->map(fn (Location $location): array => $this->row($location, $level))
            ->all();
    }

    /** @return array<string, mixed> */
    private function row(Location $location, int $level): array
    {
        $deletion = $this->user->inspect('delete', $location);

        return [
            'uuid' => $location->uuid,
            'name' => $location->name,
            'emoji' => $location->emoji,
            'icon' => $level === 1 ? ($location->address === null ? 'place' : 'home') : 'layers',
            'level' => $level,
            'line' => $this->line($location),
            'editUrl' => route('settings.locations.edit', $location),
            'newInsideUrl' => route('settings.locations.children.new', $location),
            'destroyUrl' => route('settings.locations.destroy', $location),
            'mayBeDeleted' => $deletion->allowed(),
            'deleteReason' => $deletion->denied() ? (string) $deletion->message() : '',
            'children' => $this->rowsInside(parent: $location, level: $level + 1),
        ];
    }

    private function line(Location $location): string
    {
        $holder = $this->locationTree()->addressHolder($location);

        $parts = [match (true) {
            $holder?->id === $location->id => $this->oneLine((string) $location->address),
            $holder instanceof Location => __('At the address of :name', ['name' => $holder->name]),
            default => __('No address'),
        }];

        $count = $this->locationTree()->childCount($location);

        if ($count > 0) {
            $parts[] = trans_choice(':count location inside|:count locations inside', $count);
        }

        return implode(' · ', $parts);
    }

    private function oneLine(string $address): string
    {
        return (string) preg_replace('/\s*\R\s*/', ', ', mb_trim($address));
    }

    private function locationTree(): LocationTree
    {
        return $this->tree ??= LocationTree::forAccount($this->user->account_id);
    }
}
