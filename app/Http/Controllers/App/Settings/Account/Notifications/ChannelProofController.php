<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Notifications;

use App\Actions\ConfirmChannelProofCode;
use App\Actions\SendChannelProofCode;
use App\Exceptions\ChannelIsNotValid;
use App\Exceptions\ProofCodeIsNotValid;
use App\Http\Controllers\Controller;
use App\Models\NotificationChannel;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChannelProofController extends Controller
{
    public function create(Request $request, NotificationChannel $channel): RedirectResponse
    {
        $validated = $request->validate([
            'destination' => ['nullable', 'string', 'max:255'],
        ]);

        if (($validated['destination'] ?? null) !== null) {
            $channel->destination = $validated['destination'];
            $channel->save();
        }

        try {
            new SendChannelProofCode(
                user: $request->user(),
                channel: $channel,
            )->execute();
        } catch (ChannelIsNotValid|AuthorizationException $e) {
            return back()->withErrors(['proof' => $e->getMessage()]);
        }

        return redirect()->route('settings.notifications.index')
            ->with('status', __('A code is on its way. Type it back here.'));
    }

    public function update(Request $request, NotificationChannel $channel): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:16'],
        ]);

        try {
            new ConfirmChannelProofCode(
                user: $request->user(),
                channel: $channel,
                code: $validated['code'],
            )->execute();
        } catch (ProofCodeIsNotValid $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }

        return redirect()->route('settings.notifications.index')
            ->with('status', __('That destination is yours. You can switch this channel on now.'));
    }
}
