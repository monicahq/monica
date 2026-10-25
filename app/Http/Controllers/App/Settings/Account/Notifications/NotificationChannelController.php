<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Notifications;

use App\Actions\UpdateNotificationChannel;
use App\Exceptions\ChannelIsNotValid;
use App\Http\Controllers\Controller;
use App\Models\NotificationChannel;
use App\ViewModels\Settings\Account\Notifications\NotificationChannelsViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationChannelController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.settings.account.notifications.index', [
            'viewModel' => new NotificationChannelsViewModel(
                user: $request->user(),
            ),
        ]);
    }

    public function update(Request $request, NotificationChannel $channel): RedirectResponse
    {
        $validated = $request->validate([
            'is_enabled' => ['nullable', 'boolean'],
            'destination' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            new UpdateNotificationChannel(
                user: $request->user(),
                channel: $channel,
                isEnabled: (bool) ($validated['is_enabled'] ?? false),
                destination: $validated['destination'] ?? null,
            )->execute();
        } catch (ChannelIsNotValid $e) {
            return back()->withErrors(['channel' => $e->getMessage()]);
        }

        return redirect()->route('settings.notifications.index')
            ->with('status', __('Your notification settings are saved.'));
    }
}
