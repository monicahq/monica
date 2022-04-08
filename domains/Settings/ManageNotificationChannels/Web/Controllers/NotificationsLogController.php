<?php

namespace App\Settings\ManageNotificationChannels\Web\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\UserNotificationChannel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Settings\ManageNotificationChannels\Web\ViewHelpers\NotificationsLogIndexViewHelper;

class NotificationsLogController extends Controller
{
    public function index(Request $request, int $userNotificationChannelId)
    {
        try {
            $channel = UserNotificationChannel::where('user_id', Auth::user()->id)
                ->findOrFail($userNotificationChannelId);
        } catch (ModelNotFoundException) {
            return redirect('vaults');
        }

        return Inertia::render('Settings/Notifications/Logs/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData(),
            'data' => NotificationsLogIndexViewHelper::data($channel, Auth::user()),
        ]);
    }
}
