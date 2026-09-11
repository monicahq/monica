<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Cut a message down to what a channel will carry, at a sentence boundary
 * wherever there is one.
 *
 * A text message provider will take 140 characters and silently split or drop
 * the rest, so something has to do the cutting. Cutting mid word reads as a
 * fault; ending on the last full sentence that fits reads as a short message.
 * Where not even one sentence fits, the cut falls back to the last space, and
 * then to the hard limit, because a very long first sentence still has to fit.
 */
class MessageShortener
{
    private const string ELLIPSIS = '…';

    public static function to(string $message, ?int $limit): string
    {
        $message = trim($message);

        if ($limit === null || mb_strlen($message) <= $limit) {
            return $message;
        }

        $head = mb_substr($message, 0, $limit);

        foreach (['. ', '! ', '? ', ".\n", "!\n", "?\n"] as $ending) {
            $at = mb_strrpos($head, $ending);

            if ($at !== false && $at > 0) {
                return rtrim(mb_substr($head, 0, $at + 1));
            }
        }

        if (mb_substr($message, $limit, 1) === '' || self::endsASentence($message, $limit)) {
            return rtrim($head);
        }

        return self::atTheLastWord($head, $limit);
    }

    private static function endsASentence(string $message, int $limit): bool
    {
        return in_array(mb_substr($message, $limit - 1, 1), ['.', '!', '?'], true);
    }

    private static function atTheLastWord(string $head, int $limit): string
    {
        $room = $limit - mb_strlen(self::ELLIPSIS);
        $shorter = mb_substr($head, 0, max(1, $room));
        $space = mb_strrpos($shorter, ' ');

        if ($space === false || $space === 0) {
            return rtrim($shorter).self::ELLIPSIS;
        }

        return rtrim(mb_substr($shorter, 0, $space)).self::ELLIPSIS;
    }
}
