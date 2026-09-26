<?php

declare(strict_types=1);

namespace App\Actions;

use App\Helpers\TelegramWebhook;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

/**
 * Ask Telegram where it currently sends what people say to the bot, and whether
 * delivering there last failed. It changes nothing, here or at Telegram.
 */
class CheckTelegramWebhook
{
    /** @return array{url: ?string, ok: bool, matches: bool, lastError: ?string} */
    public function execute(): array
    {
        $this->validate();

        return $this->ask();
    }

    private function validate(): void
    {
        if (blank(config('services.telegram.token'))) {
            throw ValidationException::withMessages(['telegram' => __('Set the bot token first.')]);
        }
    }

    /** @return array{url: ?string, ok: bool, matches: bool, lastError: ?string} */
    private function ask(): array
    {
        try {
            $response = Http::connectTimeout(3)
                ->timeout(6)
                ->get('https://api.telegram.org/bot'.config('services.telegram.token').'/getWebhookInfo');
        } catch (ConnectionException) {
            throw ValidationException::withMessages([
                'telegram' => __('Telegram could not be reached. Try again in a moment.'),
            ]);
        }

        if ($response->failed()) {
            throw ValidationException::withMessages([
                'telegram' => __('Telegram refused it: :reason', [
                    'reason' => $response->json('description', $response->body()),
                ]),
            ]);
        }

        $ok = $response->json('ok') === true;

        $url = $response->json('result.url');
        $url = is_string($url) && $url !== '' ? $url : null;

        return [
            'url' => $url,
            'ok' => $ok,
            'matches' => $url === TelegramWebhook::incomingAddress(),
            'lastError' => $response->json('result.last_error_message'),
        ];
    }
}
