<?php

declare(strict_types=1);

namespace App\Enums;

enum ChangelogCommentStatusEnum: string
{
    case New = 'new';
    case Reviewed = 'reviewed';
    case Hidden = 'hidden';
}
