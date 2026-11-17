<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use App\Enums\ChangelogCommentStatusEnum;
use App\Enums\SupportRequestStatusEnum;
use App\Models\ChangelogComment;
use App\Models\SupportRequest;

class AdminNavigationViewModel
{
    public function supportToHandleCount(): int
    {
        return SupportRequest::query()->where('status', SupportRequestStatusEnum::ToHandle->value)->count();
    }

    public function changelogToReviewCount(): int
    {
        return ChangelogComment::query()->where('status', ChangelogCommentStatusEnum::New->value)->count();
    }
}
