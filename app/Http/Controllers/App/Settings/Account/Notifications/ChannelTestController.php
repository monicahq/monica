<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Notifications;

use App\Actions\SendChannelTestMessage;
use App\Exceptions\ChannelIsNotValid;
use App\Http\Controllers\Controller;
use App\Models\NotificationChannel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChannelTestController extends Controller
{
    public function create(Request $request, NotificationChannel $channel): RedirectResponse
    {
        try {
            $outcome = new SendChannelTestMessage(
                user: $request->user(),
                channel: $channel,
            )->execute();
        } catch (ChannelIsNotValid $e) {
            return back()->withErrors(['test' => $e->getMessage()]);
        }

        if (! $outcome['sent']) {
            return back()->withErrors(['test' => __('It did not get through. What came back was: :reason', ['reason' => $outcome['reason']])]);
        }

        return redirect()->route('settings.notifications.index')
            ->with('status', __('A test message is on its way.'));
    }
}
