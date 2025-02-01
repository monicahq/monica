<?php

namespace App\Domains\Settings\ManageNotificationChannels\Web\Controllers;

use App\Domains\Settings\ManageNotificationChannels\Services\SendTestEmail;
use App\Domains\Settings\ManageNotificationChannels\Services\SendTestTelegramNotification;
use App\Http\Controllers\Controller;
use App\Models\UserNotificationChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationsTestController extends Controller
{
    public function store(Request $request, int $userNotificationChannelId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'user_notification_channel_id' => $userNotificationChannelId,
        ];

        $channel = UserNotificationChannel::find($userNotificationChannelId);

        if ($channel->type == UserNotificationChannel::TYPE_EMAIL) {
            (new SendTestEmail)->execute($data);
        }

        if ($channel->type == UserNotificationChannel::TYPE_TELEGRAM) {
            (new SendTestTelegramNotification)->execute($data);
        }

        return response()->json([
            'data' => true,
        ], 200);
    }
}
