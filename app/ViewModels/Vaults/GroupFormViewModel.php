<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\DatePrecisionEnum;
use App\Models\Contact;
use App\Models\Group;
use App\Models\User;
use App\Models\Vault;

class GroupFormViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly ?Group $group = null,
        private readonly ?Contact $contact = null,
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
        return $this->group instanceof Group;
    }

    public function title(): string
    {
        return $this->group instanceof Group ? __('Edit :name', ['name' => $this->group->name]) : __('New group');
    }

    public function action(): string
    {
        if ($this->group instanceof Group) {
            return route('groups.update', ['vault' => $this->vault, 'group' => $this->group]);
        }

        if ($this->contact instanceof Contact) {
            return route('contacts.groups.create', ['vault' => $this->vault, 'contact' => $this->contact]);
        }

        return route('groups.create', $this->vault);
    }

    public function cancelUrl(): string
    {
        if ($this->group instanceof Group) {
            return route('groups.show', ['vault' => $this->vault, 'group' => $this->group]);
        }

        if ($this->contact instanceof Contact) {
            return route('contacts.groups.edit', ['vault' => $this->vault, 'contact' => $this->contact]);
        }

        return route('groups.index', $this->vault);
    }

    public function destroyAction(): ?string
    {
        if (! $this->group instanceof Group) {
            return null;
        }

        return route('groups.destroy', ['vault' => $this->vault, 'group' => $this->group]);
    }

    public function groupName(): string
    {
        return $this->group->name ?? '';
    }

    /** @return array{name: string, description: string, startedOn: string, startedPrecision: string, endedOn: string, endedPrecision: string} */
    public function values(): array
    {
        return [
            'name' => old('name', $this->group->name ?? ''),
            'description' => old('description', $this->group->description ?? ''),
            'startedOn' => old('started_on', $this->group?->started_on?->format('Y-m-d') ?? ''),
            'startedPrecision' => old('started_precision', $this->knownPrecision($this->group?->started_precision)),
            'endedOn' => old('ended_on', $this->group?->ended_on?->format('Y-m-d') ?? ''),
            'endedPrecision' => old('ended_precision', $this->knownPrecision($this->group?->ended_precision)),
        ];
    }

    /** @return array<string, string> */
    public function precisions(): array
    {
        return self::precisionChoices();
    }

    /**
     * How precisely a date the form asks for may be known. A date that is not known at all is a
     * date left empty, so `unknown` is never offered.
     *
     * @return array<string, string>
     */
    public static function precisionChoices(): array
    {
        return [
            DatePrecisionEnum::Day->value => __('Known to the day'),
            DatePrecisionEnum::Month->value => __('Known to the month'),
            DatePrecisionEnum::Year->value => __('Known to the year'),
            DatePrecisionEnum::Decade->value => __('Known to the decade'),
        ];
    }

    private function knownPrecision(?DatePrecisionEnum $precision): string
    {
        return $precision === null || $precision === DatePrecisionEnum::Unknown ? DatePrecisionEnum::Day->value : $precision->value;
    }
}
