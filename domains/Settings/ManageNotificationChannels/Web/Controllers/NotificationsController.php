<?php

namespace App\Settings\ManageNotificationChannels\Web\Controllers;

use App\Http\Controllers\Controller;
use App\Models\UserNotificationChannel;
use App\Settings\ManageNotificationChannels\Services\CreateUserNotificationChannel;
use App\Settings\ManageNotificationChannels\Services\DestroyUserNotificationChannel;
use App\Settings\ManageNotificationChannels\Web\ViewHelpers\NotificationsIndexViewHelper;
use App\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

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
        $time = $request->input('hours').':'.$request->input('minutes');

        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'label' => $request->input('label'),
            'type' =>  UserNotificationChannel::TYPE_EMAIL,
            'content' =>  $request->input('content'),
            'verify_email' => true,
            'preferred_time' => $time,
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
