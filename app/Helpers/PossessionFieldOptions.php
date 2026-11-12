<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\PossessionFieldKindEnum;
use Illuminate\Validation\ValidationException;

/**
 * The choices of a possession field. Only a select field keeps them: they are trimmed, blank ones
 * are dropped, and the rest must be distinct, short enough and few enough.
 */
class PossessionFieldOptions
{
    public const int MAX_OPTIONS = 200;

    public const int MAX_LENGTH = 255;

    /**
     * @param  array<int, mixed>|null  $options
     * @return list<string>|null
     */
    public static function clean(PossessionFieldKindEnum $kind, ?array $options): ?array
    {
        if ($kind !== PossessionFieldKindEnum::Select) {
            return null;
        }

        $cleaned = array_map(
            fn (mixed $option): string => TextSanitizer::plainText((string) $option),
            $options ?? [],
        );

        return array_values(array_filter($cleaned, fn (string $option): bool => $option !== ''));
    }

    /**
     * @param  list<string>|null  $options
     *
     * @throws ValidationException
     */
    public static function validate(?array $options): void
    {
        if ($options === null) {
            return;
        }

        if (count($options) > self::MAX_OPTIONS) {
            throw ValidationException::withMessages([
                'options' => trans_choice('[0,*]A field holds at most :count choices.', self::MAX_OPTIONS),
            ]);
        }

        foreach ($options as $option) {
            if (mb_strlen($option) > self::MAX_LENGTH) {
                throw ValidationException::withMessages([
                    'options' => __('A choice holds at most :max characters.', ['max' => self::MAX_LENGTH]),
                ]);
            }
        }

        if (count(array_unique(array_map(mb_strtolower(...), $options))) !== count($options)) {
            throw ValidationException::withMessages([
                'options' => __('Each choice can appear only once.'),
            ]);
        }
    }
}
