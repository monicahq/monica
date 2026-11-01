<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\DatePrecisionEnum;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Models\Vault;

class GroupMemberFormViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Group $group,
        private readonly ?GroupMember $membership = null,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function vault(): Vault
    {
        return $this->vault;
    }

    public function groupName(): string
    {
        return $this->group->name;
    }

    public function groupUrl(): string
    {
        return route('groups.show', ['vault' => $this->vault, 'group' => $this->group]);
    }

    public function isEditing(): bool
    {
        return $this->membership instanceof GroupMember;
    }

    public function title(): string
    {
        return $this->membership instanceof GroupMember
            ? __(':name in :group', ['name' => $this->membership->contact->name, 'group' => $this->group->name])
            : __('Add somebody to :group', ['group' => $this->group->name]);
    }

    public function action(): string
    {
        return $this->membership instanceof GroupMember
            ? route('groups.members.update', ['vault' => $this->vault, 'group' => $this->group, 'membership' => $this->membership])
            : route('groups.members.create', ['vault' => $this->vault, 'group' => $this->group]);
    }

    public function destroyAction(): ?string
    {
        return $this->membership instanceof GroupMember
            ? route('groups.members.destroy', ['vault' => $this->vault, 'group' => $this->group, 'membership' => $this->membership])
            : null;
    }

    public function candidatesUrl(): string
    {
        return route('groups.memberCandidates.index', ['vault' => $this->vault, 'group' => $this->group]);
    }

    /** @return array{role: string, joinedOn: string, joinedPrecision: string, leftOn: string, leftPrecision: string} */
    public function values(): array
    {
        return [
            'role' => old('role', $this->membership->role ?? ''),
            'joinedOn' => old('joined_on', $this->membership?->joined_on?->format('Y-m-d') ?? ''),
            'joinedPrecision' => old('joined_precision', $this->knownPrecision($this->membership?->joined_precision)),
            'leftOn' => old('left_on', $this->membership?->left_on?->format('Y-m-d') ?? ''),
            'leftPrecision' => old('left_precision', $this->knownPrecision($this->membership?->left_precision)),
        ];
    }

    /** @return array<string, string> */
    public function precisions(): array
    {
        return GroupFormViewModel::precisionChoices();
    }

    private function knownPrecision(?DatePrecisionEnum $precision): string
    {
        return $precision === null || $precision === DatePrecisionEnum::Unknown ? DatePrecisionEnum::Day->value : $precision->value;
    }
}
