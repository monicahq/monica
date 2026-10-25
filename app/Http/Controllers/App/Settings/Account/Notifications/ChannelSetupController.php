<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Notifications;

use App\Actions\RemoveChannelSetup;
use App\Enums\NotificationChannelEnum;
use App\Exceptions\ChannelIsNotValid;
use App\Http\Controllers\Controller;
use App\Models\NotificationChannel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChannelSetupController extends Controller
{
    public function destroy(Request $request, string $channel): RedirectResponse
    {
        $name = NotificationChannelEnum::tryFrom($channel);

        abort_unless($name instanceof NotificationChannelEnum, 404);

        $row = NotificationChannel::query()
            ->where('account_id', $request->user()->account_id)
            ->where('channel', $name->value)
            ->first();

        abort_unless($row instanceof NotificationChannel, 404);

        try {
            new RemoveChannelSetup(
                user: $request->user(),
                channel: $row,
            )->execute();
        } catch (ChannelIsNotValid $e) {
            return back()->withErrors(['channel' => $e->getMessage()]);
        }

        return redirect()->route('settings.notifications.index')
            ->with('status', __('The setup of :name has been removed.', ['name' => __($name->label())]));
    }
}
