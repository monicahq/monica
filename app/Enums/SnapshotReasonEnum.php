<?php

declare(strict_types=1);

namespace App\Enums;

enum SnapshotReasonEnum: string
{
    case TemplateChange = 'template_change';
    case Merge = 'merge';
    case Manual = 'manual';
    case Import = 'import';
    case Restore = 'restore';
    case Api = 'api';

    public function isAutomatic(): bool
    {
        return in_array($this, [self::TemplateChange, self::Merge, self::Import, self::Restore], true);
    }
}
