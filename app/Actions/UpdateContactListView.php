<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ContactGroupingEnum;
use App\Enums\ContactSortEnum;
use App\Enums\SortDirectionEnum;
use App\Models\User;

/**
 * Remember how somebody was last looking at the people of a vault, so that the next visit opens on
 * the same view. Nothing is written when the three choices already match, which is what keeps paging
 * through a long list from writing once per page.
 */
class UpdateContactListView
{
    public function __construct(
        private readonly User $user,
        private readonly ContactGroupingEnum $grouping,
        private readonly ContactSortEnum $sort,
        private readonly SortDirectionEnum $direction,
    ) {}

    public function execute(): User
    {
        if ($this->isAlreadyRecorded()) {
            return $this->user;
        }

        $this->user->contact_list_grouping = $this->grouping->value;
        $this->user->contact_list_sort = $this->sort->value;
        $this->user->contact_list_direction = $this->direction->value;
        $this->user->save();

        return $this->user;
    }

    private function isAlreadyRecorded(): bool
    {
        return $this->user->contact_list_grouping === $this->grouping->value
            && $this->user->contact_list_sort === $this->sort->value
            && $this->user->contact_list_direction === $this->direction->value;
    }
}
