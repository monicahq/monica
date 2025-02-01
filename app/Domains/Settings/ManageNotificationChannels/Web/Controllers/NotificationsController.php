<?php

namespace App\Domains\Settings\ManageNotificationChannels\Web\Controllers;

use App\Domains\Settings\ManageNotificationChannels\Services\CreateUserNotificationChannel;
use App\Domains\Settings\ManageNotificationChannels\Services\DestroyUserNotificationChannel;
use App\Domains\Settings\ManageNotificationChannels\Web\ViewHelpers\NotificationsIndexViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
use App\Models\UserNotificationChannel;
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
            'author_id' => Auth::id(),
            'label' => $request->input('label'),
            'type' => UserNotificationChannel::TYPE_EMAIL,
            'content' => $request->input('content'),
            'verify_email' => true,
            'preferred_time' => $time,
        ];

        $channel = (new CreateUserNotificationChannel)->execute($data);

        return response()->json([
            'data' => NotificationsIndexViewHelper::dtoEmail($channel),
        ], 200);
    }

    public function destroy(Request $request, int $channelId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'user_notification_channel_id' => $channelId,
        ];

        (new DestroyUserNotificationChannel)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
