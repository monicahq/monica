<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Account;
use App\Models\Field;

/**
 * Make what an assistant sent, or what it got back, safe to keep in the MCP history.
 *
 * Two things are masked before anything is written. A key named like a secret
 * loses its value wherever it sits, since an assistant may pass a password or a
 * token in any argument. And the custom values a life event tool takes keep
 * their value only when the field is one of the account's and is not marked
 * sensitive: an unknown field id is masked too, because nothing proves it is
 * harmless. What is masked is replaced, never dropped, so the reader still sees
 * that something was there.
 *
 * The encoded result is then capped, because one call may carry a whole
 * narrative, and the cut is flagged rather than hidden.
 */
class McpPayload
{
    public const string MASK = '[masked]';

    public const int MAXIMUM_BYTES = 16384;

    private const string SECRET_KEY_PATTERN = '/password|token|secret|authorization|api_?key|cookie/i';

    /** @var list<string> */
    private const array TOOLS_TAKING_FIELD_VALUES = ['create-life-event', 'update-life-event'];

    /**
     * @param  array<array-key, mixed>  $payload
     * @return array<array-key, mixed>
     */
    public static function mask(array $payload, Account $account, string $tool): array
    {
        $payload = self::maskSecretKeys($payload);

        if (! in_array($tool, self::TOOLS_TAKING_FIELD_VALUES, true) || ! is_array($payload['fields'] ?? null)) {
            return $payload;
        }

        $readableFieldIds = Field::query()
            ->where('account_id', $account->id)
            ->whereIn('id', array_filter(array_keys($payload['fields']), is_int(...)))
            ->where('is_sensitive', false)
            ->pluck('id')
            ->all();

        foreach (array_keys($payload['fields']) as $fieldId) {
            if (! in_array($fieldId, $readableFieldIds, true)) {
                $payload['fields'][$fieldId] = self::MASK;
            }
        }

        return $payload;
    }

    /**
     * @param  array<array-key, mixed>  $payload
     * @return array{text: string, truncated: bool}
     */
    public static function encode(array $payload): array
    {
        $text = (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);

        if (strlen($text) <= self::MAXIMUM_BYTES) {
            return ['text' => $text, 'truncated' => false];
        }

        return ['text' => mb_strcut($text, 0, self::MAXIMUM_BYTES, 'UTF-8'), 'truncated' => true];
    }

    /**
     * @param  array<array-key, mixed>  $payload
     * @return array<array-key, mixed>
     */
    private static function maskSecretKeys(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (is_string($key) && preg_match(self::SECRET_KEY_PATTERN, $key) === 1) {
                $payload[$key] = self::MASK;

                continue;
            }

            if (is_array($value)) {
                $payload[$key] = self::maskSecretKeys($value);
            }
        }

        return $payload;
    }
}
