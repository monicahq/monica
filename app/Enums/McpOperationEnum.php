<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum McpOperationEnum: string implements Translatable
{
    case Read = 'read';
    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';

    public function label(): string
    {
        return match ($this) {
            self::Read => 'Read',
            self::Create => 'Create',
            self::Update => 'Update',
            self::Delete => 'Delete',
        };
    }

    public static function fromHttpMethod(string $method): ?self
    {
        $verb = strtoupper(explode('|', $method)[0]);

        return match ($verb) {
            'GET' => self::Read,
            'POST' => self::Create,
            'PUT', 'PATCH' => self::Update,
            'DELETE' => self::Delete,
            default => null,
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
