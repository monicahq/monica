<?php

namespace App\Http\Controllers\Settings\Notifications;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\User\NotificationChannels\ToggleUserNotificationChannel;
use App\Http\Controllers\Settings\Notifications\ViewHelpers\NotificationsIndexViewHelper;

class NotificationsToggleController extends Controller
{
    public function update(Request $request, int $userNotificationChannelId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'user_notification_channel_id' => $userNotificationChannelId,
        ];

        $channel = (new ToggleUserNotificationChannel)->execute($data);

        return response()->json([
            'data' => NotificationsIndexViewHelper::dtoEmail($channel, Auth::user()),
        ], 200);
    }
}
