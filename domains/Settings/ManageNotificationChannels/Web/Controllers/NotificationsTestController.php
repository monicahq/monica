<?php

namespace App\Settings\ManageNotificationChannels\Web\Controllers;

use App\Http\Controllers\Controller;
use App\Settings\ManageNotificationChannels\Services\SendTestEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationsTestController extends Controller
{
    public function store(Request $request, int $userNotificationChannelId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'user_notification_channel_id' => $userNotificationChannelId,
        ];

        (new SendTestEmail())->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
