<?php

namespace App\Domains\Settings\ManageNotificationChannels\Web\Controllers;

use App\Domains\Settings\ManageNotificationChannels\Services\VerifyUserNotificationChannelEmailAddress;
use App\Domains\Settings\ManageNotificationChannels\Web\ViewHelpers\NotificationsIndexViewHelper as ViewHelpersNotificationsIndexViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationsVerificationController extends Controller
{
    public function store(Request $request, int $userNotificationChannelId, string $uuid)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'user_notification_channel_id' => $userNotificationChannelId,
            'uuid' => $uuid,
        ];

        (new VerifyUserNotificationChannelEmailAddress)->execute($data);

        return Inertia::render('Settings/Notifications/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData(),
            'data' => ViewHelpersNotificationsIndexViewHelper::data(Auth::user()),
        ]);
    }
}
