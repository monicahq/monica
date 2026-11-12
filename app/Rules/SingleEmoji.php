<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Override;

class SingleEmoji implements ValidationRule
{
    /** @param  Closure(string, string|null=): PotentiallyTranslatedString  $fail */
    #[Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! self::isOneEmoji($value)) {
            $fail(__('Pick one emoji, or none.'));
        }
    }

    /*
     * One grapheme, so a family, a flag or a skin tone counts as one, and something in it that is drawn
     * as a picture: a pictographic character, a regional indicator of a flag, or the mark of a keycap.
     */
    private static function isOneEmoji(string $value): bool
    {
        if (preg_match('/^\X$/u', $value) !== 1) {
            return false;
        }

        return preg_match('/[\p{Extended_Pictographic}\x{1F1E6}-\x{1F1FF}\x{20E3}]/u', $value) === 1;
    }
}
