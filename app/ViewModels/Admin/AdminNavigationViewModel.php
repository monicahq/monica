<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use App\Enums\AgentFeedbackStatusEnum;
use App\Enums\ChangelogCommentStatusEnum;
use App\Enums\SupportRequestStatusEnum;
use App\Models\AgentFeedbackReport;
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

    public function newAgentFeedbackCount(): int
    {
        return AgentFeedbackReport::query()->where('status', AgentFeedbackStatusEnum::New->value)->count();
    }
}
