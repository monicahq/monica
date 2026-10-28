<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum AccountImportStatusEnum: string implements Translatable
{
    case Reading = 'reading';
    case Refused = 'refused';
    case Read = 'read';
    case Writing = 'writing';
    case RollingBack = 'rolling_back';
    case RolledBack = 'rolled_back';
    case RollbackFailed = 'rollback_failed';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Reading => 'Reading the file',
            self::Refused => 'Refused',
            self::Read => 'Ready to start',
            self::Writing => 'Importing',
            self::RollingBack => 'Being undone',
            self::RolledBack => 'Undone',
            self::RollbackFailed => 'Could not be undone',
            self::Completed => 'Completed',
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
