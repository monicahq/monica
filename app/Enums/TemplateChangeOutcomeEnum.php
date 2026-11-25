<?php

declare(strict_types=1);

namespace App\Enums;

enum TemplateChangeOutcomeEnum: string
{
    case Moved = 'moved';
    case Skipped = 'skipped';
    case Failed = 'failed';
}
