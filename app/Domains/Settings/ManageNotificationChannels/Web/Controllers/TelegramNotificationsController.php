<?php

namespace App\Domains\Settings\ManageNotificationChannels\Web\Controllers;

use App\Domains\Settings\ManageNotificationChannels\Services\CreateUserNotificationChannel;
use App\Domains\Settings\ManageNotificationChannels\Web\ViewHelpers\NotificationsIndexViewHelper;
use App\Http\Controllers\Controller;
use App\Models\UserNotificationChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TelegramNotificationsController extends Controller
{
    public function store(Request $request)
    {
        $time = $request->input('hours').':'.$request->input('minutes');

        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'label' => 'Telegram',
            'type' => UserNotificationChannel::TYPE_TELEGRAM,
            'content' => 'tbd',
            'verify_email' => false,
            'preferred_time' => $time,
        ];

        $channel = (new CreateUserNotificationChannel)->execute($data);

        return response()->json([
            'data' => NotificationsIndexViewHelper::dtoTelegram($channel),
        ], 200);
    }
}
