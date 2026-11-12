<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use App\Enums\SupportRequestStatusEnum;
use App\Models\SupportRequest;

class AdminNavigationViewModel
{
    public function supportToHandleCount(): int
    {
        return SupportRequest::query()->where('status', SupportRequestStatusEnum::ToHandle->value)->count();
    }
}
