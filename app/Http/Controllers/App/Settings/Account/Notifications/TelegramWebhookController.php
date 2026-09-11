<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Notifications;

use App\Actions\CompleteChannelConnection;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Where Telegram tells us which conversation an account has opened.
 *
 * Nobody is signed in here. Telegram proves it is Telegram with the secret
 * given to it when the webhook was registered, and the message proves which
 * account is connecting by quoting back the token from the link they followed.
 */
class TelegramWebhookController extends Controller
{
    public function handleWebhook(Request $request): JsonResponse
    {
        $secretToken = $request->header('X-Telegram-Bot-Api-Secret-Token');
        abort_unless($secretToken === config('services.telegram.webhook_secret'), 403);

        $token = $this->tokenIn($request);

        if ($token === null) {
            return response()->json(['status' => 'ignored'], 202);
        }

        $chatId = $request->input('message.chat.id');

        if (! is_int($chatId) && ! is_string($chatId)) {
            return response()->json(['status' => 'ignored'], 202);
        }

        $channel = new CompleteChannelConnection(
            verificationToken: $token,
            destination: (string) $chatId,
        )->execute();

        if ($channel === null) {
            return response()->json(['status' => 'unknown'], 404);
        }

        Log::info('Telegram channel connected', ['channel' => $channel->destination]);

        return response()->json(['status' => 'connected']);
    }

    /**
     * The token out of a `/start` message, or null for anything else Telegram
     * sends us. A bot receives every message in the conversation, and all but
     * this one are somebody else's business.
     */
    private function tokenIn(Request $request): ?string
    {
        $text = $request->input('message.text');

        if (! is_string($text)) {
            return null;
        }

        if (preg_match('/^\/start\s+([A-Za-z0-9]{64})$/', trim($text), $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }
}
