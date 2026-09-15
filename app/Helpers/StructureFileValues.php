<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Exceptions\StructureFileIsNotReadable;
use Illuminate\Support\Str;

/**
 * One value out of a pasted structure file: read it, or refuse the whole file.
 *
 * Somebody pastes a template or a section into the import screen
 * ({@see StructureFileReader} is what that screen calls). Every method here asks
 * one question about one position in that file, and answers either with the value
 * the reader wanted or with a refusal that ends the import. The refusals are the
 * sentences the person pasting reads, so they name the position in the file rather
 * than the rule that failed: ten sentences cover every position in the document.
 *
 * They are not validation rules, because they also read. `nullableString()` hands
 * back trimmed words and calls an empty string nothing at all, `integer()` supplies
 * the number the reader chose when the file says nothing, and `word()` reads either
 * the label the file wrote or the translation of the key it pointed at.
 */
class StructureFileValues
{
    /** A key: lowercase letters and numbers, joined by dashes or underscores. */
    public const string IDENTIFIER = '/^[a-z0-9]+([_-][a-z0-9]+)*$/';

    private const string LOCALE = '/^[a-z]{2,3}(_[A-Z]{2})?$/';

    /**
     * @param  list<string>  $allowed
     * @param  list<string>  $required
     * @return array<string, mixed>
     */
    public static function object(mixed $node, string $path, array $allowed, array $required): array
    {
        if (! is_array($node)) {
            self::refuse(__('The file holds something other than a block at :path.', ['path' => $path]));
        }

        foreach (array_keys($node) as $key) {
            if (! in_array((string) $key, $allowed, true)) {
                self::refuse(__('The file holds a name this version does not know at :path, so what it means cannot be read.', [
                    'path' => $path.'.'.self::safe((string) $key),
                ]));
            }
        }

        foreach ($required as $key) {
            if (($node[$key] ?? null) === null) {
                self::refuse(__('The file says nothing about :path, which it has to.', ['path' => "{$path}.{$key}"]));
            }
        }

        return $node;
    }

    /** @return list<mixed> */
    public static function collection(mixed $node, string $path, int $ceiling): array
    {
        if (! is_array($node) || ! array_is_list($node)) {
            self::refuse(__('The file holds something other than a list at :path.', ['path' => $path]));
        }

        if (count($node) > $ceiling) {
            self::refuse(trans_choice(
                'The file holds more than :count thing at :path.|The file holds more than :count things at :path.',
                $ceiling,
                ['path' => $path],
            ));
        }

        return $node;
    }

    /** @param  array<string, mixed>  $node */
    public static function string(array $node, string $key, string $path, int $max, ?string $whenMissing = null): string
    {
        $value = self::nullableString($node, $key, $path, $max);

        if ($value !== null) {
            return $value;
        }

        if ($whenMissing !== null) {
            return $whenMissing;
        }

        self::refuse(__('The file says nothing about :path, which it has to.', ['path' => "{$path}.{$key}"]));
    }

    /** @param  array<string, mixed>  $node */
    public static function nullableString(array $node, string $key, string $path, int $max): ?string
    {
        $value = $node[$key] ?? null;

        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            self::refuse(__('The file holds something other than words at :path.', ['path' => "{$path}.{$key}"]));
        }

        $value = mb_trim($value);

        if ($value === '') {
            return null;
        }

        if (mb_strlen($value) > $max) {
            self::refuse(trans_choice(
                'The file holds more than :count character at :path, which is more than can be stored.|The file holds more than :count characters at :path, which is more than can be stored.',
                $max,
                ['path' => "{$path}.{$key}"],
            ));
        }

        return $value;
    }

    /** @param  array<string, mixed>  $node */
    public static function identifier(array $node, string $key, string $path, int $max): ?string
    {
        $value = self::nullableString($node, $key, $path, $max);

        if ($value === null) {
            return null;
        }

        if (preg_match(self::IDENTIFIER, $value) !== 1) {
            self::refuse(__('The file holds something other than a key at :path. A key is lowercase letters and numbers, joined by dashes or underscores.', [
                'path' => "{$path}.{$key}",
            ]));
        }

        return $value;
    }

    /** @param  array<string, mixed>  $node */
    public static function integer(array $node, string $key, string $path, int $min, int $max, ?int $whenMissing = null): int
    {
        $value = self::nullableInteger($node, $key, $path, $min, $max);

        if ($value !== null) {
            return $value;
        }

        if ($whenMissing !== null) {
            return $whenMissing;
        }

        self::refuse(__('The file says nothing about :path, which it has to.', ['path' => "{$path}.{$key}"]));
    }

    /** @param  array<string, mixed>  $node */
    public static function nullableInteger(array $node, string $key, string $path, int $min, int $max): ?int
    {
        $value = $node[$key] ?? null;

        if ($value === null) {
            return null;
        }

        if (! is_int($value) || $value < $min || $value > $max) {
            self::refuse(__('The file holds something other than a whole number between :min and :max at :path.', [
                'min' => $min,
                'max' => $max,
                'path' => "{$path}.{$key}",
            ]));
        }

        return $value;
    }

    /** @param  array<string, mixed>  $node */
    public static function boolean(array $node, string $key, string $path, bool $whenMissing = false): bool
    {
        $value = $node[$key] ?? null;

        if ($value === null) {
            return $whenMissing;
        }

        if (! is_bool($value)) {
            self::refuse(__('The file holds something other than a yes or a no at :path.', ['path' => "{$path}.{$key}"]));
        }

        return $value;
    }

    /** @param  array<string, mixed>  $node */
    public static function locale(array $node, string $path): ?string
    {
        $locale = self::nullableString($node, 'locale', $path, 12);

        if ($locale === null || preg_match(self::LOCALE, $locale) !== 1) {
            return null;
        }

        return AvailableLocales::has($locale) ? $locale : null;
    }

    /** @param  array<string, mixed>  $node */
    public static function word(array $node, string $key, string $translationKey, string $path, int $max): string
    {
        $literal = self::nullableString($node, $key, $path, $max);

        if ($literal !== null) {
            return $literal;
        }

        $reference = self::nullableString($node, $translationKey, $path, $max);

        if ($reference === null) {
            self::refuse(__('The file says nothing about :path, which it has to.', ['path' => "{$path}.{$key}"]));
        }

        $translated = __($reference);

        if (! is_string($translated) || mb_trim($translated) === '') {
            return $reference;
        }

        return Str::limit(mb_trim($translated), $max, '');
    }

    public static function safe(string $word): string
    {
        return preg_match('/^[A-Za-z0-9_.-]{1,40}$/', $word) === 1 ? $word : '…';
    }

    public static function refuse(string $sentence): never
    {
        throw new StructureFileIsNotReadable($sentence);
    }
}
