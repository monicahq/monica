<?php

declare(strict_types=1);

namespace App\ViewModels\Support;

use App\Models\SupportMessage;
use App\Models\SupportRequest;
use App\Models\User;

class SupportConfirmationViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly SupportRequest $supportRequest,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function navigation(): SupportNavigation
    {
        return new SupportNavigation($this->user);
    }

    public function supportRequest(): SupportRequest
    {
        return $this->supportRequest;
    }

    public function email(): string
    {
        return $this->user->email;
    }

    public function categoryLabel(): string
    {
        return __($this->supportRequest->category->shortLabel());
    }

    public function sentAt(): string
    {
        return $this->supportRequest->created_at->setTimezone($this->user->timezone)->isoFormat('LLL');
    }

    public function filesLine(): string
    {
        /** @var SupportMessage|null $first */
        $first = $this->supportRequest->visibleMessages()->with('attachments')->first();

        $names = $first?->attachments->pluck('original_name')->all() ?? [];

        if ($names === []) {
            return __('None');
        }

        return implode(', ', $names);
    }
}
