<?php

declare(strict_types=1);

namespace App\Enums;

enum VersionStatusEnum: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Retired = 'retired';

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    public function isOffered(): bool
    {
        return $this === self::Published;
    }
}
