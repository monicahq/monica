<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Notifications;

use App\Actions\StartChannelConnection;
use App\Exceptions\ChannelIsNotValid;
use App\Http\Controllers\Controller;
use App\Models\NotificationChannel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChannelConnectionController extends Controller
{
    public function create(Request $request, NotificationChannel $channel): RedirectResponse
    {
        try {
            $connection = new StartChannelConnection(
                user: $request->user(),
                channel: $channel,
            );

            $connection->execute();
        } catch (ChannelIsNotValid $e) {
            return back()->withErrors(['connection' => $e->getMessage()]);
        }

        return redirect()->away(sprintf(
            '%s?start=%s',
            rtrim((string) config('services.telegram.bot_url'), '/'),
            $connection->token(),
        ));
    }
}
