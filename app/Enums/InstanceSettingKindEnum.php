<?php

declare(strict_types=1);

namespace App\Enums;

enum InstanceSettingKindEnum: string
{
    case Integer = 'integer';
    case Boolean = 'boolean';
    case Url = 'url';
    case Text = 'text';
    case Secret = 'secret';

    /**
     * The rules a submitted value is held to. A secret is nullable because an
     * empty submission means leave it alone, never clear it, so a blank field
     * can never silently drop a credential.
     *
     * @return list<string>
     */
    public function rules(): array
    {
        return match ($this) {
            self::Integer => ['required', 'integer', 'min:1'],
            self::Boolean => ['required', 'boolean'],
            self::Url => ['required', 'url', 'max:255'],
            self::Text => ['required', 'string', 'max:255'],
            self::Secret => ['nullable', 'string', 'max:1024'],
        };
    }

    public function isSecret(): bool
    {
        return $this === self::Secret;
    }

    /**
     * Read a stored string back as the type the configuration key expects, so a
     * reader that type hints an int is handed one.
     */
    public function cast(?string $value): string|int|bool|null
    {
        if ($value === null) {
            return null;
        }

        return match ($this) {
            self::Integer => (int) $value,
            self::Boolean => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            default => $value,
        };
    }
}
