<?php

namespace App\Domains\Settings\ManageNotificationChannels\Web\Controllers;

use App\Domains\Settings\ManageNotificationChannels\Services\ScheduleAllContactRemindersForNotificationChannel;
use App\Http\Controllers\Controller;
use App\Models\UserNotificationChannel;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TelegramWebhookController extends Controller
{
    /**
     * Store Telegram Chat ID from telegram webhook message.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $messageText = $request->message['text'];
        } catch (Exception $e) { /** @phpstan-ignore-line */
            return response()->json([
                'code' => $e->getCode(),
                'message' => 'Accepted with error: \''.$e->getMessage().'\'',
            ], 202);
        }

        // check if the message matches the expected pattern.
        // if the message does not match the pattern, then we return a 202 response
        // so telegram will stop trying to send the message.
        $message = Str::of($messageText);
        if (! $message->test('/^\/start\s[A-Za-z0-9-]{36}$/')) {
            return response('Accepted', 202);
        }

        // Cleanup the string
        $verificationKey = $message->remove('/start ')->rtrim();

        // Get Telegram ID from the request.
        $chatId = $request->message['chat']['id'];

        // Get the User ID from the cache using the temp code as key.
        try {
            $channel = UserNotificationChannel::where('verification_token', $verificationKey)->firstOrFail();
        } catch (Exception) {
            return response('Error', 404);
        }

        // Update user with the Telegram Chat ID
        $channel->content = $chatId;
        $channel->active = true;
        $channel->save();

        (new ScheduleAllContactRemindersForNotificationChannel)->execute([
            'account_id' => $channel->user->account_id,
            'author_id' => $channel->user->id,
            'user_notification_channel_id' => $channel->id,
        ]);

        return response('Success', 200);
    }
}
