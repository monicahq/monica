<?php

declare(strict_types=1);

namespace App\ViewModels;

use App\Helpers\ChangelogEntries;
use App\Models\User;

class ChangelogLinkViewModel
{
    private ?int $unseenCount = null;

    public function __construct(
        private readonly User $user,
    ) {}

    public function unseenCount(): int
    {
        return $this->unseenCount ??= count(app(ChangelogEntries::class)->unseenBy($this->user));
    }

    public function label(): string
    {
        if ($this->unseenCount() === 0) {
            return __('What’s new');
        }

        return trans_choice('What’s new, :count change you have not seen|What’s new, :count changes you have not seen', $this->unseenCount());
    }
}
