<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum McpCallResultEnum: string implements Translatable
{
    case Succeeded = 'succeeded';
    case Refused = 'refused';
    case InvalidParameters = 'invalid_parameters';
    case Error = 'error';
    case Interrupted = 'interrupted';

    public function label(): string
    {
        return match ($this) {
            self::Succeeded => 'Succeeded',
            self::Refused => 'Refused',
            self::InvalidParameters => 'Invalid parameters',
            self::Error => 'Error',
            self::Interrupted => 'Interrupted',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Succeeded => 'success',
            self::Refused, self::InvalidParameters => 'warning',
            self::Error => 'danger',
            self::Interrupted => 'neutral',
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
