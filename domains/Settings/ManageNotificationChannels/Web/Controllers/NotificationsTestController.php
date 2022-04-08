<?php

namespace App\Settings\ManageNotificationChannels\Web\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Settings\ManageNotificationChannels\Services\SendTestEmail;

class NotificationsTestController extends Controller
{
    public function store(Request $request, int $userNotificationChannelId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'user_notification_channel_id' => $userNotificationChannelId,
        ];

        (new SendTestEmail)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
