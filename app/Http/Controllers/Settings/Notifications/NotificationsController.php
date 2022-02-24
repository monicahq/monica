<?php

namespace App\Http\Controllers\Settings\Notifications;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\UserNotificationChannel;
use App\Http\Controllers\Vault\ViewHelpers\VaultIndexViewHelper;
use App\Services\User\NotificationChannels\CreateUserNotificationChannel;
use App\Services\User\NotificationChannels\DestroyUserNotificationChannel;
use App\Http\Controllers\Settings\Notifications\ViewHelpers\NotificationsIndexViewHelper;

class NotificationsController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/Notifications/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData(),
            'data' => NotificationsIndexViewHelper::data(Auth::user()),
        ]);
    }

    public function store(Request $request)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'label' => $request->input('label'),
            'type' =>  UserNotificationChannel::TYPE_EMAIL,
            'content' =>  $request->input('content'),
            'verify_email' => true,
        ];

        $channel = (new CreateUserNotificationChannel)->execute($data);

        return response()->json([
            'data' => NotificationsIndexViewHelper::dtoEmail($channel, Auth::user()),
        ], 200);
    }

    public function destroy(Request $request, int $channelId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'user_notification_channel_id' => $channelId,
        ];

        (new DestroyUserNotificationChannel)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
