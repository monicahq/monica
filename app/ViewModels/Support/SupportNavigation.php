<?php

declare(strict_types=1);

namespace App\ViewModels\Support;

use App\Enums\SupportRequestStatusEnum;
use App\Models\SupportRequest;
use App\Models\User;

class SupportNavigation
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function openRequestCount(): int
    {
        return once(fn (): int => SupportRequest::query()
            ->where('account_id', $this->user->account_id)
            ->where('status', '!=', SupportRequestStatusEnum::Solved->value)
            ->count());
    }
}
