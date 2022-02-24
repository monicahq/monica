<?php

namespace App\Http\Controllers\Settings\Notifications;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Vault\ViewHelpers\VaultIndexViewHelper;
use App\Services\User\NotificationChannels\VerifyUserNotificationChannelEmailAddress;
use App\Http\Controllers\Settings\Notifications\ViewHelpers\NotificationsIndexViewHelper;

class NotificationsVerificationController extends Controller
{
    public function store(Request $request, int $userNotificationChannelId, string $uuid)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'user_notification_channel_id' => $userNotificationChannelId,
            'uuid' => $uuid,
        ];

        (new VerifyUserNotificationChannelEmailAddress)->execute($data);

        return Inertia::render('Settings/Notifications/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData(),
            'data' => NotificationsIndexViewHelper::data(Auth::user()),
        ]);
    }
}
